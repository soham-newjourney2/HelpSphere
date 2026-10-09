<?php
require_once __DIR__. '/../../inc/auth.php';
donor_require_login();

$stmt=$pdo->prepare("select * from donor_master where id=?");
$stmt->bindParam(1,$_SESSION['donor_id']);
$stmt->execute();
if($stmt->rowCount()>0)
{
    $result=$stmt->fetch(PDO::FETCH_ASSOC);
    $name=$result['name'];
    $email=$result['email'];
    $phone=$result['phone'];
    $preferred_type=$result['preferred_type'];
    $preferred_ngo_cause=$result['preferred_ngo_cause'];
}
if(isset($_POST['save-btn']))
{
    $stmt1=$pdo->prepare("update donor_master set name=?, email=?, phone=?, preferred_type=?, preferred_ngo_cause=? where id=?");
    $stmt1->bindParam(1,$_POST['name']);
    $stmt1->bindParam(2,$_POST['email']);
    $stmt1->bindParam(3,$_POST['phone']);
    $stmt1->bindParam(4,$_POST['type']);
    $stmt1->bindParam(5,$_POST['preferred_ngo_cause']);
    $stmt1->bindParam(6,$_SESSION['donor_id']);
    $stmt1->execute();
    if($stmt1)
    {
        header("Location: donor-profile-preferences.php");
        exit;
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile & Preferences</title>
    <link rel="stylesheet" href="../assets/css/donor.css">
</head>
<body>

<div class="app">

       <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="brand">HelpSphere</div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="../dashboards/donor-dashboard.php" class="nav-item">
                        <span class="icon">🏠</span>
                        <span class="label">Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="../donors/donor-profile-preferences.php" class="nav-item active">
                        <span class="icon">👤</span>
                        <span class="label">Profile & Preferences</span>
                    </a>
                </li>

                <li>
                    <a href="../donors/donor-food-selection.php" class="nav-item">
                        <span class="icon">🍱</span>
                        <span class="label">Food Donation</span>
                    </a>
                </li>

                <li>
                    <a href="../donors/donor-money-selection.php" class="nav-item">
                        <span class="icon">💰</span>
                        <span class="label">Money Donation</span>
                    </a>
                </li>

                <li>
                    <a href="../donors/donor-resource-donation.php" class="nav-item">
                        <span class="icon">📦</span>
                        <span class="label">Resource Donation</span>
                    </a>
                </li>

                <li>
                    <a href="../donors/donor-volunteer-registration.php" class="nav-item">
                        <span class="icon">🤝</span>
                        <span class="label">Volunteering</span>
                    </a>
                </li>

                <li>
                    <a href="../donors/donor-messages.php" class="nav-item">
                        <span class="icon">💬</span>
                        <span class="label">Communication</span>
                    </a>
                </li> 

                <li>
                    <a href="../../inc/logout.php" class="nav-item">
                        <span class="icon">🚪</span>
                        <span class="label">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <header class="topbar">
  <div class="user-name">Book Donation Slot</div>
  <div class="menu-toggle" id="menuToggle">☰</div>
</header>
    <form action="" method="POST">
        <!-- PERSONAL DETAILS -->
        <section>
            <div class="section-header">
                <h3>Personal Details</h3>
            </div>

            <div class="card">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" value="<?php echo $name ?>" name="name">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" value="<?php echo $email ?>" name="email">
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="number" value="<?php echo $phone ?>" name="phone">
                </div>
            </div>
        </section>

        <!-- DONATION PREFERENCES -->
        <section>
            <div class="section-header">
                <h3>Donation Preferences</h3>
            </div>

            <div class="card">
                <div class="form-group">
                    <label>Preferred Donation Types</label>
                    <select name="type">
                        <option value="Food" <?php echo ($preferred_type=="Food")?"selected":'' ?>>Food</option>
                        <option value="Money" <?php echo ($preferred_type=="Money")?"selected":'' ?>>Money</option>
                        <option value="Resources" <?php echo ($preferred_type=="Resources")?"selected":'' ?>>Resources</option>
                        <option value="Blood" <?php echo ($preferred_type=="Blood")?"selected":'' ?>>Blood</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Preferred NGOs / Causes</label>
                    <input type="text" value="<?php echo $preferred_ngo_cause ?>" name="preferred_ngo_cause">
                </div>
            </div>
        </section>
        
        <section>
            <div class="card">
                <button class="btn" type="submit" name="save-btn">
                    Save Profile & Preferences
                </button>
            </div>
        </section>
        </form>
    </main>

<script src="../assets/js/donor.js"></script>
</div>

</body>
</html>

