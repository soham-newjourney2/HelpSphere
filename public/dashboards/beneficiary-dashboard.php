<?php
require_once __DIR__. '/../../inc/auth.php';
require_login();
$status="Pending";
$stmt=$pdo->prepare("select * from assistance_requests where status=? and beneficiary_id=?");
$stmt->bindParam(1,$status);
$stmt->bindParam(2, $_SESSION['beneficiary_id']);
$stmt->execute();
$c=$stmt->rowCount();

$status="Approved";
$stmt=$pdo->prepare("select * from assistance_requests where status=? and beneficiary_id=?");
$stmt->bindParam(1,$status);
$stmt->bindParam(2, $_SESSION['beneficiary_id']);
$stmt->execute();
$c1=$stmt->rowCount();

// $status="Rejected";
// $stmt=$pdo->prepare("select * from assistance_requests where status=? and beneficiary_id=?");
// $stmt->bindParam(1,$status);
// $stmt->bindParam(2, $_SESSION['beneficiary_id']);
// $stmt->execute();
// $c2=$stmt->rowCount();

$status="Fulfilled";
$stmt=$pdo->prepare("select * from assistance_requests where status=? and beneficiary_id=?");
$stmt->bindParam(1,$status);
$stmt->bindParam(2, $_SESSION['beneficiary_id']);
$stmt->execute();
$c3=$stmt->rowCount();
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Overview · Beneficiary | HelpSphere</title>
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
      <a href="../dashboards/beneficiary-dashboard.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../beneficiaries/beneficiary-request-management.php"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg>My requests</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/beneficiary-dashboard.php">Beneficiary space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Overview</strong></nav>
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
          <p class="page-head__eyebrow" data-today>Today</p>
          <h1 class="page-head__title"><span data-greeting>Hello</span>, <?php echo e(isset($_SESSION['fullname'])?$_SESSION['fullname']:'') ?></h1>
          <p class="page-head__sub">Here is where your assistance requests stand.</p>
        </div>
        <div class="page-head__actions">
          <a href="../beneficiaries/beneficiary-document-upload.php" class="btn btn--outline btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-upload"/></svg>Upload documents</a>
          <a href="../beneficiaries/beneficiary-request-create.php" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-plus"/></svg>New request</a>
        </div>
      </section>

      <!-- Request counts -->
      <section class="kpis" aria-label="Your requests">
        <article class="kpi reveal">
          <div class="kpi__top"><span class="kpi__label">Pending requests</span><span class="kpi__icon"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-clock"/></svg></span></div>
          <p class="kpi__value" data-count><?php echo e($c) ?></p>
          <p class="kpi__note">Waiting for an NGO to review</p>
        </article>
        <article class="kpi reveal">
          <div class="kpi__top"><span class="kpi__label">Approved requests</span><span class="kpi__icon"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-check"/></svg></span></div>
          <p class="kpi__value" data-count><?php echo e($c1) ?></p>
          <p class="kpi__note">An NGO is working on these</p>
        </article>
        <article class="kpi kpi--dark reveal">
          <div class="kpi__top"><span class="kpi__label">Fulfilled requests</span><span class="kpi__icon"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-heart"/></svg></span></div>
          <p class="kpi__value" data-count><?php echo e($c3) ?></p>
          <p class="kpi__note">Help that has reached you</p>
        </article>
      </section>

      <section class="row row--2-1">
        <!-- Latest requests -->
        <article class="card reveal">
          <header class="card__head">
            <div>
              <h2 class="card__title">Latest requests</h2>
              <p class="card__sub">Your five most recent requests</p>
            </div>
            <a href="../beneficiaries/beneficiary-request-management.php" class="card__link">All requests <svg class="icon"><use href="../assets/img/icons.svg#i-arrow"/></svg></a>
          </header>

          <div class="list" id="latestRequests">
                <?php
                $stmt=$pdo->prepare("select * from assistance_requests where beneficiary_id=? order by id DESC LIMIT 5");
                $stmt->bindParam(1,$_SESSION['beneficiary_id']);
                $stmt->execute();
                if($stmt->rowCount()>0)
                {
                    while($record=$stmt->fetch(PDO::FETCH_ASSOC))
                    {
                        $category=$record['category'];
                        $description=$record['description'];
                        $status=$record['status'];
                        $created_at=$record['created_at'];
                        ?>
            <div class="item">
              <span class="item__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg></span>
              <div class="item__body">
                <div class="item__top">
                  <span class="item__title"><?php echo e($category) ?> assistance</span>
                  <span class="status" data-status="<?php echo e($status) ?>"><?php echo e($status) ?></span>
                </div>
                <p class="item__text"><?php echo e($description) ?></p>
                <div class="item__meta"><span><svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg><?php echo e($created_at) ?></span></div>
              </div>
            </div>
                        <?php
                    }
                }
                 ?>
          </div>
          <div class="empty" data-empty-for="#latestRequests">
            <svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg>
            <strong>No requests yet</strong>
            <p>Tell an NGO what you need — it only takes a minute.</p>
            <a href="../beneficiaries/beneficiary-request-create.php" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-plus"/></svg>Create a request</a>
          </div>
        </article>

        <!-- How it works -->
        <article class="urgent reveal">
          <svg aria-hidden="true" class="leaf leaf--u1"><use href="../assets/img/icons.svg#leaf"/></svg>
          <svg aria-hidden="true" class="leaf leaf--u2"><use href="../assets/img/icons.svg#leaf"/></svg>
          <span class="pill pill--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-info"/></svg>How your request moves</span>
          <h2>From request to help</h2>
          <ol class="urgent__steps">
            <li>Create a request for food or resources.</li>
            <li>Upload ID and supporting documents once.</li>
            <li>A verified NGO reviews and approves it.</li>
            <li>The NGO fulfils it — you can follow every step in the timeline.</li>
          </ol>
          <a href="../beneficiaries/beneficiary-messages.php" class="btn btn--light"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Message an NGO</a>
        </article>
      </section>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
