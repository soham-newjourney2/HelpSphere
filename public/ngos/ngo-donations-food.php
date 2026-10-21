<?php
//ngo-donations-food.php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
?>
<!-- FOR REQUESTED DONATIONS : 
  Status = Donated means a donor has already donated. You have to accept it/reject it.  
-->

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Resource Allocation</title>
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

          <li class="nav-item active">
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
            <h3>Food Donations</h3>
          </div>
        </div>

        <div class="user-name">
          NGO Administrator
        </div>
      </div>

      <!-- Available Resources -->
      <section class="documents">
        <div class="section-header">
          <h3>Donations by Requests</h3>
        </div>

        <table class="document-table">
          <tr>
            <th>Donor</th>
            <th>Request Type</th>
            <th>Food Type</th>
            <th>Quantity</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
          <?php 
          $stmt=$pdo->prepare("select * from food_donation_requests where ngo_id=?");
          $stmt->bindParam(1,$_SESSION['ngo_id']);
          $stmt->execute();
          if($stmt->rowCount() > 0)
          {
            while($rec=$stmt->fetch(PDO::FETCH_ASSOC))
            {
              $name="";
              $id=$rec['id'];
              $donor_id=$rec['donor_id'];
              $request_type=$rec['request_type'];
              $food_type=$rec['food_type'];
              $quantity=$rec['quantity'];
              $status=$rec['status'];
              $stmt1=$pdo->prepare("select * from donor_master where id=?");
              $stmt1->bindParam(1, $donor_id);
              $stmt1->execute();
              if($stmt1->rowCount() > 0)
              {
                $res=$stmt1->fetch(PDO::FETCH_ASSOC);
                $name=$res['name'];
              }
              ?>
          <tr>
            <td><?php echo isset($name)?$name:'' ?></td>
            <td><?php echo $request_type ?></td>
            <td><?php echo $food_type ?></td>
            <td><?php echo $quantity ?></td>
            <td><?php echo $status ?></td>
            <?php if($status=="Donated" && $request_type=="Scheduled"): ?>
            <td><?php echo action_button('donation-accept.php', ['type'=>'Scheduled','donation'=>'requested','id'=>$id], 'Accept') ?></td>
            <?php endif ?>
            <?php if($status=="Accepted" && $request_type=="Scheduled"): ?>
            <td><?php echo action_button('donation-pickup.php', ['type'=>'Scheduled','donation'=>'requested','id'=>$id], 'Pick Up') ?></td>
            <?php endif ?>
            <?php if($status=="Picked Up" && $request_type=="Scheduled"): ?>
              <td>None</td>
            <?php endif ?>
          </tr>
              <?php
            }
          }
          ?>
        </table>
      </section>

      <section class="documents">
        <div class="section-header">
          <h3>View All Voluntary Scheduled Food Donations</h3>
          <!-- Donation Issued by any Donor => Status = Donated. Waiting for Acceptance
           Status = Accepted means any NGO [target_id] has accepted it. Pickup is Left
           Status = Rejected is NOT possible. If you reject any donation, all other NGOs except you will still be able to see it and even accept if they want to
           Status = Picked Up means acceptor NGO has Picked Up the Donation from Donor
           You can only see here donations that have NOT yet been Accepted by any NGO          
          -->
        </div>

        <table class="document-table">
          <tr>
            <th>Donor</th>
            <th>Donated At</th>
            <th>Food Type</th>
            <th>Quantity</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
          <?php 
          $stmt=$pdo->prepare("select * from food_donations where target_type=? and status = ? and is_requested_by_ngo = ?");
          $stmt->bindValue(1, "NGO");
          $stmt->bindValue(2, "Donated");
          $stmt->bindValue(3, "No");
          $stmt->execute();
          if($stmt->rowCount() > 0)
          {
            while($rec=$stmt->fetch(PDO::FETCH_ASSOC))
            {
              $name='';
              $id=$rec['id'];
              $donor_id=$rec['donor_id'];
              $donated_at=$rec['created_at'];
              $food_type=$rec['food_type'];
              $quantity=$rec['quantity'];
              $status=$rec['status'];
              $stmt1=$pdo->prepare("select * from donor_master where id=?");
              $stmt1->bindParam(1, $donor_id);
              $stmt1->execute();
              if($stmt1->rowCount() > 0)
              {
                $res=$stmt1->fetch(PDO::FETCH_ASSOC);
                $name=$res['name'];
              }
              ?>
          <tr>
            <td><?php echo $name ?></td>
            <td><?php echo $donated_at ?></td>
            <td><?php echo $food_type ?></td>
            <td><?php echo $quantity ?></td>
            <td><?php echo $status ?></td>
            <td><?php echo action_button('donation-accept.php', ['type'=>'Scheduled','donation'=>'voluntary','id'=>$id], 'Accept') ?></td>
          </tr>
              <?php
            }
          }
          ?>
          <?php 
          $stmt=$pdo->prepare("select * from food_donations where target_type=? and status = ? and target_id=?");
          $stmt->bindValue(1, "NGO");
          $stmt->bindValue(2, "Accepted");
          $stmt->bindParam(3, $_SESSION['ngo_id']);
          $stmt->execute();
          if($stmt->rowCount() > 0)
          {
            while($rec=$stmt->fetch(PDO::FETCH_ASSOC))
            {
              $name='';
              $id1=$rec['id'];
              $donor_id=$rec['donor_id'];
              $donated_at=$rec['created_at'];
              $food_type=$rec['food_type'];
              $quantity=$rec['quantity'];
              $status=$rec['status'];
              $stmt1=$pdo->prepare("select * from donor_master where id=?");
              $stmt1->bindParam(1, $donor_id);
              $stmt1->execute();
              if($stmt1->rowCount() > 0)
              {
                $res=$stmt1->fetch(PDO::FETCH_ASSOC);
                $name=$res['name'];
              }
              ?>
          <tr>
            <td><?php echo $name ?></td>
            <td><?php echo $donated_at ?></td>
            <td><?php echo $food_type ?></td>
            <td><?php echo $quantity ?></td>
            <td><?php echo $status ?></td>
            <td><?php echo action_button('donation-pickup.php', ['type'=>'Scheduled','donation'=>'voluntary','id'=>$id1], 'Pick Up') ?></td>
          </tr>
              <?php
            }
          }
          ?>
          <?php 
          $stmt=$pdo->prepare("select * from food_donations where target_type=? and status = ? and target_id=? and donation_type=?");
          $stmt->bindValue(1, "NGO");
          $stmt->bindValue(2, "Picked Up");
          $stmt->bindParam(3, $_SESSION['ngo_id']);
          $stmt->bindValue(4, "Scheduled");
          $stmt->execute();
          if($stmt->rowCount() > 0)
          {
            while($rec=$stmt->fetch(PDO::FETCH_ASSOC))
            {
              $name='';
              $id1=$rec['id'];
              $donor_id=$rec['donor_id'];
              $donated_at=$rec['created_at'];
              $food_type=$rec['food_type'];
              $quantity=$rec['quantity'];
              $status=$rec['status'];
              $stmt1=$pdo->prepare("select * from donor_master where id=?");
              $stmt1->bindParam(1, $donor_id);
              $stmt1->execute();
              if($stmt1->rowCount() > 0)
              {
                $res=$stmt1->fetch(PDO::FETCH_ASSOC);
                $name=$res['name'];
              }
              ?>
          <tr>
            <td><?php echo $name ?></td>
            <td><?php echo $donated_at ?></td>
            <td><?php echo $food_type ?></td>
            <td><?php echo $quantity ?></td>
            <td><?php echo $status ?></td>
            <td>None</td>
          </tr>
              <?php
            }
          }
          ?>
        </table>
      </section>

      <section class="documents">
        <div class="section-header">
          <h3>All Immediate Donations to You</h3>
        </div>

        <table class="document-table">
          <tr>
            <th>Donor</th>
            <th>Request Type</th>
            <th>Food Type</th>
            <th>Quantity</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
          <?php 
          $stmt=$pdo->prepare("select * from food_donations where target_id=? and donation_type=? and target_type=?");
          $stmt->bindParam(1,$_SESSION['ngo_id']);
          $stmt->bindValue(2, "Immediate");
          $stmt->bindValue(3, "NGO");
          $stmt->execute();
          if($stmt->rowCount() > 0)
          {
            while($rec=$stmt->fetch(PDO::FETCH_ASSOC))
            {
              $name="";
              $id=$rec['id'];
              $donor_id=$rec['donor_id'];
              $request_type=$rec['donation_type'];
              $food_type=$rec['food_type'];
              $quantity=$rec['quantity'];
              $status=$rec['status'];
              $stmt1=$pdo->prepare("select * from donor_master where id=?");
              $stmt1->bindParam(1, $donor_id);
              $stmt1->execute();
              if($stmt1->rowCount() > 0)
              {
                $res=$stmt1->fetch(PDO::FETCH_ASSOC);
                $name=$res['name'];
              }
              ?>
          <tr>
            <td><?php echo isset($name)?$name:'' ?></td>
            <td><?php echo $request_type ?></td>
            <td><?php echo $food_type ?></td>
            <td><?php echo $quantity ?></td>
            <td><?php echo $status ?></td>
            <?php if($status=="Donated" && $request_type=="Immediate"): ?>
            <td><?php echo action_button('donation-pickup.php', ['type'=>'Immediate','id'=>$id], 'Pick Up') ?></td>
            <?php endif ?>
            <?php if($status=="Picked Up" && $request_type=="Immediate"): ?>
              <td>None</td>
            <?php endif ?>
          </tr>
          <!-- Immediate Donation status Donated means you have already Accepted the Donation. Only Pickup is left. Immediate Donation cannot be Requested -->
              <?php
            }
          }
          ?>
        </table>
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
