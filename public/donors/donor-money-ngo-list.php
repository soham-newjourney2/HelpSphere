<?php
require_once __DIR__. '/../../inc/auth.php';
donor_require_login();
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Verified NGOs · Donor | HelpSphere</title>
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
      <a href="../dashboards/donor-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../donors/donor-food-selection.php"><svg class="icon"><use href="../assets/img/icons.svg#i-food"/></svg>Food donation</a>
      <a href="../donors/donor-money-selection.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-coin"/></svg>Money donation</a>
      <a href="../donors/donor-resource-donation.php"><svg class="icon"><use href="../assets/img/icons.svg#i-box"/></svg>Resource donation</a>
      <a href="../donors/donor-volunteer-registration.php"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteering</a>
      <a href="../donors/donor-messages.php"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Messages</a>
      <a href="../donors/donor-profile-preferences.php"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg>Profile &amp; preferences</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/donor-dashboard.php">Donor space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../donors/donor-money-selection.php">Money donation</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Verified NGOs</strong></nav>
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
          <p class="page-head__eyebrow">Money donation</p>
          <h1 class="page-head__title">Verified NGOs</h1>
          <p class="page-head__sub">Browse verified NGOs and contact them directly to donate. All listed organisations have completed platform verification.</p>
        </div>
        <div class="page-head__actions">
          <label class="search"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-search"/></svg><span class="sr-only">Search NGOs</span><input type="search" placeholder="Search by name or focus…" data-search-target="#ngoGrid" /></label>
        </div>
      </section>

      <div class="cards-grid" id="ngoGrid">
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
                    ?>
                <?php if($status=="Verified"): ?>
        <article class="mini-card">
          <div class="mini-card__top">
            <span class="item__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-building"/></svg></span>
            <span class="status" data-status="Verified">Verified</span>
          </div>
          <h3><?php echo $name ?></h3>
          <div class="mini-card__meta">
            <span><svg class="icon"><use href="../assets/img/icons.svg#i-heart"/></svg>Focus: <?php echo $focus ?></span>
          </div>
          <div class="mini-card__foot">
            <a class="btn btn--dark btn--xs" href="tel:<?php echo $phone ?>"><svg class="icon"><use href="../assets/img/icons.svg#i-phone"/></svg><?php echo $phone ?></a>
          </div>
        </article>
                <?php endif ?>
                    <?php
                }
            }
            ?>
      </div>
      <div class="empty card" data-empty-for="#ngoGrid">
        <svg class="icon"><use href="../assets/img/icons.svg#i-building"/></svg>
        <strong>No NGOs to show</strong>
        <p>NGOs appear here once an admin has verified their documents.</p>
      </div>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
