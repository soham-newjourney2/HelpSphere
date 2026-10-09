<?php
/**
 * ngo-food-accept.php
 *
 * NGOs land here from the email link:
 *   ?notif_id=X&token=Y
 *
 * On every page load this script also runs the escalation engine —
 * so no cron job is required. Any NGO hitting the page (or any other
 * page that includes this file) will trigger the next round automatically
 * if the current round has expired.
 */
require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/smtp_mail.php';   // <-- add this
ngo_require_login();

define('APP_URL',            'url of your app');
define('ESCALATION_MINUTES', 7);
define('BATCH_SIZE',         5);
define('MAIL_FROM',          'your email');

// ════════════════════════════════════════════════════════════
//  ESCALATION ENGINE  (runs on every page load)
// ════════════════════════════════════════════════════════════
function locationScore(string $a, string $b): int {
    $words = fn($s) => array_filter(
        explode(' ', strtolower(preg_replace('/[^a-z0-9\s]/i', ' ', $s)))
    );
    return count(array_intersect($words($a), $words($b)));
}

function sendRoundEmails(PDO $pdo, int $batch_id, array $ngo_ids, int $round_no, array $b): void {
    $expires_at = date('Y-m-d H:i:s', strtotime('+' . ESCALATION_MINUTES . ' minutes'));
    foreach ($ngo_ids as $ngo_id) {
        $token = bin2hex(random_bytes(32));
        $si = $pdo->prepare(
            "INSERT INTO food_donation_notifications
               (batch_id, ngo_id, round_no, token, expires_at, status)
             VALUES (?,?,?,?,?,'Pending')"
        );
        $si->execute([$batch_id, $ngo_id, $round_no, $token, $expires_at]);
        $notif_id = (int) $pdo->lastInsertId();

        $se = $pdo->prepare("SELECT name, email FROM ngo_master WHERE id = ?");
        $se->execute([$ngo_id]);
        $ngo = $se->fetch(PDO::FETCH_ASSOC);
        if (!$ngo || empty($ngo['email'])) 
            continue;

        $accept_url = APP_URL . "/public/ngos/ngo-food-accept.php"
                    . "?notif_id={$notif_id}&token={$token}";
        $exp_label  = date('h:i A', strtotime($expires_at));

        $subject = "HelpSphere: Immediate Food Donation – Accept by {$exp_label}";
        $body    =
            "Dear {$ngo['name']},\n\n"
          . "The previous batch of NGOs did not accept this donation in time.\n"
          . "You are now in the next batch. Please respond quickly.\n\n"
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
          . ">>> ACCEPT HERE:\n{$accept_url}\n\n"
          . "- HelpSphere Platform";

        smtp_mail($ngo['email'], $subject, $body);
    }
}

function runEscalationEngine(PDO $pdo): void {
    $now = date('Y-m-d H:i:s');

    // 1. Expire all overdue Pending notifications
    $pdo->prepare(
        "UPDATE food_donation_notifications
         SET status = 'Expired'
         WHERE status = 'Pending' AND expires_at < ?"
    )->execute([$now]);

    // 2. Find Pending batches whose latest round is fully expired (no accepts, no live Pending)
    $stmtB = $pdo->prepare(
        "SELECT DISTINCT fdb.id AS batch_id, fdb.food_type, fdb.quantity,
                fdb.pickup_address, fdb.pickup_within,
                MAX(fdn.round_no) AS last_round
         FROM food_donation_batches fdb
         JOIN food_donation_notifications fdn ON fdn.batch_id = fdb.id
         WHERE fdb.status = 'Pending'
         GROUP BY fdb.id, fdb.food_type, fdb.quantity, fdb.pickup_address, fdb.pickup_within"
    );
    $stmtB->execute();
    $batches = $stmtB->fetchAll(PDO::FETCH_ASSOC);

    foreach ($batches as $batch) {
        $bid        = (int) $batch['batch_id'];
        $last_round = (int) $batch['last_round'];

        // Count still-live Pending notifications in latest round
        $sc = $pdo->prepare(
            "SELECT COUNT(*) FROM food_donation_notifications
             WHERE batch_id = ? AND round_no = ? AND status = 'Pending'"
        );
        $sc->execute([$bid, $last_round]);
        $live = (int) $sc->fetchColumn();
        if ($live > 0)
             continue; // round still open

        // Check if already accepted
        $sa = $pdo->prepare(
            "SELECT COUNT(*) FROM food_donation_notifications
             WHERE batch_id = ? AND status = 'Accepted'"
        );
        $sa->execute([$bid]);
        if ((int) $sa->fetchColumn() > 0) 
            continue; // already handled

        // All expired, no accept → dispatch next round
        $already_notified = [];
        $sn = $pdo->prepare(
            "SELECT DISTINCT ngo_id FROM food_donation_notifications WHERE batch_id = ?"
        );
        $sn->execute([$bid]);
        $already_notified = array_column($sn->fetchAll(PDO::FETCH_ASSOC), 'ngo_id');

        // Fetch all verified NGOs not yet notified
        $placeholders = implode(',', array_fill(0, count($already_notified), '?'));
        $whereClause  = $already_notified
            ? "WHERE status = 'Verified' AND id NOT IN ($placeholders)"
            : "WHERE status = 'Verified'";

        $sg = $pdo->prepare("SELECT id, address FROM ngo_master $whereClause");
        $sg->execute($already_notified);
        $remaining = $sg->fetchAll(PDO::FETCH_ASSOC);

        if (empty($remaining)) {
            // No more NGOs to notify — mark batch as fully expired
            $pdo->prepare("UPDATE food_donation_batches SET status='Expired' WHERE id=?")
                ->execute([$bid]);
            continue;
        }

        // Score by proximity to pickup address
        usort($remaining, fn($a, $b) =>
            locationScore($batch['pickup_address'], $b['address']) -
            locationScore($batch['pickup_address'], $a['address'])
        );
        $next5 = array_slice(array_column($remaining, 'id'), 0, BATCH_SIZE);

        sendRoundEmails($pdo, $bid, $next5, $last_round + 1, [
            'food_type'      => $batch['food_type'],
            'quantity'       => $batch['quantity'],
            'pickup_address' => $batch['pickup_address'],
            'pickup_within'  => $batch['pickup_within'],
        ]);
    }
}

