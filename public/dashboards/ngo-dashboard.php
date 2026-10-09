<?php 
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
global $c;
global $c1;
global $c2;

$stmt=$pdo->prepare("select * from assistance_requests where status=?");
$stmt->bindValue(1,"Pending");
$stmt->execute();
if($stmt)
    $c=$stmt->rowCount(); 

$stmt1=$pdo->prepare("select * from donations where target_id=? and target_role=?");
$stmt1->bindParam(1, $_SESSION['ngo_id']);
$stmt1->bindValue(2, "NGO");
$stmt1->execute();
if($stmt1)
    $c1=$stmt1->rowCount();

$stmt2=$pdo->prepare("select * from donations where target_id=? and target_role=? and status=?");
$stmt2->bindParam(1, $_SESSION['ngo_id']);
$stmt2->bindValue(2, "NGO");
$stmt2->bindValue(3,"Accepted");
$stmt2->execute();
if($stmt2)
    $c2=$stmt2->rowCount();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>NGO Dashboard</title>
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
            <a href="../dashboards/ngo-dashboard.php active">
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


    <!-- Main Content -->
    <main class="main-content">

      <div class="topbar">
        <div class="topbar-left">
          <button class="menu-toggle" id="menuToggle">☰</button>
          <div class="section-header">
            <h3>NGO Dashboard</h3>
          </div>
        </div>

        <div class="user-name">
          NGO Admin
        </div>
      </div>

      <!-- Dashboard Summary -->
      <section class="dashboard-summary">

        <div class="summary-card">
          <div class="card-icon">📌</div>
          <div>
            <div class="label">Pending Requests</div>
            <h3><?php echo $c ?></h3>
          </div>
        </div>

        <div class="summary-card">
          <div class="card-icon">💰</div>
          <div>
            <div class="label">Total Donations</div>
            <h3><?php echo $c1 ?></h3>
          </div>
        </div>

        <div class="summary-card">
          <div class="card-icon">📦</div>
          <div>
            <div class="label">Donations Pending for Pickp</div>
            <h3><?php echo $c2 ?></h3>
          </div>
        </div>

      </section>

      <!-- Active Needs -->
      <section class="active-requests">
        <div class="section-header">
          <h3>Pending Requests (Latest 5)</h3>
        </div>

        <?php 
        $stmt=$pdo->prepare("select * from assistance_requests where status=? order by id desc limit 5");
        $stmt->bindValue(1,"Pending");
        $stmt->execute();
        if($stmt->rowCount() > 0)
        {
            while($result=$stmt->fetch(PDO::FETCH_ASSOC))
            {
                $description=$result['description'];
                $created_at=$result['created_at'];
                $ben_id=$result['beneficiary_id'];
                $stmt1=$pdo->prepare("select * from beneficiary_master where id=?");
                $stmt1->bindValue(1,$ben_id);
                $stmt1->execute();
                if($stmt1->rowCount() > 0)
                {
                   $res=$stmt1->fetch(PDO::FETCH_ASSOC);
                   $name=$res['name'];
                }
                if(isset($name))
                  {
          ?>
        <div class="request-list">
            <div class="request-item"><?php echo $description ?> <br><strong>Created at: </strong><?php echo $created_at ?> <br><strong>Created by: </strong><?php echo $name ?></div><br>
        </div>
                <?php
                }
            }
        }
        ?>
       
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
  <script src="../assets/js/donor.js"></script>

</body>
</html>
