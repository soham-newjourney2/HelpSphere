<?php
//login-donor.php
require_once __DIR__.'/inc/auth.php';
try
{
   if(isset($_POST['login-donor'])) 
   {
   $email=$_POST['email'];
   $password=$_POST['password'];
   $stmt=$pdo->prepare("Select * from donor_master where email=?");
   $stmt->bindParam(1,$email);
   $stmt->execute();
   
   $admin=$stmt->fetch(PDO::FETCH_ASSOC);
   
   if($admin && password_verify($password, $admin['password']))
   {
    if (session_status() === PHP_SESSION_ACTIVE) 
        session_regenerate_id(true);
     $_SESSION['donor_name']=$admin['name'];
     $_SESSION['donor_email']=$admin['email'];  
     $_SESSION['donor_id']=$admin['id'];   
     header("Location: public/dashboards/donor-dashboard.php");
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
  <title>Donor sign in | HelpSphere</title>
  <meta name="description" content="Sign in to your HelpSphere donor space." />
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
      <h2 class="auth__art-title">Every login is a step toward someone’s next meal.</h2>
      <p class="auth__art-text">Give food, money or resources, volunteer for campaigns and follow every donation from pickup to delivery.</p>
    </div>

    <ul class="auth__art-facts">
      <li><strong>7 min</strong><span>NGO response window</span></li>
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
      <h1 class="auth__title">Welcome back</h1>
      <p class="auth__subtitle">Sign in to continue to your donor space.</p>

      <nav class="roles-select" aria-label="Sign in as">
        <a class="role-opt" href="login-beneficiary.php"><svg class="icon"><use href="public/assets/img/icons.svg#i-user"/></svg>Beneficiary</a>
        <a class="role-opt" href="login-donor.php" aria-current="page"><svg class="icon"><use href="public/assets/img/icons.svg#i-heart"/></svg>Donor</a>
        <a class="role-opt" href="login-ngo.php"><svg class="icon"><use href="public/assets/img/icons.svg#i-building"/></svg>NGO</a>
        <a class="role-opt" href="login-admin.php"><svg class="icon"><use href="public/assets/img/icons.svg#i-shield"/></svg>Admin</a>
      </nav>

      <div class="mode-tabs mode-tabs--single">
        <span class="mode-tab is-active">Sign in</span>
        <span class="mode-tabs__ink" aria-hidden="true"></span>
      </div>

      <form class="form" action="" method="POST">
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

        <button type="submit" name="login-donor" class="btn btn--dark btn--block form__submit">
          Sign in <svg class="icon icon--arrow"><use href="public/assets/img/icons.svg#i-arrow"/></svg>
        </button>
      </form>

      <p class="auth__note">
        <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-hand"/></svg>
        Volunteering for NGO campaigns also happens from your donor space.
      </p>
    </div>
  </main>
</div>

<script src="public/assets/js/common.js"></script>
<script src="public/assets/js/auth.js"></script>
</body>
</html>
