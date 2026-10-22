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

// Only load the request if it belongs to the logged-in beneficiary
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

// Defaults, so the page works when no fulfillment row exists yet
$fulfilled_at='';
$support_type='';
$provider='';
$delivery_method='';

$stmt2=$pdo->prepare("select * from fulfillment_status where request_id=?");
$stmt2->execute([$request_id]);
$res2=$stmt2->fetch(PDO::FETCH_ASSOC);
if($res2)
{
    $fulfilled_at=$res2['fulfilled_at'];
    $support_type=$res2['support_type'];
    $provider=$res2['provider'];
    $delivery_method=$res2['delivery_method'];
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Fulfillment · Beneficiary | HelpSphere</title>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/beneficiary-dashboard.php">Beneficiary space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../beneficiaries/beneficiary-request-management.php">My requests</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Fulfillment details</strong></nav>
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
          <p class="page-head__eyebrow">Fulfillment status</p>
          <h1 class="page-head__title"><?php echo e($cat) ?> assistance</h1>
          <p class="page-head__sub">Submitted <?php echo e($created_at) ?></p>
        </div>
        <div class="page-head__actions">
          <span class="status" data-status="<?php echo e($status) ?>"><?php echo e($status) ?></span>
        </div>
      </section>

      <section class="row row--2-1">
        <article class="card reveal">
          <header class="card__head">
            <div>
              <h2 class="card__title">How your request was fulfilled</h2>
              <p class="card__sub">Recorded by the NGO that helped you</p>
            </div>
          </header>
          <dl class="details">
            <div><dt>Fulfilled at</dt><dd><?php echo $fulfilled_at!=='' ? htmlspecialchars($fulfilled_at) : 'Not recorded yet' ?></dd></div>
            <div><dt>Type of support</dt><dd><?php echo htmlspecialchars((string)$support_type) ?></dd></div>
            <div><dt>Provider</dt><dd><?php echo htmlspecialchars((string)$provider) ?></dd></div>
            <div><dt>Delivery method</dt><dd><?php echo htmlspecialchars((string)$delivery_method) ?></dd></div>
          </dl>
          <div class="card__foot">
            <a href="../beneficiaries/beneficiary-request-management.php" class="btn btn--outline btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-arrow-left"/></svg>Back to requests</a>
            <a href="../beneficiaries/beneficiary-messages.php" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Contact the NGO</a>
          </div>
        </article>

        <aside class="urgent reveal">
          <svg aria-hidden="true" class="leaf leaf--u1"><use href="../assets/img/icons.svg#leaf"/></svg>
          <svg aria-hidden="true" class="leaf leaf--u2"><use href="../assets/img/icons.svg#leaf"/></svg>
          <span class="pill pill--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-heart"/></svg>Closure note</span>
          <h2>We are glad you got help</h2>
          <p>Your request has been fulfilled. If anything is missing or you need further assistance, contact the NGO through Messages.</p>
        </aside>
      </section>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
