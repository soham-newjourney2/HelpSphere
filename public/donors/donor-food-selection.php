<?php
//donor-food-selection.php
require_once __DIR__. '/../../inc/auth.php';
donor_require_login();
?>
 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Donation</title>
    <link rel="stylesheet" href="../assets/css/donor.css">

</head>
<body>

<div class="app">

     <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="brand">HelpSphere</div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="../dashboards/donor-dashboard.php" class="nav-item">
                        <span class="icon">🏠</span>
                        <span class="label">Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="../donors/donor-profile-preferences.php" class="nav-item">
                        <span class="icon">👤</span>
                        <span class="label">Profile & Preferences</span>
                    </a>
                </li>

                <li>
                    <a href="../donors/donor-food-selection.php" class="nav-item active">
                        <span class="icon">🍱</span>
                        <span class="label">Food Donation</span>
                    </a>
                </li>

                <li>
                    <a href="../donors/donor-money-selection.php" class="nav-item">
                        <span class="icon">💰</span>
                        <span class="label">Money Donation</span>
                    </a>
                </li>

                <li>
                    <a href="../donors/donor-resource-donation.php" class="nav-item">
                        <span class="icon">📦</span>
                        <span class="label">Resource Donation</span>
                    </a>
                </li>

                <li>
                    <a href="../donors/donor-volunteer-registration.php" class="nav-item">
                        <span class="icon">🤝</span>
                        <span class="label">Volunteering</span>
                    </a>
                </li>

                <li>
                    <a href="../donors/donor-messages.php" class="nav-item">
                        <span class="icon">💬</span>
                        <span class="label">Communication</span>
                    </a>
                </li>

                <li>
                    <a href="../../inc/logout.php" class="nav-item">
                        <span class="icon">🚪</span>
                        <span class="label">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
    <!-- MAIN CONTENT -->
    <main class="main-content">

         <header class="topbar">
  <div class="user-name">Book Donation Slot</div>
  <div class="menu-toggle" id="menuToggle">☰</div>
