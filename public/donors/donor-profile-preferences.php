<?php
//donor-profile-preferences.php
require_once __DIR__. '/../../inc/auth.php';
donor_require_login();

$stmt=$pdo->prepare("select * from donor_master where id=?");
$stmt->execute([(int)($_SESSION['donor_id'] ?? 0)]);
$result=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$result)
{
    echo "Donor profile not found";
    exit;
}
$name=$result['name'];
$email=$result['email'];
$phone=$result['phone'];
$preferred_type=$result['preferred_type'];
$preferred_ngo_cause=$result['preferred_ngo_cause'];
$error='';
$allowed_types=['Food','Money','Resources','Blood'];
if(isset($_POST['save-btn']))
{
    $donor_id=(int)($_SESSION['donor_id'] ?? 0);
    $new_name=trim($_POST['name'] ?? '');
    $new_email=trim($_POST['email'] ?? '');
    $new_phone=trim($_POST['phone'] ?? '');
    $new_type=$_POST['type'] ?? '';
    $new_cause=trim($_POST['preferred_ngo_cause'] ?? '');

    if(!in_array($new_type, $allowed_types, true))
    {
        $error='Please choose a valid donation type.';
    }
    else
    {
        try
        {
            $pdo->beginTransaction();

            $stmt1=$pdo->prepare("update donor_master set name=?, email=?, phone=?, preferred_type=?, preferred_ngo_cause=? where id=?");
            $stmt1->execute([$new_name, $new_email, $new_phone, $new_type, $new_cause, $donor_id]);

            $stmt2=$pdo->prepare("update users set name=?, email=? where role_id=? and role=?");
            $stmt2->execute([$new_name, $new_email, $donor_id, 'Donor']);

            $pdo->commit();
            header("Location: donor-profile-preferences.php");
            exit;
        }
        catch(PDOException $e)
        {
            if($pdo->inTransaction()) $pdo->rollBack();
            error_log('donor-profile-preferences: '.$e->getMessage());
            $error = ($e->getCode()==='23000')
                ? 'That email address is already in use.'
                : 'Could not save changes. Please try again.';
        }
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
<?php if($error!==''): ?>
<div style="color:#f87171;margin:0 0 16px"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
    <form action="" method="POST">
        <!-- PERSONAL DETAILS -->
        <section>
            <div class="section-header">
                <h3>Personal Details</h3>
            </div>

            <div class="card">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" value="<?php echo htmlspecialchars((string)$name) ?>" name="name">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" value="<?php echo htmlspecialchars((string)$email) ?>" name="email">
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" value="<?php echo htmlspecialchars((string)$phone) ?>" name="phone">
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
                    <input type="text" value="<?php echo htmlspecialchars((string)$preferred_ngo_cause) ?>" name="preferred_ngo_cause">
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

