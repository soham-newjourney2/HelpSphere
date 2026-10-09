<?php
require_once __DIR__. '/../../inc/auth.php';
require_login();

$request_id=isset($_GET['id'])? $_GET['id'] : 0;
$category=isset($_GET['category'])? $_GET['category'] : 0;
if($request_id <= 0)
{
    echo "Invalid Request ID";
    exit;
}
else
{
    $stmt=$pdo->prepare("select * from assistance_requests where id=?");
    $stmt->bindParam(1, $request_id);
    $stmt->execute();
    if(($stmt->rowCount()>0))
    {
        $record=$stmt->fetch(PDO::FETCH_ASSOC);
        $cat=$record['category'];
        $status=$record['status'];
        $created_at=$record['created_at'];
    }
}
   $stmt2=$pdo->prepare("select * from fulfillment_status where request_id=?");
   $stmt2->bindParam(1,$request_id);
   $stmt2->execute();
   $res2=$stmt2->fetch(PDO::FETCH_ASSOC);  
   if($res2){
   $fulfilled_at=$res2['fulfilled_at'];
   $support_type=$res2['support_type'];
   $provider=$res2['provider'];
   $delivery_method=$res2['delivery_method'];  
   }

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fulfillment Status</title>
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
                <div class="user-name">Fulfillment Status</div>
            </div>
        </header>

        <!-- REQUEST SUMMARY -->
        <section class="active-requests">
            <div class="section-header">
                <h3>Request Summary</h3>
            </div>

            <div class="request-item">
                <strong><?php echo $cat ?> Assistance</strong>
                <div class="status fulfilled"><?php echo $status ?></div>
                <p>Submitted: <?php echo $created_at ?></p>
                <p>Fulfilled: <?php echo $fulfilled_at ?></p>
            </div>
        </section>

        <!-- FULFILLMENT DETAILS -->
        <section class="active-requests">
            <div class="section-header">
                <h3>Fulfillment Details</h3>
            </div>

            <div class="request-item">
                <p>
                    <strong>Type of Support:</strong><br>
                    <?php echo isset($support_type)?$support_type : '' ?>
                </p>

                <p>
                    <strong>Provider:</strong><br>
                    <?php echo isset($provider)?$provider : '' ?>
                </p>

                <p>
                    <strong>Delivery Method:</strong><br>
                    <?php echo isset($delivery_method)?$delivery_method : '' ?>
                </p>

            </div>
        </section>

        <!-- CLOSURE NOTES -->
        <section class="active-requests">
            <div class="section-header">
                <h3>Closure Notes</h3>
            </div>

            <div class="request-item">
                <p>
                    Your request has been successfully fulfilled.
                    If you have any concerns or need further assistance,
                    please contact the NGO through the Messages section.
                </p>
            </div>
        </section>

        <!-- NAVIGATION -->
        <section class="active-requests">
            <div class="request-item">
                <div class="action-row">
                    <a href="../beneficiaries/beneficiary-request-management.php" class="action-btn secondary">
                        Back to Requests
                    </a>
                    <a href="../beneficiaries/beneficiary-messages.php" class="action-btn">
                        Contact NGO
                    </a>
                </div>
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
