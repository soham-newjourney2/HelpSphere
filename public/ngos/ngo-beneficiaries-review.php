<?php 
//ngo-beneficiaries-review.php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Review requests · NGO | HelpSphere</title>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../ngos/ngo-beneficiaries.php">Beneficiaries</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Review requests</strong></nav>
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
          <h1 class="page-head__title">Review requests</h1>
          <p class="page-head__sub">Verify identity and eligibility first. Approving claims a request for your NGO; rejecting only hides it from you — other NGOs can still help.</p>
        </div>
        <div class="page-head__actions">
          <a href="../ngos/ngo-beneficiaries-reports.php" class="btn btn--outline btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-flag"/></svg>Document reports</a>
        </div>
      </section>

      <!-- Beneficiaries & their documents -->
      <section class="card reveal">
        <header class="card__head">
          <div>
            <h2 class="card__title">Beneficiary documents</h2>
            <p class="card__sub">Open a beneficiary to verify, reject or report their documents</p>
          </div>
          <label class="search"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-search"/></svg><span class="sr-only">Search beneficiaries</span><input type="search" placeholder="Search…" data-search-target="#beneficiaryTable" /></label>
        </header>
        <div class="table-wrap">
          <table class="table table--stack" id="beneficiaryTable">
            <thead>
              <tr><th>Beneficiary</th><th>Address</th><th>Documents</th></tr>
            </thead>
            <tbody>
          <?php
          $stmtBen = $pdo->prepare("SELECT * FROM beneficiary_master ORDER BY name ASC");
          $stmtBen->execute();
          if ($stmtBen->rowCount() > 0):
            while ($ben = $stmtBen->fetch(PDO::FETCH_ASSOC)):
              $ben_id      = (int) $ben['id'];
              $ben_name    = htmlspecialchars($ben['name'] ?? '');
              $ben_address = htmlspecialchars($ben['address'] ?? '—');
          ?>
              <tr>
                <td><div class="who"><span class="avatar"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg></span><strong><?php echo $ben_name; ?></strong></div></td>
                <td class="text-cell"><?php echo $ben_address; ?></td>
                <td><a href="../ngos/ngo-beneficiaries-documents.php?beneficiary_id=<?php echo $ben_id; ?>" class="act act--plain"><svg class="icon"><use href="../assets/img/icons.svg#i-file"/></svg>View documents</a></td>
              </tr>
          <?php
            endwhile;
          else:
          ?>
          <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="empty" data-empty-for="#beneficiaryTable">
          <svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg>
          <strong>No beneficiaries registered yet</strong>
        </div>
      </section>

      <!-- Pending requests with Approve / Reject -->
      <section class="card reveal is-first">
        <header class="card__head">
          <div>
            <h2 class="card__title">Pending beneficiary requests</h2>
            <p class="card__sub">First come, first served — only one NGO can approve a request</p>
          </div>
          <label class="search"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-search"/></svg><span class="sr-only">Search requests</span><input type="search" placeholder="Search…" data-search-target="#pendingTable" /></label>
        </header>
        <div class="table-wrap">
          <table class="table table--stack" id="pendingTable">
            <thead>
              <tr><th>Applicant</th><th>Requested need</th><th>Submitted</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
          <?php
          $name="";
          $description="";
          $status="";
          $created_at="";
          $stmt = $pdo->prepare("SELECT * FROM assistance_requests WHERE status = ?");
          $stmt->bindValue(1, "Pending");
          $stmt->execute();
          if ($stmt->rowCount() > 0):
            while ($rec = $stmt->fetch(PDO::FETCH_ASSOC)):
              $stmt1=$pdo->prepare("SELECT * FROM assistance_rejection WHERE request_id=? and rejected_by_ngo_id=?");
              $stmt1->bindParam(1, $rec['id']);
              $stmt1->bindParam(2, $_SESSION['ngo_id']);
              $stmt1->execute();
              if($stmt1->rowCount() == 0){
                $id          = $rec['beneficiary_id'];
              $request_id  = $rec['id'];
              $description = htmlspecialchars($rec['description']);
              $status      = htmlspecialchars($rec['status']);
              $created_at  = $rec['created_at'];
              $stmt1 = $pdo->prepare("SELECT * FROM beneficiary_master WHERE id = ?");
              $stmt1->bindParam(1, $id);
              $stmt1->execute();
              $name = '—';
              if ($stmt1->rowCount() > 0) {
                $res  = $stmt1->fetch(PDO::FETCH_ASSOC);
                $name = htmlspecialchars($res['name']);
              }
          ?>
              <tr>
                <td><div class="who"><span class="avatar"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg></span><strong><?php echo $name; ?></strong></div></td>
                <td class="text-cell"><?php echo $description; ?></td>
                <td class="date-cell"><?php echo $created_at; ?></td>
                <td><span class="status" data-status="<?php echo $status; ?>"><?php echo $status; ?></span></td>
                <td>
                  <div class="actions">
                    <span class="actions actions--yes"><?php echo action_button('../ngos/request-edit.php', ['value'=>'Approved','request_id'=>$request_id], 'Approve') ?></span>
                    <span class="actions actions--no"><?php echo action_button('../ngos/request-edit.php', ['value'=>'Rejected','request_id'=>$request_id], 'Reject') ?></span>
                  </div>
                </td>
              </tr>
          <?php
              }
            endwhile;
          else:
          ?>
          <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="empty" data-empty-for="#pendingTable">
          <svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>
          <strong>No pending requests</strong>
          <p>New requests from beneficiaries will appear here.</p>
        </div>
      </section>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
