<?php
/**
 * inc/escalation.php
 * Escalation engine for Immediate Food Donations.
 * Run every minute by scripts/run_escalation_loop.php.
 */
require_once __DIR__ . '/smtp_mail.php';

if (!defined('APP_URL'))            define('APP_URL',            'url of your app');  // <-- set this
if (!defined('ESCALATION_MINUTES')) define('ESCALATION_MINUTES', 7);
if (!defined('BATCH_SIZE'))         define('BATCH_SIZE',         5);
if (!defined('MAIL_FROM'))          define('MAIL_FROM',          'your email');       // <-- set this

function locationScore(?string $a, ?string $b): int {
    $a = (string)$a; $b = (string)$b;
    $words = fn($s) => array_filter(
        explode(' ', strtolower(preg_replace('/[^a-z0-9\s]/i', ' ', $s)))
    );
    return count(array_intersect($words($a), $words($b)));
}

function createRoundNotifications(PDO $pdo, int $batch_id, array $ngo_ids, int $round_no): array {
    // Expiry comes from the database clock, so cron and web requests always agree
    $expires_at = (string) $pdo->query(
        "SELECT DATE_ADD(NOW(), INTERVAL " . (int) ESCALATION_MINUTES . " MINUTE)"
    )->fetchColumn();

    $si = $pdo->prepare(
        "INSERT INTO food_donation_notifications
           (batch_id, ngo_id, round_no, token, expires_at, status)
         VALUES (?,?,?,?,?,'Pending')"
    );
    $items = [];
    foreach ($ngo_ids as $ngo_id) {
        $token = bin2hex(random_bytes(32));
        $si->execute([$batch_id, (int) $ngo_id, $round_no, $token, $expires_at]);
        $items[] = [
            'notif_id' => (int) $pdo->lastInsertId(),
            'ngo_id'   => (int) $ngo_id,
            'token'    => $token,
        ];
    }
    return ['expires_at' => $expires_at, 'items' => $items];
}

function sendRoundEmails(PDO $pdo, array $round, array $b, int $round_no = 2): void {
    $expires_at = $round['expires_at'];
    $exp_label  = date('h:i A', strtotime($expires_at));
    $se = $pdo->prepare("SELECT name, email FROM ngo_master WHERE id = ?");
    $intro = ($round_no === 1)
        ? "An urgent food donation is available near your area.\n\n"
        : "The previous batch of NGOs did not accept this donation in time.\n"
        . "You are now in the next batch. Please respond quickly.\n\n";

    foreach ($round['items'] as $item) {
        $se->execute([$item['ngo_id']]);
        $ngo = $se->fetch(PDO::FETCH_ASSOC);
        if (!$ngo || empty($ngo['email'])) {
            continue;
        }

        $accept_url = APP_URL . "/public/ngos/ngo-food-accept.php"
                    . "?notif_id={$item['notif_id']}&token={$item['token']}";

        $subject = "HelpSphere: Immediate Food Donation – Accept by {$exp_label}";
        $body    =
            "Dear {$ngo['name']},\n\n"
           . $intro
          . "-------------------------------------------\n"
          . " DONATION DETAILS\n"
          . "-------------------------------------------\n"
          . " Food Type     : {$b['food_type']}\n"
          . " Quantity      : {$b['quantity']}\n"
          . " Pickup Address: {$b['pickup_address']}\n"
          . " Pickup Within : {$b['pickup_within']} hour(s)\n"
          . " Urgency       : High\n"
          . "-------------------------------------------\n\n"
          . "You have until {$exp_label} to accept.\n\n"
          . ">>> ACCEPT HERE (you will need to log in):\n{$accept_url}\n\n"
          . "- HelpSphere Platform";

        // One failed email must not stop the rest
        try {
            smtp_mail($ngo['email'], $subject, $body);
        } catch (Throwable $e) {
            error_log('smtp_mail failed for NGO ' . $item['ngo_id'] . ': ' . $e->getMessage());
        }
    }
}

