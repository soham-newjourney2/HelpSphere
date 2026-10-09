 <?php

//starting PHP Sessions
session_start();

// DB constants (update for your environment) 
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'your database name');
define('DB_USER', 'your username');
define('DB_PASS', 'your password');

// Uploads
define('UPLOAD_DIR', __DIR__ . '/../public/uploads');
define('UPLOAD_URL', '/uploads');

function e($s){
    return htmlspecialchars($s, ENT_QUOTES);
} 
