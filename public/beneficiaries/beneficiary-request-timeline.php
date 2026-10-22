<?php
require_once __DIR__. '/../../inc/auth.php';
require_login();

$request_id=(int)($_GET['id'] ?? 0);
$beneficiary_id=(int)($_SESSION['beneficiary_id'] ?? 0);

if($request_id <= 0)
{
    echo "Invalid Request ID";
    exit;
}

$stmt=$pdo->prepare("select * from assistance_requests where id=? and beneficiary_id=?");
$stmt->execute([$request_id, $beneficiary_id]);
$record=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$record)
{
    echo "Request not found";
    exit;
}
$cat=$record['category'];
$status=$record['status'];
$created_at=$record['created_at'];

/* Document progress: summarised across all of this beneficiary's documents */
$stmt=$pdo->prepare("select count(*) as total,
                            coalesce(sum(status='Verified'),0) as verified,
                            max(uploaded_at) as last_upload
                     from beneficiary_documents where beneficiary_id=?");
$stmt->execute([$beneficiary_id]);
$docs=$stmt->fetch(PDO::FETCH_ASSOC);

$doc_total=(int)$docs['total'];
$docs_uploaded=$doc_total>0;
$docs_verified=$doc_total>0 && (int)$docs['verified']===$doc_total;
$doc_last=$docs['last_upload'];
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Request timeline · Beneficiary | HelpSphere</title>
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

    <span class="sidebar__tag">Beneficiary space</span>

    <nav class="sidebar__nav" aria-label="Beneficiary space">
      <a href="../dashboards/beneficiary-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../beneficiaries/beneficiary-request-management.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg>My requests</a>
      <a href="../beneficiaries/beneficiary-document-upload.php"><svg class="icon"><use href="../assets/img/icons.svg#i-upload"/></svg>Upload documents</a>
      <a href="../beneficiaries/beneficiary-documents.php"><svg class="icon"><use href="../assets/img/icons.svg#i-file"/></svg>My documents</a>
      <a href="../beneficiaries/beneficiary-messages.php"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Messages</a>
      <a href="../beneficiaries/beneficiary-profile.php"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg>Profile</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/beneficiary-dashboard.php">Beneficiary space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../beneficiaries/beneficiary-request-management.php">My requests</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Timeline</strong></nav>
      <div class="topbar__right">
        <a href="../../index.php" class="topbar__site"><svg class="icon"><use href="../assets/img/icons.svg#i-sprout"/></svg><span>HelpSphere home</span></a>
        <div class="profile">
          <span class="avatar avatar--brand"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg></span>
          <div class="profile__text"><strong>Beneficiary</strong><span>Beneficiary space</span></div>
        </div>
      </div>
    </header>

    <main class="content" id="content">

      <section class="page-head">
        <svg aria-hidden="true" class="leaf leaf--p1"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p2"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p3"><use href="../assets/img/icons.svg#leaf"/></svg>
        <div>
          <p class="page-head__eyebrow">Request timeline</p>
          <h1 class="page-head__title"><?php echo e($cat) ?> assistance</h1>
          <p class="page-head__sub">Submitted on <?php echo e($created_at) ?></p>
        </div>
        <div class="page-head__actions">
          <span class="status" data-status="<?php echo e($status) ?>"><?php echo e($status) ?></span>
        </div>
      </section>

      <section class="row row--2-1">
        <article class="card reveal">
          <header class="card__head">
            <div>
              <h2 class="card__title">Progress</h2>
              <p class="card__sub">Every step of your request so far</p>
            </div>
          </header>

          <ol class="timeline">
            <li>
              <span class="timeline__dot"></span>
              <div>
                <strong>Request submitted</strong>
                <p><?php echo e($created_at) ?> — your request has been successfully submitted.</p>
              </div>
            </li>
            <?php if($docs_uploaded): ?>
            <li>
              <span class="timeline__dot"></span>
              <div>
                <strong>Documents uploaded</strong>
                <p><?php echo htmlspecialchars(date('d M Y, h:i A', strtotime($doc_last))) ?> — your supporting documents were uploaded for verification.</p>
              </div>
            </li>
            <?php endif ?>
            <?php if($docs_verified): ?>
            <li>
              <span class="timeline__dot"></span>
              <div>
                <strong>Verification completed</strong>
                <p>All your documents have been successfully verified.</p>
              </div>
            </li>
            <?php endif ?>
            <?php if($status==="Approved" || $status==="Fulfilled"): ?>
            <li>
              <span class="timeline__dot"></span>
              <div>
                <strong>Request approved</strong>
                <p>An NGO team has approved your request.</p>
              </div>
            </li>
            <?php endif ?>
            <?php if($status==="Fulfilled"): ?>
            <li>
              <span class="timeline__dot"></span>
              <div>
                <strong>Request fulfilled</strong>
                <p>The NGO team has fulfilled your request.</p>
              </div>
            </li>
            <?php endif ?>
          </ol>
        </article>

        <aside class="card reveal">
          <header class="card__head">
            <div>
              <h2 class="card__title">What happens next</h2>
              <p class="card__sub">Steps you can take</p>
            </div>
          </header>
          <div class="list">
            <div class="item">
              <span class="item__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-upload"/></svg></span>
              <div class="item__body">
                <span class="item__title">Keep documents up to date</span>
                <p class="item__text">NGOs verify your documents before approving help.</p>
                <div class="item__actions"><a href="../beneficiaries/beneficiary-document-upload.php" class="btn btn--outline btn--xs">Upload documents</a></div>
              </div>
            </div>
            <div class="item">
              <span class="item__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg></span>
              <div class="item__body">
                <span class="item__title">Talk to an NGO</span>
                <p class="item__text">Ask questions or share details about your request.</p>
                <div class="item__actions"><a href="../beneficiaries/beneficiary-messages.php" class="btn btn--outline btn--xs">Open messages</a></div>
              </div>
            </div>
          </div>
        </aside>
      </section>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
