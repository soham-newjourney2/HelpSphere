<?php 
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Beneficiaries · NGO | HelpSphere</title>
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
      <a href="../ngos/ngo-beneficiaries.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg>Beneficiaries</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../ngos/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Beneficiaries</strong></nav>
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
          <p class="page-head__eyebrow">Beneficiaries</p>
          <h1 class="page-head__title">Support people in need</h1>
          <p class="page-head__sub">Verify documents, approve requests and record how each request was fulfilled.</p>
        </div>
      </section>

      <section class="hub">
        <a href="../ngos/ngo-beneficiaries-review.php" class="hub-card hub-card--dark reveal">
          <span class="hub-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg></span>
          <h2>Review requests</h2>
          <p>Check beneficiary documents and approve or pass on pending requests.</p>
          <span class="hub-card__link">Evaluate requests <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg></span>
        </a>
        <a href="../ngos/ngo-beneficiaries-assigned.php" class="hub-card reveal">
          <span class="hub-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg></span>
          <h2>Your beneficiaries</h2>
          <p>Pending requests plus everything your NGO has approved or fulfilled.</p>
          <span class="hub-card__link">View list <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg></span>
        </a>
        <a href="../ngos/ngo-beneficiaries-fulfillment.php" class="hub-card reveal">
          <span class="hub-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-truck"/></svg></span>
          <h2>Fulfil requests</h2>
          <p>Record how and when an approved request was delivered.</p>
          <span class="hub-card__link">Track delivery <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg></span>
        </a>
        <a href="../ngos/ngo-beneficiaries-reports.php" class="hub-card reveal">
          <span class="hub-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-flag"/></svg></span>
          <h2>Document reports</h2>
          <p>Issues NGOs have reported about beneficiary documents.</p>
          <span class="hub-card__link">View reports <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg></span>
        </a>
      </section>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
