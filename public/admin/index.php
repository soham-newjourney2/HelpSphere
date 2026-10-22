<?php
require_once __DIR__. '/../../inc/auth.php';
admin_require_login();
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Overview · Admin | HelpSphere</title>
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

    <span class="sidebar__tag">Admin console</span>

    <nav class="sidebar__nav" aria-label="Admin console">
      <a href="../admin/index.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../admin/users.php"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg>Users</a>
      <a href="../admin/ngo-verification.php"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg>NGO verification</a>
      <a href="../admin/add-ngo.php"><svg class="icon"><use href="../assets/img/icons.svg#i-user-plus"/></svg>Add NGO</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../admin/index.php">Admin console</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Overview</strong></nav>
      <div class="topbar__right">
        <a href="../../index.php" class="topbar__site"><svg class="icon"><use href="../assets/img/icons.svg#i-sprout"/></svg><span>HelpSphere home</span></a>
        <div class="profile">
          <span class="avatar avatar--brand"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg></span>
          <div class="profile__text"><strong>Administrator</strong><span>Admin console</span></div>
        </div>
      </div>
    </header>

    <main class="content" id="content">

      <section class="page-head">
        <svg aria-hidden="true" class="leaf leaf--p1"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p2"><use href="../assets/img/icons.svg#leaf"/></svg><svg aria-hidden="true" class="leaf leaf--p3"><use href="../assets/img/icons.svg#leaf"/></svg>
        <div>
          <p class="page-head__eyebrow" data-today>Today</p>
          <h1 class="page-head__title"><span data-greeting>Hello</span>, admin</h1>
          <p class="page-head__sub">You keep HelpSphere trustworthy: verify NGOs before they receive donations, and keep user accounts accurate.</p>
        </div>
        <div class="page-head__actions">
          <a href="../admin/users.php" class="btn btn--outline btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg>Manage users</a>
          <a href="../admin/ngo-verification.php" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg>Review NGOs</a>
        </div>
      </section>

      <section class="hub" aria-label="Admin tools">
        <a href="../admin/ngo-verification.php" class="hub-card hub-card--dark reveal">
          <span class="hub-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg></span>
          <h2>NGO verification</h2>
          <p>Open each NGO’s compliance documents and verify or reject them. A reason is required for every rejection.</p>
          <span class="hub-card__link">Review pending NGOs <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg></span>
        </a>
        <a href="../admin/users.php" class="hub-card reveal">
          <span class="hub-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg></span>
          <h2>Users</h2>
          <p>Search beneficiaries, donors and NGOs; view, edit or delete their accounts.</p>
          <span class="hub-card__link">Open user list <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg></span>
        </a>
        <a href="../admin/add-ngo.php" class="hub-card reveal">
          <span class="hub-card__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-user-plus"/></svg></span>
          <h2>Add an NGO</h2>
          <p>NGO accounts are created by admins only. New NGOs start as unverified until their documents are approved.</p>
          <span class="hub-card__link">Register an NGO <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg></span>
        </a>
      </section>

      <section class="row row--2-1">
        <article class="card reveal">
          <header class="card__head">
            <div>
              <h2 class="card__title">How NGO verification works</h2>
              <p class="card__sub">An NGO only receives immediate food alerts once it is verified</p>
            </div>
          </header>
          <ol class="timeline">
            <li>
              <span class="timeline__dot"></span>
              <div>
                <strong>You add the NGO</strong>
                <p>Create its account with its registration number, contact details and a password.</p>
              </div>
            </li>
            <li>
              <span class="timeline__dot"></span>
              <div>
                <strong>The NGO uploads compliance documents</strong>
                <p>From its Profile &amp; compliance page — PDF, image or Word files.</p>
              </div>
            </li>
            <li>
              <span class="timeline__dot"></span>
              <div>
                <strong>You review each document</strong>
                <p>Verify it, or reject it with a reason the NGO can act on.</p>
              </div>
            </li>
            <li>
              <span class="timeline__dot"></span>
              <div>
                <strong>The NGO becomes Verified</strong>
                <p>When every document is verified, the NGO is marked Verified and starts receiving food alerts.</p>
              </div>
            </li>
          </ol>
        </article>

        <aside class="urgent reveal">
          <svg aria-hidden="true" class="leaf leaf--u1"><use href="../assets/img/icons.svg#leaf"/></svg>
          <svg aria-hidden="true" class="leaf leaf--u2"><use href="../assets/img/icons.svg#leaf"/></svg>
          <span class="pill pill--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-info"/></svg>Good to know</span>
          <h2>Before you act</h2>
          <ol class="urgent__steps">
            <li>Every rejection needs a written reason.</li>
            <li>Moving an NGO back to pending removes its Verified status straight away.</li>
            <li>Users linked to other records, such as assistance requests, cannot be deleted.</li>
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
