<?php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
$ngo_id = (int) $_SESSION['ngo_id'];

// ─── Handle Approve / Reject ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['volunteer_id'], $_POST['new_status'])) {
    csrf_require_post();
    $volunteer_id = (int) $_POST['volunteer_id'];
    $new_status   = $_POST['new_status'] === 'Approved' ? 'Approved' : 'Rejected';
    // Security: only update if the campaign belongs to this NGO
    $stmt = $pdo->prepare(
        "UPDATE campaign_volunteers cv
         JOIN campaigns c ON c.id = cv.campaign_id
         SET cv.status = ?
         WHERE cv.id = ? AND c.ngo_id = ?"
    );
    $stmt->execute([$new_status, $volunteer_id, $ngo_id]);
    header("Location: ngo-volunteers.php");
    exit;
}

// ─── Fetch ongoing campaigns for this NGO ─────────────────────────────────────
$stmtOngoing = $pdo->prepare(
    "SELECT id, title, st_date, end_date FROM campaigns
     WHERE ngo_id = ? AND status = 'Ongoing'
     ORDER BY st_date ASC"
);
$stmtOngoing->execute([$ngo_id]);
$ongoingCampaigns = $stmtOngoing->fetchAll(PDO::FETCH_ASSOC);

// ─── Fetch completed campaigns for this NGO ───────────────────────────────────
$stmtDone = $pdo->prepare(
    "SELECT id, title, st_date, end_date FROM campaigns
     WHERE ngo_id = ? AND status = 'Completed'
     ORDER BY end_date DESC"
);
$stmtDone->execute([$ngo_id]);
$doneCampaigns = $stmtDone->fetchAll(PDO::FETCH_ASSOC);

