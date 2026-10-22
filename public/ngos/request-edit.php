<?php 
//request-edit.php
require_once __DIR__. '/../../inc/auth.php';
ngo_require_login();
csrf_require_post();

$value=(string)($_POST['value'] ?? '');
$request_id=(int)($_POST['request_id'] ?? 0);
$ngo_id=(int)($_SESSION['ngo_id'] ?? 0);

if($request_id>0 && $value==="Approved")
{
    // First come, first served: only a Pending request can be approved
    $stmt=$pdo->prepare("update assistance_requests set status='Approved', ngo_id=? where id=? and status='Pending'");
    $stmt->execute([$ngo_id, $request_id]);
}
elseif($request_id>0 && $value==="Rejected")
{
    // A rejection only hides a Pending request from this NGO
    $chk=$pdo->prepare("select id from assistance_requests where id=? and status='Pending'");
    $chk->execute([$request_id]);
    if($chk->fetchColumn()!==false)
    {
        $stmt=$pdo->prepare("insert into assistance_rejection (request_id, rejected_by_ngo_id) values (?,?)");
        $stmt->execute([$request_id, $ngo_id]);
    }
}
header("Location: ngo-beneficiaries-review.php");
exit;
