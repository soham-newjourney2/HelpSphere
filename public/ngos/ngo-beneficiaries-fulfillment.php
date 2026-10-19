<?php 
//ngo-beneficiaries-fulfillment.php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
?>

<!DOCTYPE html>
<html lang="en">
<head> 
  <meta charset="UTF-8">
  <title>Fulfillment Updates</title>
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

          <li class="nav-item active">
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
            <h3>Fulfillment Updates</h3>
          </div>
        </div>

        <div class="user-name">
          NGO Administrator
        </div>
      </div>

      <!-- Fulfillment Status -->
      <section class="documents">
        <div class="section-header">
          <h3>Fulfillment Records</h3>
        </div>

        <table class="document-table">
          <tr>
            <th>Beneficiary</th>
            <th>Need</th>
            <th>Status</th>
            <th>Created At: </th>
            <th>Action</th>
          </tr>
          <?php
          $stmt=$pdo->prepare("select * from assistance_requests");
          $stmt->execute();
          if($stmt->rowCount()>0)
          {
            while($rec=$stmt->fetch(PDO::FETCH_ASSOC))
            {
              $category=$rec['category'];
              $id=$rec['beneficiary_id'];
              $request_id=$rec['id'];
              $description=$rec['description'];
              $status=$rec['status'];
              $created_at=$rec['created_at'];
              $name='—';
              $stmt1=$pdo->prepare("select * from beneficiary_master where id=?");
              $stmt1->bindParam(1, $id);
              $stmt1->execute();
              if($stmt1->rowCount() > 0)
              {
                $res=$stmt1->fetch(PDO::FETCH_ASSOC);
                $name=$res['name'];
                $address=$res['address'];
              }
              ?>
              <?php if($status=="Approved" && $rec['ngo_id']==$_SESSION['ngo_id']): ?>
          <tr>
            <td><?php echo $name ?></td>
            <td><?php echo $description ?></td>
            <td><?php echo $status ?></td>
            <td><?php echo $created_at ?></td>
            <td><a href="request-fulfill.php?beneficiary_id=<?php echo $id ?>&request_id=<?php echo $request_id ?>&category=<?php echo $category ?>">Fulfill</a></td>
          </tr>
          <?php endif ?>
              <?php 
            }
         } ?>
        </table>
      </section>

      <!-- Update Guidance -->
      <section class="active-requests">
        <div class="section-header">
          <h3>Update Guidelines</h3>
        </div>

        <div class="request-list">
          <div class="request-item">
            Ensure delivery confirmation is recorded accurately.
          </div>
          <div class="request-item">
            Update status promptly after each fulfillment step.
          </div>
          <div class="request-item">
            Maintain transparency for audit and reporting purposes.
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
