<?php
require_once __DIR__. '/../../inc/auth.php';
require_login();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Management</title>
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
                    <a href="../dashboards/beneficiary-dashboard.php" class="nav-item">
                        <span class="icon">🏠</span>
                        <span class="label">Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="../beneficiaries/beneficiary-request-management.php" class="nav-item active">
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
                <div class="user-name">Request Management</div>
            </div>
        </header>

        <!-- CREATE REQUEST -->
        <section class="active-requests">
            <div class="section-header">
                <h3>Create Assistance Request</h3>
            </div>

            <div class="request-item">
                <p>
                    Start a new request for support such as medical, education, food,
                    or emergency assistance.
                </p>

                <div class="action-row">
                    <a href="../beneficiaries/beneficiary-request-create.php" class="action-btn">
                        + Create New Request
                    </a>
                </div>
            </div>
        </section>

        <!-- MY REQUESTS -->
        <section class="active-requests">
            <div class="section-header">
                <h3>My Requests</h3>
            </div>

            <div class="request-list">

            <?php 
            $stmt=$pdo->prepare("select * from assistance_requests where beneficiary_id=? ORDER BY created_at DESC");
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
                        $id=$record['id'];
                        ?>
                    <div class="request-item">
                         <strong><?php echo $category ?> Assistance</strong>
                         <div class="status review"><?php echo $status ?></div>
                         <p><strong>Submitted: </strong><?php echo $created_at ?></p>
                         <p><strong>Request: </strong><?php echo $description ?></p>

                    <div class="action-row">
                        <?php if($status=="Pending"): ?>
                        <a href="../beneficiaries/beneficiary-request-edit.php?id=<?php echo $id ?>" class="action-btn secondary">
                            Edit
                        </a>
                        <?php endif ?>
                        <?php if($status!="Fulfilled" && $status!="Approved"): ?>
                        <a href="../beneficiaries/request-cancel.php?id=<?php echo $id ?>" class="action-btn danger">
                            Cancel
                        </a>
                        <?php endif ?>
                        <a href="../beneficiaries/beneficiary-request-timeline.php?id=<?php echo $id ?>" class="action-btn secondary">
                            Timeline
                        </a>
                        <?php if($status=="Fulfilled"): ?>
                        <a href="../beneficiaries/beneficiary-request-fulfillment.php?id=<?php echo $id ?>&category=<?php echo $category ?>" class="action-btn">
                            Fulfillment Status
                        </a>
                        <?php endif ?>
                    </div>
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
