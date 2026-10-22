<?php
//login-ngo.php
require_once __DIR__.'/inc/auth.php';
try
{
   if(isset($_POST['login-btn'])) 
   {
   $email=$_POST['email'];
   $password=$_POST['password'];   
   $stmt=$pdo->prepare("Select * from ngo_master where email=?");
   $stmt->bindParam(1,$email);
   $stmt->execute();
   
   $ngo=$stmt->fetch(PDO::FETCH_ASSOC);
   
   if($ngo && password_verify($password,$ngo['password']))
{
  if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);   // keeps session data
  $_SESSION['ngo_name']=$ngo['name'];
  $_SESSION['ngo_email']=$ngo['email'];
  $_SESSION['ngo_id']=$ngo['id'];

  // Fix 41: go back to the email link if the NGO came from one
  $to = (string)($_SESSION['ngo_return_to'] ?? '');
  unset($_SESSION['ngo_return_to']);
  if (preg_match('/^ngo-food-accept\.php\?notif_id=\d+&token=[a-f0-9]{64}$/', $to)) {
      header('Location: public/ngos/' . $to);
  } else {
      header('Location: public/dashboards/ngo-dashboard.php');   // forward slashes, not backslashes
  }
  exit();
}
   else
   {
     echo "Invalid email or password";
   }
}
} catch(PDOException $e)
{ 
    error_log('login: '.$e->getMessage()); 
    echo "Something went wrong. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>NGO sign in | HelpSphere</title>
  <meta name="description" content="Sign in to the HelpSphere NGO portal." />
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
      <h2 class="auth__art-title">Verified NGOs, trusted by every donor.</h2>
      <p class="auth__art-text">Accept nearby food donations, review beneficiary requests and run volunteer campaigns from one place.</p>
    </div>

    <ul class="auth__art-facts">
      <li><strong>7 min</strong><span>to accept a food alert</span></li>
      <li><strong>5 NGOs</strong><span>alerted per round</span></li>
      <li><strong>₹0</strong><span>platform fees</span></li>
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
      <p class="auth__subtitle" id="formSubtitle">Sign in to manage needs, donations and campaigns.</p>

      <nav class="roles-select" aria-label="Sign in as">
        <a class="role-opt" href="login-beneficiary.php"><svg class="icon"><use href="public/assets/img/icons.svg#i-user"/></svg>Beneficiary</a>
        <a class="role-opt" href="login-donor.php"><svg class="icon"><use href="public/assets/img/icons.svg#i-heart"/></svg>Donor</a>
        <a class="role-opt" href="login-ngo.php" aria-current="page"><svg class="icon"><use href="public/assets/img/icons.svg#i-building"/></svg>NGO</a>
        <a class="role-opt" href="login-admin.php"><svg class="icon"><use href="public/assets/img/icons.svg#i-shield"/></svg>Admin</a>
      </nav>

      <div class="mode-tabs" role="tablist" id="modeTabs" data-mode="signin">
        <button type="button" role="tab" class="mode-tab is-active" aria-selected="true" data-mode="signin"
                data-title="Welcome back" data-subtitle="Sign in to manage needs, donations and campaigns.">Sign in</button>
        <button type="button" role="tab" class="mode-tab" aria-selected="false" data-mode="verify"
                data-title="Register your NGO" data-subtitle="Get your organisation verified to start receiving donations.">Get verified</button>
        <span class="mode-tabs__ink" aria-hidden="true"></span>
      </div>

      <!-- ===== Sign in ===== -->
      <form class="form" action="" method="POST" data-panel="signin">
        <div class="field">
          <label for="si-email">Registered email</label>
          <div class="field__control">
            <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-mail"/></svg>
            <input type="email" id="si-email" name="email" placeholder="ngo@example.org" autocomplete="username" required />
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

        <button type="submit" name="login-btn" class="btn btn--dark btn--block form__submit">
          Sign in <svg class="icon icon--arrow"><use href="public/assets/img/icons.svg#i-arrow"/></svg>
        </button>

        <p class="form__switch">New organisation? <button type="button" data-goto="verify">How to get verified</button></p>
      </form>

      <!-- ===== How NGOs join (accounts are created by admins) ===== -->
      <div class="notice" data-panel="verify" hidden>
        <span class="notice__icon"><svg class="icon"><use href="public/assets/img/icons.svg#i-file"/></svg></span>
        <div>
          <h3>NGO accounts are verified first</h3>
          <p>To keep every donation safe, NGO accounts are created by HelpSphere admins. After you sign in, you upload your compliance documents and an admin reviews them before your NGO receives food alerts.</p>
          <ol class="notice__steps">
            <li>Share your registration details with the HelpSphere admin team</li>
            <li>An admin creates your NGO account</li>
            <li>Sign in and upload your compliance documents for review</li>
          </ol>
          <p class="form__switch">Already have an account? <button type="button" data-goto="signin">Sign in</button></p>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="public/assets/js/common.js"></script>
<script src="public/assets/js/auth.js"></script>
</body>
</html>
