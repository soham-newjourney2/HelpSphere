<?php
/**
 * ngo-food-accept.php
 *
 * NGOs land here from the email link:  ?notif_id=X&token=Y
 *
 * Escalation (next-round emails) no longer runs here. It is run every minute
 * by scripts/run_escalation_loop.php (started by start_escalation.bat).
 */
require_once __DIR__ . '/../../inc/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!function_exists('h')) {
    function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

// ── Fix 41: remember the email link so login can send the NGO straight back ──
if (empty($_SESSION['ngo_id'])) {
    $ret_notif = (int) ($_GET['notif_id'] ?? 0);
    $ret_token = (string) ($_GET['token'] ?? '');
    if ($ret_notif > 0 && preg_match('/^[a-f0-9]{64}$/', $ret_token)) {
        $_SESSION['ngo_return_to'] = 'ngo-food-accept.php?notif_id=' . $ret_notif . '&token=' . $ret_token;
    }
    header('Location: ../../login-ngo.php');   // redirect here, before auth.php's check can print a warning
    exit;
}
ngo_require_login();

$logged_ngo_id = (int) ($_SESSION['ngo_id'] ?? 0);

// ════════════════════════════════════════════════════════════
//  ACCEPT HANDLER
// ════════════════════════════════════════════════════════════
$notif_id = (int) ($_GET['notif_id'] ?? 0);
$token    = trim((string) ($_GET['token'] ?? ''));

$state    = 'invalid';   // invalid | wrong_account | expired | already_taken | already_accepted | success | error
$ngo_name = '';
$batch    = null;

// Database clock, the same clock the cron job uses
$db_now = (string) $pdo->query("SELECT NOW()")->fetchColumn();

if ($notif_id > 0 && $token !== '') {

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

    if (!$notif || !hash_equals((string) $notif['token'], $token)) {
        $state = 'invalid';

    // ── Fix 42: the logged-in NGO must be the NGO that was notified ──
    } elseif ((int) $notif['ngo_id'] !== $logged_ngo_id) {
        $state = 'wrong_account';

    } elseif ($notif['status'] === 'Accepted') {
        $state    = 'already_accepted';
        $ngo_name = $notif['ngo_name_val'];

    } elseif ($notif['batch_status'] === 'Accepted') {
        $state = 'already_taken';

    } elseif ($notif['status'] === 'Expired' || $notif['expires_at'] < $db_now) {
        $state = 'expired';

    } else {
        // ── Valid, live notification: process the accept ──
        $batch_id  = (int) $notif['batch_id'];
        $ngo_id    = (int) $notif['ngo_id'];
        $donor_id  = (int) $notif['donor_id'];
        $ngo_name  = $notif['ngo_name_val'];

        try {
            $pdo->beginTransaction();

            // Race guard: only the first NGO to run this UPDATE wins
            $su = $pdo->prepare(
                "UPDATE food_donation_batches
                 SET status = 'Accepted', ngo_id = ?
                 WHERE id = ? AND status = 'Pending'"
            );
            $su->execute([$ngo_id, $batch_id]);

            if ($su->rowCount() === 1) {

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
                        status, pickup_address, pickup_time, urgency, donation_type, is_requested_by_ngo)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)"
                );
                $pickup_time = date('H:i:s', strtotime('+' . (int) $notif['pickup_within'] . ' hours'));
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
                    'Immediate',
                    'No'
                ]);

                // 4. Generic donations log
                $pdo->prepare(
                    "INSERT INTO donations (donor_id, target_id, target_role, status, donation_type)
                     VALUES (?,?,'NGO','Donated','Food')"
                )->execute([$donor_id, $ngo_id]);

                $pdo->commit();
                $state = 'success';
                $batch = $notif;

            } else {
                // Another NGO accepted first
                $pdo->rollBack();
                $state = 'already_taken';
            }

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('ngo-food-accept: ' . $e->getMessage());
            $state = 'error';
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
    </style>
</head>
<body>
<div class="sky"><div class="aurora a1"></div><div class="aurora a2"></div><div class="aurora a3"></div></div>

<div class="accept-page">
    <div class="accept-card">

        <?php if ($state === 'success'): ?>
            <div class="icon">✅</div>
            <h2>Donation Accepted!</h2>
            <p>Thank you, <strong><?php echo h($ngo_name); ?></strong>!<br>
               You have successfully accepted this food donation.</p>

            <div class="detail-grid">
                <p><strong>Food Type:</strong> <?php echo h($batch['food_type']); ?></p>
                <p><strong>Quantity:</strong> <?php echo h($batch['quantity']); ?></p>
                <p><strong>Pickup Address:</strong> <?php echo h($batch['pickup_address']); ?></p>
                <p><strong>Pickup Within:</strong> <?php echo (int) $batch['pickup_within']; ?> hour(s)</p>
                <p><strong>Urgency:</strong> <span style="color:#f87171;font-weight:700;">High</span></p>
            </div>
            <p>Please arrange pickup as soon as possible. The donation is now recorded under your NGO.</p>
            <a class="btn-back" href="../dashboards/ngo-dashboard.php">Go to Dashboard →</a>

        <?php elseif ($state === 'already_accepted'): ?>
            <div class="icon">✅</div>
            <h2>You Already Accepted This</h2>
            <p>You have already accepted this donation, <strong><?php echo h($ngo_name); ?></strong>.<br>
               Please proceed with pickup.</p>
            <a class="btn-back" href="../dashboards/ngo-dashboard.php">Go to Dashboard →</a>

        <?php elseif ($state === 'already_taken'): ?>
            <div class="icon">⚡</div>
            <h2>Already Claimed</h2>
            <p>Another NGO accepted this donation just before you.<br>
               Thank you for your willingness. We'll notify you of future requests.</p>
            <a class="btn-back" href="../dashboards/ngo-dashboard.php">Go to Dashboard →</a>

        <?php elseif ($state === 'expired'): ?>
            <div class="icon">⏰</div>
            <h2>This Link Has Expired</h2>
            <p>The acceptance window for this donation has closed.<br>
               The request is being forwarded to the next batch of NGOs.</p>
            <a class="btn-back" href="../dashboards/ngo-dashboard.php">Go to Dashboard →</a>

        <?php elseif ($state === 'wrong_account'): ?>
            <div class="icon">🔒</div>
            <h2>Different NGO Account</h2>
            <p>This link was sent to a different NGO account than the one you are signed in with.<br>
               Please sign out, sign in with the NGO that received the email, and open the link again.</p>
            <a class="btn-back" href="../../inc/logout.php">Sign Out</a>

        <?php elseif ($state === 'error'): ?>
            <div class="icon">⚠️</div>
            <h2>Something Went Wrong</h2>
            <p>We couldn't process your acceptance. Nothing was changed.<br>
               Please try the link again, or contact the platform administrator.</p>
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
