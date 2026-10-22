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
function csrf_token()
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_require_post()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(csrf_token(), (string)($_POST['csrf'] ?? '')))
    {
        http_response_code(403);
        exit('Invalid request.');
    }
}

// A one-button POST form, used instead of a link that changes data
function action_button($url, array $fields, $label, $class = '')
{
    $html  = '<form method="POST" action="' . htmlspecialchars($url) . '" class="inline-form">';
    $html .= '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token()) . '">';
    foreach ($fields as $k => $v)
        $html .= '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars((string)$v) . '">';
    $html .= '<button type="submit" class="' . htmlspecialchars($class) . '">' . htmlspecialchars($label) . '</button></form>';
    return $html;
}
