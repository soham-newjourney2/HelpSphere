<?php
require_once __DIR__. '/../../inc/auth.php';
donor_require_login();
$donor_id = (int) $_SESSION['donor_id'];

// ─── Handle Volunteer Application ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['campaign_id'])) {
    csrf_require_post();
    $campaign_id = (int) $_POST['campaign_id'];
    $skills      = trim($_POST['skills'] ?? '');

    if ($campaign_id > 0) {
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO campaign_volunteers (campaign_id, donor_id, skills, status)
             VALUES (?, ?, ?, 'Pending')"
        );
        $stmt->execute([$campaign_id, $donor_id, $skills]);
    }
    header("Location: donor-volunteer-registration.php");
    exit;
}

// ─── Fetch all ongoing campaigns ─────────────────────────────────────────────
$stmtC = $pdo->prepare(
    "SELECT c.id, c.title, c.description, c.st_date, c.end_date, n.name AS ngo_name
     FROM campaigns c
     JOIN ngo_master n ON n.id = c.ngo_id
     WHERE c.status = 'Ongoing'
     ORDER BY c.st_date ASC"
);
$stmtC->execute();
$campaigns = $stmtC->fetchAll(PDO::FETCH_ASSOC);

// ─── Fetch campaigns this donor already applied for ───────────────────────────
$stmtA = $pdo->prepare("SELECT campaign_id, status FROM campaign_volunteers WHERE donor_id = ?");
$stmtA->execute([$donor_id]);
$applied = [];
foreach ($stmtA->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $applied[$row['campaign_id']] = $row['status'];
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Volunteering · Donor | HelpSphere</title>
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

    <span class="sidebar__tag">Donor space</span>

    <nav class="sidebar__nav" aria-label="Donor space">
      <a href="../dashboards/donor-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../donors/donor-food-selection.php"><svg class="icon"><use href="../assets/img/icons.svg#i-food"/></svg>Food donation</a>
      <a href="../donors/donor-money-selection.php"><svg class="icon"><use href="../assets/img/icons.svg#i-coin"/></svg>Money donation</a>
      <a href="../donors/donor-resource-donation.php"><svg class="icon"><use href="../assets/img/icons.svg#i-box"/></svg>Resource donation</a>
      <a href="../donors/donor-volunteer-registration.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteering</a>
      <a href="../donors/donor-messages.php"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Messages</a>
      <a href="../donors/donor-profile-preferences.php"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg>Profile &amp; preferences</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/donor-dashboard.php">Donor space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Volunteering</strong></nav>
      <div class="topbar__right">
        <a href="../../index.php" class="topbar__site"><svg class="icon"><use href="../assets/img/icons.svg#i-sprout"/></svg><span>HelpSphere home</span></a>
        <div class="profile">
          <span class="avatar avatar--brand"><svg class="icon"><use href="../assets/img/icons.svg#i-heart"/></svg></span>
          <div class="profile__text"><strong>Donor</strong><span>Donor space</span></div>
        </div>
      </div>
    </header>

    <main class="content" id="content">

      <section class="page-head">
        <svg aria-hidden="true" class="leaf leaf--p1"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p2"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p3"><use href="../assets/img/icons.svg#leaf"/></svg>
        <div>
          <p class="page-head__eyebrow">Volunteering</p>
          <h1 class="page-head__title">Volunteer for a campaign</h1>
          <p class="page-head__sub">Ongoing NGO campaigns that need people on the ground. Apply with your skills — the NGO approves volunteers.</p>
        </div>
      </section>

      <!-- ── Volunteer modal ── -->
      <div class="modal" id="volunteerModal" hidden role="dialog" aria-modal="true" aria-labelledby="vmTitle">
        <div class="modal__box">
          <div class="modal__head">
            <div>
              <h2 id="vmTitle" data-fill="title">Campaign</h2>
              <p>Organised by <span data-fill="ngo">—</span></p>
            </div>
            <button type="button" class="icon-btn" data-modal-close aria-label="Close"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-close"/></svg></button>
          </div>
          <dl class="details">
            <div><dt>Starts</dt><dd data-fill="start">—</dd></div>
            <div><dt>Ends</dt><dd data-fill="end">—</dd></div>
          </dl>
          <p class="muted" data-fill="description"></p>
          <form method="POST" action="" class="form">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
            <input type="hidden" name="campaign_id" id="modalCampaignId">
            <div class="field">
              <label for="skillsInput">Your skills</label>
              <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-hand"/></svg><input type="text" id="skillsInput" name="skills" placeholder="e.g. Teaching, first aid, driving" required></div>
            </div>
            <div class="modal__actions">
              <button type="button" class="btn btn--outline btn--sm" data-modal-close>Cancel</button>
              <button type="submit" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteer</button>
            </div>
          </form>
        </div>
      </div>

      <div class="cards-grid">
            <?php if (empty($campaigns)): ?>
        <div class="card empty">
          <svg class="icon"><use href="../assets/img/icons.svg#i-megaphone"/></svg>
          <strong>No ongoing campaigns right now</strong>
          <p>Check back soon — NGOs post new campaigns regularly.</p>
        </div>
            <?php else: ?>
                <?php foreach ($campaigns as $c):
                    $cid         = (int) $c['id'];
                    $title       = htmlspecialchars($c['title']);
                    $description = htmlspecialchars($c['description']);
                    $st_date     = htmlspecialchars($c['st_date']);
                    $end_date    = htmlspecialchars($c['end_date']);
                    $ngo_name    = htmlspecialchars($c['ngo_name']);
                    $isApplied   = isset($applied[$cid]);
                    $appStatus   = $isApplied ? $applied[$cid] : '';
                    $statusClass = 's-' . strtolower($appStatus);
                ?>
        <article class="mini-card">
          <div class="mini-card__top">
            <span class="item__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-megaphone"/></svg></span>
            <span class="pill pill--info"><svg class="icon"><use href="../assets/img/icons.svg#i-clock"/></svg>Ongoing</span>
          </div>
          <h3><?php echo $title; ?></h3>
          <p><?php echo $description; ?></p>
          <div class="mini-card__meta">
            <span><svg class="icon"><use href="../assets/img/icons.svg#i-building"/></svg><?php echo $ngo_name; ?></span>
            <span><svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg><?php echo $st_date; ?> → <?php echo $end_date; ?></span>
          </div>
          <div class="mini-card__foot">
                    <?php if ($isApplied): ?>
            <span class="pill pill--good"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Applied</span>
            <span class="status <?php echo e($statusClass); ?>" data-status="<?php echo e($appStatus); ?>"><?php echo e($appStatus); ?></span>
                    <?php else: ?>
            <button type="button" class="btn btn--dark btn--xs" data-modal-open="#volunteerModal"
                    data-fill-campaign_id="<?php echo $cid; ?>"
                    data-fill-title="<?php echo $title; ?>"
                    data-fill-description="<?php echo $description; ?>"
                    data-fill-start="<?php echo $st_date; ?>"
                    data-fill-end="<?php echo $end_date; ?>"
                    data-fill-ngo="<?php echo $ngo_name; ?>"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteer</button>
                    <?php endif; ?>
          </div>
        </article>
                <?php endforeach; ?>
            <?php endif; ?>
      </div>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
