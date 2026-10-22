<?php
//request-fulfill.php
require_once __DIR__ . '/../../inc/auth.php';
ngo_require_login();

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$request_id = (int)($_GET['request_id'] ?? 0);
$error = '';

if (isset($_POST['submit-btn'])) {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        die("Invalid session token. Please go back and try again.");
    }

    $time     = trim((string)($_POST['time'] ?? ''));
    $type     = trim((string)($_POST['type'] ?? ''));
    $provider = trim((string)($_POST['provider'] ?? ''));
    $delivery = trim((string)($_POST['delivery_method'] ?? ''));

    if ($time === '') {
        $time = date('H:i:s');   // blank time -> current server time
    } elseif (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $time)) {
        $error = 'Enter a valid time.';
    }
    if ($request_id <= 0) $error = 'Invalid request.';

    if ($error === '') {
        try {
            $pdo->beginTransaction();

            $ngo_id = (int)($_SESSION['ngo_id'] ?? 0);

            // Only a request this NGO approved, and only once
            $s = $pdo->prepare("select beneficiary_id from assistance_requests where id=? and status='Approved' and ngo_id=?");
            $s->execute([$request_id, $ngo_id]);
            $beneficiary_id = $s->fetchColumn();
            if ($beneficiary_id === false) throw new RuntimeException('not-allowed');

            $s = $pdo->prepare("update assistance_requests set status='Fulfilled' where id=? and status='Approved' and ngo_id=?");
            $s->execute([$request_id, $ngo_id]);
            if ($s->rowCount() === 0) throw new RuntimeException('not-allowed');

            $s = $pdo->prepare("insert into fulfillment_status (beneficiary_id, request_id, fulfilled_at, support_type, provider, delivery_method) values (?,?,?,?,?,?)");
            $s->execute([$beneficiary_id, $request_id, $time, $type, $provider, $delivery]);

            $pdo->commit();
            header("Location: ngo-beneficiaries-fulfillment.php");
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($e->getMessage() === 'not-allowed') $error = 'Only the NGO that approved this request can fulfill it, and only once.';
            else { error_log('request-fulfill: ' . $e->getMessage()); $error = 'Could not save. Please try again.'; }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Fulfil request · NGO | HelpSphere</title>
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
      <a href="../ngos/ngo-beneficiaries.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg>Beneficiaries</a>
      <a href="../ngos/ngo-donations.php"><svg class="icon"><use href="../assets/img/icons.svg#i-gift"/></svg>Donations &amp; resources</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../ngos/ngo-beneficiaries-fulfillment.php">Requests to fulfil</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Fulfil request</strong></nav>
      <div class="topbar__right">
        <a href="../../index.php" class="topbar__site"><svg class="icon"><use href="../assets/img/icons.svg#i-sprout"/></svg><span>HelpSphere home</span></a>
        <div class="profile">
          <span class="avatar avatar--brand"><svg class="icon"><use href="../assets/img/icons.svg#i-building"/></svg></span>
          <div class="profile__text"><strong>NGO team</strong><span>NGO workspace</span></div>
        </div>
      </div>
    </header>

    <main class="content" id="content">

      <section class="page-head">
        <svg aria-hidden="true" class="leaf leaf--p1"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p2"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p3"><use href="../assets/img/icons.svg#leaf"/></svg>
        <div>
          <p class="page-head__eyebrow">Beneficiaries</p>
          <h1 class="page-head__title">Record fulfilment</h1>
          <p class="page-head__sub">The beneficiary sees these details on their request. Leave the time empty to use the current time.</p>
        </div>
      </section>

      <section class="row row--2-1">
        <article class="card reveal">
          <form class="form" method="POST">
            <div class="form__grid">
              <div class="field">
                <label for="time">Time of fulfilment</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-clock"/></svg><input type="time" id="time" name="time"></div>
              </div>
              <div class="field">
                <label for="type">Support type</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-gift"/></svg><input type="text" id="type" name="type" placeholder="e.g. Monthly ration kit"></div>
              </div>
              <div class="field">
                <label for="provider">Provider</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-building"/></svg><input type="text" id="provider" name="provider" placeholder="Who provided it"></div>
              </div>
              <div class="field">
                <label for="delivery_method">Delivery method</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-truck"/></svg><input type="text" id="delivery_method" name="delivery_method" placeholder="e.g. Home delivery, collected"></div>
              </div>
            </div>
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8'); ?>" />
          <?php if ($error !== ''): ?>
            <p class="alert alert--error"><svg class="icon"><use href="../assets/img/icons.svg#i-alert"/></svg><span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span></p>
          <?php endif ?>
            <div class="card__foot">
              <button class="btn btn--dark" name="submit-btn"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Mark as fulfilled</button>
              <a href="../ngos/ngo-beneficiaries-fulfillment.php" class="btn btn--outline">Cancel</a>
            </div>
          </form>
        </article>

        <aside class="urgent reveal">
          <svg aria-hidden="true" class="leaf leaf--u1"><use href="../assets/img/icons.svg#leaf"/></svg>
          <span class="pill pill--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-info"/></svg>Once only</span>
          <h2>This closes the request</h2>
          <p>Only the NGO that approved a request can fulfil it, and only once. The request moves to <strong>Fulfilled</strong> and the beneficiary is shown these details.</p>
        </aside>
      </section>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
