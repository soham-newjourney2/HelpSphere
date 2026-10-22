<?php
//login-beneficiary.php
require_once __DIR__.'/inc/db.php';
$error='';
$notice='';
$mode='signin';
try
{
if(isset($_POST['register-beneficiary']))
{
    $mode='register';
    $fullname=trim($_POST['fullname'] ?? '');
    $email=trim($_POST['email'] ?? '');
    $dob=trim($_POST['dob'] ?? '');
    $gender=$_POST['gender'] ?? '';
    $address=trim($_POST['address'] ?? '');
    $phone=trim($_POST['phone'] ?? '');
    $pass=(string)($_POST['password'] ?? '');
    $d=DateTime::createFromFormat('Y-m-d', $dob);

    if($fullname==='')
        $error='Full name is required.';
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL))
        $error='Enter a valid email address.';
    elseif(!$d || $d->format('Y-m-d')!==$dob || $d > new DateTime())
        $error='Enter a valid date of birth.';
    elseif(!in_array($gender, ['Male','Female','Other'], true))
        $error='Please choose a gender.';
    elseif($address==='')
        $error='Address is required.';
    elseif(!preg_match('/^[0-9+\- ]{7,20}$/', $phone))
        $error='Enter a valid phone number.';
    elseif(strlen($pass)<6)
        $error='Password must be at least 6 characters.';
    elseif($pass!==(string)($_POST['cpassword'] ?? ''))
        $error='Passwords do not match.';
    else
    {
        $stmt=$pdo->prepare("select id from beneficiary_master where email=?");
        $stmt->execute([$email]);
        if($stmt->fetch())
        {
            $error='An account with this email already exists.';
        }
        else
        {
            $pdo->beginTransaction();
            $stmt=$pdo->prepare("insert into beneficiary_master (name, email, password, dob, gender, address, phone) values (?,?,?,?,?,?,?)");
            $stmt->execute([$fullname, $email, password_hash($pass, PASSWORD_ARGON2ID), $dob, $gender, $address, $phone]);
            $stmt1=$pdo->prepare("insert into users (role_id, name, role, email) values (?,?,?,?)");
            $stmt1->execute([$pdo->lastInsertId(), $fullname, 'Beneficiary', $email]);
            $pdo->commit();
            $notice='Account created — please sign in.';
            $mode='signin';
            $_POST=[];
        }
    }
}
else if(isset($_POST['login-beneficiary']))
{
   $stmt=$pdo->prepare("Select * from beneficiary_master where email=?");
   $stmt->execute([(string)($_POST['email'] ?? '')]);
   $user=$stmt->fetch(PDO::FETCH_ASSOC);

   if($user && password_verify((string)($_POST['password'] ?? ''),$user['password']))
   {
     session_regenerate_id(true);
     $_SESSION['beneficiary_id']=$user['id'];
     $_SESSION['fullname']=$user['name'];
     $_SESSION['beneficiary_email']=$user['email'];
     header("Location: ./public/beneficiaries/beneficiary-dashboard.php");
     exit();
   }
   $error='Invalid email or password.';
}
} catch(PDOException $e)
{
    if($pdo->inTransaction()) $pdo->rollBack();
    error_log('login: '.$e->getMessage());
    $error='Something went wrong. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Beneficiary sign in | HelpSphere</title>
  <meta name="description" content="Sign in or create a HelpSphere account to request help." />
  <meta name="hs-icons" content="public/assets/img/icons.svg" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Inter+Tight:wght@500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="public/assets/css/base.css" />
  <link rel="stylesheet" href="public/assets/css/auth.css" />
</head>
<body>

<div class="auth">

  <!-- ============ Art panel ============ -->
  <aside class="auth__art" aria-hidden="true">
    <svg class="leaf leaf--blur leaf--a1"><use href="public/assets/img/icons.svg#leaf"/></svg>
    <svg class="leaf leaf--a2"><use href="public/assets/img/icons.svg#leaf"/></svg>
    <svg class="leaf leaf--a3"><use href="public/assets/img/icons.svg#leaf"/></svg>
    <svg class="leaf leaf--blur leaf--a4"><use href="public/assets/img/icons.svg#leaf"/></svg>

    <a href="index.php" class="logo logo--light" tabindex="-1">
      <span class="logo__mark"><svg><use href="public/assets/img/icons.svg#i-sprout"/></svg></span>
      HelpSphere
    </a>

    <div class="auth__art-body">
      <div class="badge-ring badge-ring--light badge-ring--md">
        <svg class="badge-ring__text" viewBox="0 0 200 200">
          <defs><path id="ringPath" d="M100,100 m-78,0 a78,78 0 1,1 156,0 a78,78 0 1,1 -156,0"/></defs>
          <text><textPath href="#ringPath" textLength="480">FOR PEOPLE · FOR THE PLANET · FOR TOMORROW ·</textPath></text>
        </svg>
        <div class="badge-ring__core"><svg><use href="public/assets/img/icons.svg#i-sprout"/></svg></div>
      </div>
      <h2 class="auth__art-title">Help is closer than you think.</h2>
      <p class="auth__art-text">Request food or resources, upload your documents once and follow every step of your request.</p>
    </div>

    <ul class="auth__art-facts">
      <li><strong>Free</strong><span>to ask for help</span></li>
      <li><strong>Verified</strong><span>NGOs only</span></li>
      <li><strong>Tracked</strong><span>every step</span></li>
    </ul>
  </aside>

  <!-- ============ Form panel ============ -->
  <main class="auth__main">
    <div class="auth__topbar">
      <a href="index.php" class="logo auth__logo-mobile">
        <span class="logo__mark"><svg><use href="public/assets/img/icons.svg#i-sprout"/></svg></span>
        HelpSphere
      </a>
      <a href="index.php" class="auth__back">
        <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-arrow-left"/></svg> Back to home
      </a>
    </div>

    <div class="auth__panel">
      <h1 class="auth__title" id="formTitle">Welcome back</h1>
      <p class="auth__subtitle" id="formSubtitle">Sign in to track your assistance requests.</p>

      <nav class="roles-select" aria-label="Sign in as">
        <a class="role-opt" href="login-beneficiary.php" aria-current="page"><svg class="icon"><use href="public/assets/img/icons.svg#i-user"/></svg>Beneficiary</a>
        <a class="role-opt" href="login-donor.php"><svg class="icon"><use href="public/assets/img/icons.svg#i-heart"/></svg>Donor</a>
        <a class="role-opt" href="login-ngo.php"><svg class="icon"><use href="public/assets/img/icons.svg#i-building"/></svg>NGO</a>
        <a class="role-opt" href="login-admin.php"><svg class="icon"><use href="public/assets/img/icons.svg#i-shield"/></svg>Admin</a>
      </nav>

      <?php if ($error !== ''): ?>
      <p class="alert alert--error" role="alert"><svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-alert"/></svg><span><?php echo e($error) ?></span></p>
      <?php endif ?>
      <?php if ($notice !== ''): ?>
      <p class="alert alert--success" role="status"><svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-check"/></svg><span><?php echo e($notice) ?></span></p>
      <?php endif ?>

      <div class="mode-tabs" role="tablist" id="modeTabs" data-mode="signin" data-start="<?php echo e($mode) ?>">
        <button type="button" role="tab" class="mode-tab is-active" aria-selected="true" data-mode="signin"
                data-title="Welcome back" data-subtitle="Sign in to track your assistance requests.">Sign in</button>
        <button type="button" role="tab" class="mode-tab" aria-selected="false" data-mode="register"
                data-title="Create your account" data-subtitle="Create an account to request help with dignity.">Create account</button>
        <span class="mode-tabs__ink" aria-hidden="true"></span>
      </div>

      <!-- ===== Sign in ===== -->
      <form class="form" action="" method="POST" data-panel="signin">
        <div class="field">
          <label for="si-email">Email address</label>
          <div class="field__control">
            <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-mail"/></svg>
            <input type="email" id="si-email" name="email" placeholder="you@example.com" autocomplete="username" required />
          </div>
        </div>

        <div class="field">
          <label for="si-pass">Password</label>
          <div class="field__control">
            <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-lock"/></svg>
            <input type="password" id="si-pass" name="password" placeholder="Enter your password" autocomplete="current-password" required />
            <button type="button" class="field__eye" aria-label="Show password" data-toggle-pass="si-pass">
              <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-eye"/></svg>
            </button>
          </div>
        </div>

        <button type="submit" name="login-beneficiary" class="btn btn--dark btn--block form__submit">
          Sign in <svg class="icon icon--arrow"><use href="public/assets/img/icons.svg#i-arrow"/></svg>
        </button>

        <p class="form__switch">New to HelpSphere? <button type="button" data-goto="register">Create an account</button></p>
      </form>

      <!-- ===== Register ===== -->
      <form class="form" action="" method="POST" data-panel="register" hidden>
        <div class="field">
          <label for="rg-name">Full name</label>
          <div class="field__control">
            <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-user"/></svg>
            <input type="text" id="rg-name" name="fullname" placeholder="Your full name" autocomplete="name" value="<?php echo e($_POST['fullname'] ?? '') ?>" required />
          </div>
        </div>

        <div class="field">
          <label for="rg-email">Email address</label>
          <div class="field__control">
            <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-mail"/></svg>
            <input type="email" id="rg-email" name="email" placeholder="you@example.com" autocomplete="email" value="<?php echo e($mode === 'register' ? ($_POST['email'] ?? '') : '') ?>" required />
          </div>
        </div>

        <div class="form__grid">
          <div class="field">
            <label for="rg-dob">Date of birth</label>
            <div class="field__control">
              <input type="date" id="rg-dob" name="dob" max="<?php echo date('Y-m-d') ?>" value="<?php echo e($_POST['dob'] ?? '') ?>" required />
            </div>
          </div>

          <div class="field">
            <label for="rg-gender">Gender</label>
            <div class="field__control field__control--select">
              <select id="rg-gender" name="gender" required>
                <option value="">Select</option>
                <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                <option value="<?php echo $g ?>" <?php if (($_POST['gender'] ?? '') === $g) echo 'selected' ?>><?php echo $g ?></option>
                <?php endforeach ?>
              </select>
            </div>
          </div>

          <div class="field">
            <label for="rg-phone">Phone</label>
            <div class="field__control">
              <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-phone"/></svg>
              <input type="tel" id="rg-phone" name="phone" placeholder="10-digit mobile" autocomplete="tel" value="<?php echo e($_POST['phone'] ?? '') ?>" required />
            </div>
          </div>

          <div class="field">
            <label for="rg-address">Address</label>
            <div class="field__control">
              <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-pin"/></svg>
              <input type="text" id="rg-address" name="address" placeholder="House, street, city" autocomplete="street-address" value="<?php echo e($_POST['address'] ?? '') ?>" required />
            </div>
          </div>
        </div>

        <div class="form__grid">
          <div class="field">
            <label for="rg-pass">Password</label>
            <div class="field__control">
              <input type="password" id="rg-pass" name="password" placeholder="Min. 6 characters" autocomplete="new-password" minlength="6" required />
              <button type="button" class="field__eye" aria-label="Show password" data-toggle-pass="rg-pass">
                <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-eye"/></svg>
              </button>
            </div>
            <div class="strength" id="strength" aria-live="polite">
              <span class="strength__bar"><i></i><i></i><i></i><i></i></span>
              <span class="strength__label">At least 6 characters</span>
            </div>
          </div>

          <div class="field">
            <label for="rg-cpass">Confirm password</label>
            <div class="field__control">
              <input type="password" id="rg-cpass" name="cpassword" placeholder="Repeat password" autocomplete="new-password" required />
            </div>
            <p class="match-hint" id="matchHint" hidden>Passwords do not match.</p>
          </div>
        </div>

        <button type="submit" name="register-beneficiary" class="btn btn--dark btn--block form__submit">
          Create account <svg class="icon icon--arrow"><use href="public/assets/img/icons.svg#i-arrow"/></svg>
        </button>

        <p class="form__switch">Already have an account? <button type="button" data-goto="signin">Sign in</button></p>
      </form>
    </div>
  </main>
</div>

<script src="public/assets/js/common.js"></script>
<script src="public/assets/js/auth.js"></script>
</body>
</html>
