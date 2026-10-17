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
    echo "Error : " . $e->getMessage();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>NGO Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./public/assets/css/ngo.css">
</head>

<body>

  <div class="sky">
    <div class="aurora a1"></div>
    <div class="aurora a2"></div>
    <div class="aurora a3"></div>
  </div>

  <!-- <div class="moon"></div> -->

  <main class="login-container">

    <section class="login-card">

      <div class="login-header">
        <div class="login-title">NGO Portal</div>
        <div class="login-subtitle">Authorized access only</div>
      </div>

      <form class="login-form" action="" method="POST">
        
        <div class="login-field">
          <label>Registered Email</label>
          <input type="email" required name="email">
        </div>

        <div class="login-field">
          <label>Password</label>
          <input type="password" required name="password">
        </div>

        <button type="submit" class="login-submit" name="login-btn">
          Login
        </button>

      </form>

      <div class="login-footer">
        © NGO Management System
      </div>

    </section>

  </main>

  <!-- <div class="snow">
    <span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span>
  </div>

  <div class="snow-accumulation"></div> -->

</body>
</html>
