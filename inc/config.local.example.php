<?php
// Copy this file to inc/config.local.php and fill in your own values.
// config.local.php is git-ignored, so your credentials never reach GitHub.
// The values below match a default XAMPP install.

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'helpsphere');
define('DB_USER', 'root');
define('DB_PASS', '');

// Address of the site, used in the "Accept" links emailed to NGOs (no trailing slash)
define('APP_URL', 'http://localhost/HelpSphere');

// Outgoing mail for immediate food-donation alerts (Gmail: use an App Password)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'you@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'you@gmail.com');
define('SMTP_NAME', 'HelpSphere');
