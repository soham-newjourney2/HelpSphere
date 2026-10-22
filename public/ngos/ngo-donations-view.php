<?php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>All donations · NGO | HelpSphere</title>
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
      <a href="../ngos/ngo-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../ngos/ngo-needs.php"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg>Needs &amp; requests</a>
      <a href="../ngos/ngo-beneficiaries.php"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg>Beneficiaries</a>
      <a href="../ngos/ngo-donations.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-gift"/></svg>Donations &amp; resources</a>
      <a href="../ngos/ngo-campaigns.php"><svg class="icon"><use href="../assets/img/icons.svg#i-megaphone"/></svg>Campaigns</a>
      <a href="../ngos/ngo-volunteers.php"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteers</a>
      <a href="../ngos/ngo-communication.php"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Communication</a>
      <a href="../ngos/ngo-profile.php"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg>Profile &amp; compliance</a>
    </nav>

    <div class="sidebar__foot">
      <a href="../../logout.php" class="sidebar__logout"><svg class="icon"><use href="../assets/img/icons.svg#i-logout"/></svg>Sign out</a>
    </div>
  </aside>
  <div class="scrim" id="scrim" hidden></div>

  <!-- ============ Main ============ -->
  <div class="main">
    <header class="topbar">
      <button type="button" class="icon-btn topbar__menu" id="menuBtn" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-menu"/></svg></button>
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../ngos/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../ngos/ngo-donations.php">Donations &amp; resources</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>All donations</strong></nav>
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
          <p class="page-head__eyebrow">Donations &amp; resources</p>
          <h1 class="page-head__title">Donations to your NGO</h1>
          <p class="page-head__sub">A complete log of donations made to your NGO, newest first.</p>
        </div>
      </section>

      <article class="card reveal">
        <header class="card__head">
          <div class="tabs" data-filter-target="#donationsTable" aria-label="Filter by status">
            <button type="button" class="tabs__btn is-active" data-filter="all" aria-pressed="true">All <span></span></button>
            <button type="button" class="tabs__btn" data-filter="Donated" aria-pressed="false">Donated <span></span></button>
            <button type="button" class="tabs__btn" data-filter="Accepted" aria-pressed="false">Accepted <span></span></button>
            <button type="button" class="tabs__btn" data-filter="Picked Up" aria-pressed="false">Picked up <span></span></button>
          </div>
          <label class="search"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-search"/></svg><span class="sr-only">Search donations</span><input type="search" placeholder="Search…" data-search-target="#donationsTable" /></label>
        </header>
        <div class="table-wrap">
          <table class="table table--stack" id="donationsTable">
            <thead>
              <tr><th>Donor</th><th>Type</th><th>Date &amp; time</th><th>Status</th></tr>
            </thead>
            <tbody>
          <?php
          $stmt = $pdo->prepare("SELECT * FROM donations WHERE target_id = ? AND target_role = ? ORDER BY created_at DESC");
          $stmt->bindParam(1, $_SESSION['ngo_id']);
          $stmt->bindValue(2, 'NGO');
          $stmt->execute();
          if ($stmt->rowCount() > 0):
            while ($result = $stmt->fetch(PDO::FETCH_ASSOC)):
              $donor_id    = (int) $result['donor_id'];
              $type        = htmlspecialchars($result['donation_type']);
              $date        = $result['created_at'];
              $status      = $result['status'];
              // ── Donor name from donor_master ──────────────────────────────
              $donor = '—';
              if ($donor_id > 0) {
                $stmtD = $pdo->prepare("SELECT name FROM donor_master WHERE id = ?");
                $stmtD->execute([$donor_id]);
                $rowD = $stmtD->fetch(PDO::FETCH_ASSOC);
                if ($rowD !== false) $donor = htmlspecialchars($rowD['name']);
              }
              $badgeClass = $status === 'Donated' ? 'badge-donated' : 'badge-pending';
          ?>
              <tr data-status="<?php echo htmlspecialchars($status); ?>">
                <td><div class="who"><span class="avatar"><svg class="icon"><use href="../assets/img/icons.svg#i-heart"/></svg></span><strong><?php echo $donor; ?></strong></div></td>
                <td><span class="pill pill--muted"><?php echo $type; ?></span></td>
                <td class="date-cell"><?php echo e($date); ?></td>
                <td><span class="status <?php echo e($badgeClass); ?>" data-status="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></span></td>
              </tr>
          <?php
            endwhile;
          else:
          ?>
          <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="empty" data-empty-for="#donationsTable">
          <svg class="icon"><use href="../assets/img/icons.svg#i-gift"/></svg>
          <strong>No donations recorded yet</strong>
          <p>Donations accepted by your NGO will be listed here.</p>
        </div>
      </article>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
