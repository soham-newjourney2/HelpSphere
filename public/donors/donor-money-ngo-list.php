<?php
require_once __DIR__. '/../../inc/auth.php';
donor_require_login();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate to NGO</title>
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
                    <a href="../donors/donor-money-selection.php" class="nav-item active">
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

        <!-- INFO -->
        <section>
            <div class="section-header">
                <h3>Verified NGOs</h3>
            </div>

            <div class="card">
                <p>
                    Browse verified NGOs and support causes you care about.
                    All listed organizations have completed platform verification.
                </p>
            </div>
            <br>
        </section>
 
        <!-- NGO LIST -->
        <section>
            <div class="dashboard-grid">
            <?php 
            $stmt=$pdo->prepare("select * from ngo_master");
            $stmt->execute();
            if($stmt->rowCount() > 0)
            {
                while($rec=$stmt->fetch(PDO::FETCH_ASSOC))
                {
                    $name=$rec['name'];
                    $focus=$rec['focus'];
                    $status=$rec['status'];
                    $ngo_id=$rec['id'];
                    $phone=$rec['phone'];
                    ?>
                <?php if($status=="Verified"): ?>
                <div class="card">
                    <h4><?php echo $name ?></h4>
                    <p>Focus: <?php echo $focus ?></p>
                    <span class="status success">Verified</span>

                    <h3><strong>Contact Number:  </strong><?php echo $phone ?></h3>
                </div>
                <?php endif ?>
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

