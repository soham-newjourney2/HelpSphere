<?php
//donation-pickup.php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
csrf_require_post();

$ngo_id=(int)($_SESSION['ngo_id'] ?? 0);
$type=(string)($_POST['type'] ?? '');
$donation=(string)($_POST['donation'] ?? '');
$id=(int)($_POST['id'] ?? 0);

function back_to_list()
{
    header("Location: ngo-donations-food.php");
    exit;
}

function row_or_leave(PDOStatement $s)
{
    $row=$s->fetch(PDO::FETCH_ASSOC);
    if(!$row) back_to_list();
    return $row;
}

if($type==="Scheduled")
{
    if($donation==="requested")
    {
        // Only the NGO that posted the request, and only after it was accepted
        $stmt=$pdo->prepare("UPDATE food_donation_requests SET status='Picked Up' WHERE id=? AND status='Accepted' AND ngo_id=?");
        $stmt->execute([$id, $ngo_id]);
        if($stmt->rowCount()===0) back_to_list();

        $stmt1=$pdo->prepare("SELECT * FROM food_donation_requests WHERE id=?");
        $stmt1->execute([$id]);
        $row=row_or_leave($stmt1);
        $created_at=$row['donated_at'];
        $donor_id=(int)$row['donor_id'];

        $stmt2=$pdo->prepare("UPDATE food_donations SET status='Picked Up' WHERE created_at=? AND donor_id=?");
        $stmt2->execute([$created_at, $donor_id]);
    }
    else
    {
        // Only the NGO that accepted this donation
        $stmt=$pdo->prepare("UPDATE food_donations SET status='Picked Up' WHERE id=? AND status='Accepted' AND target_id=?");
        $stmt->execute([$id, $ngo_id]);
        if($stmt->rowCount()===0) back_to_list();

        $stmt1=$pdo->prepare("SELECT * FROM food_donations WHERE id=?");
        $stmt1->execute([$id]);
        $row=row_or_leave($stmt1);
        $created_at=$row['created_at'];
        $donor_id=(int)$row['donor_id'];
    }

    $stmt3=$pdo->prepare("UPDATE donations SET status='Picked Up', target_id=? WHERE donor_id=? AND target_role='NGO' AND donation_type='Food' AND created_at=?");
    $stmt3->execute([$ngo_id, $donor_id, $created_at]);
}
elseif($type==="Immediate")
{
    // Immediate donations are made to one NGO: only that NGO can pick up
    $stmt=$pdo->prepare("UPDATE food_donations SET status='Picked Up' WHERE id=? AND status='Donated' AND target_id=? AND donation_type='Immediate'");
    $stmt->execute([$id, $ngo_id]);
    if($stmt->rowCount()===0) back_to_list();

    $stmt1=$pdo->prepare("SELECT * FROM food_donations WHERE id=?");
    $stmt1->execute([$id]);
    $row=row_or_leave($stmt1);

    $stmt3=$pdo->prepare("UPDATE donations SET status='Picked Up' WHERE donor_id=? AND target_id=? AND target_role='NGO' AND donation_type='Food' AND created_at=?");
    $stmt3->execute([(int)$row['donor_id'], $ngo_id, $row['created_at']]);
}
back_to_list();
