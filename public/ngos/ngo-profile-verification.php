<?php
require_once __DIR__ . '/../../inc/auth.php';
ngo_require_login();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Verification Status</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/ngo.css">
</head>

<body>

  <div class="overlay" id="overlay"></div>

  <div class="sky">
    <div class="aurora a1"></div>
    <div class="aurora a2"></div>
    <div class="aurora a3"></div>
  </div>

  <!-- <div class="moon"></div> -->

  <div class="app">

    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="brand">NGO</div>
        <button class="sidebar-close" id="sidebarClose">✕</button>
      </div>

      <div class="tagline">Management & Operations</div>

      <nav class="sidebar-nav">
        <ul>

          <li class="nav-item">
            <a href="../dashboards/ngo-dashboard.php">
              <span class="icon">📊</span>
              <span class="label">Dashboard Overview</span>
            </a>
          </li>

          <li class="nav-item active">
            <a href="../ngos/ngo-profile.php">
              <span class="icon">🧾</span>
              <span class="label">Profile & Compliance</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="../ngos/ngo-needs.php">
              <span class="icon">📌</span>
              <span class="label">Needs & Requests</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="../ngos/ngo-beneficiaries.php">
              <span class="icon">👥</span>
              <span class="label">Beneficiaries</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="../ngos/ngo-donations.php">
              <span class="icon">🎁</span>
              <span class="label">Donations & Resources</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="../ngos/ngo-campaigns.php">
              <span class="icon">📣</span>
              <span class="label">Campaigns</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="../ngos/ngo-volunteers.php">
              <span class="icon">🧑‍🤝‍🧑</span>
              <span class="label">Volunteers</span>
            </a>
          </li>

          <li class="nav-item">
            <a href="../ngos/ngo-communication.php">
              <span class="icon">💬</span>
              <span class="label">Communication</span>
            </a>
          </li>

          <li class="nav-item logout">
            <a href="../../inc/logout.php">
              <span class="icon">⎋</span>
              <span class="label">Sign Out</span>
            </a>
          </li>

        </ul>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">

      <div class="topbar">
        <div class="topbar-left">
          <button class="menu-toggle" id="menuToggle">☰</button>
          <div class="section-header">
            <h3>Verification Status</h3>
          </div>
        </div>

        <div class="user-name">
          NGO Administrator
        </div>
      </div>

      <!-- Status Overview -->
      <section class="documents">
        <div class="section-header">
          <h3>Document Verification</h3>
        </div>

        <table class="document-table">
          <tr>
            <th>Document Name</th>
            <th>Verification Status</th>
            <th>Submitted On</th>
            <th>Reviewed By</th>
          </tr>
          <?php
          $stmt = $pdo->prepare("select * from ngo_documents where ngo_id=?");
          $stmt->bindParam(1, $_SESSION['ngo_id']);
          $stmt->execute();
          if ($stmt->rowCount() > 0) {
            while ($res = $stmt->fetch(PDO::FETCH_ASSOC)) {
              $docname = isset($res['document_name']) ? $res['document_name'] : '';
              $status = isset($res['status']) ? $res['status'] : '';
              $created_at = isset($res['uploaded_at']) ? $res['uploaded_at'] : '';
          ?>
          <tr>
            <td><?php echo $docname ?></td>
            <td>
              <?php if ($status == "Uploaded"): ?>
                Under Review
              <?php elseif ($status == "Verified"): ?>
                Verified
              <?php endif ?>
            </td>
            <td><?php echo $created_at ?></td>
            <td>Compliance Authority</td>
          </tr>
          <?php
            }
          }
          ?>
        </table>
      </section>

  

        <div class="request-list">
          <?php if (isset($status)): ?>
            <?php if ($status == "Uploaded"): ?>
              <div class="request-item">
                Your organization's documents are currently being reviewed by the verification authority.
              </div>
              <div class="request-item">
                No action is required from your side at this stage unless additional information is requested.
              </div>
              <div class="request-item">
                You will be notified once the verification process is completed.
              </div>
            <?php endif ?>

            <?php if ($status == "Verified"): ?>
              <div class="request-item">
                Congratulations! Your Documents have been successfully verified and you are approved for using HelpSphere
              </div>
            <?php endif ?>
          <?php endif ?>
        </div>
      </section> -->

      <div class="footer">
        © NGO Management System
      </div>

    </main>

  </div>

  <!-- <div class="snow">
    <span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span>
  </div>

  <div class="snow-accumulation"></div> -->

  <script src="../assets/js/sidebar.js"></script>

</body>

</html>