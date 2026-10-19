<?php 
//donor-dashboard.php
require_once __DIR__. '/../../inc/auth.php';
donor_require_login();

global $count;
global $count1;
global $count2;
global $count3;
global $count4;

$stmt=$pdo->prepare("select * from donations where donor_id=?");
$stmt->bindParam(1,$_SESSION['donor_id']);
$stmt->execute();
if($stmt){
$count=$stmt->rowCount();
}

$stmt=$pdo->prepare("select * from donations where donor_id=? and target_role=?");
$stmt->bindParam(1,$_SESSION['donor_id']);
$stmt->bindValue(2,"NGO");
$stmt->execute();
if($stmt){
$count2=$stmt->rowCount();
}

$stmt=$pdo->prepare("select * from campaigns where status=?");
$stmt->bindValue(1,"Completed");
$stmt->execute();
if($stmt){
$count3=$stmt->rowCount();
}

$stmt=$pdo->prepare("select * from campaigns where status=?");
$stmt->bindValue(1,"Ongoing");
$stmt->execute();
if($stmt){
$count4=$stmt->rowCount();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard</title>
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
                    <a href="../dashboards/donor-dashboard.php" class="nav-item active">
                        <span class="icon">🏠</span>
                        <span class="label">Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="../donors/donor-profile-preferences.php" class="nav-item">
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

                <!-- <li>
                    <a href="../donors/donor-blood-registration.php" class="nav-item">
                        <span class="icon">🩸</span>
                        <span class="label">Blood Donation</span>
                    </a>
                </li> -->

                <li>
                    <a href="../donors/donor-volunteer-registration.php" class="nav-item">
                        <span class="icon">🤝</span>
                        <span class="label">Volunteering</span>
                    </a>
                </li>

                <!-- <li>
                    <a href="../donors/donor-donation-history.php" class="nav-item">
                        <span class="icon">📊</span>
                        <span class="label">Donation History</span>
                    </a>
                </li>

                <li>
                    <a href="../donors/donor-messages.php" class="nav-item">
                        <span class="icon">💬</span>
                        <span class="label">Communication</span>
                    </a>
                </li> -->

                <li>
                    <a href="../../inc/logout.php" class="nav-item">
                        <span class="icon">🚪</span>
                        <span class="label">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
<main class="main-content">

  <!-- TOPBAR -->
    <header class="topbar">
  <div class="user-name">Book Donation Slot</div>

  <!-- MOBILE MENU ICON -->
  <div class="menu-toggle" id="menuToggle">☰</div>
</header>

  <!-- DONATION SUMMARY -->
  <section>
    <div class="section-header">
      <h3>Donation Summary</h3>
    </div>

    <div class="dashboard-grid">
      <div class="card stat-card">
        <h4>Total Donations</h4>
        <h2><?php echo $count ?></h2>
      </div>

      <div class="card stat-card">
        <h4>NGOs Helped</h4>
        <h2><?php echo $count2 ?></h2>
      </div>

      <div class="card stat-card">
        <h4>Campaigns Completed</h4>
        <h2><?php echo $count3 ?></h2>
      </div>

      <div class="card stat-card">
        <h4>Campaigns Ongoing</h4>
        <h2><?php echo $count4 ?></h2>
      </div>
      
    </div>
  </section>

  <!-- IMPACT OVERVIEW -->
  <section style="margin-top:40px;">
    <div class="section-header">
      <h3>Impact Overview (Last 5 donations)</h3>
    </div>

    <div class="dashboard-grid">
      <?php 
        $stmt=$pdo->prepare("select * from donations where donor_id=? order by id DESC LIMIT 5");
        $stmt->bindParam(1,$_SESSION['donor_id']);
        $stmt->execute();
        if($stmt->rowCount()>0)
        {
             while($record=$stmt->fetch(PDO::FETCH_ASSOC))
            {
               $type=$record['donation_type'];
               $status=$record['status'];
               $target_id=$record['target_id'];
               $target_role=$record['target_role'];
               $created_at=$record['created_at'];
               $target='';
               $stmt1=$pdo->prepare("select * from users where role_id=? and role=?");
               $stmt1->bindParam(1,$target_id);
               $stmt1->bindParam(2, $target_role);
               $stmt1->execute();
               if($stmt1->rowCount()>0) 
               {
                  $res=$stmt1->fetch(PDO::FETCH_ASSOC);
                  $target=$res['name'];
               }
              ?>
              <div class="card">
                <h4><strong>Type of Donation : <?php echo $type ?></strong></h4>
                <h4><strong>Donated to: </strong><?php echo isset($target)?$target:'' ?> (<?php echo isset($target_role)?$target_role:'' ?>)</h4>
                <br>
                <span class="status success">Status: <?php echo $status ?></span>
                <div>
                <span class="status success">Date and Time of Donation: <?php echo $created_at ?></span>
                </div>
              </div>
              <?php 
            }
        }
      ?>
    </div>
  </section>
</main>

<script src="../assets/js/donor.js"></script>
</div>

</body>
</html>
