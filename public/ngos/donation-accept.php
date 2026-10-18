<?php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
 
$type=isset($_GET['type'])? $_GET['type'] : '';
$id=isset($_GET['id'])? $_GET['id'] : 0;
$donor_id=isset($_GET['donor_id'])? $_GET['donor_id'] : 0;
$donation=isset($_GET['donation'])? $_GET['donation'] : '';

if($type=="Scheduled"){
    if($donation=="requested"){
        $stmt=$pdo->prepare("UPDATE food_donation_requests SET status=? WHERE id=?");
        $stmt->bindValue(1, "Accepted");
        $stmt->bindParam(2, $id);
        $stmt->execute();

        $stmt1=$pdo->prepare("SELECT * FROM food_donation_requests WHERE id=?");
        $stmt1->bindParam(1, $id);
        $stmt1->execute();
        $created_at=$stmt1->fetch(PDO::FETCH_ASSOC)['donated_at'];

        $stmt2=$pdo->prepare("UPDATE food_donations SET status=? WHERE created_at=?");
        $stmt2->bindValue(1, "Accepted");
        $stmt2->bindParam(2, $created_at);
        $stmt2->execute();    

        $stmt3=$pdo->prepare("UPDATE donations SET status=?, target_id=? WHERE donor_id=? and target_role=? and donation_type=? and created_at=?");
        $stmt3->bindValue(1, "Accepted");
        $stmt3->bindParam(2,$_SESSION['ngo_id']);
        $stmt3->bindParam(3, $donor_id);
        $stmt3->bindValue(4,"NGO");
        $stmt3->bindValue(5,"Food");
        $stmt3->bindParam(6,$created_at);
        $stmt3->execute();

        header("Location: ngo-donations-food.php");
        exit;
    }
    else {
        $stmt=$pdo->prepare("UPDATE food_donations SET status=?, target_id=? WHERE id=?");
        $stmt->bindValue(1, "Accepted");
        $stmt->bindParam(2, $_SESSION['ngo_id']);
        $stmt->bindParam(3, $id);
        $stmt->execute();      

        $stmt2=$pdo->prepare("SELECT * FROM food_donations WHERE id=?");
        $stmt2->bindParam(1, $id);
        $stmt2->execute();
        $created_at=$stmt2->fetch(PDO::FETCH_ASSOC)['created_at'];

        $stmt1=$pdo->prepare("UPDATE donations SET status=?, target_id=? WHERE donor_id=? and target_role=? and donation_type=? and created_at=?");
        $stmt1->bindValue(1, "Accepted");
         $stmt1->bindParam(2,$_SESSION['ngo_id']);
        $stmt1->bindParam(3, $donor_id);
        $stmt1->bindValue(4,"NGO");
        $stmt1->bindValue(5,"Food");
        $stmt1->bindParam(6,$created_at);
        $stmt1->execute();

        header("Location: ngo-donations-food.php");
        exit;
    }
}
?>
