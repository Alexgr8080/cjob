<?php
require_once 'session.php';
require_once 'config.php';

// Destroy session and logout
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to homepage with logout message
header('Location: index.php?logout=success');
exit();
?>