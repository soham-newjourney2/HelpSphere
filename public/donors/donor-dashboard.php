<?php 
//donor-dashboard.php
require_once __DIR__. '/../../inc/auth.php';
donor_require_login();

global $count;
global $count1;
global $count2;
global $count3;
global $count4;

$stmt=$pdo->prepare("select * from donations where donor_id=?");
$stmt->bindParam(1,$_SESSION['donor_id']);
$stmt->execute();
if($stmt){
$count=$stmt->rowCount();
}

$stmt=$pdo->prepare("select * from donations where donor_id=? and target_role=?");
$stmt->bindParam(1,$_SESSION['donor_id']);
$stmt->bindValue(2,"NGO");
$stmt->execute();
if($stmt){
$count2=$stmt->rowCount();
}

$stmt=$pdo->prepare("select * from campaigns where status=?");
$stmt->bindValue(1,"Completed");
$stmt->execute();
if($stmt){
$count3=$stmt->rowCount();
}

$stmt=$pdo->prepare("select * from campaigns where status=?");
$stmt->bindValue(1,"Ongoing");
$stmt->execute();
if($stmt){
$count4=$stmt->rowCount();
}

?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Overview · Donor | HelpSphere</title>
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
      <a href="../donors/donor-dashboard.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../donors/donor-food-selection.php"><svg class="icon"><use href="../assets/img/icons.svg#i-food"/></svg>Food donation</a>
      <a href="../donors/donor-money-selection.php"><svg class="icon"><use href="../assets/img/icons.svg#i-coin"/></svg>Money donation</a>
      <a href="../donors/donor-resource-donation.php"><svg class="icon"><use href="../assets/img/icons.svg#i-box"/></svg>Resource donation</a>
      <a href="../donors/donor-volunteer-registration.php"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteering</a>
      <a href="../donors/donor-messages.php"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Messages</a>
      <a href="../donors/donor-profile-preferences.php"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg>Profile &amp; preferences</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../donors/donor-dashboard.php">Donor space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Overview</strong></nav>
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
          <p class="page-head__eyebrow" data-today>Today</p>
          <h1 class="page-head__title"><span data-greeting>Hello</span>, thank you for giving</h1>
          <p class="page-head__sub">Your donations at a glance, and the campaigns running on HelpSphere right now.</p>
        </div>
        <div class="page-head__actions">
          <a href="../donors/donor-volunteer-registration.php" class="btn btn--outline btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteer</a>
          <a href="../donors/donor-food-selection.php" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-food"/></svg>Donate food</a>
        </div>
      </section>

      <!-- Donation summary -->
      <section class="kpis" aria-label="Donation summary">
        <article class="kpi kpi--dark reveal">
          <div class="kpi__top"><span class="kpi__label">Total donations</span><span class="kpi__icon"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-gift"/></svg></span></div>
          <p class="kpi__value" data-count><?php echo e($count) ?></p>
          <p class="kpi__note">Everything you have given</p>
        </article>
        <article class="kpi reveal">
          <div class="kpi__top"><span class="kpi__label">Donations to NGOs</span><span class="kpi__icon"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-building"/></svg></span></div>
          <p class="kpi__value" data-count><?php echo e($count2) ?></p>
          <p class="kpi__note">Given to verified NGOs</p>
        </article>
        <article class="kpi reveal">
          <div class="kpi__top"><span class="kpi__label">Campaigns running</span><span class="kpi__icon"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-megaphone"/></svg></span></div>
          <p class="kpi__value" data-count><?php echo e($count4) ?></p>
          <p class="kpi__note">Across HelpSphere — open to volunteers</p>
        </article>
        <article class="kpi reveal">
          <div class="kpi__top"><span class="kpi__label">Campaigns completed</span><span class="kpi__icon"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-check"/></svg></span></div>
          <p class="kpi__value" data-count><?php echo e($count3) ?></p>
          <p class="kpi__note">Across HelpSphere</p>
        </article>
      </section>

      <section class="row row--2-1">
        <article class="card reveal">
          <header class="card__head">
            <div>
              <h2 class="card__title">Your latest donations</h2>
              <p class="card__sub">Your five most recent donations and where they went</p>
            </div>
            <a href="../donors/donor-food-selection.php" class="card__link">Food history <svg class="icon"><use href="../assets/img/icons.svg#i-arrow"/></svg></a>
          </header>

          <div class="list" id="latestDonations">
      <?php
        $stmt=$pdo->prepare("select * from donations where donor_id=? order by id DESC LIMIT 5");
        $stmt->bindParam(1,$_SESSION['donor_id']);
        $stmt->execute();
        if($stmt->rowCount()>0)
        {
             while($record=$stmt->fetch(PDO::FETCH_ASSOC))
            {
               $type=$record['donation_type'];
               $status=$record['status'];
               $target_id=$record['target_id'];
               $target_role=$record['target_role'];
               $created_at=$record['created_at'];
               $target='';
               $stmt1=$pdo->prepare("select * from users where role_id=? and role=?");
               $stmt1->bindParam(1,$target_id);
               $stmt1->bindParam(2, $target_role);
               $stmt1->execute();
               if($stmt1->rowCount()>0)
               {
                  $res=$stmt1->fetch(PDO::FETCH_ASSOC);
                  $target=$res['name'];
               }
              ?>
            <div class="item">
              <span class="item__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-gift"/></svg></span>
              <div class="item__body">
                <div class="item__top">
                  <span class="item__title"><?php echo e($type) ?> donation</span>
                  <span class="status" data-status="<?php echo e($status) ?>"><?php echo e($status) ?></span>
                </div>
                <div class="item__meta">
                  <span><svg class="icon"><use href="../assets/img/icons.svg#i-building"/></svg><?php echo e(isset($target)?$target:'') ?> (<?php echo e(isset($target_role)?$target_role:'') ?>)</span>
                  <span><svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg><?php echo e($created_at) ?></span>
                </div>
              </div>
            </div>
              <?php
            }
        }
      ?>
          </div>
          <div class="empty" data-empty-for="#latestDonations">
            <svg class="icon"><use href="../assets/img/icons.svg#i-gift"/></svg>
            <strong>No donations yet</strong>
            <p>Your first donation will show up here, with its status at every step.</p>
            <a href="../donors/donor-food-selection.php" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-food"/></svg>Donate food</a>
          </div>
        </article>

        <aside class="urgent reveal">
          <svg aria-hidden="true" class="leaf leaf--u1"><use href="../assets/img/icons.svg#leaf"/></svg>
          <svg aria-hidden="true" class="leaf leaf--u2"><use href="../assets/img/icons.svg#leaf"/></svg>
          <span class="pill pill--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-clock"/></svg>7-min response</span>
          <h2>Surplus food right now?</h2>
          <p>Post an immediate donation and the 5 nearest verified NGOs are alerted at once. If nobody accepts in 7 minutes, the next 5 are notified automatically.</p>
          <a href="../donors/donor-food-immediate.php?donation=voluntary" class="btn btn--light"><svg class="icon"><use href="../assets/img/icons.svg#i-food"/></svg>Donate food now</a>
        </aside>
      </section>

      <section class="choice" aria-label="Other ways to give">
        <a href="../donors/donor-resource-donation.php" class="choice-card reveal">
          <span class="choice-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-box"/></svg></span>
          <h3>Resources</h3>
          <p>Clothes, books, stationery and medical supplies — schedule a pickup.</p>
        </a>
        <a href="../donors/donor-money-ngo-list.php" class="choice-card reveal">
          <span class="choice-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-coin"/></svg></span>
          <h3>Money</h3>
          <p>Find a verified NGO and contact them directly. No platform cut.</p>
        </a>
        <a href="../donors/donor-volunteer-registration.php" class="choice-card reveal">
          <span class="choice-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg></span>
          <h3>Volunteer</h3>
          <p>Join an ongoing NGO campaign and lend your skills.</p>
        </a>
      </section>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
