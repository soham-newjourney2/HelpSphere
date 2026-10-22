<?php
require_once __DIR__ . '/../../inc/auth.php';
admin_require_login();

$message = '';   // error text shown inside the page body
$success = false;

if (isset($_POST['submit-btn'])) {
    csrf_require_post();
    $fullname         = trim($_POST['ngo-name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password_raw     = $_POST['password'] ?? '';
    $confirm_password = $_POST['cpassword'] ?? '';
    $reg_date         = trim($_POST['reg-date'] ?? '');

    // Validate first, insert only if everything passes
    if ($fullname === '') {
        $message = 'Full Name Required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Valid Email ID Required';
    } elseif (strlen($password_raw) < 6) {
        $message = 'Password must be at least 6 characters';
    } elseif ($password_raw !== $confirm_password) {
        $message = 'Passwords do not match';
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $reg_date);
        if (!$d || $d->format('Y-m-d') !== $reg_date) {
            $message = 'Valid Registration Date Required';
        }
    }

    if ($message === '') {
        try {
            $stmt = $pdo->prepare("SELECT id FROM ngo_master WHERE email=?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $message = 'Email already exists';
            } else {
                $password = password_hash($password_raw, PASSWORD_ARGON2ID);

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO ngo_master (name, email, password, focus, description, registration_no, address, registration_date, status, phone) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $fullname, $email, $password,
                    $_POST['focus'], $_POST['description'],
                    $_POST['reg-no'], $_POST['address'],
                    $reg_date, 'Unverified', $_POST['phone']
                ]);
                $id = $pdo->lastInsertId();

                $stmt1 = $pdo->prepare("INSERT INTO users (role_id, name, role, email) VALUES (?,?,?,?)");
                $stmt1->execute([$id, $fullname, 'NGO', $email]);

                $pdo->commit();
                $success = true;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log($e->getMessage());
            $message = 'Something went wrong. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add NGO · Admin | HelpSphere</title>
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
      <a href="../admin/index.php"><svg class="icon"><use href="../assets/img/icons.svg#i-grid"/></svg>Overview</a>
      <a href="../admin/users.php"><svg class="icon"><use href="../assets/img/icons.svg#i-users"/></svg>Users</a>
      <a href="../admin/ngo-verification.php"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg>NGO verification</a>
      <a href="../admin/add-ngo.php" class="is-active" aria-current="page"><svg class="icon"><use href="../assets/img/icons.svg#i-user-plus"/></svg>Add NGO</a>
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
      <nav class="crumbs" aria-label="Breadcrumb"><a href="../admin/index.php">Admin console</a><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-arrow"/></svg><strong>Add NGO</strong></nav>
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
          <p class="page-head__eyebrow">NGO management</p>
          <h1 class="page-head__title">Register a new NGO</h1>
          <p class="page-head__sub">Only administrators can add NGOs. The new account starts as <strong>Unverified</strong> until its documents are approved.</p>
        </div>
      </section>

      <?php if ($message !== ''): ?>
      <p class="alert alert--error"><svg class="icon"><use href="../assets/img/icons.svg#i-alert"/></svg><span><?php echo htmlspecialchars($message); ?></span></p>
      <?php endif; ?>

      <section class="row row--2-1">
        <article class="card reveal">
          <form class="form" id="ngo-form" action="" method="POST">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
            <header class="card__head">
              <div>
                <h2 class="card__title">Organisation details</h2>
                <p class="card__sub">As shown on the NGO’s registration certificate</p>
              </div>
              <span class="pill pill--info"><svg class="icon"><use href="../assets/img/icons.svg#i-shield"/></svg>Admin only</span>
            </header>
            <div class="form__grid">
              <div class="field">
                <label for="ngo-name">NGO name</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-building"/></svg><input type="text" id="ngo-name" placeholder="e.g. Hope Foundation" name="ngo-name" required></div>
              </div>
              <div class="field">
                <label for="ngo-reg">Registration no.</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-file"/></svg><input type="text" id="ngo-reg" placeholder="As on the certificate" name="reg-no" required></div>
              </div>
              <div class="field">
                <label for="ngo-focus">Focus area</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-heart"/></svg><input type="text" id="ngo-focus" placeholder="e.g. Child welfare" name="focus" required></div>
              </div>
              <div class="field">
                <label for="ngo-established">Registration date</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-calendar"/></svg><input type="date" id="ngo-established" name="reg-date" required></div>
              </div>
            </div>

            <header class="card__head modal__title--spaced">
              <div>
                <h2 class="card__title">Contact &amp; sign-in</h2>
                <p class="card__sub">The NGO signs in with this email and password</p>
              </div>
            </header>
            <div class="form__grid">
              <div class="field">
                <label for="ngo-email">Email address</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-mail"/></svg><input type="email" id="ngo-email" placeholder="contact@ngo.org" name="email" required></div>
              </div>
              <div class="field">
                <label for="ngo-phone">Phone number</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-phone"/></svg><input type="tel" id="ngo-phone" placeholder="10-digit mobile" name="phone" required></div>
              </div>
              <div class="field">
                <label for="ngo-password">Password</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-lock"/></svg><input type="password" id="ngo-password" placeholder="Min. 6 characters" name="password" minlength="6" autocomplete="new-password" required>
                  <button type="button" class="field__eye" aria-label="Show password" data-toggle-pass="ngo-password"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-eye"/></svg></button></div>
              </div>
              <div class="field">
                <label for="ngo-cpassword">Confirm password</label>
                <div class="field__control"><svg class="icon icon--sm"><use href="../assets/img/icons.svg#i-lock"/></svg><input type="password" id="ngo-cpassword" placeholder="Repeat password" name="cpassword" autocomplete="new-password" required></div>
              </div>
              <div class="field field--full">
                <label for="ngo-address">Full address</label>
                <div class="field__control field__control--area"><textarea id="ngo-address" rows="3" placeholder="Street, area, city, PIN" name="address" required></textarea></div>
                <p class="field__hint">Used to find the nearest NGOs for immediate food donations.</p>
              </div>
              <div class="field field--full">
                <label for="ngo-desc">Description / mission</label>
                <div class="field__control field__control--area"><textarea id="ngo-desc" rows="4" placeholder="Briefly describe the NGO’s mission and activities…" name="description"></textarea></div>
              </div>
            </div>

            <div class="card__foot">
              <button type="submit" class="btn btn--dark" id="btn-submit-ngo" name="submit-btn"><svg class="icon"><use href="../assets/img/icons.svg#i-user-plus"/></svg>Register NGO</button>
              <button type="reset" class="btn btn--ghost">Reset form</button>
            </div>
          </form>
        </article>

        <aside class="urgent reveal">
          <svg aria-hidden="true" class="leaf leaf--u1"><use href="../assets/img/icons.svg#leaf"/></svg>
          <svg aria-hidden="true" class="leaf leaf--u2"><use href="../assets/img/icons.svg#leaf"/></svg>
          <span class="pill pill--warn"><svg class="icon"><use href="../assets/img/icons.svg#i-info"/></svg>After you register it</span>
          <h2>Next steps</h2>
          <ol class="urgent__steps">
            <li>Share the email and password with the NGO yourself — no email is sent.</li>
            <li>The NGO signs in and uploads its compliance documents.</li>
            <li>You verify them under <strong>NGO verification</strong>. Then it starts receiving food alerts.</li>
          </ol>
          <p>Each email address can belong to only one NGO.</p>
        </aside>
      </section>

  <?php if ($success): ?>
      <div data-toast="NGO registered successfully"></div>
<?php endif; ?>

    </main>
  </div>
</div>

<script src="../assets/js/common.js"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
