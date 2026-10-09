<?php 
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
$value=isset($_GET['value'])?$_GET['value']:'';

if($value=="Approved"){
$stmt=$pdo->prepare("update assistance_requests set status=?, ngo_id=? where id=?");
$stmt->bindParam(1, $value);
$stmt->bindParam(2, $_SESSION['ngo_id']);
$stmt->bindParam(3, $_GET['request_id']);
$stmt->execute();
if($stmt)
    header("Location: ngo-beneficiaries-review.php");
}

if($value=="Rejected"){
    $stmt=$pdo->prepare("insert into assistance_rejection (request_id, rejected_by_ngo_id) values (?,?) ");
    $stmt->bindParam(1, $_GET['request_id']);
    $stmt->bindParam(2, $_SESSION['ngo_id']);
    $stmt->execute();
    if($stmt)
        header("Location: ngo-beneficiaries-review.php");
}
?>