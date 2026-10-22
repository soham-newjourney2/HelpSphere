<?php
require_once __DIR__ . '/../../inc/auth.php';
require_login();

$allowed_categories = ['Food', 'Resources'];
$error = '';

if (isset($_POST['submit-btn'])) {
    csrf_require_post();
    $cat  = trim($_POST['category'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (!in_array($cat, $allowed_categories, true)) {
        $error = 'Please select a valid category.';
    } elseif ($desc === '') {
        $error = 'Description is required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO assistance_requests (beneficiary_id, category, description) VALUES (?,?,?)");
        if ($stmt->execute([$_SESSION['beneficiary_id'], $cat, $desc])) {
            header("Location: ../beneficiaries/beneficiary-request-management.php");
            exit;
        }
        $error = 'Could not save your request. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>New request · Beneficiary | HelpSphere</title>
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
      <a href="../dashboards/beneficiary-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../beneficiaries/beneficiary-request-management.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg>My requests</a>
      <a href="../beneficiaries/beneficiary-document-upload.php"><svg class="icon"><use href="../assets/img/icons.svg#i-upload"/></svg>Upload documents</a>
      <a href="../beneficiaries/beneficiary-documents.php"><svg class="icon"><use href="../assets/img/icons.svg#i-file"/></svg>My documents</a>
      <a href="../beneficiaries/beneficiary-messages.php"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Messages</a>
      <a href="../beneficiaries/beneficiary-profile.php"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg>Profile</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/beneficiary-dashboard.php">Beneficiary space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../beneficiaries/beneficiary-request-management.php">My requests</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>New request</strong></nav>
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
          <h1 class="page-head__title">Create an assistance request</h1>
          <p class="page-head__sub">Tell NGOs what you need. You can edit or cancel it until an NGO approves it.</p>
        </div>
      </section>

      <section class="row row--2-1">
        <article class="card reveal">
            <?php if ($error !== ''): ?>
            <p class="alert alert--error"><svg class="icon"><use href="../assets/img/icons.svg#i-alert"/></svg><span><?php echo htmlspecialchars($error); ?></span></p>
            <?php endif; ?>
          <form class="form" action="" method="POST">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
            <div class="field">
              <label for="category">Category of assistance</label>
              <div class="field__control field__control--select">
                <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-list"/></svg>
                <select id="category" name="category" required>
                  <option value="" disabled selected>Select a category</option>
                  <option value="Food">Food</option>
                  <option value="Resources">Resources</option>
                </select>
              </div>
            </div>

            <div class="field">
              <label for="description">Brief description</label>
              <div class="field__control field__control--area">
                <textarea id="description" rows="7" placeholder="Explain your situation briefly — what you need and for how many people." name="description" required></textarea>
              </div>
              <p class="field__hint">Supporting documents can be uploaded after you submit the request.</p>
            </div>

            <div class="card__foot">
              <button class="btn btn--dark" type="submit" name="submit-btn"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Submit request</button>
              <a href="../beneficiaries/beneficiary-request-management.php" class="btn btn--outline">Cancel</a>
            </div>
          </form>
        </article>

        <aside class="urgent reveal">
          <svg aria-hidden="true" class="leaf leaf--u1"><use href="../assets/img/icons.svg#leaf"/></svg>
          <span class="pill pill--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-info"/></svg>Tips</span>
          <h2>A good request</h2>
          <ol class="urgent__steps">
            <li>Says what you need — food, clothes, books…</li>
            <li>Mentions how many people it is for.</li>
            <li>Explains briefly why you need help now.</li>
          </ol>
          <a href="../beneficiaries/beneficiary-document-upload.php" class="btn btn--light"><svg class="icon"><use href="../assets/img/icons.svg#i-upload"/></svg>Upload documents</a>
        </aside>
      </section>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
