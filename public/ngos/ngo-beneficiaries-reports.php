<?php
require_once __DIR__ . '/../../inc/auth.php';
ngo_require_login(); 

/*
  ── Required DB table (run once) ────────────────────────────────────────────
  CREATE TABLE document_reports (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    ngo_id           INT          NOT NULL,
    ngo_name         VARCHAR(255) NOT NULL,
    beneficiary_name VARCHAR(255) NOT NULL,
    document_name    VARCHAR(255) NOT NULL,
    report           TEXT         NOT NULL,
    reported_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
  );
  ─────────────────────────────────────────────────────────────────────────────
*/

$stmtReports = $pdo->prepare("SELECT * FROM document_reports ORDER BY reported_at DESC");
$stmtReports->execute();
$reports = $stmtReports->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Document reports · NGO | HelpSphere</title>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../ngos/ngo-beneficiaries.php">Beneficiaries</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Document reports</strong></nav>
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
          <h1 class="page-head__title">Document reports</h1>
          <p class="page-head__sub">Issues NGOs have reported about beneficiary documents, newest first.</p>
        </div>
        <div class="page-head__actions">
          <a href="ngo-beneficiaries-review.php" class="btn btn--outline btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-arrow-left"/></svg>Back to requests</a>
        </div>
      </section>

      <div class="list card reveal" id="reportList">
          <?php if (!empty($reports)): ?>
            <?php foreach ($reports as $report): ?>
        <div class="item">
          <span class="item__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-flag"/></svg></span>
          <div class="item__body">
            <div class="item__top">
              <span class="item__title"><?php echo htmlspecialchars($report['document_name']); ?></span>
              <span class="pill pill--muted"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg><?php echo htmlspecialchars($report['beneficiary_name']); ?></span>
            </div>
            <p class="item__text"><?php echo htmlspecialchars($report['report']); ?></p>
            <div class="item__meta">
              <span><svg class="icon"><use href="../assets/img/icons.svg#i-building"/></svg>Reported by <?php echo htmlspecialchars($report['ngo_name']); ?></span>
              <span><svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg><?php echo isset($report['reported_at']) ? date('M d, Y H:i', strtotime($report['reported_at'])) : '—'; ?></span>
            </div>
          </div>
        </div>
            <?php endforeach; ?>
          <?php else: ?>
        <div class="empty">
          <svg class="icon"><use href="../assets/img/icons.svg#i-flag"/></svg>
          <strong>No reports submitted yet</strong>
          <p>Reports appear here when an NGO flags a beneficiary document.</p>
        </div>
          <?php endif; ?>
      </div>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
