<?php
require_once __DIR__. '/../../inc/auth.php';
require_login();

if(isset($_POST['submit-btn']))
{
    $cat=isset($_POST['category'])?$_POST['category']:'';
    $desc=isset($_POST['description'])?$_POST['description']:'';
    $stmt=$pdo->prepare("insert into assistance_requests (beneficiary_id, category, description) values (?,?,?)");
    $stmt->bindParam(1,$_SESSION['beneficiary_id']);
    $stmt->bindParam(2, $cat);
    $stmt->bindParam(3, $desc);
    $stmt->execute();
    if($stmt){
        header("Location: ../beneficiaries/beneficiary-request-management.php");
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assistance Request</title>
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
                <div class="user-name">Create Assistance Request</div>
            </div>
        </header>

        <section class="active-requests">
            <div class="section-header">
                <h3>Request Details</h3>
            </div>

            <div class="request-item">

                <form class="request-form" action="" method="POST">

                    <div class="form-group">
                        <label>Category of Assistance</label>
                        <select name="category" required>
                            <option>Select assistance category</option>
                            <option>Food</option>
                            <option>Resources</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Brief Description</label>
                        <textarea rows="7" placeholder="Explain your situation briefly" name="description" required></textarea>
                    </div>

                    <div class="form-info">
                        Supporting documents can be uploaded after submitting the request.
                    </div>

                    <div class="action-row">
                        <button class="action-btn" type="submit" name="submit-btn">Submit Request</button>
                        <a href="../beneficiaries/beneficiary-request-management.php" class="action-btn secondary" name="cancel-btn">
                            Cancel
                        </a>
                    </div>

                </form>

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
