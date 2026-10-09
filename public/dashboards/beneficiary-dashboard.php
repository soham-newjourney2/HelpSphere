<?php
require_once __DIR__. '/../../inc/auth.php';
require_login();
$status="Pending";
$stmt=$pdo->prepare("select * from assistance_requests where status=? and beneficiary_id=?");
$stmt->bindParam(1,$status);
$stmt->bindParam(2, $_SESSION['beneficiary_id']);
$stmt->execute();
$c=$stmt->rowCount();

$status="Approved";
$stmt=$pdo->prepare("select * from assistance_requests where status=? and beneficiary_id=?");
$stmt->bindParam(1,$status);
$stmt->bindParam(2, $_SESSION['beneficiary_id']);
$stmt->execute();
$c1=$stmt->rowCount();

// $status="Rejected";
// $stmt=$pdo->prepare("select * from assistance_requests where status=? and beneficiary_id=?");
// $stmt->bindParam(1,$status);
// $stmt->bindParam(2, $_SESSION['beneficiary_id']);
// $stmt->execute();
// $c2=$stmt->rowCount();

$status="Fulfilled";
$stmt=$pdo->prepare("select * from assistance_requests where status=? and beneficiary_id=?");
$stmt->bindParam(1,$status);
$stmt->bindParam(2, $_SESSION['beneficiary_id']);
$stmt->execute();
$c3=$stmt->rowCount();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficiary Dashboard</title>
    <link rel="stylesheet" href="../assets/css/beneficiary.css">
</head>
<body>

<div id="overlay" class="overlay"></div>

<div class="app">

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="brand">HelpSphere</div>
            <button id="sidebarClose" class="sidebar-close">✕</button>
        </div>

        <p class="tagline">Support that reaches you</p>

        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="../dashboards/beneficiary-dashboard.php" class="nav-item active">
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
                    <a href="../beneficiaries/beneficiary-profile.php" class="nav-item">
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

    <main class="main-content">

        <header class="topbar">
            <div class="topbar-left">
                <button id="menuToggle" class="menu-toggle">☰</button>
                <div class="user-name">Welcome, <?php echo isset($_SESSION['fullname'])?$_SESSION['fullname']:'' ?></div>
            </div>
        </header>

        <!-- COUNT SECTION -->
        <section class="dashboard-summary">
            <div class="summary-card">
                <div class="card-icon">📌</div>
                <div>
                    <span>Pending Requests</span>
                    <h3><?php echo $c ?></h3>
                </div>
            </div>

            <div class="summary-card">
                <div class="card-icon">⏳</div>
                <div>
                    <span>Approved Requests</span>
                    <h3><?php echo $c1 ?></h3>
                </div>
            </div>

            <!-- <div class="summary-card">
                <div class="card-icon">✅</div>
                <div>
                    <span>Rejected Requests</span>
                    <h3>
                     
                    </h3>
                </div>
            </div> -->

            <div class="summary-card">
                <div class="card-icon">📁</div>
                <div>
                    <span>Fulfilled Requests</span>
                    <h3><?php echo $c3 ?></h3>
                </div>
            </div>
        </section>

        <!-- REQUEST TIMELINE -->
        <section class="active-requests">
            <div class="section-header">
                <h3>Latest Requests</h3>
            </div>

            <div class="request-list">
                <?php 
                $stmt=$pdo->prepare("select * from assistance_requests where beneficiary_id=? order by id DESC LIMIT 5");
                $stmt->bindParam(1,$_SESSION['beneficiary_id']);
                $stmt->execute();
                if($stmt->rowCount()>0)
                {
                    while($record=$stmt->fetch(PDO::FETCH_ASSOC))
                    {
                        $category=$record['category'];
                        $description=$record['description'];
                        $status=$record['status'];
                        $created_at=$record['created_at'];
                        ?>
                    <div class="request-item">
                        Category: <strong><?php echo $category ?></strong>
                        <p><?php echo $description ?></p>
                        <p>Request <?php echo $status ?></p>
                        <p>Created At: <?php echo $created_at ?></p>
                    </div>
                        <?php
                    }
                }
                 ?>                
            </div>
        </section>

        <footer class="footer">
            © 2026 HelpSphere · Beneficiary Portal
        </footer>
    </main>

</div>

<script src="../assets/js/beneficiary-dashboard.js"></script>
</body>
</html>