// ── Run escalation every time this page is loaded ───────────────────────────
runEscalationEngine($pdo);

// ════════════════════════════════════════════════════════════
//  ACCEPT HANDLER
// ════════════════════════════════════════════════════════════
$notif_id = (int) ($_GET['notif_id'] ?? 0);
$token    = trim($_GET['token'] ?? '');

$state    = 'invalid';   // invalid | expired | already_taken | already_accepted | success
$ngo_name = '';
$batch    = null;

if ($notif_id > 0 && $token !== '') {

    // Fetch notification row
    $sn = $pdo->prepare(
        "SELECT fdn.*, fdb.food_type, fdb.quantity, fdb.pickup_address,
                fdb.pickup_within, fdb.status AS batch_status, fdb.donor_id,
                nm.name AS ngo_name_val
         FROM food_donation_notifications fdn
         JOIN food_donation_batches fdb ON fdb.id = fdn.batch_id
         JOIN ngo_master nm ON nm.id = fdn.ngo_id
         WHERE fdn.id = ?"
    );
    $sn->execute([$notif_id]);
    $notif = $sn->fetch(PDO::FETCH_ASSOC);

    if (!$notif || $notif['token'] !== $token) {
        $state = 'invalid';

    } elseif ($notif['status'] === 'Accepted') {
        $state    = 'already_accepted';
        $ngo_name = $notif['ngo_name_val'];

    } elseif ($notif['batch_status'] === 'Accepted') {
        $state = 'already_taken';

    } elseif ($notif['status'] === 'Expired' || $notif['expires_at'] < date('Y-m-d H:i:s')) {
        $state = 'expired';

    } else {
        // ── Valid, live notification — process the accept ──
        $batch_id  = (int) $notif['batch_id'];
        $ngo_id    = (int) $notif['ngo_id'];
        $donor_id  = (int) $notif['donor_id'];
        $ngo_name  = $notif['ngo_name_val'];

        // Race condition guard: only first NGO to hit UPDATE wins
        $su = $pdo->prepare(
            "UPDATE food_donation_batches
             SET status = 'Accepted', ngo_id = ?
             WHERE id = ? AND status = 'Pending'"
        );
        $su->execute([$ngo_id, $batch_id]);

        if ($su->rowCount() === 1) {
            // We won the race ✔

            // 1. Mark this notification as Accepted
            $pdo->prepare(
                "UPDATE food_donation_notifications SET status='Accepted' WHERE id=?"
            )->execute([$notif_id]);

            // 2. Expire all other notifications for this batch
            $pdo->prepare(
                "UPDATE food_donation_notifications
                 SET status='Expired'
                 WHERE batch_id=? AND id != ?"
            )->execute([$batch_id, $notif_id]);

            // 3. Insert into food_donations
            $si = $pdo->prepare(
                "INSERT INTO food_donations
                   (donor_id, food_type, target_type, target_id, quantity,
                    status, pickup_address, pickup_time, urgency, donation_type)
                 VALUES (?,?,?,?,?,?,?,?,?,?)"
            );
            // pickup_time stored as "within X hours from now"
            $pickup_time = date('H:i:s', strtotime('+' . (int)$notif['pickup_within'] . ' hours'));
            $si->execute([
                $donor_id,
                $notif['food_type'],
                'NGO',
                $ngo_id,
                $notif['quantity'],
                'Donated',
                $notif['pickup_address'],
                $pickup_time,
                'High',
                'Immediate'
            ]);

            // 4. Update donations table (generic log)
            $sd = $pdo->prepare(
                "INSERT INTO donations (donor_id, target_id, target_role, status, donation_type)
                 VALUES (?,?,'NGO','Donated','Food')"
            );
            $sd->execute([$donor_id, $ngo_id]);

            $state = 'success';
            $batch = $notif;

        } else {
            // Another NGO accepted first
            $state = 'already_taken';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accept Food Donation – HelpSphere</title>
    <link rel="stylesheet" href="../assets/css/ngo.css">
    <style>
        .accept-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .accept-card {
            background: rgba(15,23,42,0.95);
            border-radius: 16px;
            padding: 36px 38px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            border: 1px solid rgba(99,179,237,0.18);
            box-shadow: 0 24px 64px rgba(0,0,0,0.6);
        }
        .accept-card .icon { font-size: 3rem; margin-bottom: 16px; }
        .accept-card h2 { margin: 0 0 10px; color: #e2e8f0; font-size: 1.25rem; }
        .accept-card p  { color: #94a3b8; font-size: 0.9rem; line-height: 1.6; margin: 0; }

        .detail-grid {
            background: rgba(30,41,59,0.6);
            border: 1px solid rgba(99,179,237,0.12);
            border-radius: 10px;
            padding: 16px 20px;
            margin: 20px 0;
            text-align: left;
        }
        .detail-grid p { margin: 6px 0; font-size: 0.87rem; color: #94a3b8; }
        .detail-grid p strong { color: #cbd5e1; }

        .btn-back {
            display: inline-block;
            margin-top: 22px;
            padding: 10px 24px;
            background: rgba(99,179,237,0.18);
            color: #63b3ed;
            border: 1px solid rgba(99,179,237,0.35);
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-back:hover { background: rgba(99,179,237,0.35); color: #fff; }

        /* state colours */
        .state-success  .icon-wrap { color: #4ade80; }
        .state-expired  .icon-wrap { color: #fbbf24; }
        .state-taken    .icon-wrap { color: #f87171; }
        .state-invalid  .icon-wrap { color: #f87171; }
    </style>
</head>
<body>
<div class="sky"><div class="aurora a1"></div><div class="aurora a2"></div><div class="aurora a3"></div></div>

<div class="accept-page">
    <div class="accept-card">

        <?php if ($state === 'success'): ?>
            <div class="icon">✅</div>
            <h2>Donation Accepted!</h2>
            <p>Thank you, <strong><?php echo htmlspecialchars($ngo_name); ?></strong>!<br>
               You have successfully accepted this food donation.</p>

            <div class="detail-grid">
                <p><strong>Food Type:</strong> <?php echo htmlspecialchars($batch['food_type']); ?></p>
                <p><strong>Quantity:</strong> <?php echo htmlspecialchars($batch['quantity']); ?></p>
                <p><strong>Pickup Address:</strong> <?php echo htmlspecialchars($batch['pickup_address']); ?></p>
                <p><strong>Pickup Within:</strong> <?php echo (int)$batch['pickup_within']; ?> hour(s)</p>
                <p><strong>Urgency:</strong> <span style="color:#f87171;font-weight:700;">High</span></p>
            </div>
            <p>Please arrange pickup as soon as possible. The donor has been notified.</p>
            <a class="btn-back" href="../dashboards/ngo-dashboard.php">Go to Dashboard →</a>

        <?php elseif ($state === 'already_accepted'): ?>
            <div class="icon">✅</div>
            <h2>You Already Accepted This</h2>
            <p>You have already accepted this donation, <strong><?php echo htmlspecialchars($ngo_name); ?></strong>.<br>
               Please proceed with pickup.</p>
            <a class="btn-back" href="../dashboards/ngo-dashboard.php">Go to Dashboard →</a>

        <?php elseif ($state === 'already_taken'): ?>
            <div class="icon">⚡</div>
            <h2>Already Claimed</h2>
            <p>Another NGO accepted this donation just before you.<br>
               Thank you for your willingness — we'll notify you of future requests.</p>
            <a class="btn-back" href="../dashboards/ngo-dashboard.php">Go to Dashboard →</a>

        <?php elseif ($state === 'expired'): ?>
            <div class="icon">⏰</div>
            <h2>This Link Has Expired</h2>
            <p>The acceptance window for this donation has closed.<br>
               The request has been forwarded to the next batch of NGOs.</p>
            <a class="btn-back" href="../dashboards/ngo-dashboard.php">Go to Dashboard →</a>

        <?php else: ?>
            <div class="icon">❌</div>
            <h2>Invalid Link</h2>
            <p>This acceptance link is not valid or has already been used.<br>
               If you believe this is an error, please contact the platform administrator.</p>
            <a class="btn-back" href="../dashboards/ngo-dashboard.php">Go to Dashboard →</a>
        <?php endif; ?>

    </div>
</div>

<script src="../assets/js/sidebar.js"></script>
</body>
</html>