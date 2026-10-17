<?php
require_once __DIR__. '/../../inc/auth.php';
require_login();

$request_id=isset($_GET['id'])? $_GET['id'] : 0;
if($request_id <= 0 && !isset($_POST['edit-btn']))
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
        $description=$record['description'];
        $status=$record['status'];
        $created_at=$record['created_at'];
    }
}

if(isset($_POST['edit-btn']))
{
    $stmt1=$pdo->prepare("update assistance_requests set category=?, description=? where id=?");
    $stmt1->bindParam(1, $_POST['category']);
    $stmt1->bindParam(2, $_POST['description']);
    $stmt1->bindParam(3, $request_id);
    $stmt1->execute();
    if($stmt1){
        echo "Updation Successful";
        header("Location: ../beneficiaries/beneficiary-request-management.php");
    }
    else
        echo "Failed to update assistance";
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
                        <textarea rows="7" name="description">
                        <?php echo $description ?>
                        </textarea>
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
