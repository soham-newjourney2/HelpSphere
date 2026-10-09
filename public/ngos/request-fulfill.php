<?php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();

if(isset($_POST['submit-btn'])){
$stmt=$pdo->prepare("update assistance_requests set status=? where id=?");
$stmt->bindValue(1,"Fulfilled");
$stmt->bindParam(2, $_GET['request_id']);
$stmt->execute();

$stmt1=$pdo->prepare("insert into fulfillment_status (beneficiary_id, request_id, fulfilled_at, support_type, provider, delivery_method) values (?,?,?,?,?,?)");
    $stmt1->bindParam(1,$_GET['beneficiary_id']);
    $stmt1->bindParam(2, $_GET['request_id']);    
    $stmt1->bindValue(3, isset($_POST['time'])?$_POST['time']:'');
    $stmt1->bindValue(4, isset($_POST['type'])?$_POST['type']:'');
    $stmt1->bindParam(5, $_POST['provider']);
    $stmt1->bindParam(6, $_POST['delivery_method']);
    $stmt1->execute();
    if($stmt1)
        header("Location: ngo-beneficiaries-fulfillment.php");
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

          <li class="nav-item active">
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

    <main class="main-content">

      <div class="topbar">
        <div class="topbar-left">
          <button class="menu-toggle" id="menuToggle">☰</button>
          <div class="section-header">
            <h3>Enter Fulfillment Details</h3>
          </div>
        </div>

        <div class="user-name">
          NGO Administrator
        </div>
      </div>

      <!-- Campaign Form -->
      <section class="active-requests">
        <div class="section-header">
          <h3>Fulfillment Details</h3>
        </div>

        <form method="POST">
        <div class="request-list">

          <div class="request-item">
            <label class="label">Time of Fulfillment</label>
            <input type="time" placeholder="e.g. Winter Relief Drive" name="time">
          </div>

          <div class="request-item">
            <label class="label">Support Type</label>
            <input type="text" placeholder="Purpose of the campaign" name="type">
          </div>

          <div class="request-item">
            <label class="label">Provider</label>
            <input type="text" placeholder="Food kits / Amount / Supplies" name="provider">
          </div>

          <div class="request-item">
            <label class="label">Delivery Type</label>
            <input type="text" name="delivery_method">
          </div>

          <button class="menu" name="submit-btn" >
            Fulfill Request
          </button>

        </div>
        </form>
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

  <script src="sidebar.js"></script>

</body>
</html>
