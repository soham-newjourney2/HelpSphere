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

// ── Remember the email link so login can send the NGO straight back ──
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

    // ── The logged-in NGO must be the NGO that was notified ──
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

                // 3. Insert into food_donations (same timestamp as the donations row: pickup matches on it)
                $si = $pdo->prepare(
                    "INSERT INTO food_donations
                       (donor_id, food_type, target_type, target_id, quantity,
                        status, pickup_address, pickup_time, urgency, donation_type, is_requested_by_ngo, created_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
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
                    'No',
                    $db_now
                ]);

                // 4. Generic donations log
                $pdo->prepare(
                    "INSERT INTO donations (donor_id, target_id, target_role, status, donation_type, created_at)
                     VALUES (?,?,'NGO','Donated','Food',?)"
                )->execute([$donor_id, $ngo_id, $db_now]);

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
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Accept food donation · NGO | HelpSphere</title>
  <meta name="hs-icons" content="../assets/img/icons.svg" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Inter+Tight:wght@500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/css/base.css" />
  <link rel="stylesheet" href="../assets/css/app.css" />
</head>
<body>
<div class="app">

  <!-- ============ Sidebar ============ -->
  <aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <div class="sidebar__head">
      <a href="../../index.php" class="logo logo--light">
        <span class="logo__mark"><svg ><use href="../assets/img/icons.svg#i-sprout"/></svg></span>
        HelpSphere
      </a>
      <button type="button" class="sidebar__close" data-sidebar-close aria-label="Close menu"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-close"/></svg></button>
    </div>

    <span class="sidebar__tag">NGO workspace</span>

    <nav class="sidebar__nav" aria-label="NGO workspace">
      <a href="../dashboards/ngo-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../ngos/ngo-needs.php"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg>Needs &amp; requests</a>
      <a href="../ngos/ngo-beneficiaries.php"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg>Beneficiaries</a>
      <a href="../ngos/ngo-donations.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-gift"/></svg>Donations &amp; resources</a>
      <a href="../ngos/ngo-campaigns.php"><svg class="icon"><use href="../assets/img/icons.svg#i-megaphone"/></svg>Campaigns</a>
      <a href="../ngos/ngo-volunteers.php"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteers</a>
      <a href="../ngos/ngo-communication.php"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Communication</a>
      <a href="../ngos/ngo-profile.php"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg>Profile &amp; compliance</a>
    </nav>

    <div class="sidebar__foot">
      <a href="../../inc/logout.php" class="sidebar__logout"><svg class="icon"><use href="../assets/img/icons.svg#i-logout"/></svg>Sign out</a>
    </div>
  </aside>
  <div class="scrim" id="scrim" hidden></div>

  <!-- ============ Main ============ -->
  <div class="main">
    <header class="topbar">
      <button type="button" class="icon-btn topbar__menu" id="menuBtn" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-menu"/></svg></button>
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../ngos/ngo-donations.php">Donations &amp; resources</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Accept food donation</strong></nav>
      <div class="topbar__right">
        <a href="../../index.php" class="topbar__site"><svg class="icon"><use href="../assets/img/icons.svg#i-sprout"/></svg><span>HelpSphere home</span></a>
        <div class="profile">
          <span class="avatar avatar--brand"><svg class="icon"><use href="../assets/img/icons.svg#i-building"/></svg></span>
          <div class="profile__text"><strong>NGO team</strong><span>NGO workspace</span></div>
        </div>
      </div>
    </header>

    <main class="content" id="content">

        <?php if ($state === 'success'): ?>
      <section class="result">
        <span class="result__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg></span>
        <h1>Donation accepted!</h1>
        <p>Thank you, <strong><?php echo h($ngo_name); ?></strong>! You have accepted this food donation and it is now recorded under your NGO.</p>
        <dl class="details">
          <div><dt>Food type</dt><dd><?php echo h($batch['food_type']); ?></dd></div>
          <div><dt>Quantity</dt><dd><?php echo h($batch['quantity']); ?></dd></div>
          <div><dt>Pickup address</dt><dd><?php echo h($batch['pickup_address']); ?></dd></div>
          <div><dt>Pick up within</dt><dd><?php echo (int) $batch['pickup_within']; ?> hour(s)</dd></div>
          <div><dt>Urgency</dt><dd>High</dd></div>
        </dl>
        <p>Please arrange the pickup as soon as possible.</p>
        <a class="btn btn--dark" href="../ngos/ngo-donations-food.php"><svg class="icon"><use href="../assets/img/icons.svg#i-truck"/></svg>Go to food donations</a>
      </section>
        <?php elseif ($state === 'already_accepted'): ?>
      <section class="result">
        <span class="result__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg></span>
        <h1>You already accepted this</h1>
        <p>You have already accepted this donation, <strong><?php echo h($ngo_name); ?></strong>. Please go ahead with the pickup.</p>
        <a class="btn btn--dark" href="../ngos/ngo-donations-food.php"><svg class="icon"><use href="../assets/img/icons.svg#i-truck"/></svg>Go to food donations</a>
      </section>
        <?php elseif ($state === 'already_taken'): ?>
      <section class="result">
        <span class="result__icon result__icon--info"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg></span>
        <h1>Already claimed</h1>
        <p>Another NGO accepted this donation just before you. Thank you for responding — you will be alerted about future donations near you.</p>
        <a class="btn btn--dark" href="../dashboards/ngo-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Go to dashboard</a>
      </section>
        <?php elseif ($state === 'expired'): ?>
      <section class="result">
        <span class="result__icon result__icon--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-clock"/></svg></span>
        <h1>This link has expired</h1>
        <p>The acceptance window for this donation has closed, and it is being forwarded to the next group of NGOs.</p>
        <a class="btn btn--dark" href="../dashboards/ngo-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Go to dashboard</a>
      </section>
        <?php elseif ($state === 'wrong_account'): ?>
      <section class="result">
        <span class="result__icon result__icon--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-lock"/></svg></span>
        <h1>Different NGO account</h1>
        <p>This link was sent to a different NGO than the one you are signed in as. Sign out, sign in with the NGO that received the email, and open the link again.</p>
        <a class="btn btn--dark" href="../../inc/logout.php"><svg class="icon"><use href="../assets/img/icons.svg#i-logout"/></svg>Sign out</a>
      </section>
        <?php elseif ($state === 'error'): ?>
      <section class="result">
        <span class="result__icon result__icon--bad"><svg class="icon"><use href="../assets/img/icons.svg#i-alert"/></svg></span>
        <h1>Something went wrong</h1>
        <p>We couldn’t process your acceptance and nothing was changed. Please try the link again, or contact the platform administrator.</p>
        <a class="btn btn--dark" href="../dashboards/ngo-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Go to dashboard</a>
      </section>
        <?php else: ?>
      <section class="result">
        <span class="result__icon result__icon--bad"><svg class="icon"><use href="../assets/img/icons.svg#i-x"/></svg></span>
        <h1>Invalid link</h1>
        <p>This acceptance link is not valid or has already been used. If you think this is a mistake, please contact the platform administrator.</p>
        <a class="btn btn--dark" href="../dashboards/ngo-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Go to dashboard</a>
      </section>
        <?php endif; ?>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
