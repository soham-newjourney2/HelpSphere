<?php 
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();

if(isset($_POST['save-btn']))
{
  $stmt=$pdo->prepare("update ngo_master set name=?, email=?, focus=?, description=?, address=?, phone=?  where id=?");
  $stmt->bindParam(1,$_POST['name']);
  $stmt->bindParam(2,$_POST['email']);
  $stmt->bindParam(3,$_POST['focus']);
  $stmt->bindParam(4,$_POST['description']);
  $stmt->bindParam(5,$_POST['address']);
  $stmt->bindParam(6,$_POST['phone']);
  $stmt->bindParam(7,$_SESSION['ngo_id']);
  $stmt->execute();
  $stmt1=$pdo->prepare("update users set name=?, email=? where role_id=? and role=?");
  $stmt1->bindParam(1,$_POST['name']);
  $stmt1->bindParam(2,$_POST['email']);
  $stmt1->bindParam(3,$_SESSION['ngo_id']);
  $stmt1->bindValue(4,"NGO");
  $stmt1->execute();
  if($stmt) 
    header("Location: ngo-profile-registration.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registration details · NGO | HelpSphere</title>
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
      <a href="../ngos/ngo-donations.php"><svg class="icon"><use href="../assets/img/icons.svg#i-gift"/></svg>Donations &amp; resources</a>
      <a href="../ngos/ngo-campaigns.php"><svg class="icon"><use href="../assets/img/icons.svg#i-megaphone"/></svg>Campaigns</a>
      <a href="../ngos/ngo-volunteers.php"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteers</a>
      <a href="../ngos/ngo-communication.php"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Communication</a>
      <a href="../ngos/ngo-profile.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg>Profile &amp; compliance</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../ngos/ngo-profile.php">Profile &amp; compliance</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Registration details</strong></nav>
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
          <h1 class="page-head__title">Registration details</h1>
          <p class="page-head__sub">Donors and beneficiaries see your name, focus and contact details. Your registration number can only be changed by an admin.</p>
        </div>
      </section>

          <?php
          $stmt=$pdo->prepare("select * from ngo_master where id=?");
          $stmt->bindParam(1, $_SESSION['ngo_id']);
          $stmt->execute();
          if($stmt->rowCount()>0)
          {
            $result=$stmt->fetch(PDO::FETCH_ASSOC);
            $name=$result['name'];
            $reg_no=$result['registration_no'];
            $address=$result['address'];
            $date=$result['registration_date'];
            $email=$result['email'];
            $focus=$result['focus'];
            $description=$result['description'];
            $phone=$result['phone'];
          }
          ?>

      <section class="row row--1-2">
        <article class="card card--dark reveal">
          <header class="card__head">
            <div>
              <h2 class="card__title"><?php echo $name ?></h2>
              <p class="card__sub"><?php echo $email ?></p>
            </div>
            <span class="avatar avatar--brand"><svg class="icon"><use href="../assets/img/icons.svg#i-building"/></svg></span>
          </header>
          <table class="table table--kv">
            <tr><th>Focus</th><td><?php echo $focus ?></td></tr>
            <tr><th>Description</th><td><?php echo $description ?></td></tr>
            <tr><th>Registration no.</th><td><?php echo $reg_no ?></td></tr>
            <tr><th>Address</th><td><?php echo $address ?></td></tr>
            <tr><th>Registered on</th><td><?php echo $date ?></td></tr>
            <tr><th>Phone</th><td><?php echo $phone ?></td></tr>
          </table>
        </article>

        <article class="card reveal">
          <header class="card__head">
            <div>
              <h2 class="card__title">Edit registration details</h2>
              <p class="card__sub">Changes apply immediately</p>
            </div>
          </header>
          <form class="form" action="" method="POST">
            <div class="form__grid">
              <div class="field">
                <label for="ngo-name">NGO name</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-building"/></svg><input type="text" id="ngo-name" value="<?php echo $name ?>" name="name"></div>
              </div>
              <div class="field">
                <label for="ngo-email">NGO email</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-mail"/></svg><input type="email" id="ngo-email" value="<?php echo $email ?>" name="email"></div>
              </div>
              <div class="field">
                <label for="ngo-focus">Focus area</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-heart"/></svg><input type="text" id="ngo-focus" value="<?php echo $focus ?>" name="focus"></div>
              </div>
              <div class="field">
                <label for="ngo-phone">Phone number</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-phone"/></svg><input type="tel" id="ngo-phone" value="<?php echo $phone ?>" name="phone"></div>
              </div>
              <div class="field field--full">
                <label for="ngo-description">Description</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-file"/></svg><input type="text" id="ngo-description" value="<?php echo $description ?>" name="description"></div>
              </div>
              <div class="field field--full">
                <label for="ngo-address">Registered address</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-pin"/></svg><input type="text" id="ngo-address" value="<?php echo $address ?>" name="address"></div>
              </div>
              <div class="field">
                <label for="ngo-reg">Registration number</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-lock"/></svg><input type="text" id="ngo-reg" value="<?php echo $reg_no ?>" readonly></div>
              </div>
            </div>
            <div class="card__foot">
              <button type="submit" name="save-btn" class="btn btn--dark"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Save changes</button>
            </div>
          </form>
        </article>
      </section>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
