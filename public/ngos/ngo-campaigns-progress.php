<?php 
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();

// ─── Handle Close Campaign ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_campaign'])) {
  csrf_require_post();
  $campaign_id = (int) $_POST['close_campaign'];
  if ($campaign_id > 0) {
    $stmtClose = $pdo->prepare("UPDATE campaigns SET status = 'Completed' WHERE id = ? AND ngo_id = ?");
    $stmtClose->execute([$campaign_id, $_SESSION['ngo_id']]);
  }
  header("Location: ngo-campaigns-progress.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Active campaigns · NGO | HelpSphere</title>
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
      <a href="../ngos/ngo-donations.php"><svg class="icon"><use href="../assets/img/icons.svg#i-gift"/></svg>Donations &amp; resources</a>
      <a href="../ngos/ngo-campaigns.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-megaphone"/></svg>Campaigns</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../ngos/ngo-campaigns.php">Campaigns</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Active campaigns</strong></nav>
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
          <p class="page-head__eyebrow">Campaigns</p>
          <h1 class="page-head__title">Active campaigns</h1>
          <p class="page-head__sub">Campaigns that are running now. Close one when it is finished — it moves to the archive.</p>
        </div>
        <div class="page-head__actions">
          <a href="../ngos/ngo-campaigns-create.php" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-plus"/></svg>New campaign</a>
        </div>
      </section>

      <div class="cards-grid" id="activeCampaigns">
          <?php
          $stmt=$pdo->prepare("select * from campaigns where status=? and ngo_id=?");
          $stmt->bindValue(1, "Ongoing");
          $stmt->bindParam(2, $_SESSION['ngo_id']);
          $stmt->execute();
          if($stmt->rowCount() > 0)
          {
            while($record=$stmt->fetch(PDO::FETCH_ASSOC))
            {
              $campaign_id = (int) $record['id'];
              $name   = $record['title'];
              $desc   = $record['description'];
              $st_date  = $record['st_date'];
              $end_date = $record['end_date'];
              $status   = $record['status'];
              ?>
        <article class="mini-card">
          <div class="mini-card__top">
            <span class="item__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-megaphone"/></svg></span>
            <span class="status" data-status="<?php echo e($status) ?>"><?php echo e($status) ?></span>
          </div>
          <h3><?php echo e($name) ?></h3>
          <p><?php echo e($desc) ?></p>
          <div class="mini-card__meta">
            <span><svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg>Starts <?php echo e($st_date) ?></span>
            <span><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Ends <?php echo e($end_date) ?></span>
          </div>
          <div class="mini-card__foot">
            <form method="POST" data-confirm="Mark this campaign as completed?">
              <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
              <button type="submit" name="close_campaign" value="<?php echo $campaign_id; ?>" class="act act--dark"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Close campaign</button>
            </form>
            <a href="../ngos/ngo-volunteers.php" class="act act--plain"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteers</a>
          </div>
        </article>
              <?php
            }
          }
          ?>
      </div>
      <div class="empty card" data-empty-for="#activeCampaigns">
        <svg class="icon"><use href="../assets/img/icons.svg#i-megaphone"/></svg>
        <strong>No active campaigns</strong>
        <p>Create a campaign to invite volunteers.</p>
        <a href="../ngos/ngo-campaigns-create.php" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-plus"/></svg>New campaign</a>
      </div>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
