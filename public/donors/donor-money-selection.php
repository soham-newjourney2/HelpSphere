<?php
require_once __DIR__. '/../../inc/auth.php';
donor_require_login();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Money Donation</title>
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

        <!-- SELECTION -->
        <section>
            <div class="section-header">
                <h3>Select Donation Type</h3>
            </div>

            <div class="dashboard-grid">

                <!-- DONATE TO NGO -->
                <div class="card">
                    <h4>Donate to NGO</h4>
                    <p>
                        Support verified NGOs working across education,
                        healthcare, hunger relief, and social welfare.
                    </p>

                    <span class="status success">Verified NGOs</span>

                    <div style="margin-top:16px">
                        <a href="../donors/donor-money-ngo-list.php" class="btn">
                            View NGOs & Donate
                        </a>
                    </div>
                </div>
        </section>

    </main>

<script src="../assets/js/script.js"></script>
</div>

</body>
</html>

