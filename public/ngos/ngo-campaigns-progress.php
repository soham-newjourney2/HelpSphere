<?php 
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();

// ─── Handle Close Campaign ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_campaign'])) {
  $campaign_id = (int) $_POST['close_campaign'];
  if ($campaign_id > 0) {
    $stmtClose = $pdo->prepare("UPDATE campaigns SET status = 'Completed' WHERE id = ? AND ngo_id = ?");
    $stmtClose->execute([$campaign_id, $_SESSION['ngo_id']]);
  }
  header("Location: ngo-campaigns-progress.php");
  exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Campaign Progress</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/ngo.css">
  <style>
    .btn-close-campaign {
      padding: 6px 16px;
      background: rgba(239, 68, 68, 0.15);
      color: #f87171;
      border: 1px solid rgba(239, 68, 68, 0.35);
      border-radius: 6px;
      font-size: 0.85rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .btn-close-campaign:hover {
      background: rgba(239, 68, 68, 0.35);
      color: #fff;
    }
  </style>
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
            <h3>Campaign Progress</h3>
          </div>
        </div>

        <div class="user-name">
          NGO Administrator
        </div>
      </div>

      <!-- Active Campaigns -->
      <section class="documents">
        <div class="section-header">
          <h3>Active Campaigns</h3>
        </div>

        <table class="document-table">
          <tr>
            <th>Campaign</th>
            <th>Description</th>
            <th>Start Date</th>
            <th>Status</th>
            <th>End Date</th>
            <th>Action</th>
          </tr>
          <?php 
          $stmt=$pdo->prepare("select * from campaigns where status=? and ngo_id=?");
          $stmt->bindValue(1, "Ongoing");
          $stmt->bindParam(2, $_SESSION['ngo_id']);
          $stmt->execute();
          if($stmt->rowCount() > 0)
          {
            while($record=$stmt->fetch(PDO::FETCH_ASSOC))
            {
              $campaign_id = (int) $record['id'];
              $name   = $record['title'];
              $desc   = $record['description'];
              $st_date  = $record['st_date'];
              $end_date = $record['end_date'];
              $status   = $record['status'];
              ?>
          <tr>
            <td><?php echo $name ?></td>
            <td><?php echo $desc ?></td>
            <td><?php echo $st_date ?></td>
            <td><?php echo $status ?></td>
            <td><?php echo $end_date ?></td>
            <td>
              <!-- campaign_id carried on the button itself — no hidden input needed -->
              <form method="POST" style="display:inline;"
                    onsubmit="return confirm('Mark this campaign as Completed?');">
                <button type="submit" name="close_campaign"
                        value="<?php echo $campaign_id; ?>"
                        class="btn-close-campaign">✔ Close</button>
              </form>
            </td>
          </tr>
              <?php
            }
          }
          ?>
        </table>
      </section>

      <!-- Progress Notes -->
      <section class="active-requests">
        <div class="section-header">
          <h3>Progress Insights</h3>
        </div>

        <div class="request-list">
          <div class="request-item">
            Campaign performance is updated automatically from donation intake records.
          </div>
          <div class="request-item">
            Monitor underperforming campaigns and adjust outreach strategies.
          </div>
          <div class="request-item">
            Completed campaigns should be closed with a final report.
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