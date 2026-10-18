<?php
require_once __DIR__. '/db.php';
function is_logged_in()
{
    return !empty($_SESSION['beneficiary_id']);
}
function require_login()
{
    if(!is_logged_in())
    {
        header('Location: ../../login-beneficiary.php');
        exit;
    }
}
function admin_require_login()
{
    if(!isset($_SESSION['admin_id']) || $_SESSION['admin_id'] < 1)
    {
        header('Location: ../../login-admin.php');
        unset($_SESSION['admin_id']);
        exit;
    }
}
function donor_require_login()
{
    if(!isset($_SESSION['donor_id']) || $_SESSION['donor_id'] < 1)
    {
        header('Location: ../../login-donor.php');
        unset($_SESSION['donor_id']);
        exit;
    }
}
function ngo_require_login()
{
    if(!isset($_SESSION['ngo_id']) || $_SESSION['ngo_id'] < 1)
    {
        header('Location: ../../login-ngo.php');
        unset($_SESSION['ngo_id']);
        exit;
    }
}
