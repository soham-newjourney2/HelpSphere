<?php
//donation-accept.php
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

// Fetch the first row; if there is none, go back to the list
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
        // Donation for this NGO's own request: only that NGO can accept it
        $stmt=$pdo->prepare("UPDATE food_donation_requests SET status='Accepted' WHERE id=? AND status='Donated' AND ngo_id=?");
        $stmt->execute([$id, $ngo_id]);
        if($stmt->rowCount()===0) back_to_list();

        $stmt1=$pdo->prepare("SELECT * FROM food_donation_requests WHERE id=?");
        $stmt1->execute([$id]);
        $row=row_or_leave($stmt1);
        $created_at=$row['donated_at'];
        $donor_id=(int)$row['donor_id'];

        $stmt2=$pdo->prepare("UPDATE food_donations SET status='Accepted' WHERE created_at=? AND donor_id=?");
        $stmt2->execute([$created_at, $donor_id]);
    }
    else
    {
        // Voluntary donation: open to every NGO, first one to accept wins
        $stmt=$pdo->prepare("UPDATE food_donations SET status='Accepted', target_id=? WHERE id=? AND status='Donated' AND is_requested_by_ngo='No'");
        $stmt->execute([$ngo_id, $id]);
        if($stmt->rowCount()===0) back_to_list();

        $stmt1=$pdo->prepare("SELECT * FROM food_donations WHERE id=?");
        $stmt1->execute([$id]);
        $row=row_or_leave($stmt1);
        $created_at=$row['created_at'];
        $donor_id=(int)$row['donor_id'];
    }

    $stmt3=$pdo->prepare("UPDATE donations SET status='Accepted', target_id=? WHERE donor_id=? AND target_role='NGO' AND donation_type='Food' AND created_at=?");
    $stmt3->execute([$ngo_id, $donor_id, $created_at]);
}
back_to_list();
