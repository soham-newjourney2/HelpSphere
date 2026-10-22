<?php 
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();

if(isset($_POST['create-btn']))
{
  $stmt=$pdo->prepare("insert into campaigns (ngo_id, title, description, st_date, end_date, status) values (?,?,?,?,?,?)");
  $stmt->bindParam(1, $_SESSION['ngo_id']);
  $stmt->bindParam(2, $_POST['title']);
  $stmt->bindParam(3, $_POST['description']);
  $stmt->bindParam(4, $_POST['st_date']);
  $stmt->bindParam(5, $_POST['end_date']);
  $stmt->bindValue(6, "Ongoing");
  $stmt->execute();
  if($stmt)
    header("Location: ngo-campaigns.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>New campaign · NGO | HelpSphere</title>
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
      <a href="../ngos/ngo-campaigns.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-megaphone"/></svg>Campaigns</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../ngos/ngo-campaigns.php">Campaigns</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>New campaign</strong></nav>
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
          <p class="page-head__eyebrow">Campaigns</p>
          <h1 class="page-head__title">Create a campaign</h1>
          <p class="page-head__sub">It goes live straight away, and donors can apply to volunteer for it.</p>
        </div>
      </section>

      <section class="row row--2-1">
        <article class="card reveal">
          <form class="form" action="" method="POST">
            <div class="field">
              <label for="title">Campaign title</label>
              <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-megaphone"/></svg><input type="text" id="title" placeholder="e.g. Winter Relief Drive" name="title" required></div>
            </div>
            <div class="form__grid">
              <div class="field">
                <label for="st_date">Start date</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-calendar"/></svg><input type="date" id="st_date" name="st_date" required></div>
              </div>
              <div class="field">
                <label for="end_date">End date</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-calendar"/></svg><input type="date" id="end_date" name="end_date" required></div>
              </div>
            </div>
            <div class="field">
              <label for="description">Description</label>
              <div class="field__control field__control--area"><textarea id="description" rows="6" placeholder="What is the campaign about, and what kind of help do you need?" name="description"></textarea></div>
            </div>
            <div class="card__foot">
              <button class="btn btn--dark" type="submit" name="create-btn"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Create campaign</button>
              <a href="../ngos/ngo-campaigns.php" class="btn btn--outline">Cancel</a>
            </div>
          </form>
        </article>

        <aside class="urgent reveal">
          <svg aria-hidden="true" class="leaf leaf--u1"><use href="../assets/img/icons.svg#leaf"/></svg>
          <svg aria-hidden="true" class="leaf leaf--u2"><use href="../assets/img/icons.svg#leaf"/></svg>
          <span class="pill pill--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteers</span>
          <h2>Bring people with you</h2>
          <ol class="urgent__steps">
            <li>Your campaign is listed for every donor as <strong>Ongoing</strong>.</li>
            <li>Donors apply with their skills.</li>
            <li>You approve or reject them under <strong>Volunteers</strong>.</li>
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
