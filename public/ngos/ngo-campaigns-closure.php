<?php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Campaign Closure Reports</title>
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

          <li class="nav-item">
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

          <li class="nav-item active">
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

    <main class="main-content">

      <div class="topbar">
        <div class="topbar-left">
          <button class="menu-toggle" id="menuToggle">☰</button>
          <div class="section-header">
            <h3>Campaign Closure Reports</h3>
          </div>
        </div>

        <div class="user-name">
          NGO Administrator
        </div>
      </div>

      <!-- Completed Campaigns -->
      <section class="documents">
        <div class="section-header">
          <h3>Completed Campaigns</h3>
        </div>

        <table class="document-table">
          <tr>
            <th>Campaign</th>
            <th>Starting Date</th>
            <th>Ending Date</th>
            <th>Status</th>
          </tr>
          <?php 
          $stmt=$pdo->prepare("select * from campaigns where ngo_id=? and status=?");
          $stmt->bindParam(1, $_SESSION['ngo_id']);
          $stmt->bindValue(2, "Completed");
          $stmt->execute();
          if($stmt->rowCount() > 0)
          {
            while($result=$stmt->fetch(PDO::FETCH_ASSOC))
            {
              $title=$result['title'];
              $st_date=$result['st_date'];
              $end_date=$result['end_date'];
              ?>
          <tr>
            <td><?php echo $title ?></td>
            <td><?php echo $st_date ?></td>
            <td><?php echo $end_date ?></td>
            <td>Closed</td>
          </tr>
              <?php
            }
          }
          ?>
        </table>
      </section>

      <!-- Closure Notes -->
      <section class="active-requests">
        <div class="section-header">
          <h3>Closure & Reporting Notes</h3>
        </div>

        <div class="request-list">
          <div class="request-item">
            All completed campaigns must have final utilization reports.
          </div>
          <div class="request-item">
            Closure data supports donor transparency and regulatory compliance.
          </div>
          <div class="request-item">
            Archived campaigns remain accessible for audits and analytics.
          </div>
        </div>
      </section>

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