</header>

        <!-- SELECTION -->
        <section>
            <div class="section-header">
                <h3>Select Donation</h3>
            </div>
            
            <button class="btn secondary" id="NGOrequestBtn">
            View Donation Requests by NGO
            </button>

            <button class="btn secondary" id="donateBtn">
            View all NGOs 
            </button>

            <a href="../donors/donor-food-immediate.php?donation=voluntary&donor_id=<?php echo htmlspecialchars($_SESSION['donor_id']); ?>" class="btn">
            Immediate Donation
            </a>

            
            <a href="../donors/donor-food-scheduled.php?donation=voluntary" class="btn">
            Scheduled Donation
            </a>
           

             <button class="btn secondary" id="historyBtn">
            View Donation History 
            </button>

            <br><br>

            <div class="dashboard-grid-one" id="history" style="display: none;">

                <div class="section-header">
                <h3>All Donations</h3>
                </div>
                    
                <?php 
                $stmt=$pdo->prepare("select * from food_donations where donor_id=?");
                $stmt->bindParam(1,$_SESSION['donor_id']);
                $stmt->execute();
                if($stmt->rowCount() > 0)
                {
                    while($result=$stmt->fetch(PDO::FETCH_ASSOC))
                    {
                        $food_type=$result['food_type'];
                        $datetime=$result['created_at'];
                        $status=$result['status'];
                        ?>
                    <div class="card">
                    <p>Type of Food Donated: <?php echo $food_type ?></p>
                    <p>Date and Time of Donation: <?php echo $datetime ?></p>
                    <br>
                    <p><strong>Status: <?php echo $status ?></strong> </p>
                    </div>
                    <br>
                        <?php
                    }
                }
                ?>                
                
                <div class="section-header">
                <h3>Donations for NGO Requests</h3>
                </div>
                    
                <?php 
                $stmt=$pdo->prepare("select * from food_donation_requests where donor_id=?");
                $stmt->bindParam(1,$_SESSION['donor_id']);
                $stmt->execute();
                if($stmt->rowCount() > 0)
                {
                    while($result=$stmt->fetch(PDO::FETCH_ASSOC))
                    {
                        $ngo_name='';
                        $food_type=$result['food_type'];
                        $status=$result['status'];
                        $request_type=$result['request_type'];
                        $ngo_id=$result['ngo_id'];
                        $datetime=$result['donated_at'];

                        $stmt1=$pdo->prepare("select * from ngo_master where id=?");
                        $stmt1->bindParam(1,$ngo_id);
                        $stmt1->execute();
                        if($stmt1->rowCount()>0)
                        {
                            $result=$stmt1->fetch(PDO::FETCH_ASSOC);
                            $ngo_name=isset($result['name'])?$result['name']:'';
                        }
                        ?>
                    <div class="card">
                    <p>Type of Request: <?php echo $request_type ?></p>
                    <p>Type of Food Donated: <?php echo $food_type ?></p>
                    <p>Donated To : <?php echo $ngo_name ?></p>
                    <p>Date and Time of Donation: <?php echo $datetime ?></p>
                    <br>
                    <p><strong>Status: <?php echo $status ?></strong> </p>
                    </div>
                    <br>
                        <?php
                    }
                }
                ?>

                </div>

            <div class="dashboard-grid-one" id="donate" style="display: none;">

            <div class="section-header">
                <h3>Verified NGOs</h3>
            </div>

            <div class="card">
                <p>
                    Browse verified NGOs and support causes you care about.
                    All listed organizations have completed platform verification.
                </p>
            </div>
            <br>

            <?php 
            $stmt=$pdo->prepare("select * from ngo_master");
            $stmt->execute();
            if($stmt->rowCount() > 0)
            {
                while($rec=$stmt->fetch(PDO::FETCH_ASSOC))
                {
                    $name=$rec['name'];
                    $focus=$rec['focus'];
                    $status=$rec['status'];
                    $ngo_id=$rec['id'];
                    $phone=$rec['phone'];
                    $address=$rec['address'];
                    ?>
                <?php if($status=="Verified"): ?>
                <div class="card">
                    <h4><?php echo $name ?></h4>
                    <p>Focus: <?php echo $focus ?></p>
                    <p>Address: <?php echo $address ?></p>
                    <p>Phone: <?php echo $phone ?></p>

                    <span class="status success">Verified</span>

                </div>
                <br>
                <?php endif ?>
                    <?php

                }
            }
            ?>           

            </div>

            <div class="dashboard-grid" id="NGORequest" style="display: none;">

               <?php 
               $stmt=$pdo->prepare("select * from food_donation_requests");
               $stmt->execute();
               if($stmt->rowCount()>0)
               {
                  while($rec=$stmt->fetch(PDO::FETCH_ASSOC))
                  {
                    $ngo_name=''; 
                    $id=isset($rec['id'])?$rec['id']:0;
                    $request_type=isset($rec['request_type'])?$rec['request_type']:'';
                    $ngo_id=isset($rec['ngo_id'])?$rec['ngo_id']:0;
                    $urgency=isset($rec['urgency'])?$rec['urgency']:'';
                    $description=isset($rec['description'])?$rec['description']:'';
                    $status=isset($rec['status'])?$rec['status']:'';
                    $stmt1=$pdo->prepare("select * from ngo_master where id=?");
                    $stmt1->bindParam(1,$ngo_id);
                    $stmt1->execute();
                    if($stmt1->rowCount()>0)
                    {
                        $result=$stmt1->fetch(PDO::FETCH_ASSOC);
                        $ngo_name=isset($result['name'])?$result['name']:'';
                    }
                    ?>
                    <div class="card">
                    <h4><?php echo isset($request_type)?$request_type:'' ?> Food Donation</h4>
                    <p>
                        <?php echo isset($description)?$description:'' ?>
                    </p>
                    <h4>Donate to: <?php echo $ngo_name ?></h4>
                    <h4>Urgency: <?php echo $urgency ?></h4>

                    <?php if($request_type=="Scheduled"): ?>
                    <span class="status pending">Planned</span>
                    <?php endif ?>

                    <div style="margin-top:16px">
                        <?php if($status=="Pending"): ?>
                        <?php if($request_type=="Scheduled"): ?>
                        <a href="../donors/donor-food-scheduled.php?id=<?php echo $id ?>&ngo_id=<?php echo $ngo_id ?>&donation=requested_by_NGO" class="btn secondary">
                            Proceed to Scheduled Donation
                        </a>
                        <?php endif ?>
                        <?php endif ?>

                        <?php if($status=="Donated"): ?>
                            <h4><strong>Status: </strong>Donated</h4>
                            <p>Donation Initiated. Awaiting Acceptance</p>
                        <?php endif ?>

                        <?php if($status=="Accepted"): ?>
                            <h4><strong>Status: </strong>Accepted</h4>
                            <p>Donation Accepted. Please wait for Pickup</p>
                        <?php endif ?>


                        <?php if($status=="Picked Up"): ?>
                            <h4><strong>Status: </strong>Picked Up</h4>
                            <p>Donation Picked Up Successfully. Thank You</p>
                        <?php endif ?>

                    </div>
                    </div>
                    <?php

                  }
               }
               ?>
                
            </div>
            <br><br>
            <button class="btn secondary" id="closeBtn" style="display: none;">Close</button>
        </section>

    </main>

    <script>
        document.getElementById('NGOrequestBtn').addEventListener("click", (e)=>{
            document.getElementById('NGORequest').style.display="block";
            document.getElementById('closeBtn').style.display="block";
            document.getElementById('donate').style.display="none";
            document.getElementById('history').style.display="none";
        });

        document.getElementById('closeBtn').addEventListener("click",(e)=>{
            document.getElementById('NGORequest').style.display="none";
            document.getElementById('closeBtn').style.display="none";
            document.getElementById('history').style.display="none";
            document.getElementById('donate').style.display="none";
        });

        document.getElementById('donateBtn').addEventListener("click",(e)=>{
            document.getElementById('donate').style.display="block";
            document.getElementById('NGORequest').style.display="none";
            document.getElementById('history').style.display="none";
            document.getElementById('closeBtn').style.display="block";
        });

        document.getElementById('historyBtn').addEventListener("click", (e)=>{
            document.getElementById('history').style.display="block";
            document.getElementById('NGORequest').style.display="none";
            document.getElementById('donate').style.display="none";
            document.getElementById('closeBtn').style.display="block";
        });
    </script>
    <!-- <script src="../assets/css/donor.css"></script>  -->
</div>

</body>
</html>

