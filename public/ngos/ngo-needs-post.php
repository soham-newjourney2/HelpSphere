<?php 
require_once __DIR__. '/../../inc/auth.php'; // Contains PDO $pdo connection
ngo_require_login();

$message = "";

if (isset($_POST['submit-need'])) {
    csrf_require_post();
    $ngo_id = $_SESSION['ngo_id'];
    $need_type = $_POST['need_type'] ?? '';

    try {
        if ($need_type === 'food' && trim($_POST['food_desc'] ?? '') === '') {
            $message = "Please describe what you need.";
        } elseif ($need_type === 'food') {
            $sql = "INSERT INTO food_donation_requests (ngo_id, description, request_type, urgency) 
                    VALUES (:ngo_id, :description, :request_type, :urgency)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ngo_id'       => $ngo_id,
                ':description'  => mb_substr(trim($_POST['food_desc']), 0, 200),
                ':request_type' => "Scheduled",
                ':urgency'      => "N/A"
            ]);
            $message = "Food request submitted successfully!";
        } 
    } catch (PDOException $e) {
        error_log('ngo-needs-post: ' . $e->getMessage());
        $message = "Could not save your request. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Post a need · NGO | HelpSphere</title>
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
      <a href="../ngos/ngo-needs.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg>Needs &amp; requests</a>
      <a href="../ngos/ngo-beneficiaries.php"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg>Beneficiaries</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../ngos/ngo-dashboard.php">NGO workspace</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../ngos/ngo-needs.php">Needs &amp; requests</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Post a need</strong></nav>
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
          <p class="page-head__eyebrow">Needs &amp; requests</p>
          <h1 class="page-head__title">Post a new need</h1>
          <p class="page-head__sub">Donors see your need and can schedule a donation for it.</p>
        </div>
      </section>

        <?php if($message): ?>
      <p class="alert alert--plain"><svg class="icon"><use href="../assets/img/icons.svg#i-info"/></svg><span><?php echo e($message); ?></span></p>
        <?php endif; ?>

      <section class="row row--2-1">
        <article class="card reveal">
          <form class="form" action="" method="POST">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
            <div class="field">
              <label for="needTypeSelector">Type of need</label>
              <div class="field__control field__control--select">
                <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-list"/></svg>
                <select name="need_type" id="needTypeSelector" data-reveal required>
                  <option value="" disabled selected>Select a category</option>
                  <option value="food">Food</option>
                </select>
              </div>
            </div>

            <div class="field" data-reveal-for="food" hidden>
              <label for="food_desc">Description</label>
              <div class="field__control field__control--area">
                <textarea id="food_desc" name="food_desc" rows="5" placeholder="e.g. 50 meals for the local shelter on Friday" data-required></textarea>
              </div>
              <p class="field__hint">Say what you need, how much, and by when.</p>
            </div>

            <div class="card__foot">
              <button class="btn btn--dark" type="submit" name="submit-need" id="submitBtn"><svg class="icon"><use href="../assets/img/icons.svg#i-send"/></svg>Submit need</button>
              <a href="../ngos/ngo-needs.php" class="btn btn--outline">Cancel</a>
            </div>
          </form>
        </article>

        <aside class="urgent reveal">
          <svg aria-hidden="true" class="leaf leaf--u1"><use href="../assets/img/icons.svg#leaf"/></svg>
          <span class="pill pill--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-info"/></svg>After you post</span>
          <h2>What happens next</h2>
          <ol class="urgent__steps">
            <li>Donors see your need under <strong>NGO food requests</strong>.</li>
            <li>A donor schedules a donation for it — its status becomes <strong>Donated</strong>.</li>
            <li>You accept it, then mark it <strong>Picked up</strong> once collected.</li>
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
