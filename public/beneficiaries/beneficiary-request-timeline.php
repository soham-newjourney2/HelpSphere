<?php
require_once __DIR__. '/../../inc/auth.php';
require_login();

$request_id=(int)($_GET['id'] ?? 0);
$beneficiary_id=(int)($_SESSION['beneficiary_id'] ?? 0);

if($request_id <= 0)
{
    echo "Invalid Request ID";
    exit;
}

$stmt=$pdo->prepare("select * from assistance_requests where id=? and beneficiary_id=?");
$stmt->execute([$request_id, $beneficiary_id]);
$record=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$record)
{
    echo "Request not found";
    exit;
}
$cat=$record['category'];
$status=$record['status'];
$created_at=$record['created_at'];

/* Document progress: summarised across all of this beneficiary's documents */
$stmt=$pdo->prepare("select count(*) as total,
                            coalesce(sum(status='Verified'),0) as verified,
                            max(uploaded_at) as last_upload
                     from beneficiary_documents where beneficiary_id=?");
$stmt->execute([$beneficiary_id]);
$docs=$stmt->fetch(PDO::FETCH_ASSOC);

$doc_total=(int)$docs['total'];
$docs_uploaded=$doc_total>0;
$docs_verified=$doc_total>0 && (int)$docs['verified']===$doc_total;
$doc_last=$docs['last_upload'];
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

            <?php if($docs_uploaded): ?>
            <div class="timeline-item">
                <strong>Documents Uploaded</strong>
                <p><strong>Uploaded at: </strong><?php echo htmlspecialchars(date('d M Y, h:i A', strtotime($doc_last))) ?></p>
                <p>Required documents were uploaded for verification.</p>
            </div>
            <?php endif ?>

            <?php if($docs_verified): ?>
            <div class="timeline-item">
                <strong>Verification Completed</strong>
                <p>All your documents have been successfully verified</p>
            </div>
            <?php endif ?>

            <?php if($status==="Approved" || $status==="Fulfilled"): ?>
            <div class="timeline-item">
                <strong>Request Approved!</strong>
                <p>The NGO Team has approved your request</p>
            </div>
            <?php endif ?>

            <?php if($status==="Fulfilled"): ?>
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