function runEscalationEngine(PDO $pdo): void {
    $now = (string) $pdo->query("SELECT NOW()")->fetchColumn();

    // 1. Expire all overdue Pending notifications
    $pdo->prepare(
        "UPDATE food_donation_notifications
         SET status = 'Expired'
         WHERE status = 'Pending' AND expires_at < ?"
    )->execute([$now]);

    // 2. Candidate batches (re-checked under a lock below)
    $stmtB = $pdo->prepare(
        "SELECT DISTINCT fdb.id AS batch_id, fdb.food_type, fdb.quantity,
                fdb.pickup_address, fdb.pickup_within
         FROM food_donation_batches fdb
         JOIN food_donation_notifications fdn ON fdn.batch_id = fdb.id
         WHERE fdb.status = 'Pending'"
    );
    $stmtB->execute();
    $batches = $stmtB->fetchAll(PDO::FETCH_ASSOC);

    foreach ($batches as $batch) {
        $bid   = (int) $batch['batch_id'];
        $round = null;

        try {
            $pdo->beginTransaction();

            // Lock the batch row. A second run waits here until the first commits.
            $lk = $pdo->prepare("SELECT status FROM food_donation_batches WHERE id = ? FOR UPDATE");
            $lk->execute([$bid]);
            if ($lk->fetchColumn() !== 'Pending') {
                $pdo->rollBack();
                continue;
            }

            // Re-read everything AFTER taking the lock
            $lr = $pdo->prepare("SELECT MAX(round_no) FROM food_donation_notifications WHERE batch_id = ?");
            $lr->execute([$bid]);
            $last_round = (int) $lr->fetchColumn();

            $sc = $pdo->prepare(
                "SELECT COUNT(*) FROM food_donation_notifications
                 WHERE batch_id = ? AND round_no = ? AND status = 'Pending'"
            );
            $sc->execute([$bid, $last_round]);
            if ((int) $sc->fetchColumn() > 0) {
                $pdo->rollBack();
                continue; // round still open
            }

            $sa = $pdo->prepare(
                "SELECT COUNT(*) FROM food_donation_notifications
                 WHERE batch_id = ? AND status = 'Accepted'"
            );
            $sa->execute([$bid]);
            if ((int) $sa->fetchColumn() > 0) {
                $pdo->rollBack();
                continue; // already handled
            }

            $sn = $pdo->prepare("SELECT DISTINCT ngo_id FROM food_donation_notifications WHERE batch_id = ?");
            $sn->execute([$bid]);
            $already_notified = array_column($sn->fetchAll(PDO::FETCH_ASSOC), 'ngo_id');

            if ($already_notified) {
                $placeholders = implode(',', array_fill(0, count($already_notified), '?'));
                $whereClause  = "WHERE status = 'Verified' AND id NOT IN ($placeholders)";
            } else {
                $whereClause  = "WHERE status = 'Verified'";
            }

            $sg = $pdo->prepare("SELECT id, address FROM ngo_master $whereClause");
            $sg->execute($already_notified);
            $remaining = $sg->fetchAll(PDO::FETCH_ASSOC);

            if (empty($remaining)) {
                $pdo->prepare("UPDATE food_donation_batches SET status='Expired' WHERE id=?")
                    ->execute([$bid]);
                $pdo->commit();
                continue;
            }

            usort($remaining, fn($a, $b) =>
                locationScore($batch['pickup_address'], $b['address']) -
                locationScore($batch['pickup_address'], $a['address'])
            );
            $next5 = array_slice(array_column($remaining, 'id'), 0, BATCH_SIZE);

            // Only DB work happens while the lock is held
            $round = createRoundNotifications($pdo, $bid, $next5, $last_round + 1);
            $pdo->commit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Escalation engine (batch ' . $bid . '): ' . $e->getMessage());
            continue;
        }

        // Emails go out AFTER the commit, with no lock held
        if ($round !== null) {
            sendRoundEmails($pdo, $round, [
                'food_type'      => $batch['food_type'],
                'quantity'       => $batch['quantity'],
                'pickup_address' => $batch['pickup_address'],
                'pickup_within'  => $batch['pickup_within'],
            ]);
        }
    }
}
