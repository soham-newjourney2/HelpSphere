<?php 
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
$value=isset($_GET['value'])?$_GET['value']:'';

if($value=="Approved"){
    $request_id=(int)($_GET['request_id'] ?? 0);

    $stmt=$pdo->prepare("update assistance_requests set status='Approved', ngo_id=? where id=? and status='Pending'");
    $stmt->execute([$_SESSION['ngo_id'], $request_id]);

    // rowCount()==0 means another NGO already approved it (or it isn't Pending).
    // Either way, nothing is overwritten; the request just disappears from the pending list.
    header("Location: ngo-beneficiaries-review.php");
    exit;
}

if($value=="Rejected"){
    $request_id=(int)($_GET['request_id'] ?? 0);

    $stmt=$pdo->prepare("insert into assistance_rejection (request_id, rejected_by_ngo_id) values (?,?)");
    $stmt->execute([$request_id, $_SESSION['ngo_id']]);

    header("Location: ngo-beneficiaries-review.php");
    exit;
}

header("Location: ngo-beneficiaries-review.php");
exit;
?>
