<?php
//logout.php — ends the session for any role and returns to the home page
session_start();
session_unset();
session_destroy();
header("Location: index.php");
exit();
