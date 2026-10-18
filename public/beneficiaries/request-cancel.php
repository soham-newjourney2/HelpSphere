<?php
require_once __DIR__. '/../../inc/auth.php';
require_login();

$request_id=isset($_GET['id'])? $_GET['id'] : 0;
if($request_id <= 0 && !isset($_POST['edit-btn']))
{
    echo "Invalid Request ID";
    exit;
}
else
{
    $stmt=$pdo->prepare("delete from assistance_requests where id=?");
    $stmt->bindParam(1, $request_id);
    $stmt->execute();
    if($stmt)
    {
        header("Location: ../beneficiaries/beneficiary-request-management.php");
        exit;
    }
}
