<?php 
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();

if(isset($_POST['save-btn']))
{
  $stmt=$pdo->prepare("update ngo_master set name=?, email=?, focus=?, description=?, address=?, phone=?  where id=?");
  $stmt->bindParam(1,$_POST['name']);
  $stmt->bindParam(2,$_POST['email']);
  $stmt->bindParam(3,$_POST['focus']);
  $stmt->bindParam(4,$_POST['description']);
  $stmt->bindParam(5,$_POST['address']);
  $stmt->bindParam(6,$_POST['phone']);
  $stmt->bindParam(7,$_SESSION['ngo_id']);
  $stmt->execute();
  $stmt1=$pdo->prepare("update users set name=?, email=? where role_id=? and role=?");
  $stmt1->bindParam(1,$_POST['name']);
  $stmt1->bindParam(2,$_POST['email']);
  $stmt1->bindParam(3,$_SESSION['ngo_id']);
  $stmt1->bindValue(4,"NGO");
  $stmt1->execute();
  if($stmt) 
    header("Location: ngo-profile-registration.php");
  exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Registration Details</title>
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

          <li class="nav-item  active">
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

    <!-- Main -->
    <main class="main-content">

      <div class="topbar">
        <div class="topbar-left">
          <button class="menu-toggle" id="menuToggle">☰</button>
          <div class="section-header">
            <h3>Registration Details</h3>
          </div>
        </div>

        <div class="user-name">
          NGO Admin
        </div>
      </div>

      <!-- View Section -->
      <section class="documents">
        <div class="section-header">
          <h3>Current Registration Information</h3>
        </div>

        <table class="document-table">
          <?php 
          $stmt=$pdo->prepare("select * from ngo_master where id=?");
          $stmt->bindParam(1, $_SESSION['ngo_id']);
          $stmt->execute();
          if($stmt->rowCount()>0)
          {
            $result=$stmt->fetch(PDO::FETCH_ASSOC);
            $name=$result['name'];
            $reg_no=$result['registration_no'];
            $address=$result['address'];
            $date=$result['registration_date'];
            $email=$result['email'];
            $focus=$result['focus'];
            $description=$result['description'];
            $phone=$result['phone'];
          }
          ?>
          <tr>
            <th>NGO Name</th>
            <td><?php echo $name ?></td>
          </tr>
          <tr>
            <th>NGO Email</th>
            <td><?php echo $email ?></td>
          </tr>
          <tr>
            <th>NGO Focus</th>
            <td><?php echo $focus ?></td>
          </tr>
          <tr>
            <th>Description</th>
            <td><?php echo $description ?></td>
          </tr>
          <tr>
            <th>Registration Number</th>
            <td><?php echo $reg_no ?></td>
          </tr>
          <tr>
            <th>Registered Address</th>
            <td><?php echo $address ?></td>
          </tr>
          <tr>
            <th>Date of Registration</th>
            <td><?php echo $date ?></td>
          </tr>
          <tr>
            <th>Phone Number</th>
            <td><?php echo $phone ?></td>
          </tr>
        </table>
      </section>

      <!-- Edit Section -->
      <section class="documents">
        <div class="section-header">
          <h3>Edit Registration Details</h3>
        </div>

        <div class="request-list">

        <form action="" method="POST">
          <div class="request-item">
            <label class="label">NGO Name</label>
            <input type="text" value="<?php echo $name ?>" name="name">
          </div>

          <div class="request-item">
            <label class="label">NGO Email</label>
            <input type="text" value="<?php echo $email ?>" name="email">
          </div>

          <div class="request-item">
            <label class="label">NGO Focus</label>
            <input type="text" value="<?php echo $focus ?>" name="focus">
          </div>

          <div class="request-item">
            <label class="label">Description</label>
            <input type="text" value="<?php echo $description ?>" name="description">
          </div>

          <div class="request-item">
            <label class="label">Registration Number</label>
            <input type="text" value="<?php echo $reg_no ?>" readonly>
          </div>

           <div class="request-item">
            <label class="label">Registered Address</label>
            <input type="text" value="<?php echo $address ?>" name="address">
          </div>

          <div class="request-item">
            <label class="label">Phone Number</label>
            <input type="text" value="<?php echo $phone ?>" name="phone">
          </div>

          <button type="submit" name="save-btn">
            Save Changes
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
