<?php
//donor-food-immediate.php
require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/escalation.php';   // shared: APP_URL, BATCH_SIZE, ESCALATION_MINUTES, engine functions

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

donor_require_login();
$donor_id = (int) $_SESSION['donor_id'];

$errors  = [];
$success = isset($_GET['sent']);   // set after redirect, so a refresh can't resubmit

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_donation'])) {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        die("Invalid session token. Please go back and try again.");
    }

    $food_type     = (string)($_POST['food_type'] ?? 'Cooked');
    $quantity      = trim((string)($_POST['quantity'] ?? ''));
    $pickup_addr   = trim((string)($_POST['pickup_address'] ?? ''));
    $pickup_within = (int)($_POST['pickup_within'] ?? 1);

    if (!in_array($food_type, ['Cooked', 'Packaged', 'Raw'], true)) $errors[] = "Invalid food type.";
    if ($quantity === '')    $errors[] = "Quantity is required.";
    if ($pickup_addr === '') $errors[] = "Pickup address is required.";
    if ($pickup_within < 1 || $pickup_within > 8) $errors[] = "Pickup within must be between 1 and 8 hours.";

    if (empty($errors)) {
        $round = null;
        try {
            $pdo->beginTransaction();

            // 1. Batch record
            $sb = $pdo->prepare(
                "INSERT INTO food_donation_batches
                   (donor_id, food_type, quantity, pickup_address, pickup_within, urgency, status)
                 VALUES (?,?,?,?,?,'High','Pending')"
            );
            $sb->execute([$donor_id, $food_type, $quantity, $pickup_addr, $pickup_within]);
            $batch_id = (int) $pdo->lastInsertId();

            // 2. Rank verified NGOs by proximity to the pickup address (same rule the later rounds use)
            $ngos = $pdo->query("SELECT id, address FROM ngo_master WHERE status = 'Verified'")
                        ->fetchAll(PDO::FETCH_ASSOC);
            if (!$ngos) throw new RuntimeException('no-ngos');
            usort($ngos, fn($x, $y) =>
                locationScore($pickup_addr, (string)$y['address']) -
                locationScore($pickup_addr, (string)$x['address'])
            );
            $top5 = array_slice(array_column($ngos, 'id'), 0, BATCH_SIZE);

            // 3. Round 1 notifications (expiry from the MySQL clock, like every later round)
            $round = createRoundNotifications($pdo, $batch_id, $top5, 1);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $round = null;
            if ($e->getMessage() === 'no-ngos') {
                $errors[] = "No verified NGOs are available right now.";
            } else {
                error_log('donor-food-immediate: ' . $e->getMessage());
                $errors[] = "Could not submit your donation. Please try again.";
            }
        }

        // Emails go out after the commit
        if ($round !== null) {
            sendRoundEmails($pdo, $round, [
                'food_type'      => $food_type,
                'quantity'       => $quantity,
                'pickup_address' => $pickup_addr,
                'pickup_within'  => $pickup_within,
            ], 1);
            header('Location: donor-food-immediate.php?sent=1');
            exit;
        }
    }
}
// A Pending donation is NOT inserted into food_donations / donations.
// Only when an NGO accepts (ngo-food-accept.php) are the rows created with status=Donated.
?> 

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Immediate food donation · Donor | HelpSphere</title>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../dashboards/donor-dashboard.php">Donor space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><a href="../donors/donor-food-selection.php">Food donation</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Immediate donation</strong></nav>
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
          <h1 class="page-head__title">Immediate food donation</h1>
          <p class="page-head__sub">For food that is ready now. Urgency is always <strong>High</strong> for immediate donations.</p>
        </div>
      </section>

            <?php if ($success): ?>
      <section class="result">
        <span class="result__icon"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg></span>
        <h2>Donation submitted!</h2>
        <p>The nearest <?php echo BATCH_SIZE; ?> verified NGOs have been notified by email. They have <?php echo ESCALATION_MINUTES; ?> minutes to accept. If none respond, the next <?php echo BATCH_SIZE; ?> NGOs are contacted automatically.</p>
        <a href="donor-food-selection.php" class="btn btn--dark"><svg class="icon"><use href="../assets/img/icons.svg#i-arrow-left"/></svg>Back to food donations</a>
      </section>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
      <div class="alert alert--error">
        <svg class="icon"><use href="../assets/img/icons.svg#i-alert"/></svg>
        <div>
          <strong>Please fix the following:</strong>
          <ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
        </div>
      </div>
                <?php endif; ?>

      <section class="row row--2-1">
        <article class="card reveal">
          <header class="card__head">
            <div>
              <h2 class="card__title">What are you donating?</h2>
              <p class="card__sub">NGOs see these details in their alert email</p>
            </div>
            <span class="pill pill--bad"><svg class="icon"><use href="../assets/img/icons.svg#i-clock"/></svg>Urgency: High</span>
          </header>

          <form class="form" method="POST" action="">
            <div class="form__grid">
              <div class="field">
                <label for="food_type">Food type</label>
                <div class="field__control field__control--select">
                  <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-food"/></svg>
                  <select id="food_type" name="food_type" required>
                                <?php foreach (['Cooked','Packaged','Raw'] as $ft): ?>
                                <option value="<?php echo $ft; ?>"
                                    <?php echo (($_POST['food_type'] ?? '') === $ft) ? 'selected' : ''; ?>>
                                    <?php echo $ft; ?>
                                </option>
                                <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="field">
                <label for="quantity">Approximate quantity (with unit)</label>
                <div class="field__control">
                  <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-box"/></svg>
                  <input type="text" id="quantity" name="quantity"
                         placeholder="e.g. 5 kg, 20 packs, 3 trays"
                         value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>"
                         required>
                </div>
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8'); ?>">
              </div>

              <div class="field field--full">
                <label for="pickup_address">Pickup address</label>
                <div class="field__control field__control--area">
                  <textarea id="pickup_address" name="pickup_address" rows="3"
                            placeholder="Full address where the food can be picked up…"
                            required><?php echo htmlspecialchars($_POST['pickup_address'] ?? ''); ?></textarea>
                </div>
                <p class="field__hint">NGOs are ranked by how closely their address matches this one.</p>
              </div>

              <div class="field">
                <label for="pickup_within">Pick up within (hours)</label>
                <div class="field__control">
                  <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-clock"/></svg>
                  <input type="number" id="pickup_within" name="pickup_within"
                         min="1" max="8"
                         value="<?php echo (int)($_POST['pickup_within'] ?? 2); ?>"
                         required>
                </div>
                <p class="field__hint">Between 1 and 8 hours.</p>
              </div>

              <div class="field">
                <label for="urgency">Urgency</label>
                <div class="field__control">
                  <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-alert"/></svg>
                  <input type="text" id="urgency" value="High" readonly>
                </div>
                <p class="field__hint">Fixed for all immediate donations.</p>
              </div>
            </div>

            <div class="card__foot">
              <button type="submit" name="submit_donation" class="btn btn--dark"><svg class="icon"><use href="../assets/img/icons.svg#i-send"/></svg>Submit &amp; notify NGOs</button>
              <a href="donor-food-selection.php" class="btn btn--outline">Cancel</a>
            </div>
          </form>
        </article>

        <aside class="urgent reveal">
          <svg aria-hidden="true" class="leaf leaf--u1"><use href="../assets/img/icons.svg#leaf"/></svg>
          <svg aria-hidden="true" class="leaf leaf--u2"><use href="../assets/img/icons.svg#leaf"/></svg>
          <span class="pill pill--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-clock"/></svg>How it works</span>
          <h2>Help on its way, fast</h2>
          <ol class="urgent__steps">
            <li>The <strong><?php echo BATCH_SIZE; ?> nearest verified NGOs</strong> get an email with an Accept link.</li>
            <li>If none accept within <strong><?php echo ESCALATION_MINUTES; ?> minutes</strong>, their window closes.</li>
            <li>The <strong>next <?php echo BATCH_SIZE; ?> NGOs</strong> are contacted automatically — until someone accepts.</li>
          </ol>
        </aside>
      </section>
            <?php endif; ?>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
