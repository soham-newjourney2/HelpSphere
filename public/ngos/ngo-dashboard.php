<?php 
//ngo-dashboard.php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
global $c;
global $c1;
global $c2;

$stmt=$pdo->prepare("select * from assistance_requests where status=?");
$stmt->bindValue(1,"Pending");
$stmt->execute();
if($stmt)
    $c=$stmt->rowCount(); 

$stmt1=$pdo->prepare("select * from donations where target_id=? and target_role=?");
$stmt1->bindParam(1, $_SESSION['ngo_id']);
$stmt1->bindValue(2, "NGO");
$stmt1->execute();
if($stmt1)
    $c1=$stmt1->rowCount();

$stmt2=$pdo->prepare("select * from donations where target_id=? and target_role=? and status=?");
$stmt2->bindParam(1, $_SESSION['ngo_id']);
$stmt2->bindValue(2, "NGO");
$stmt2->bindValue(3,"Accepted");
$stmt2->execute();
if($stmt2)
    $c2=$stmt2->rowCount();

?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Overview · NGO | HelpSphere</title>
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
      <a href="../ngos/ngo-dashboard.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../ngos/ngo-needs.php"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg>Needs &amp; requests</a>
      <a href="../ngos/ngo-beneficiaries.php"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg>Beneficiaries</a>
      <a href="../ngos/ngo-donations.php"><svg class="icon"><use href="../assets/img/icons.svg#i-gift"/></svg>Donations &amp; resources</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../ngos/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Overview</strong></nav>
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
          <p class="page-head__eyebrow" data-today>Today</p>
          <h1 class="page-head__title"><span data-greeting>Hello</span>, NGO team</h1>
          <p class="page-head__sub">Requests waiting for review and donations coming your way.</p>
        </div>
        <div class="page-head__actions">
          <a href="../ngos/ngo-campaigns-create.php" class="btn btn--outline btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-megaphone"/></svg>New campaign</a>
          <a href="../ngos/ngo-needs-post.php" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-plus"/></svg>Post a need</a>
        </div>
      </section>

      <!-- Key figures -->
      <section class="kpis" aria-label="Key figures">
        <article class="kpi kpi--dark reveal">
          <div class="kpi__top"><span class="kpi__label">Pending requests</span><span class="kpi__icon"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-pin"/></svg></span></div>
          <p class="kpi__value" data-count><?php echo e($c) ?></p>
          <p class="kpi__note">From beneficiaries, waiting for an NGO</p>
        </article>
        <article class="kpi reveal">
          <div class="kpi__top"><span class="kpi__label">Donations received</span><span class="kpi__icon"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-gift"/></svg></span></div>
          <p class="kpi__value" data-count><?php echo e($c1) ?></p>
          <p class="kpi__note">All donations made to your NGO</p>
        </article>
        <article class="kpi reveal">
          <div class="kpi__top"><span class="kpi__label">Awaiting pickup</span><span class="kpi__icon"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-truck"/></svg></span></div>
          <p class="kpi__value" data-count><?php echo e($c2) ?></p>
          <p class="kpi__note">Accepted, not yet picked up</p>
        </article>
      </section>

      <section class="row row--2-1">
        <article class="card reveal">
          <header class="card__head">
            <div>
              <h2 class="card__title">Pending requests</h2>
              <p class="card__sub">The five newest requests from beneficiaries</p>
            </div>
            <a href="../ngos/ngo-beneficiaries-review.php" class="card__link">Review all <svg class="icon"><use href="../assets/img/icons.svg#i-arrow"/></svg></a>
          </header>

          <div class="list" id="pendingRequests">
        <?php
        $stmt=$pdo->prepare("select * from assistance_requests where status=? order by id desc limit 5");
        $stmt->bindValue(1,"Pending");
        $stmt->execute();
        if($stmt->rowCount() > 0)
        {
            while($result=$stmt->fetch(PDO::FETCH_ASSOC))
            {
                $description=$result['description'];
                $created_at=$result['created_at'];
                $ben_id=$result['beneficiary_id'];
                $name=null;
                $stmt1=$pdo->prepare("select * from beneficiary_master where id=?");
                $stmt1->bindValue(1,$ben_id);
                $stmt1->execute();
                if($stmt1->rowCount() > 0)
                {
                   $res=$stmt1->fetch(PDO::FETCH_ASSOC);
                   $name=$res['name'];
                }
                if(isset($name))
                  {
          ?>
            <div class="item">
              <span class="item__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg></span>
              <div class="item__body">
                <div class="item__top">
                  <span class="item__title"><?php echo e($name) ?></span>
                  <span class="status" data-status="Pending">Pending</span>
                </div>
                <p class="item__text"><?php echo e($description) ?></p>
                <div class="item__meta"><span><svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg><?php echo e($created_at) ?></span></div>
              </div>
            </div>
                <?php
                }
            }
        }
        ?>
          </div>
          <div class="empty" data-empty-for="#pendingRequests">
            <svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>
            <strong>All caught up</strong>
            <p>There are no pending requests from beneficiaries right now.</p>
          </div>
        </article>

        <aside class="urgent reveal">
          <svg aria-hidden="true" class="leaf leaf--u1"><use href="../assets/img/icons.svg#leaf"/></svg>
          <svg aria-hidden="true" class="leaf leaf--u2"><use href="../assets/img/icons.svg#leaf"/></svg>
          <span class="pill pill--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-clock"/></svg>Immediate food alerts</span>
          <h2>Respond within 7 minutes</h2>
          <p>When a donor posts surplus food near you, you get an email with an Accept link. The first NGO to accept gets the donation — after 7 minutes, the next 5 NGOs are alerted.</p>
          <a href="../ngos/ngo-donations-food.php" class="btn btn--light"><svg class="icon"><use href="../assets/img/icons.svg#i-truck"/></svg>Food donations &amp; pickups</a>
        </aside>
      </section>

      <section class="hub" aria-label="Shortcuts">
        <a href="../ngos/ngo-beneficiaries-review.php" class="hub-card reveal">
          <span class="hub-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg></span>
          <h3>Review requests</h3>
          <p>Check documents and approve or pass on beneficiary requests.</p>
          <span class="hub-card__link">Open <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg></span>
        </a>
        <a href="../ngos/ngo-donations-food.php" class="hub-card reveal">
          <span class="hub-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-food"/></svg></span>
          <h3>Food donations</h3>
          <p>Accept scheduled donations and mark pickups complete.</p>
          <span class="hub-card__link">Open <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg></span>
        </a>
        <a href="../ngos/ngo-volunteers.php" class="hub-card reveal">
          <span class="hub-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg></span>
          <h3>Volunteers</h3>
          <p>Approve people who applied to your campaigns.</p>
          <span class="hub-card__link">Open <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg></span>
        </a>
      </section>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
