<?php
require_once __DIR__ . '/../../inc/auth.php';
ngo_require_login();
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Verification status · NGO | HelpSphere</title>
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
      <a href="../ngos/ngo-donations.php"><svg class="icon"><use href="../assets/img/icons.svg#i-gift"/></svg>Donations &amp; resources</a>
      <a href="../ngos/ngo-campaigns.php"><svg class="icon"><use href="../assets/img/icons.svg#i-megaphone"/></svg>Campaigns</a>
      <a href="../ngos/ngo-volunteers.php"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteers</a>
      <a href="../ngos/ngo-communication.php"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Communication</a>
      <a href="../ngos/ngo-profile.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg>Profile &amp; compliance</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../ngos/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../ngos/ngo-profile.php">Profile &amp; compliance</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Verification status</strong></nav>
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
          <p class="page-head__eyebrow">Profile &amp; compliance</p>
          <h1 class="page-head__title">Verification status</h1>
          <p class="page-head__sub">An admin reviews each compliance document. Your NGO is verified once every document is approved.</p>
        </div>
        <div class="page-head__actions">
          <a href="../ngos/ngo-profile-documents.php" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-upload"/></svg>Upload documents</a>
        </div>
      </section>

      <section class="row row--2-1">
        <article class="card reveal">
          <header class="card__head">
            <div>
              <h2 class="card__title">Document verification</h2>
              <p class="card__sub">Reviewed by the HelpSphere compliance team</p>
            </div>
          </header>
          <div class="table-wrap">
            <table class="table table--stack" id="verifyTable">
              <thead>
                <tr><th>Document</th><th>Status</th><th>Submitted on</th><th>Reviewed by</th></tr>
              </thead>
              <tbody>
          <?php
          $stmt = $pdo->prepare("select * from ngo_documents where ngo_id=?");
          $stmt->bindParam(1, $_SESSION['ngo_id']);
          $stmt->execute();
          if ($stmt->rowCount() > 0) {
            while ($res = $stmt->fetch(PDO::FETCH_ASSOC)) {
              $docname = isset($res['document_name']) ? $res['document_name'] : '';
              $status = isset($res['status']) ? $res['status'] : '';
              $created_at = isset($res['uploaded_at']) ? $res['uploaded_at'] : '';
          ?>
                <tr>
                  <td><div class="who"><span class="doc-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-file"/></svg></span><strong><?php echo e($docname) ?></strong></div></td>
                  <td>
              <?php if ($status == "Uploaded"): ?>
                    <span class="status" data-status="Under Review">Under review</span>
              <?php elseif ($status == "Verified"): ?>
                    <span class="status" data-status="Verified">Verified</span>
              <?php endif ?>
                  </td>
                  <td class="date-cell"><?php echo e($created_at) ?></td>
                  <td class="muted">Compliance team</td>
                </tr>
          <?php
            }
          }
          ?>
              </tbody>
            </table>
          </div>
          <div class="empty" data-empty-for="#verifyTable">
            <svg class="icon"><use href="../assets/img/icons.svg#i-file"/></svg>
            <strong>No documents submitted yet</strong>
            <p>Upload your registration and compliance documents to get verified.</p>
          </div>
        </article>

        <aside class="urgent reveal">
          <svg aria-hidden="true" class="leaf leaf--u1"><use href="../assets/img/icons.svg#leaf"/></svg>
          <svg aria-hidden="true" class="leaf leaf--u2"><use href="../assets/img/icons.svg#leaf"/></svg>
          <span class="pill pill--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg>Status</span>
          <?php if (isset($status)): ?>
            <?php if ($status == "Uploaded"): ?>
          <h2>Under review</h2>
          <p>Your documents are being reviewed by the verification team. No action is needed unless more information is requested — you will see the result here once the review is complete.</p>
            <?php endif ?>
            <?php if ($status == "Verified"): ?>
          <h2>Verified</h2>
          <p>Congratulations! Your documents have been verified and your NGO is approved to use HelpSphere.</p>
            <?php endif ?>
          <?php endif ?>
          <ol class="urgent__steps">
            <li>Upload your compliance documents.</li>
            <li>An admin verifies or rejects each one.</li>
            <li>When all are verified, you start receiving immediate food alerts.</li>
          </ol>
        </aside>
      </section>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
