<?php
require_once __DIR__.'/inc/db.php';
require_once __DIR__.'/inc/config.php';
$value=1;
$val=1;
$flag=0;
try
{
if(isset($_POST['register-beneficiary']))
{
    // Function to remove leading and trailing whitespaces,'_','\', and prevent Cross Site Scripting (XSS) Attacks 
    function validateInput($data)
    {
        $data=trim($data,"_");
        $data=stripslashes($data);
        $data=htmlspecialchars($data);
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
    $password=password_hash($_POST['password'], PASSWORD_ARGON2ID); 
    $confirm_password=$_POST['cpassword'];

    // Checking if username or email exists
    $stmt=$pdo->prepare("Select * from beneficiary_master where email=?");
    $stmt->bindParam(1,$email);
    $stmt->execute();
    if($stmt->rowCount()>0)
    {
        $val=0;
    }
    
    // Password Verification
    if(password_verify($confirm_password,$password)==FALSE)
    {
        $value=0;
    }

    if($value==1 && $val==1)
    {
    // Insert data into table patient_master and prevent SQL Injection Attacks
    $stmt=$pdo->prepare("insert into beneficiary_master (name, email, password, last_login, created_at) values (?,?,?,NOW(),NOW())");
    $stmt->bindParam(1, $fullname);
    $stmt->bindParam(2, $email);
    $stmt->bindParam(3, $password);
    $stmt->execute();
    $records=$stmt->fetch(PDO::FETCH_ASSOC);
    $id=$pdo->lastInsertId();
    $stmt1=$pdo->prepare("insert into users (role_id, name, role, email) values (?,?,?,?)");
    $stmt1->bindParam(1,$id);
    $stmt1->bindParam(2,$fullname);
    $stmt1->bindValue(3, "Beneficiary");
    $stmt1->bindParam(4,$email);
    $stmt1->execute();
    echo "Registered Successfully. Redirecting to Login Page...";
    header("refresh:2;url=login-beneficiary.php");
    exit();
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
    else if(strlen($password) < 6)
        echo "Password must be >= 6 chars";
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
    echo "Error : " . $e->getMessage();
}
$conn=null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HelpSphere - Beneficiary-Register-Login</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="./public/assets/css/beneficiary-login.css">
</head>
<body>
    <div class="container">
        <div class="form-box login">
            <form action="" method="POST">
                <h1>Login</h1>
                <div class="input-box">
                    <input type="email" placeholder="Email" name="email" required>
                    <i class="fa-regular fa-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" placeholder="Password" name="password" autocomplete="current-password" required>
                    <i class="fa-solid fa-lock"></i>
                </div>
                <button class="btn" type="submit" name="login-beneficiary">Login</button>
            </form>
        </div>

        <div class="form-box register">
            <form action="" method="POST">
                <h1>Registration</h1>
                <div class="input-box">
                    <input type="text" placeholder="Name" name="fullname" required>
                    <i class="fa-regular fa-user"></i>
                </div>
                <div class="input-box">
                    <input type="email" placeholder="Email" name="email" required>
                    <i class="fa-regular fa-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" placeholder="Password" name="password" autocomplete="new-password" required>
                    <i class="fa-solid fa-lock"></i>
                </div>
                <div class="input-box">
                    <input type="password" placeholder="Confirm Password" name="cpassword" autocomplete="new-password" required>
                    <i class="fa-solid fa-lock"></i>
                </div>
                <button class="btn" type="submit" name="register-beneficiary">Register</button>
            </form>
        </div>

        <div class="toggle-box">
            <div class="toggle-panel toggle-left">
                <h1>Hello, Welcome</h1>
                <p>Don't have an account?</p>
                <button class="btn register-btn">Register</button>
            </div>

            <div class="toggle-panel toggle-right">
                <h1>Welcome Back!</h1>
                <p>Already have an account?</p>
                <button class="btn login-btn">Login</button>
            </div>
        </div>
    </div>

     <script>
    const container = document.querySelector('.container')
    const registerBtn = document.querySelector('.register-btn')
    const loginBtn = document.querySelector('.login-btn')

    registerBtn.addEventListener('click', ()=> {container.classList.add('active');});
    loginBtn.addEventListener('click', ()=> {container.classList.remove('active');});
     </script>
</body>
</html>