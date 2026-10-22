<?php
//donor-food-selection.php
require_once __DIR__. '/../../inc/auth.php';
donor_require_login();
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Food donation · Donor | HelpSphere</title>
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
      <a href="../donors/donor-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../donors/donor-food-selection.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-food"/></svg>Food donation</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../donors/donor-dashboard.php">Donor space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Food donation</strong></nav>
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
          <p class="page-head__eyebrow">Food donation</p>
          <h1 class="page-head__title">How would you like to give food?</h1>
          <p class="page-head__sub">Share surplus food right away, schedule a pickup, or answer a request an NGO has posted.</p>
        </div>
      </section>

      <section class="choice" aria-label="Donation type">
        <a href="../donors/donor-food-immediate.php?donation=voluntary" class="choice-card choice-card--dark reveal">
          <span class="choice-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-clock"/></svg></span>
          <span class="pill pill--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-clock"/></svg>7-min response</span>
          <h2>Immediate donation</h2>
          <p>Food ready now? The 5 nearest verified NGOs are alerted at once.</p>
        </a>
        <a href="../donors/donor-food-scheduled.php?donation=voluntary" class="choice-card reveal">
          <span class="choice-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg></span>
          <h2>Scheduled donation</h2>
          <p>Pick a date and time for pickup — any NGO can accept it.</p>
        </a>
        <button type="button" class="choice-card reveal" data-show-panel="requests" data-panel-group="food" data-toggle aria-expanded="false">
          <span class="choice-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg></span>
          <h2>NGO food requests</h2>
          <p>See what NGOs have asked for and donate to a specific need.</p>
        </button>
      </section>

      <div class="tabs" aria-label="More">
        <button type="button" class="tabs__btn" data-show-panel="ngos" data-panel-group="food" data-toggle aria-expanded="false"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-building"/></svg>Verified NGOs</button>
        <button type="button" class="tabs__btn" data-show-panel="history" data-panel-group="food" data-toggle aria-expanded="false"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-history"/></svg>My donation history</button>
      </div>

      <!-- ===== History ===== -->
      <section class="row row--2 panel" data-panel="history" data-panel-group="food" hidden>
        <article class="card">
          <header class="card__head">
            <div>
              <h2 class="card__title">All my food donations</h2>
              <p class="card__sub">Immediate and scheduled</p>
            </div>
          </header>
          <div class="list" id="foodHistory">
                <?php
                $stmt=$pdo->prepare("select * from food_donations where donor_id=?");
                $stmt->bindParam(1,$_SESSION['donor_id']);
                $stmt->execute();
                if($stmt->rowCount() > 0)
                {
                    while($result=$stmt->fetch(PDO::FETCH_ASSOC))
                    {
                        $food_type=$result['food_type'];
                        $datetime=$result['created_at'];
                        $status=$result['status'];
                        ?>
            <div class="item">
              <span class="item__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-food"/></svg></span>
              <div class="item__body">
                <div class="item__top">
                  <span class="item__title"><?php echo e($food_type) ?> food</span>
                  <span class="status" data-status="<?php echo e($status) ?>"><?php echo e($status) ?></span>
                </div>
                <div class="item__meta"><span><svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg><?php echo e($datetime) ?></span></div>
              </div>
            </div>
                        <?php
                    }
                }
                ?>
          </div>
          <div class="empty" data-empty-for="#foodHistory"><svg class="icon"><use href="../assets/img/icons.svg#i-food"/></svg><strong>No food donations yet</strong></div>
        </article>

        <article class="card">
          <header class="card__head">
            <div>
              <h2 class="card__title">Donations for NGO requests</h2>
              <p class="card__sub">Requests you answered</p>
            </div>
          </header>
          <div class="list" id="requestHistory">
                <?php
                $stmt=$pdo->prepare("select * from food_donation_requests where donor_id=?");
                $stmt->bindParam(1,$_SESSION['donor_id']);
                $stmt->execute();
                if($stmt->rowCount() > 0)
                {
                    while($result=$stmt->fetch(PDO::FETCH_ASSOC))
                    {
                        $ngo_name='';
                        $food_type=$result['food_type'];
                        $status=$result['status'];
                        $request_type=$result['request_type'];
                        $ngo_id=$result['ngo_id'];
                        $datetime=$result['donated_at'];
                        $stmt1=$pdo->prepare("select * from ngo_master where id=?");
                        $stmt1->bindParam(1,$ngo_id);
                        $stmt1->execute();
                        if($stmt1->rowCount()>0)
                        {
                            $result=$stmt1->fetch(PDO::FETCH_ASSOC);
                            $ngo_name=isset($result['name'])?$result['name']:'';
                        }
                        ?>
            <div class="item">
              <span class="item__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg></span>
              <div class="item__body">
                <div class="item__top">
                  <span class="item__title"><?php echo e($food_type) ?> food · <?php echo e($request_type) ?></span>
                  <span class="status" data-status="<?php echo e($status) ?>"><?php echo e($status) ?></span>
                </div>
                <div class="item__meta">
                  <span><svg class="icon"><use href="../assets/img/icons.svg#i-building"/></svg><?php echo e($ngo_name) ?></span>
                  <span><svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg><?php echo e($datetime) ?></span>
                </div>
              </div>
            </div>
                        <?php
                    }
                }
                ?>
          </div>
          <div class="empty" data-empty-for="#requestHistory"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg><strong>You haven’t answered an NGO request yet</strong></div>
        </article>
      </section>

      <!-- ===== Verified NGOs ===== -->
      <section class="card panel" data-panel="ngos" data-panel-group="food" hidden>
        <header class="card__head">
          <div>
            <h2 class="card__title">Verified NGOs</h2>
            <p class="card__sub">All listed organisations have completed platform verification</p>
          </div>
        </header>
        <div class="cards-grid" id="ngoList">
            <?php
            $stmt=$pdo->prepare("select * from ngo_master");
            $stmt->execute();
            if($stmt->rowCount() > 0)
            {
                while($rec=$stmt->fetch(PDO::FETCH_ASSOC))
                {
                    $name=$rec['name'];
                    $focus=$rec['focus'];
                    $status=$rec['status'];
                    $ngo_id=$rec['id'];
                    $phone=$rec['phone'];
                    $address=$rec['address'];
                    ?>
                <?php if($status=="Verified"): ?>
          <article class="mini-card">
            <div class="mini-card__top">
              <h3><?php echo e($name) ?></h3>
              <span class="status" data-status="Verified">Verified</span>
            </div>
            <div class="mini-card__meta">
              <span><svg class="icon"><use href="../assets/img/icons.svg#i-heart"/></svg>Focus: <?php echo e($focus) ?></span>
              <span><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg><?php echo e($address) ?></span>
              <span><svg class="icon"><use href="../assets/img/icons.svg#i-phone"/></svg><?php echo e($phone) ?></span>
            </div>
          </article>
                <?php endif ?>
                    <?php
                }
            }
            ?>
        </div>
        <div class="empty" data-empty-for="#ngoList"><svg class="icon"><use href="../assets/img/icons.svg#i-building"/></svg><strong>No verified NGOs yet</strong><p>NGOs appear here once an admin has verified their documents.</p></div>
      </section>

      <!-- ===== NGO food requests ===== -->
      <section class="card panel" data-panel="requests" data-panel-group="food" hidden>
        <header class="card__head">
          <div>
            <h2 class="card__title">Food requests from NGOs</h2>
            <p class="card__sub">Pending requests can be answered with a scheduled donation</p>
          </div>
        </header>
        <div class="cards-grid" id="ngoRequests">
               <?php
               $stmt=$pdo->prepare("select * from food_donation_requests");
               $stmt->execute();
               if($stmt->rowCount()>0)
               {
                  while($rec=$stmt->fetch(PDO::FETCH_ASSOC))
                  {
                    $ngo_name='';
                    $id=isset($rec['id'])?$rec['id']:0;
                    $request_type=isset($rec['request_type'])?$rec['request_type']:'';
                    $ngo_id=isset($rec['ngo_id'])?$rec['ngo_id']:0;
                    $urgency=isset($rec['urgency'])?$rec['urgency']:'';
                    $description=isset($rec['description'])?$rec['description']:'';
                    $status=isset($rec['status'])?$rec['status']:'';
                    $stmt1=$pdo->prepare("select * from ngo_master where id=?");
                    $stmt1->bindParam(1,$ngo_id);
                    $stmt1->execute();
                    if($stmt1->rowCount()>0)
                    {
                        $result=$stmt1->fetch(PDO::FETCH_ASSOC);
                        $ngo_name=isset($result['name'])?$result['name']:'';
                    }
                    ?>
          <article class="mini-card">
            <div class="mini-card__top">
              <h3><?php echo e(isset($request_type)?$request_type:'') ?> food request</h3>
              <span class="status" data-status="<?php echo e($status) ?>"><?php echo e($status) ?></span>
            </div>
            <p><?php echo isset($description)?htmlspecialchars($description):'' ?></p>
            <div class="mini-card__meta">
              <span><svg class="icon"><use href="../assets/img/icons.svg#i-building"/></svg><?php echo e($ngo_name) ?></span>
              <span><svg class="icon"><use href="../assets/img/icons.svg#i-clock"/></svg>Urgency: <?php echo e($urgency) ?></span>
            </div>
                    <?php if($request_type=="Scheduled"): ?>
            <span class="pill pill--muted"><svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg>Planned</span>
                    <?php endif ?>
            <div class="mini-card__foot">
                        <?php if($status=="Pending"): ?>
                        <?php if($request_type=="Scheduled"): ?>
              <a href="../donors/donor-food-scheduled.php?id=<?php echo e($id) ?>&ngo_id=<?php echo e($ngo_id) ?>&donation=requested_by_NGO" class="btn btn--dark btn--xs"><svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg>Donate to this request</a>
                        <?php endif ?>
                        <?php endif ?>
                        <?php if($status=="Donated"): ?>
              <span class="muted">Donation made — awaiting the NGO’s acceptance.</span>
                        <?php endif ?>
                        <?php if($status=="Accepted"): ?>
              <span class="muted">Accepted — waiting for pickup.</span>
                        <?php endif ?>
                        <?php if($status=="Picked Up"): ?>
              <span class="muted">Picked up. Thank you!</span>
                        <?php endif ?>
            </div>
          </article>
                    <?php
                  }
               }
               ?>
        </div>
        <div class="empty" data-empty-for="#ngoRequests"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg><strong>No NGO food requests right now</strong><p>Check back later, or make a scheduled donation any NGO can accept.</p></div>
      </section>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
