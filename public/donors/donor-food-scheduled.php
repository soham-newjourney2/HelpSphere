<?php
require_once __DIR__ . '/../../inc/auth.php';
donor_require_login();

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

if (!function_exists('h')) {
    function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$id       = (int)($_GET['id'] ?? 0);
$ngo_id   = (int)($_GET['ngo_id'] ?? 0);
$donation = (string)($_GET['donation'] ?? '');
$by_ngo   = ($donation === 'requested_by_NGO');

$TYPES = ['Cooked', 'Packaged', 'Raw'];
$error = '';
$form  = ['type' => 'Cooked', 'quantity' => '', 'date' => '', 'time' => '', 'address' => ''];

if ($by_ngo) {
    $stmt = $pdo->prepare("select * from food_donation_requests where request_type=? and id=?");
    $stmt->execute(['Scheduled', $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);   // false if not found
    if ($record) $ngo_id = (int)$record['ngo_id'];   // the request decides the NGO, not the URL
}

if (isset($_POST['donate-btn'])) {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        die("Invalid session token. Please go back and try again.");
    }

    $form['type']     = trim((string)($_POST['type'] ?? ''));
    $form['quantity'] = trim((string)($_POST['quantity'] ?? ''));
    $form['date']     = trim((string)($_POST['date'] ?? ''));
    $form['time']     = trim((string)($_POST['time'] ?? ''));
    $form['address']  = trim((string)($_POST['address'] ?? ''));

    // ----- validation -----
    if ($by_ngo && (!$record || $record['status'] !== 'Pending')) {
        $error = 'This request has already been answered by another donor.';
    } elseif (!in_array($form['type'], $TYPES, true)) {
        $error = 'Please choose a valid food type.';
    } elseif ($form['date'] === '') {
        $error = 'Please choose a pickup date.';
    } elseif (!($d = DateTime::createFromFormat('Y-m-d', $form['date'])) || $d->format('Y-m-d') !== $form['date']) {
        $error = 'Enter a valid pickup date.';
    } elseif ($form['time'] === '') {
        $error = 'Please choose a pickup time.';
    } elseif (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $form['time'])) {
        $error = 'Enter a valid pickup time.';
    } elseif ($form['address'] === '') {
        $error = 'Please enter the pickup address.';
    }

    if ($error === '') {
        $donor_id = (int)($_SESSION['donor_id'] ?? 0);
        $run = function (PDOStatement $s, array $params) {
            if (!$s->execute($params)) throw new RuntimeException('query-failed');
        };

        try {
            $pdo->beginTransaction();
            // One timestamp for all rows: accepting/picking up later matches these rows by it
            $now = (string)$pdo->query("SELECT NOW()")->fetchColumn();

            $run($pdo->prepare("insert into donations (donor_id, target_id, target_role, status, donation_type, created_at) values (?,?,?,?,?,?)"),
                 [$donor_id, $ngo_id, 'NGO', 'Donated', 'Food', $now]);

            if ($by_ngo) {
                $s = $pdo->prepare("update food_donation_requests set donor_id=?, food_type=?, quantity=?, status=?, donated_at=? where id=? and status='Pending'");
                $run($s, [$donor_id, $form['type'], $form['quantity'], 'Donated', $now, $id]);
                if ($s->rowCount() === 0) throw new RuntimeException('already-answered');

                $run($pdo->prepare("insert into food_donations (donor_id, food_type, target_type, target_id, quantity, status, created_at, pickup_address, pickup_date, pickup_time, urgency, donation_type, is_requested_by_ngo) values (?,?,?,?,?,?,?,?,?,?,?,?,?)"),
                     [$donor_id, $form['type'], 'NGO', $ngo_id, $form['quantity'], 'Donated', $now,
                      $form['address'], $form['date'], $form['time'], 'N/A', 'Scheduled', 'Yes']);
            } else {
                $run($pdo->prepare("insert into food_donations (donor_id, food_type, target_type, quantity, status, created_at, pickup_address, pickup_date, pickup_time, urgency, donation_type, is_requested_by_ngo) values (?,?,?,?,?,?,?,?,?,?,?,?)"),
                     [$donor_id, $form['type'], 'NGO', $form['quantity'], 'Donated', $now,
                      $form['address'], $form['date'], $form['time'], 'N/A', 'Scheduled', 'No']);
            }

            $pdo->commit();
            header("Location: ../donors/donor-food-selection.php");
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('donor-food-scheduled: ' . $e->getMessage());
            $error = $e->getMessage() === 'already-answered'
                ? 'This request has already been answered by another donor.'
                : 'Could not save your donation. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Scheduled food donation · Donor | HelpSphere</title>
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
      <a href="../donors/donor-food-selection.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-food"/></svg>Food donation</a>
      <a href="../donors/donor-money-selection.php"><svg class="icon"><use href="../assets/img/icons.svg#i-coin"/></svg>Money donation</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/donor-dashboard.php">Donor space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../donors/donor-food-selection.php">Food donation</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Scheduled donation</strong></nav>
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
          <p class="page-head__eyebrow">Food donation</p>
          <h1 class="page-head__title">Schedule a food pickup</h1>
          <p class="page-head__sub">Choose when and where the food can be collected. The NGO coordinates the pickup closer to the time.</p>
        </div>
      </section>

      <form class="row row--2-1" action="" method="POST">
        <article class="card reveal">
        <input type="hidden" name="csrf" value="<?php echo h($_SESSION['csrf']) ?>" />
        <?php if ($error !== ''): ?>
          <p class="alert alert--error"><svg class="icon"><use href="../assets/img/icons.svg#i-alert"/></svg><span><?php echo h($error) ?></span></p>
        <?php endif ?>

          <header class="card__head">
            <div>
              <h2 class="card__title">Donation schedule</h2>
              <p class="card__sub">What you are giving and when it can be picked up</p>
            </div>
          </header>

          <div class="form">
            <div class="form__grid">
              <div class="field">
                <label for="type">Food type</label>
                <div class="field__control field__control--select">
                  <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-food"/></svg>
                  <select id="type" name="type">
                        <?php foreach ($TYPES as $t): ?>
                        <option value="<?php echo h($t) ?>" <?php if ($form['type'] === $t) echo 'selected' ?>><?php echo h($t) ?></option>
                        <?php endforeach ?>
                  </select>
                </div>
              </div>

              <div class="field">
                <label for="quantity">Estimated quantity (with unit)</label>
                <div class="field__control">
                  <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-box"/></svg>
                  <input type="text" id="quantity" placeholder="e.g. 10 kg, 30 meals" name="quantity" value="<?php echo h($form['quantity']) ?>">
                </div>
              </div>

              <div class="field">
                <label for="date">Pickup date</label>
                <div class="field__control">
                  <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-calendar"/></svg>
                  <input type="date" id="date" name="date" value="<?php echo h($form['date']) ?>" required>
                </div>
              </div>

              <div class="field">
                <label for="time">Pickup time</label>
                <div class="field__control">
                  <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-clock"/></svg>
                  <input type="time" id="time" name="time" value="<?php echo h($form['time']) ?>" required>
                </div>
              </div>

              <div class="field field--full">
                <label for="address">Pickup address</label>
                <div class="field__control field__control--area">
                  <textarea id="address" rows="3" placeholder="Exact pickup location" name="address"><?php echo h($form['address']) ?></textarea>
                </div>
              </div>

              <div class="field">
                <label for="urgency">Urgency</label>
                <div class="field__control">
                  <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-info"/></svg>
                  <input type="text" id="urgency" value="N/A" readonly>
                </div>
                <p class="field__hint">Scheduled donations have no urgency level.</p>
              </div>
            </div>
          </div>
        </article>

        <aside class="urgent reveal">
          <svg aria-hidden="true" class="leaf leaf--u1"><use href="../assets/img/icons.svg#leaf"/></svg>
          <svg aria-hidden="true" class="leaf leaf--u2"><use href="../assets/img/icons.svg#leaf"/></svg>
          <span class="pill pill--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg>Scheduled</span>
          <h2>Confirm your donation</h2>
          <p>Your donation will be listed for NGOs. The NGO that accepts it will coordinate the pickup at the time you chose.</p>
          <button class="btn btn--light" type="submit" name="donate-btn"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Submit scheduled donation</button>
        </aside>
      </form>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
