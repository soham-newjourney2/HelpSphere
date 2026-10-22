<?php
require_once __DIR__. '/../../inc/auth.php';
require_login();

$allowed_categories=['Food','Resources'];
$beneficiary_id=(int)($_SESSION['beneficiary_id'] ?? 0);
$request_id=(int)($_GET['id'] ?? 0);
$error='';

if($request_id <= 0)
{
    echo "Invalid Request ID";
    exit;
}

// Load the request, only if it belongs to the logged-in beneficiary
$stmt=$pdo->prepare("select * from assistance_requests where id=? and beneficiary_id=?");
$stmt->execute([$request_id, $beneficiary_id]);
$record=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$record)
{
    echo "Request not found";
    exit;
}
$cat=$record['category'];
$description=$record['description'];
$status=$record['status'];
$created_at=$record['created_at'];

if(isset($_POST['edit-btn']))
{
    csrf_require_post();
    $new_cat=trim($_POST['category'] ?? '');
    $new_desc=trim($_POST['description'] ?? '');

    if(!in_array($new_cat, $allowed_categories, true))
        $error='Please select a valid category.';
    elseif($new_desc==='')
        $error='Description is required.';
    elseif($status!=='Pending')
        $error='Only pending requests can be edited.';
    else
    {
        $stmt1=$pdo->prepare("update assistance_requests set category=?, description=? where id=? and beneficiary_id=? and status='Pending'");
        $stmt1->execute([$new_cat, $new_desc, $request_id, $beneficiary_id]);
        header("Location: ../beneficiaries/beneficiary-request-management.php");
        exit;
    }
    $description=$new_desc;   // validation failed: keep what the user typed
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit request · Beneficiary | HelpSphere</title>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../beneficiaries/beneficiary-dashboard.php">Beneficiary space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../beneficiaries/beneficiary-request-management.php">My requests</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Edit request</strong></nav>
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
          <h1 class="page-head__title">Edit your request</h1>
          <p class="page-head__sub">Editing is possible only while the request is still pending.</p>
        </div>
      </section>

      <section class="row row--2-1">
        <article class="card reveal">
          <header class="card__head">
            <div>
              <h2 class="card__title"><?php echo e($cat) ?> assistance</h2>
              <p class="card__sub">Submitted on <?php echo e($created_at) ?></p>
            </div>
            <span class="status" data-status="<?php echo e(isset($status)?$status:'') ?>"><?php echo e(isset($status)?$status:'') ?></span>
          </header>

            <?php if($error!==''): ?>
                <p class="alert alert--error"><svg class="icon"><use href="../assets/img/icons.svg#i-alert"/></svg><span><?php echo htmlspecialchars($error); ?></span></p>
            <?php endif; ?>

          <form class="form" action="" method="POST">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
            <div class="field">
              <label for="category">Category of assistance</label>
              <div class="field__control field__control--select">
                <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-list"/></svg>
                <select id="category" name="category">
                            <option <?php echo ($cat == "Food" ? "selected" : ""); ?>>Food</option>
                            <option <?php echo ($cat == "Resources" ? "selected" : ""); ?>>Resources</option>
                </select>
              </div>
            </div>

            <div class="field">
              <label for="description">Description</label>
              <div class="field__control field__control--area">
                <textarea id="description" rows="7" name="description" required><?php echo htmlspecialchars($description) ?></textarea>
              </div>
            </div>

            <div class="card__foot">
              <button class="btn btn--dark" type="submit" name="edit-btn"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Save changes</button>
              <a href="../beneficiaries/beneficiary-request-management.php" class="btn btn--outline">Discard</a>
            </div>
          </form>
        </article>

        <aside class="card reveal">
          <header class="card__head">
            <div>
              <h2 class="card__title">Need to cancel?</h2>
              <p class="card__sub">Cancelling stops the review and cannot be undone.</p>
            </div>
          </header>
          <p class="muted">Use the <strong>Cancel</strong> button next to this request on the <em>My requests</em> page. It is available while the request is pending.</p>
          <div class="card__foot">
            <a href="../beneficiaries/beneficiary-request-management.php" class="btn btn--danger btn--sm"><svg class="icon"><use href="../assets/img/icons.svg#i-x"/></svg>Go to My requests</a>
          </div>
        </aside>
      </section>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
