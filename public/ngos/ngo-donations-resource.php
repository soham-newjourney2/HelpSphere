<?php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
$ngo_id = (int) $_SESSION['ngo_id'];

// ─── Handle Accept ────────────────────────────────────────────────────────────
if (isset($_POST['accept_id'])) {
    csrf_require_post();
    $res_id   = (int) $_POST['accept_id'];

    $pdo->beginTransaction();
    // 1. Mark resource_donations as Approved, record which NGO accepted
    $stmt = $pdo->prepare(
        "UPDATE resource_donations
         SET status = 'Approved', ngo_id = ?
         WHERE id = ? AND status = 'Pending'"
    );
    $stmt->execute([$ngo_id, $res_id]);

    // 2. Only if this NGO won it: update the matching donations row (donor taken from the database, not the form)
    if ($stmt->rowCount() === 1) {
        $sd = $pdo->prepare("SELECT donor_id FROM resource_donations WHERE id = ?");
        $sd->execute([$res_id]);
        $stmt2 = $pdo->prepare(
            "UPDATE donations
             SET status = 'Donated', target_id = ?
             WHERE donor_id = ? AND donation_type = 'Resource' AND status = 'Pending'
             ORDER BY created_at DESC
             LIMIT 1"
        );
        $stmt2->execute([$ngo_id, (int) $sd->fetchColumn()]);
    }
    $pdo->commit();

    header("Location: ngo-donations-resource.php");
    exit;
}

// ─── Fetch donations visible to this NGO ─────────────────────────────────────
// Show: all Pending rows + Approved rows where THIS NGO accepted
$stmt = $pdo->prepare(
    "SELECT rd.*, dm.name AS donor_name
     FROM resource_donations rd
     JOIN donor_master dm ON dm.id = rd.donor_id
     WHERE rd.status = 'Pending'
        OR (rd.status = 'Approved' AND rd.ngo_id = ?)
     ORDER BY rd.id DESC"
);
$stmt->execute([$ngo_id]);
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Resource donations · NGO | HelpSphere</title>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../ngos/ngo-donations.php">Donations &amp; resources</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Resource donations</strong></nav>
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
          <h1 class="page-head__title">Resource donations</h1>
          <p class="page-head__sub">Open offers from donors, plus the ones your NGO has accepted. The first NGO to accept an offer gets it.</p>
        </div>
      </section>

      <article class="card reveal">
        <header class="card__head">
          <div class="tabs" data-filter-target="#resourceTable" aria-label="Filter by status">
            <button type="button" class="tabs__btn is-active" data-filter="all" aria-pressed="true">All <span></span></button>
            <button type="button" class="tabs__btn" data-filter="Pending" aria-pressed="false">Open <span></span></button>
            <button type="button" class="tabs__btn" data-filter="Approved" aria-pressed="false">Accepted by you <span></span></button>
          </div>
        </header>
        <div class="table-wrap">
          <table class="table table--stack" id="resourceTable">
            <thead>
              <tr><th>Donor</th><th>Resource</th><th>Condition</th><th>Quantity</th><th>Pickup date</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
          <?php if (empty($donations)): ?>
          <?php else: ?>
            <?php foreach ($donations as $rec):
              $res_id    = (int) $rec['id'];
              $donor_id  = (int) $rec['donor_id'];
              $status    = $rec['status'];
            ?>
              <tr data-status="<?php echo htmlspecialchars($status); ?>">
                <td><div class="who"><span class="avatar"><svg class="icon"><use href="../assets/img/icons.svg#i-heart"/></svg></span><strong><?php echo htmlspecialchars($rec['donor_name']); ?></strong></div></td>
                <td><span class="pill pill--muted"><svg class="icon"><use href="../assets/img/icons.svg#i-box"/></svg><?php echo htmlspecialchars($rec['resource_type']); ?></span></td>
                <td><?php echo htmlspecialchars($rec['resource_condition']); ?></td>
                <td><?php echo htmlspecialchars($rec['quantity']); ?></td>
                <td class="date-cell"><?php echo htmlspecialchars($rec['pickup_date']); ?></td>
                <td>
                  <span class="status <?php echo $status === 'Approved' ? 'badge-approved' : 'badge-pending'; ?>" data-status="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></span>
                </td>
                <td>
              <?php if ($status === 'Pending'): ?>
                  <form method="POST" action="" data-confirm="Accept this resource donation?">
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
                    <input type="hidden" name="accept_id" value="<?php echo $res_id; ?>">
                    <input type="hidden" name="donor_id"  value="<?php echo $donor_id; ?>">
                    <button type="submit" class="act act--yes"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Accept</button>
                  </form>
              <?php else: ?>
                  <span class="pill pill--good"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Accepted by you</span>
              <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="empty" data-empty-for="#resourceTable">
          <svg class="icon"><use href="../assets/img/icons.svg#i-box"/></svg>
          <strong>No resource donations available</strong>
          <p>Donors’ offers of clothes, books and supplies appear here.</p>
        </div>
      </article>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
