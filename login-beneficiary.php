<?php
require_once __DIR__.'/inc/db.php';
require_once __DIR__.'/inc/config.php';
$value=1;
$val=1;
$flag=0;
$password="";
try
{
if(isset($_POST['register-beneficiary']))
{
    // Function to remove leading and trailing whitespaces,'_','\', and prevent Cross Site Scripting (XSS) Attacks 
    function validateInput($data)
    {
        $data=trim($data,"_");
        $data=stripslashes($data);
        return $data;
    }

    // Function To Prevent Cross Site Scripting (XSS) Attacks
    function XSSPrevention($data)
    {
        $data=htmlspecialchars($data);
        return $data;
    }

    $fullname=validateInput($_POST['fullname']);
    $email=validateInput($_POST['email']);
    if(!$fullname)
    {
        $val=-1;        
    }

    // Hashing the Password before storing in Database 
    $password='';
    $confirm_password=$_POST['cpassword'] ?? '';
    $pw_short=strlen($_POST['password'])<6;
    if(!$pw_short)
    {
        $password=password_hash($_POST['password'], PASSWORD_ARGON2ID);
    }

    // Checking if username or email exists
    $stmt=$pdo->prepare("Select * from beneficiary_master where email=?");
    $stmt->bindParam(1,$email);
    $stmt->execute();
    if($stmt->rowCount()>0)
    {
        $val=0;
    }
    
    // Password Verification
    if(!$pw_short && password_verify($confirm_password,$password)==FALSE)
    {
        $value=0;
    }

    if($value==1 && $val==1 && !$pw_short && filter_var($email, FILTER_VALIDATE_EMAIL))
    {
    // Insert data into table patient_master and prevent SQL Injection Attacks
    $stmt=$pdo->prepare("insert into beneficiary_master (name, email, password, last_login, created_at) values (?,?,?,NOW(),NOW())");
    $stmt->bindParam(1, $fullname);
    $stmt->bindParam(2, $email);
    $stmt->bindParam(3, $password);
    $stmt->execute();
    $id=$pdo->lastInsertId();
    $stmt1=$pdo->prepare("insert into users (role_id, name, role, email) values (?,?,?,?)");
    $stmt1->bindParam(1,$id);
    $stmt1->bindParam(2,$fullname);
    $stmt1->bindValue(3, "Beneficiary");
    $stmt1->bindParam(4,$email);
    $stmt1->execute();
    header("refresh:2;url=login-beneficiary.php");
    echo "Registered Successfully. Redirecting to Login Page...";
    exit();
    }
    else if($pw_short)
    {
        echo "<h1>Password must be at least 6 characters</h1>";
    }
    else if($value==0)
    {
        echo "<h1>Passwords Do Not Match. Please Enter Correct Password</h1>";
    }
    else if($val==0)
    {
        echo "<h1>Username or Email exists.</h1>";
    }
    else if($val==-1)
        echo "Full Name Required";
    else if(!filter_var($email, FILTER_VALIDATE_EMAIL))
        echo "Valid Email ID Required";
}
else if(isset($_POST['login-beneficiary']))
{
   $email=$_POST['email'];
   $password=$_POST['password'];
   
   $stmt=$pdo->prepare("Select * from beneficiary_master where email=?");
   $stmt->bindParam(1,$email);
   $stmt->execute();

   $user=$stmt->fetch(PDO::FETCH_ASSOC);

   if($user && password_verify($password,$user['password']))
   {
    if (session_status() === PHP_SESSION_ACTIVE) 
        session_regenerate_id(true);
     $_SESSION['beneficiary_id']=$user['id'];
     $_SESSION['fullname']=$user['name'];
     $_SESSION['beneficiary_email']=$user['email'];     
     header("Location: ./public/dashboards/beneficiary-dashboard.php");
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
$conn=null;
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

      <div class="mode-tabs" role="tablist" id="modeTabs" data-mode="signin">
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
            <input type="text" id="rg-name" name="fullname" placeholder="Your full name" autocomplete="name" required />
          </div>
        </div>

        <div class="field">
          <label for="rg-email">Email address</label>
          <div class="field__control">
            <svg class="icon icon--sm"><use href="public/assets/img/icons.svg#i-mail"/></svg>
            <input type="email" id="rg-email" name="email" placeholder="you@example.com" autocomplete="email" required />
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
