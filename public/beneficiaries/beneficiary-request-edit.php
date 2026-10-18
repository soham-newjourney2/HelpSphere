<?php
require_once __DIR__. '/../../inc/auth.php';
require_login();

$allowed_categories=['Food','Resources'];
$beneficiary_id=(int)($_SESSION['beneficiary_id'] ?? 0);
$request_id=(int)($_GET['id'] ?? 0);
$error='';

if($request_id <= 0)
{
    echo "Invalid Request ID";
    exit;
}

// Load the request, only if it belongs to the logged-in beneficiary
$stmt=$pdo->prepare("select * from assistance_requests where id=? and beneficiary_id=?");
$stmt->execute([$request_id, $beneficiary_id]);
$record=$stmt->fetch(PDO::FETCH_ASSOC);
if(!$record)
{
    echo "Request not found";
    exit;
}
$cat=$record['category'];
$description=$record['description'];
$status=$record['status'];
$created_at=$record['created_at'];

if(isset($_POST['edit-btn']))
{
    $new_cat=trim($_POST['category'] ?? '');
    $new_desc=trim($_POST['description'] ?? '');

    if(!in_array($new_cat, $allowed_categories, true))
        $error='Please select a valid category.';
    elseif($new_desc==='')
        $error='Description is required.';
    elseif($status!=='Pending')
        $error='Only pending requests can be edited.';
    else
    {
        $stmt1=$pdo->prepare("update assistance_requests set category=?, description=? where id=? and beneficiary_id=? and status='Pending'");
        $stmt1->execute([$new_cat, $new_desc, $request_id, $beneficiary_id]);
        header("Location: ../beneficiaries/beneficiary-request-management.php");
        exit;
    }
    $description=$new_desc;   // validation failed: keep what the user typed
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit / Cancel Request</title>
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
                <div class="user-name">Edit / Cancel Request</div>
            </div>
        </header>

        <!-- REQUEST SUMMARY -->
        <section class="active-requests">
            <div class="section-header">
                <h3>Request Summary</h3>
            </div>

            <div class="request-item">
                <strong><?php echo $cat ?></strong>
                <div class="status review"><?php echo isset($status)?$status:'' ?></div>
                <p>Submitted on: <?php echo $created_at ?></p>
            </div>
        </section>

        <!-- EDIT REQUEST -->
        <section class="active-requests">
            <div class="section-header">
                <h3>Edit Request</h3>
            </div>

            <?php if($error!==''): ?>
                <p class="form-error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <div class="request-item">
                <form class="request-form" action="" method="POST">

                    <div class="form-group">
                        <label>Category of Assistance</label>
                        <select name="category">
                            <option <?php echo ($cat == "Food" ? "selected" : ""); ?>>Food</option>
                            <option <?php echo ($cat == "Resources" ? "selected" : ""); ?>>Resources</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea rows="7" name="description" required><?php echo htmlspecialchars($description) ?></textarea>
                    </div>

                    <div class="form-info">
                        Editing is allowed only while the request is under review.
                    </div>

                    <div class="action-row">
                        <button class="action-btn" type="submit" name="edit-btn">Save Changes</button>
                        <a href="../beneficiaries/beneficiary-request-management.php" class="action-btn secondary">
                            Discard
                        </a>
                    </div>

                </form>
            </div>
        </section>

        <!-- CANCEL REQUEST -->
        <section class="active-requests">
            <div class="section-header">
                <h3>Cancel Request</h3>
            </div>

            <div class="request-item">
                <p>
                    Canceling a request will stop the verification process.
                    This action cannot be undone.
                </p>

                <div class="action-row">
                    <a href="beneficiary-request-management.html" class="action-btn danger">
                        Cancel Request
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
