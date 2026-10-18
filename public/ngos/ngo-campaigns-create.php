<?php 
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();

if(isset($_POST['create-btn']))
{
  $stmt=$pdo->prepare("insert into campaigns (ngo_id, title, description, st_date, end_date, status) values (?,?,?,?,?,?)");
  $stmt->bindParam(1, $_SESSION['ngo_id']);
  $stmt->bindParam(2, $_POST['title']);
  $stmt->bindParam(3, $_POST['description']);
  $stmt->bindParam(4, $_POST['st_date']);
  $stmt->bindParam(5, $_POST['end_date']);
  $stmt->bindValue(6, "Ongoing");
  $stmt->execute();
  if($stmt)
    header("Location: ngo-campaigns.php");
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Campaign</title>
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
            <h3>Create Campaign</h3>
          </div>
        </div>

        <div class="user-name">
          NGO Administrator
        </div>
      </div>

      <!-- Campaign Form -->
      <section class="active-requests">
        <div class="section-header">
          <h3>Campaign Details</h3>
        </div>

        <div class="request-list">

        <form action="" method="POST">
          <div class="request-item">
            <label class="label">Campaign Title</label>
            <input type="text" placeholder="e.g. Winter Relief Drive" name="title">
          </div>
           <br>

          <div class="request-item">
            <label class="label">Start Date</label>
            <input type="date" name="st_date">
          </div>
          <br>

          <div class="request-item">
            <label class="label">End Date</label>
            <input type="date" name="end_date">
          </div>
          <br>

          <div class="request-item">
            <label class="label">Description</label>
            <textarea rows="7" placeholder="Brief campaign description" name="description"></textarea>
          </div>
          <br>

          <button class="menu" type="submit" name="create-btn">
            Create Campaign
          </button>
        </form>
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
