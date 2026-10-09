<?php
require_once __DIR__. '/../../inc/auth.php';
donor_require_login();

global $stmt, $stmt1;
$id=isset($_GET['id'])?$_GET['id']:0;
$ngo_id=isset($_GET['ngo_id'])?$_GET['ngo_id']:0;
$donation=isset($_GET['donation'])?$_GET['donation']:'';

if($donation=="requested_by_NGO")
{
$stmt=$pdo->prepare("select * from food_donation_requests where request_type=? and id=?");
$stmt->bindValue(1,"Scheduled");
$stmt->bindValue(2,$id);
$stmt->execute();
if($stmt->rowCount() > 0)
{
    $record=$stmt->fetch(PDO::FETCH_ASSOC);
    $urgency=$record['urgency'];
}
}

if(isset($_POST['donate-btn']))
{
    $stmt2=$pdo->prepare("insert into donations (donor_id, target_id, target_role, status, donation_type) values (?,?,?,?,?)");
    $stmt2->bindParam(1, $_SESSION['donor_id']);
    $stmt2->bindParam(2, $ngo_id);
    $stmt2->bindValue(3, "NGO");
    $stmt2->bindValue(4, "Donated");
    $stmt2->bindValue(5, "Food");
    $stmt2->execute();
    
    if($donation=="requested_by_NGO")
    {
    $type=$_POST['type'];
    $quantity=$_POST['quantity'];
    $date=$_POST['date'];
    $time=$_POST['time'];
    $address=$_POST['address'];
    $stmt=$pdo->prepare("update food_donation_requests set donor_id=?, food_type=?, quantity=?, status=?, donated_at=NOW() where id=?");
    $stmt->bindParam(1,$_SESSION['donor_id']);
    $stmt->bindParam(2,$type);
    $stmt->bindParam(3,$quantity);
    $stmt->bindValue(4,"Donated");
    $stmt->bindParam(5,$id);
    $stmt->execute();
    $stmt1=$pdo->prepare("insert into food_donations (donor_id, food_type, target_type, target_id, quantity, status, created_at, pickup_address, pickup_date, pickup_time, urgency, donation_type, is_requested_by_ngo) values (?,?,?,?,?,?,NOW(),?,?,?,?,?,?)");
    $stmt1->bindParam(1, $_SESSION['donor_id']);
    $stmt1->bindValue(2, $type);
    $stmt1->bindValue(3, "NGO");
    $stmt1->bindParam(4, $ngo_id);
    $stmt1->bindParam(5, $_POST['quantity']);
    $stmt1->bindValue(6, "Donated");
    $stmt1->bindParam(7,$address);
    $stmt1->bindParam(8,$date);
    $stmt1->bindParam(9,$time);
    $stmt1->bindValue(10,"N/A");
    $stmt1->bindValue(11,"Scheduled");
    $stmt1->bindValue(12,"Yes");
    $stmt1->execute();
    }
    else {
    $type=$_POST['type'];
    $quantity=$_POST['quantity'];
    $date=$_POST['date'];
    $time=$_POST['time'];
    $address=$_POST['address'];
    $stmt1=$pdo->prepare("insert into food_donations (donor_id, food_type, target_type, quantity, status, created_at, pickup_address, pickup_date, pickup_time, urgency, donation_type, is_requested_by_ngo) values (?,?,?,?,?,NOW(),?,?,?,?,?,?)");
    $stmt1->bindParam(1, $_SESSION['donor_id']);
    $stmt1->bindValue(2, $type);
    $stmt1->bindValue(3, "NGO");
    $stmt1->bindParam(4, $_POST['quantity']);
    $stmt1->bindValue(5, "Donated");
    $stmt1->bindParam(6,$address);
    $stmt1->bindParam(7,$date);
    $stmt1->bindParam(8,$time);
    $stmt1->bindValue(9,"N/A");
    $stmt1->bindValue(10,"Scheduled");
    $stmt1->bindValue(11,"No");
    $stmt1->execute();
    }
    if($donation=="requested_by_NGO")
    {
        if($stmt1 && $stmt)
        {
            header("Location: ../donors/donor-food-selection.php");
        }
    }
    else 
    {
        if($stmt1)
        {
            header("Location: ../donors/donor-food-selection.php");
        }
    }   
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Food Donation</title>
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

       <form action="" method="POST">
        <!-- SCHEDULE DETAILS -->
        <section>
            <div class="section-header">
                <h3>Donation Schedule</h3>
            </div>

            <div class="card">

                <div class="form-group">
                    <label>Food Type</label>
                    <select name="type">
                        <option value="Cooked">Cooked</option>
                        <option value="Packaged">Packaged</option>
                        <option value="Raw">Raw</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Estimated Quantity (Do mention the unit)</label>
                    <input type="text" placeholder="e.g. 50 gram" name="quantity">
                </div>

                <div class="form-group">
                    <label>Pickup Date</label>
                    <input type="date" name="date">
                </div>

                <div class="form-group">
                    <label>Pickup Time Window</label>
                    <input type="time" name="time">
                </div>

                <div class="form-group">
                    <label>Pickup Address</label>
                    <textarea rows="3" placeholder="Exact pickup location" name="address"></textarea>
                </div>

            </div>
        </section>

        <!-- PICKUP COORDINATION -->
        <section>
            <div class="section-header">
                <h3>Pickup Coordination</h3>
            </div>

            <div class="card">
                <div class="form-group">
                    <label>Urgency Level</label>
                    <input type="text" value="N/A" readonly>
                </div>
            </div>
        </section>

        <!-- CONFIRM -->
        <section>
            <div class="section-header">
                <h3>Confirm Scheduled Donation</h3>
            </div>

            <div class="card">
                <p>
                    Your donation will be scheduled and coordinated
                    with the selected NGO closer to the pickup time.
                </p>

                <button class="btn" type="submit" name="donate-btn">
                 Submit Scheduled Food Donation
                </button>
            </div>
        </section>
        </form>

    </main>
<script src="../assets/js/donor.js"></script>
</div>

</body>
</html>

