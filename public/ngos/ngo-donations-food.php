<?php
//ngo-donations-food.php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Food donations · NGO | HelpSphere</title>
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
      <a href="../ngos/ngo-donations.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-gift"/></svg>Donations &amp; resources</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../ngos/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../ngos/ngo-donations.php">Donations &amp; resources</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Food donations</strong></nav>
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
          <h1 class="page-head__title">Food donations</h1>
          <p class="page-head__sub">Accept donations, then mark them picked up once you have collected the food.</p>
        </div>
      </section>

      <!-- Donations made for this NGO's own requests -->
      <article class="card reveal">
        <header class="card__head">
          <div>
            <h2 class="card__title">Donations for your requests</h2>
            <p class="card__sub">“Donated” means a donor has answered your need — accept it, then mark it picked up</p>
          </div>
        </header>
        <div class="table-wrap">
          <table class="table table--stack" id="requestDonations">
            <thead>
              <tr><th>Donor</th><th>Request type</th><th>Food type</th><th>Quantity</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
          <?php
          $stmt=$pdo->prepare("select * from food_donation_requests where ngo_id=?");
          $stmt->bindParam(1,$_SESSION['ngo_id']);
          $stmt->execute();
          if($stmt->rowCount() > 0)
          {
            while($rec=$stmt->fetch(PDO::FETCH_ASSOC))
            {
              $name="";
              $id=$rec['id'];
              $donor_id=$rec['donor_id'];
              $request_type=$rec['request_type'];
              $food_type=$rec['food_type'];
              $quantity=$rec['quantity'];
              $status=$rec['status'];
              $stmt1=$pdo->prepare("select * from donor_master where id=?");
              $stmt1->bindParam(1, $donor_id);
              $stmt1->execute();
              if($stmt1->rowCount() > 0)
              {
                $res=$stmt1->fetch(PDO::FETCH_ASSOC);
                $name=$res['name'];
              }
              ?>
              <tr>
                <td><div class="who"><span class="avatar"><svg class="icon"><use href="../assets/img/icons.svg#i-heart"/></svg></span><strong><?php echo e(isset($name)?$name:'') ?></strong></div></td>
                <td><?php echo e($request_type) ?></td>
                <td><?php echo e($food_type) ?></td>
                <td><?php echo e($quantity) ?></td>
                <td><span class="status" data-status="<?php echo e($status) ?>"><?php echo e($status) ?></span></td>
            <?php if($status=="Donated" && $request_type=="Scheduled"): ?>
                <td><div class="actions actions--yes"><?php echo action_button('donation-accept.php', ['type'=>'Scheduled','donation'=>'requested','id'=>$id], 'Accept') ?></div></td>
            <?php endif ?>
            <?php if($status=="Accepted" && $request_type=="Scheduled"): ?>
                <td><div class="actions"><?php echo action_button('donation-pickup.php', ['type'=>'Scheduled','donation'=>'requested','id'=>$id], 'Pick Up') ?></div></td>
            <?php endif ?>
            <?php if($status=="Picked Up" && $request_type=="Scheduled"): ?>
                <td><span class="muted">Done</span></td>
            <?php endif ?>
              </tr>
              <?php
            }
          }
          ?>
            </tbody>
          </table>
        </div>
        <div class="empty" data-empty-for="#requestDonations">
          <svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg>
          <strong>No requests posted yet</strong>
          <p>Post a food need and donors can answer it.</p>
          <a href="../ngos/ngo-needs-post.php" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-plus"/></svg>Post a need</a>
        </div>
      </article>

      <!-- Voluntary scheduled donations (open to every NGO) -->
      <article class="card reveal">
        <header class="card__head">
          <div>
            <h2 class="card__title">Voluntary scheduled donations</h2>
            <p class="card__sub">Open donations any NGO can accept — first to accept gets it. Your accepted and completed pickups are listed too.</p>
          </div>
        </header>
        <div class="table-wrap">
          <table class="table table--stack" id="voluntaryDonations">
            <thead>
              <tr><th>Donor</th><th>Donated at</th><th>Food type</th><th>Quantity</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
          <?php
          $stmt=$pdo->prepare("select * from food_donations where target_type=? and status = ? and is_requested_by_ngo = ?");
          $stmt->bindValue(1, "NGO");
          $stmt->bindValue(2, "Donated");
          $stmt->bindValue(3, "No");
          $stmt->execute();
          if($stmt->rowCount() > 0)
          {
            while($rec=$stmt->fetch(PDO::FETCH_ASSOC))
            {
              $name='';
              $id=$rec['id'];
              $donor_id=$rec['donor_id'];
              $donated_at=$rec['created_at'];
              $food_type=$rec['food_type'];
              $quantity=$rec['quantity'];
              $status=$rec['status'];
              $stmt1=$pdo->prepare("select * from donor_master where id=?");
              $stmt1->bindParam(1, $donor_id);
              $stmt1->execute();
              if($stmt1->rowCount() > 0)
              {
                $res=$stmt1->fetch(PDO::FETCH_ASSOC);
                $name=$res['name'];
              }
              ?>
              <tr>
                <td><div class="who"><span class="avatar"><svg class="icon"><use href="../assets/img/icons.svg#i-heart"/></svg></span><strong><?php echo e($name) ?></strong></div></td>
                <td class="date-cell"><?php echo e($donated_at) ?></td>
                <td><?php echo e($food_type) ?></td>
                <td><?php echo e($quantity) ?></td>
                <td><span class="status" data-status="<?php echo e($status) ?>"><?php echo e($status) ?></span></td>
                <td><div class="actions actions--yes"><?php echo action_button('donation-accept.php', ['type'=>'Scheduled','donation'=>'voluntary','id'=>$id], 'Accept') ?></div></td>
              </tr>
              <?php
            }
          }
          ?>
          <?php
          $stmt=$pdo->prepare("select * from food_donations where target_type=? and status = ? and target_id=?");
          $stmt->bindValue(1, "NGO");
          $stmt->bindValue(2, "Accepted");
          $stmt->bindParam(3, $_SESSION['ngo_id']);
          $stmt->execute();
          if($stmt->rowCount() > 0)
          {
            while($rec=$stmt->fetch(PDO::FETCH_ASSOC))
            {
              $name='';
              $id1=$rec['id'];
              $donor_id=$rec['donor_id'];
              $donated_at=$rec['created_at'];
              $food_type=$rec['food_type'];
              $quantity=$rec['quantity'];
              $status=$rec['status'];
              $stmt1=$pdo->prepare("select * from donor_master where id=?");
              $stmt1->bindParam(1, $donor_id);
              $stmt1->execute();
              if($stmt1->rowCount() > 0)
              {
                $res=$stmt1->fetch(PDO::FETCH_ASSOC);
                $name=$res['name'];
              }
              ?>
              <tr>
                <td><div class="who"><span class="avatar"><svg class="icon"><use href="../assets/img/icons.svg#i-heart"/></svg></span><strong><?php echo e($name) ?></strong></div></td>
                <td class="date-cell"><?php echo e($donated_at) ?></td>
                <td><?php echo e($food_type) ?></td>
                <td><?php echo e($quantity) ?></td>
                <td><span class="status" data-status="<?php echo e($status) ?>"><?php echo e($status) ?></span></td>
                <td><div class="actions"><?php echo action_button('donation-pickup.php', ['type'=>'Scheduled','donation'=>'voluntary','id'=>$id1], 'Pick Up') ?></div></td>
              </tr>
              <?php
            }
          }
          ?>
          <?php
          $stmt=$pdo->prepare("select * from food_donations where target_type=? and status = ? and target_id=? and donation_type=?");
          $stmt->bindValue(1, "NGO");
          $stmt->bindValue(2, "Picked Up");
          $stmt->bindParam(3, $_SESSION['ngo_id']);
          $stmt->bindValue(4, "Scheduled");
          $stmt->execute();
          if($stmt->rowCount() > 0)
          {
            while($rec=$stmt->fetch(PDO::FETCH_ASSOC))
            {
              $name='';
              $id1=$rec['id'];
              $donor_id=$rec['donor_id'];
              $donated_at=$rec['created_at'];
              $food_type=$rec['food_type'];
              $quantity=$rec['quantity'];
              $status=$rec['status'];
              $stmt1=$pdo->prepare("select * from donor_master where id=?");
              $stmt1->bindParam(1, $donor_id);
              $stmt1->execute();
              if($stmt1->rowCount() > 0)
              {
                $res=$stmt1->fetch(PDO::FETCH_ASSOC);
                $name=$res['name'];
              }
              ?>
              <tr>
                <td><div class="who"><span class="avatar"><svg class="icon"><use href="../assets/img/icons.svg#i-heart"/></svg></span><strong><?php echo e($name) ?></strong></div></td>
                <td class="date-cell"><?php echo e($donated_at) ?></td>
                <td><?php echo e($food_type) ?></td>
                <td><?php echo e($quantity) ?></td>
                <td><span class="status" data-status="<?php echo e($status) ?>"><?php echo e($status) ?></span></td>
                <td><span class="muted">Done</span></td>
              </tr>
              <?php
            }
          }
          ?>
            </tbody>
          </table>
        </div>
        <div class="empty" data-empty-for="#voluntaryDonations">
          <svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg>
          <strong>No scheduled donations right now</strong>
          <p>When a donor schedules a food donation, it appears here for every NGO.</p>
        </div>
      </article>

      <!-- Immediate donations accepted by this NGO -->
      <article class="card reveal">
        <header class="card__head">
          <div>
            <h2 class="card__title">Immediate donations you accepted</h2>
            <p class="card__sub">Accepted from an email alert — collect them within the donor’s pickup window</p>
          </div>
          <span class="pill pill--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-clock"/></svg>High urgency</span>
        </header>
        <div class="table-wrap">
          <table class="table table--stack" id="immediateDonations">
            <thead>
              <tr><th>Donor</th><th>Type</th><th>Food type</th><th>Quantity</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
          <?php
          $stmt=$pdo->prepare("select * from food_donations where target_id=? and donation_type=? and target_type=?");
          $stmt->bindParam(1,$_SESSION['ngo_id']);
          $stmt->bindValue(2, "Immediate");
          $stmt->bindValue(3, "NGO");
          $stmt->execute();
          if($stmt->rowCount() > 0)
          {
            while($rec=$stmt->fetch(PDO::FETCH_ASSOC))
            {
              $name="";
              $id=$rec['id'];
              $donor_id=$rec['donor_id'];
              $request_type=$rec['donation_type'];
              $food_type=$rec['food_type'];
              $quantity=$rec['quantity'];
              $status=$rec['status'];
              $stmt1=$pdo->prepare("select * from donor_master where id=?");
              $stmt1->bindParam(1, $donor_id);
              $stmt1->execute();
              if($stmt1->rowCount() > 0)
              {
                $res=$stmt1->fetch(PDO::FETCH_ASSOC);
                $name=$res['name'];
              }
              ?>
              <tr>
                <td><div class="who"><span class="avatar"><svg class="icon"><use href="../assets/img/icons.svg#i-heart"/></svg></span><strong><?php echo e(isset($name)?$name:'') ?></strong></div></td>
                <td><?php echo e($request_type) ?></td>
                <td><?php echo e($food_type) ?></td>
                <td><?php echo e($quantity) ?></td>
                <td><span class="status" data-status="<?php echo e($status) ?>"><?php echo e($status) ?></span></td>
            <?php if($status=="Donated" && $request_type=="Immediate"): ?>
                <td><div class="actions"><?php echo action_button('donation-pickup.php', ['type'=>'Immediate','id'=>$id], 'Pick Up') ?></div></td>
            <?php endif ?>
            <?php if($status=="Picked Up" && $request_type=="Immediate"): ?>
                <td><span class="muted">Done</span></td>
            <?php endif ?>
              </tr>
              <?php
            }
          }
          ?>
            </tbody>
          </table>
        </div>
        <div class="empty" data-empty-for="#immediateDonations">
          <svg class="icon"><use href="../assets/img/icons.svg#i-food"/></svg>
          <strong>No immediate donations yet</strong>
          <p>Accept an immediate food alert from your email and it will be listed here.</p>
        </div>
      </article>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
