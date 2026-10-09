
<?php 
require_once __DIR__. '/../../inc/auth.php';
require_login();

$stmt=$pdo->prepare("select * from beneficiary_master where id=?");
$stmt->bindParam(1,$_SESSION['beneficiary_id']);
$stmt->execute();
if($stmt->rowCount()>0)
{
    $res=$stmt->fetch(PDO::FETCH_ASSOC);
    $name=isset($res['name'])?$res['name']:'';
    $email=isset($res['email'])?$res['email']:'';
    $dob=isset($res['dob'])?$res['dob']:'';
    $gender=isset($res['gender'])?$res['gender']:'';
    $address=isset($res['address'])?$res['address']:'';
    $phone=isset($res['phone'])?$res['phone']:'';
}

if(isset($_POST['update-btn']))
{
    $stmt=$pdo->prepare("update users set name=?,email=? where id=?");
    $stmt->bindParam(1,$_POST['name']);
    $stmt->bindParam(2,$_POST['email']);
    $stmt->bindParam(3,$_SESSION['beneficiary_id']);
    $stmt->execute();
    
    $stmt=$pdo->prepare("update beneficiary_master set name=?,email=?,dob=?,gender=?,address=?,phone=? where id=?");
    $stmt->bindParam(1,$_POST['name']);
    $stmt->bindParam(2,$_POST['email']);
    $stmt->bindParam(3,$_POST['dob']);
    $stmt->bindParam(4,$_POST['gender']);
    $stmt->bindParam(5,$_POST['address']);
    $stmt->bindParam(6,$_POST['phone']);
    $stmt->bindParam(7,$_SESSION['beneficiary_id']);
    $stmt->execute();
    if($stmt){
        header("Location: beneficiary-profile.php");
        exit;
    }        
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile & Settings</title>
   <link rel="stylesheet" href="../assets/css/beneficiary.css">
</head>
<body>

<div id="overlay" class="overlay"></div>

<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="brand">HelpSphere</div>
            <button id="sidebarClose" class="sidebar-close">✕</button>
        </div>

        <p class="tagline">Support that reaches you</p>

        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="../dashboards/beneficiary-dashboard.php" class="nav-item">
                        <span class="icon">🏠</span>
                        <span class="label">Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="../beneficiaries/beneficiary-request-management.php" class="nav-item">
                        <span class="icon">📄</span>
                        <span class="label">Requests</span>
                    </a>
                </li>

                <li>
                    <a href="../beneficiaries/beneficiary-document-upload.php" class="nav-item">
                        <span class="icon">📁</span>
                        <span class="label">Upload Documents</span>
                    </a>
                </li>

                <li>
                    <a href="../beneficiaries/beneficiary-documents.php" class="nav-item">
                        <span class="icon">📁</span>
                        <span class="label">Documents</span>
                    </a>
                </li>

                <li>
                    <a href="../beneficiaries/beneficiary-messages.php" class="nav-item">
                        <span class="icon">💬</span>
                        <span class="label">Messages</span>
                    </a>
                </li>

                <li>
                    <a href="../beneficiaries/beneficiary-profile.php" class="nav-item active">
                        <span class="icon">👤</span>
                        <span class="label">Profile</span>
                    </a>
                </li>

                <li>
                    <a href="../../inc/logout.php" class="nav-item logout">
                        <span class="icon">🚪</span>
                        <span class="label">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <header class="topbar">
            <div class="topbar-left">
                <button id="menuToggle" class="menu-toggle">☰</button>
                <div class="user-name">Profile & Settings</div>
            </div>
        </header>

        <!-- PERSONAL DETAILS -->
         <form class="request-form" action="" method="POST">
        <section class="active-requests">
            <div class="section-header">
                <h3>Personal Details</h3>
            </div>

            <div class="request-item">

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="<?php echo isset($name)?$name:'' ?>" name="name">
                    </div>

                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" value="<?php echo isset($dob)?$dob:'' ?>" name="dob">
                    </div>

                    <?php if(isset($gender)): ?>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="Male" <?php if($gender=='Male') echo 'selected'; ?>>Male</option>
                            <option value="Female" <?php if($gender=='Female') echo 'selected'; ?>>Female</option>
                            <option value="Other" <?php if($gender=='Other') echo 'selected'; ?>>Other</option>
                        </select>
                    </div>
                    <?php endif ?>
            </div>
        </section>

        <!-- ADDRESS & CONTACT -->
        <section class="active-requests">
            <div class="section-header">
                <h3>Address & Contact</h3>
            </div>

            <div class="request-item">
                <form class="request-form">

                    <div class="form-group">
                        <label>Address</label>
                        <textarea rows="3" name="address"><?php echo isset($address) ? htmlspecialchars($address) : '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="number" value="<?php echo isset($phone)?$phone:'' ?>" name="phone">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?php echo isset($email)?$email:'' ?>" name="email">
                    </div>
            </div>
        </section>

        <!-- SAVE ACTION -->
        <section class="active-requests">
            <div class="request-item">
                <div class="action-row">
                    <button type="submit" name="update-btn" class="action-btn">
                        Save Changes
                    </button>
                </div>
            </div>
        </section>
        </form>

        <footer class="footer">
            © 2026 HelpSphere · Beneficiary Portal
        </footer>

    </main>

</div>

<script src="../assets/js/beneficiary-dashboard.js"></script>
</body>
</html>
