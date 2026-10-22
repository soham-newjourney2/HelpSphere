<?php
//request-cancel.php
require_once __DIR__. '/../../inc/auth.php';
require_login();
csrf_require_post();

$request_id=(int)($_POST['id'] ?? 0);
$beneficiary_id=(int)($_SESSION['beneficiary_id'] ?? 0);

if($request_id > 0)
{
    // Only your own request, and only while it is still Pending
    $stmt=$pdo->prepare("delete from assistance_requests where id=? and beneficiary_id=? and status='Pending'");
    $stmt->execute([$request_id, $beneficiary_id]);
}
header("Location: ../beneficiaries/beneficiary-request-management.php");
exit;
