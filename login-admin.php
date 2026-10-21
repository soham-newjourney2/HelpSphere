<?php
//login-admin.php
require_once __DIR__.'/inc/auth.php';
try
{
   if(isset($_POST['login-admin'])) 
   {
   $email=$_POST['email'];
   $password=$_POST['password'];   
   $stmt=$pdo->prepare("Select * from admin_master where email=?");
   $stmt->bindParam(1,$email);
   $stmt->execute();
   
   $admin=$stmt->fetch(PDO::FETCH_ASSOC);
   
   if($admin && $password == $admin['password'])
   {
    if (session_status() === PHP_SESSION_ACTIVE) 
        session_regenerate_id(true);
     $_SESSION['ad_name']=$admin['name'];
     $_SESSION['ad_email']=$admin['email'];  
     $_SESSION['admin_id']=$admin['id'];   
     header("Location: public/admin/index.php");
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
<!-- Admin login -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Login</title>
    <link rel="stylesheet" href="./public/assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<a href="./"></a>
<div class="background"></div>

<div class="login-wrapper">
    <div class="login-box">

        <div class="icon-head">
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <form action="" method="POST">
            <div class="field">
                <i class="fa-solid fa-user"></i>
                <input type="text" placeholder="Enter your email" required name="email">
            </div>

            <div class="field">
                <i class="fa-solid fa-lock"></i>
                <input type="password" placeholder="Enter Your Password" required name="password">
            </div>

            <button type="submit" name="login-admin">
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
            </button>
        </form>

        <div class="roles">
            <i class="fa-solid fa-user-tie"></i>
            <i class="fa-solid fa-users-gear"></i>
            <i class="fa-solid fa-code"></i>
            <i class="fa-solid fa-user-graduate"></i>
        </div>

    </div>
</div>

</body>
</html>
