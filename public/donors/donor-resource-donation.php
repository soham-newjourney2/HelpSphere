<?php
require_once __DIR__. '/../../inc/auth.php';
donor_require_login();

$error='';
if(isset($_POST['confirm-btn']))
{
    csrf_require_post();
    $type=$_POST['type'] ?? '';
    $quantity=(int)($_POST['quantity'] ?? 0);
    $description=trim($_POST['description'] ?? '');
    $condition=$_POST['condition'] ?? '';
    $address=trim($_POST['address'] ?? '');
    $date=trim($_POST['date'] ?? '');
    $time=trim($_POST['time'] ?? '');
    $d=DateTime::createFromFormat('Y-m-d', $date);

    if(!in_array($type, ['Clothes','Books','Medical Supplies','Stationery','Other'], true))
        $error='Choose a resource type.';
    elseif($quantity<1)
        $error='Quantity must be at least 1.';
    elseif(!in_array($condition, ['New','Gently Used'], true))
        $error='Choose the condition.';
    elseif($address==='')
        $error='Pickup address is required.';
    elseif(!$d || $d->format('Y-m-d')!==$date)
        $error='Choose a valid pickup date.';
    elseif(!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $time))
        $error='Choose a valid pickup time.';
    else
    {
        try
        {
            $pdo->beginTransaction();
            $stmt=$pdo->prepare("insert into resource_donations (donor_id, resource_type, quantity, description, resource_condition, pickup_address, pickup_date, pickup_time, status) values (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$_SESSION['donor_id'], $type, $quantity, $description, $condition, $address, $date, $time, 'Pending']);
            $stmt1=$pdo->prepare("insert into donations (donor_id, target_role, status, donation_type) values (?,?,?,?)");
            $stmt1->execute([$_SESSION['donor_id'], 'NGO', 'Pending', 'Resource']);
            $pdo->commit();
            header("Location: donor-resource-donation.php");
            exit;
        }
        catch(PDOException $e)
        {
            if($pdo->inTransaction()) $pdo->rollBack();
            error_log('donor-resource-donation: '.$e->getMessage());
            $error='Could not save your donation. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Resource donation · Donor | HelpSphere</title>
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
      <a href="../donors/donor-dashboard.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../donors/donor-food-selection.php"><svg class="icon"><use href="../assets/img/icons.svg#i-food"/></svg>Food donation</a>
      <a href="../donors/donor-money-selection.php"><svg class="icon"><use href="../assets/img/icons.svg#i-coin"/></svg>Money donation</a>
      <a href="../donors/donor-resource-donation.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-box"/></svg>Resource donation</a>
      <a href="../donors/donor-volunteer-registration.php"><svg class="icon"><use href="../assets/img/icons.svg#i-hand"/></svg>Volunteering</a>
      <a href="../donors/donor-messages.php"><svg class="icon"><use href="../assets/img/icons.svg#i-chat"/></svg>Messages</a>
      <a href="../donors/donor-profile-preferences.php"><svg class="icon"><use href="../assets/img/icons.svg#i-user"/></svg>Profile &amp; preferences</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../donors/donor-dashboard.php">Donor space</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Resource donation</strong></nav>
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
          <p class="page-head__eyebrow">Resource donation</p>
          <h1 class="page-head__title">Donate clothes, books &amp; supplies</h1>
          <p class="page-head__sub">Tell us what you have and when it can be collected. A nearby NGO accepts it and arranges the pickup.</p>
        </div>
      </section>

      <?php if ($error !== ''): ?>
      <p class="alert alert--error"><svg class="icon"><use href="../assets/img/icons.svg#i-alert"/></svg><span><?php echo e($error) ?></span></p>
      <?php endif ?>

      <div class="tabs" aria-label="Resource donation">
        <button type="button" class="tabs__btn is-active" data-show-panel="form" data-panel-group="res" aria-expanded="true"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-box"/></svg>Donate resources</button>
        <button type="button" class="tabs__btn" data-show-panel="history" data-panel-group="res" aria-expanded="false"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-history"/></svg>Donation history</button>
      </div>

      <!-- ── Donation form ── -->
      <section class="row row--2-1 panel" data-panel="form" data-panel-group="res">
        <article class="card">
          <form class="form" action="" method="POST">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
            <div class="form__grid">
              <div class="field">
                <label for="type">Resource type</label>
                <div class="field__control field__control--select">
                  <svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-box"/></svg>
                  <select id="type" name="type">
                    <option>Clothes</option>
                    <option>Books</option>
                    <option>Medical Supplies</option>
                    <option>Stationery</option>
                    <option>Other</option>
                  </select>
                </div>
              </div>
              <div class="field">
                <label for="quantity">Quantity</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-list"/></svg><input type="number" id="quantity" min="1" placeholder="e.g. 20" name="quantity" required></div>
              </div>
              <div class="field field--full">
                <label for="description">Description</label>
                <div class="field__control field__control--area"><textarea id="description" rows="4" placeholder="What exactly are you giving? Sizes, subjects, condition…" name="description"></textarea></div>
              </div>
              <div class="field field--full">
                <span class="field__label">Condition</span>
                <div class="chips" role="radiogroup" aria-label="Condition">
                  <label class="chip"><input type="radio" name="condition" value="New" checked><span>New</span></label>
                  <label class="chip"><input type="radio" name="condition" value="Gently Used"><span>Gently used</span></label>
                </div>
              </div>
              <div class="field field--full">
                <label for="address">Pickup address</label>
                <div class="field__control field__control--area"><textarea id="address" rows="3" name="address" placeholder="Where can the NGO collect it?" required></textarea></div>
              </div>
              <div class="field">
                <label for="date">Pickup date</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-calendar"/></svg><input type="date" id="date" name="date" required></div>
              </div>
              <div class="field">
                <label for="time">Pickup time</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-clock"/></svg><input type="time" id="time" name="time" required></div>
              </div>
            </div>
            <div class="card__foot">
              <button type="submit" name="confirm-btn" class="btn btn--dark"><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Confirm resource donation</button>
            </div>
          </form>
        </article>

        <aside class="urgent">
          <svg aria-hidden="true" class="leaf leaf--u1"><use href="../assets/img/icons.svg#leaf"/></svg>
          <span class="pill pill--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-truck"/></svg>What happens next</span>
          <h2>From your door to someone in need</h2>
          <ol class="urgent__steps">
            <li>Your donation is listed as <strong>Pending</strong> for nearby NGOs.</li>
            <li>An NGO accepts it — the status changes to <strong>Approved</strong>.</li>
            <li>The NGO collects it at the date and time you chose.</li>
          </ol>
        </aside>
      </section>

      <!-- ── Donation history ── -->
      <section class="card panel" data-panel="history" data-panel-group="res" hidden>
        <header class="card__head">
          <div>
            <h2 class="card__title">Your resource donations</h2>
            <p class="card__sub">Newest pickup dates first</p>
          </div>
        </header>
        <div class="cards-grid">
            <?php
            $stmt = $pdo->prepare("SELECT * FROM resource_donations WHERE donor_id = ? ORDER BY pickup_date DESC");
            $stmt->bindParam(1, $_SESSION['donor_id']);
            $stmt->execute();
            if ($stmt->rowCount() > 0):
                while ($record = $stmt->fetch(PDO::FETCH_ASSOC)):
                    $resource_type   = htmlspecialchars($record['resource_type']);
                    $quantity        = htmlspecialchars($record['quantity']);
                    $description     = htmlspecialchars($record['description'] ?? '—');
                    $condition       = htmlspecialchars($record['resource_condition'] ?? '—');
                    $pickup_address  = htmlspecialchars($record['pickup_address']);
                    $pickup_date     = $record['pickup_date'];
                    $pickup_time     = $record['pickup_time'];
                    $status          = $record['status'];
                    $status_class    = strtolower($status);
            ?>
          <article class="mini-card" data-kind="<?php echo e($status_class); ?>">
            <div class="mini-card__top">
              <h3><?php echo $resource_type; ?> · Qty <?php echo $quantity; ?></h3>
              <span class="status" data-status="<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status); ?></span>
            </div>
            <div class="mini-card__meta">
              <span><svg class="icon"><use href="../assets/img/icons.svg#i-check"/></svg>Condition: <?php echo $condition; ?></span>
              <span><svg class="icon"><use href="../assets/img/icons.svg#i-calendar"/></svg><?php echo e($pickup_date); ?> at <?php echo e($pickup_time); ?></span>
              <span><svg class="icon"><use href="../assets/img/icons.svg#i-pin"/></svg><?php echo $pickup_address; ?></span>
            </div>
                        <?php if ($description !== '—'): ?>
            <p><?php echo $description; ?></p>
                        <?php endif; ?>
          </article>
            <?php
                endwhile;
            else:
            ?>
          <div class="empty"><svg class="icon"><use href="../assets/img/icons.svg#i-box"/></svg><strong>No resource donations yet</strong><p>Your donations will appear here with their status.</p></div>
            <?php endif; ?>
        </div>
      </section>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
