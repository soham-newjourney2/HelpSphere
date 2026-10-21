<?php 
//ngo-needs-status.php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();    
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Need Status Tracking</title>
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

          <li class="nav-item">
            <a href="../ngos/ngo-profile.php">
              <span class="icon">🧾</span>
              <span class="label">Profile & Compliance</span>
            </a>
          </li>

          <li class="nav-item active">
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
            <h3>Need Status Tracking</h3>
          </div>
        </div>

        <div class="user-name">
          NGO Administrator
        </div>
      </div>

      <!-- Status Overview -->
      <section class="documents">
        <div class="section-header">
          <h3>All Posted Requests</h3>
        </div>

        <table class="document-table">
          <tr>
            <th>Need</th>
            <th>Category</th>
            <th>Status</th>
          </tr>
          <?php 
          $stmt=$pdo->prepare("select * from food_donation_requests where ngo_id=?");
          $stmt->bindParam(1, $_SESSION['ngo_id']);
          $stmt->execute();
          if($stmt->rowCount() > 0)
          {
            while($res=$stmt->fetch(PDO::FETCH_ASSOC))
            {
              $id=$res['id'];
              $description=$res['description'];
              $urgency=$res['urgency'];
              $status=$res['status'];
              ?>
          <tr>
            <td><?php echo htmlspecialchars($description) ?></td>
            <td>Food</td>
            <td><?php echo $status ?></td>
          </tr>
              <?php
            }
          }
          ?>
          <br>
          <br>
        </table>
      </section>

      <!-- Status Meaning -->
      <section class="active-requests">
        <div class="section-header">
          <h3>Status Definitions</h3>
        </div>

        <div class="request-list">
          <div class="request-item">
            <strong>Pending</strong> — Need has been posted and is awaiting allocation or support.
          </div>
          <div class="request-item">
            <strong>Donated</strong> — Donors have fulfilled the need
          </div>
          <div class="request-item">
            <strong>Picked Up</strong> — Donation has been picked up (For Food)
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
