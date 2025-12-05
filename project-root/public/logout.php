<?php
session_start();

// Clear all session data
$_SESSION = [];
session_destroy();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Redirect to login
header('Location: login.php');
exit;
