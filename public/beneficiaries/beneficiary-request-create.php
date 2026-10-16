<?php
require_once __DIR__ . '/../../inc/auth.php';
require_login();

$allowed_categories = ['Food', 'Resources'];
$error = '';

if (isset($_POST['submit-btn'])) {
    $cat  = trim($_POST['category'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (!in_array($cat, $allowed_categories, true)) {
        $error = 'Please select a valid category.';
    } elseif ($desc === '') {
        $error = 'Description is required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO assistance_requests (beneficiary_id, category, description) VALUES (?,?,?)");
        if ($stmt->execute([$_SESSION['beneficiary_id'], $cat, $desc])) {
            header("Location: ../beneficiaries/beneficiary-request-management.php");
            exit;
        }
        $error = 'Could not save your request. Please try again.';
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

            <?php if ($error !== ''): ?>
            <p class="form-error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <div class="request-item">

                <form class="request-form" action="" method="POST">

                    <div class="form-group">
                        <label>Category of Assistance</label>
                        <select name="category" required>
                            <option value="" disabled selected>Select assistance category</option>
                            <option value="Food">Food</option>
                            <option value="Resources">Resources</option>
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
