<?php
require_once __DIR__. '/../../inc/auth.php';
require_login();

$request_id=isset($_GET['id'])? $_GET['id'] : 0;
$doc_status='';
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Timeline</title>
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
                <div class="user-name">Request Timeline</div>
            </div>
        </header>

        <!-- REQUEST INFO -->
        <section class="active-requests">
            <div class="section-header">
                <h3>Request Overview</h3>
            </div>

            <div class="request-item">
                <strong><?php echo $cat ?> Assistance</strong>
                <div class="status review"><?php echo $status ?></div>
                <p>Submitted on: <?php echo $created_at ?></p>
            </div>
        </section>

        <!-- TIMELINE -->
        <section class="active-requests">
            <div class="section-header">
                <h3>Progress Timeline</h3>
            </div>

            <div class="timeline">

                <div class="timeline-item">
                    <strong>Request Submitted</strong>
                    <p><strong>Submission Date and Time:</strong> <?php echo $created_at ?></p>
                    <p>Your request has been successfully submitted.</p>
                </div>

                <?php 
                $stmt1=$pdo->prepare("select * from beneficiary_documents where beneficiary_id=?");
                $stmt1->bindParam(1, $_SESSION['beneficiary_id']);
                $stmt1->execute();
                if($stmt1->rowCount()>0)
                {
                    $result=$stmt1->fetch(PDO::FETCH_ASSOC);
                    $doc_status=isset($result['status'])?$result['status']:'';
                }
                ?>
                <?php if($doc_status=="Uploaded"): ?>
                <div class="timeline-item">
                    <strong>Documents Uploaded</strong>
                    <p><strong>Uploaded at: </strong><?php  ?></p>
                    <p>Required documents were uploaded for verification.</p>
                </div>
                <?php endif ?>

                <?php if($doc_status=="Under Verification"): ?>
                <div class="timeline-item">
                    <strong>Verification In Progress</strong>
                    <p>The NGO team is currently reviewing your documents.</p>
                </div>
                <?php endif ?>

                <?php if($doc_status=="Verified"): ?>
                <div class="timeline-item">
                    <strong>Verification Completed</strong>
                    <p>All your documents have been successfully verified</p>
                </div>
                <?php endif ?>

                <?php if($status=="Approved"): ?>
                <div class="timeline-item">
                    <strong>Request Approved!</strong>
                    <p>The NGO Team has approved your request</p>
                </div>
                <?php endif ?>

                <?php if($doc_status=="Verification Failed"): ?>
                <div class="timeline-item">
                    <strong>Verification Failed</strong>
                    <p>Documents could not be verified properly. Contact NGO </p>
                </div>
                <?php endif ?>

                <?php if($status=="Rejected"): ?>
                <div class="timeline-item">
                    <strong>Request Rejected</strong>
                    <p>Your Request has been rejected</p>
                </div>
                <?php endif ?>

                <?php if($status=="Fulfilled"): ?>
                <div class="timeline-item">
                    <strong>Request Fulfilled!</strong>
                    <p>The NGO Team has fulfilled your request</p>
                </div>
                <?php endif ?>

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
