<?php
//beneficiary-request-management.php
require_once __DIR__. '/../../inc/auth.php';
require_login();
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My requests · Beneficiary | HelpSphere</title>
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
      <a href="../beneficiaries/beneficiary-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../beneficiaries/beneficiary-request-management.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg>My requests</a>
      <a href="../beneficiaries/beneficiary-document-upload.php"><svg class="icon"><use href="../assets/img/icons.svg#i-upload"/></svg>Upload documents</a>
      <a href="../beneficiaries/beneficiary-documents.php"><svg class="icon"><use href="../assets/img/icons.svg#i-file"/></svg>My documents</a>
      <a href="../beneficiaries/beneficiary-messages.php"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Messages</a>
      <a href="../beneficiaries/beneficiary-profile.php"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg>Profile</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../beneficiaries/beneficiary-dashboard.php">Beneficiary space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>My requests</strong></nav>
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
          <p class="page-head__eyebrow">Requests</p>
          <h1 class="page-head__title">My assistance requests</h1>
          <p class="page-head__sub">Edit or cancel a request while it is pending, and follow its timeline once an NGO picks it up.</p>
        </div>
        <div class="page-head__actions">
          <a href="../beneficiaries/beneficiary-request-create.php" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-plus"/></svg>New request</a>
        </div>
      </section>

      <article class="card reveal">
        <header class="card__head">
          <div class="tabs" data-filter-target="#requestList" aria-label="Filter by status">
            <button type="button" class="tabs__btn is-active" data-filter="all" aria-pressed="true">All <span></span></button>
            <button type="button" class="tabs__btn" data-filter="Pending" aria-pressed="false">Pending <span></span></button>
            <button type="button" class="tabs__btn" data-filter="Approved" aria-pressed="false">Approved <span></span></button>
            <button type="button" class="tabs__btn" data-filter="Fulfilled" aria-pressed="false">Fulfilled <span></span></button>
          </div>
          <label class="search"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-search"/></svg><span class="sr-only">Search requests</span><input type="search" placeholder="Search requests…" data-search-target="#requestList" /></label>
        </header>

        <div class="list" id="requestList">
            <?php
            $stmt=$pdo->prepare("select * from assistance_requests where beneficiary_id=? ORDER BY created_at DESC");
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
                        $id=$record['id'];
                        ?>
          <div class="item" data-status="<?php echo e($status) ?>">
            <span class="item__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg></span>
            <div class="item__body">
              <div class="item__top">
                <span class="item__title"><?php echo e($category) ?> assistance</span>
                <span class="status" data-status="<?php echo e($status) ?>"><?php echo e($status) ?></span>
              </div>
              <p class="item__text"><?php echo htmlspecialchars($description) ?></p>
              <div class="item__meta"><span><svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg>Submitted <?php echo e($created_at) ?></span></div>
              <div class="item__actions">
                        <?php if($status=="Pending"): ?>
                <a href="../beneficiaries/beneficiary-request-edit.php?id=<?php echo e($id) ?>" class="btn btn--outline btn--xs"><svg class="icon"><use href="../assets/img/icons.svg#i-edit"/></svg>Edit</a>
                        <?php endif ?>
                        <?php if($status=="Pending"): ?>
                        <?php echo action_button('../beneficiaries/request-cancel.php', ['id'=>$id], 'Cancel', 'action-btn danger') ?>
                        <?php endif ?>
                <a href="../beneficiaries/beneficiary-request-timeline.php?id=<?php echo e($id) ?>" class="btn btn--outline btn--xs"><svg class="icon"><use href="../assets/img/icons.svg#i-history"/></svg>Timeline</a>
                        <?php if($status=="Fulfilled"): ?>
                <a href="../beneficiaries/beneficiary-request-fulfillment.php?id=<?php echo e($id) ?>&category=<?php echo e($category) ?>" class="btn btn--dark btn--xs"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Fulfillment details</a>
                        <?php endif ?>
              </div>
            </div>
          </div>
                        <?php
                    }
                }
            ?>
        </div>
        <div class="empty" data-empty-for="#requestList">
          <svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg>
          <strong>No requests here</strong>
          <p>Create a request and a verified NGO will review it.</p>
          <a href="../beneficiaries/beneficiary-request-create.php" class="btn btn--dark btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-plus"/></svg>New request</a>
        </div>
      </article>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