// ─── Helper: fetch volunteers for a campaign ──────────────────────────────────
function getVolunteers($pdo, $campaign_id) {
    $stmt = $pdo->prepare(
        "SELECT cv.id AS vol_id, cv.skills, cv.status, cv.applied_at,
                d.name AS donor_name
         FROM campaign_volunteers cv
         JOIN donor_master d ON d.id = cv.donor_id
         WHERE cv.campaign_id = ?
         ORDER BY cv.applied_at ASC"
    );
    $stmt->execute([(int)$campaign_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Volunteers · NGO | HelpSphere</title>
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
      <a href="../ngos/ngo-campaigns.php"><svg class="icon"><use href="../assets/img/icons.svg#i-megaphone"/></svg>Campaigns</a>
      <a href="../ngos/ngo-volunteers.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteers</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Volunteers</strong></nav>
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
          <p class="page-head__eyebrow">Volunteers</p>
          <h1 class="page-head__title">Volunteer management</h1>
          <p class="page-head__sub">People who applied to your campaigns. Approve the ones you want on the ground.</p>
        </div>
        <div class="page-head__actions">
          <a href="../ngos/ngo-campaigns-create.php" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-plus"/></svg>New campaign</a>
        </div>
      </section>

      <section class="card reveal">
        <header class="card__head">
          <div>
            <h2 class="card__title">Ongoing campaigns</h2>
            <p class="card__sub">Open a campaign to review its applicants</p>
          </div>
        </header>
        <?php if (empty($ongoingCampaigns)): ?>
        <div class="empty"><svg class="icon"><use href="../assets/img/icons.svg#i-megaphone"/></svg><strong>No ongoing campaigns at the moment</strong></div>
        <?php else: ?>
        <div class="list">
          <?php foreach ($ongoingCampaigns as $c):
            $cid  = (int) $c['id'];
            $vols = getVolunteers($pdo, $cid);
          ?>
          <div class="item">
            <span class="item__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-megaphone"/></svg></span>
            <div class="item__body">
              <div class="item__top">
                <span class="item__title"><?php echo htmlspecialchars($c['title']); ?></span>
                <span class="pill pill--info"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg><?php echo count($vols); ?> applicant<?php echo count($vols) !== 1 ? 's' : ''; ?></span>
              </div>
              <div class="item__meta"><span><svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg><?php echo htmlspecialchars($c['st_date']); ?> → <?php echo htmlspecialchars($c['end_date']); ?></span></div>
              <div class="item__actions">
                <button type="button" class="btn btn--outline btn--xs" data-expand="#panel-<?php echo $cid; ?>"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg><span data-expand-label data-closed="View volunteers" data-open="Hide volunteers">View volunteers</span></button>
              </div>
              <div id="panel-<?php echo $cid; ?>" hidden>
              <?php if (empty($vols)): ?>
                <p class="muted">No volunteers have applied for this campaign yet.</p>
              <?php else: ?>
                <div class="table-wrap">
                <table class="table table--stack">
                  <thead>
                    <tr><th>#</th><th>Volunteer</th><th>Skills</th><th>Applied on</th><th>Status</th><th>Actions</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($vols as $i => $v):
                      $statusClass = strtolower($v['status']);
                      $isPending   = $v['status'] === 'Pending';
                    ?>
                    <tr>
                      <td><?php echo $i + 1; ?></td>
                      <td><div class="who"><span class="avatar"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg></span><strong><?php echo htmlspecialchars($v['donor_name']); ?></strong></div></td>
                      <td class="text-cell"><?php echo htmlspecialchars($v['skills'] ?: '—'); ?></td>
                      <td class="date-cell"><?php echo date('d M Y', strtotime($v['applied_at'])); ?></td>
                      <td>
                        <span class="status <?php echo e($statusClass); ?>" data-status="<?php echo htmlspecialchars($v['status']); ?>"><?php echo htmlspecialchars($v['status']); ?></span>
                      </td>
                      <td>
                        <?php if ($isPending): ?>
                        <div class="actions">
                          <form method="POST" action="">
                            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
                            <input type="hidden" name="volunteer_id" value="<?php echo (int)$v['vol_id']; ?>">
                            <input type="hidden" name="new_status"   value="Approved">
                            <button type="submit" class="act act--yes"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Approve</button>
                          </form>
                          <form method="POST" action="">
                            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
                            <input type="hidden" name="volunteer_id" value="<?php echo (int)$v['vol_id']; ?>">
                            <input type="hidden" name="new_status"   value="Rejected">
                            <button type="submit" class="act act--no"><svg class="icon"><use href="../assets/img/icons.svg#i-x"/></svg>Reject</button>
                          </form>
                        </div>
                        <?php else: ?>
                          <span class="muted">—</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
                </div>
              <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </section>

      <section class="card reveal">
        <header class="card__head">
          <div>
            <h2 class="card__title">Completed campaigns</h2>
            <p class="card__sub">Who took part in your past campaigns</p>
          </div>
        </header>
        <?php if (empty($doneCampaigns)): ?>
        <div class="empty"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg><strong>No completed campaigns yet</strong></div>
        <?php else: ?>
        <div class="list">
          <?php foreach ($doneCampaigns as $c):
            $cid  = (int) $c['id'];
            $vols = getVolunteers($pdo, $cid);
          ?>
          <div class="item">
            <span class="item__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg></span>
            <div class="item__body">
              <div class="item__top">
                <span class="item__title">
                  <?php echo htmlspecialchars($c['title']); ?>
                </span>
                <span class="status" data-status="Completed">Completed</span>
                <span class="pill pill--muted"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg><?php echo count($vols); ?> participant<?php echo count($vols) !== 1 ? 's' : ''; ?></span>
              </div>
              <div class="item__meta"><span><svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg><?php echo htmlspecialchars($c['st_date']); ?> → <?php echo htmlspecialchars($c['end_date']); ?></span></div>
              <div class="item__actions">
                <button type="button" class="btn btn--outline btn--xs" data-expand="#done-panel-<?php echo $cid; ?>"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg><span data-expand-label data-closed="View volunteers" data-open="Hide volunteers">View volunteers</span></button>
              </div>
              <div id="done-panel-<?php echo $cid; ?>" hidden>
              <?php if (empty($vols)): ?>
                <p class="muted">No volunteers took part in this campaign.</p>
              <?php else: ?>
                <div class="table-wrap">
                <table class="table table--stack">
                  <thead>
                    <tr><th>#</th><th>Volunteer</th><th>Skills</th><th>Applied on</th><th>Status</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($vols as $i => $v):
                      $statusClass = strtolower($v['status']);
                    ?>
                    <tr>
                      <td><?php echo $i + 1; ?></td>
                      <td><div class="who"><span class="avatar"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg></span><strong><?php echo htmlspecialchars($v['donor_name']); ?></strong></div></td>
                      <td class="text-cell"><?php echo htmlspecialchars($v['skills'] ?: '—'); ?></td>
                      <td class="date-cell"><?php echo date('d M Y', strtotime($v['applied_at'])); ?></td>
                      <td>
                        <span class="status <?php echo e($statusClass); ?>" data-status="<?php echo htmlspecialchars($v['status']); ?>"><?php echo htmlspecialchars($v['status']); ?></span>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
                </div>
              <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </section>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
