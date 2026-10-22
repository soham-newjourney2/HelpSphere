<?php

//starting PHP Sessions
session_start();

// Machine-specific settings live in inc/config.local.php (git-ignored).
// Copy inc/config.local.example.php to inc/config.local.php and fill it in.
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

// DB constants (defaults, used when config.local.php does not set them)
defined('DB_HOST') || define('DB_HOST', '127.0.0.1');
defined('DB_PORT') || define('DB_PORT', 3306);
defined('DB_NAME') || define('DB_NAME', 'helpsphere');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', '');

date_default_timezone_set('Asia/Kolkata');

function e($s){
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
