<?php
/**
 * Session Handler
 * This file MUST be included BEFORE any session_start() calls
 * It configures session settings before the session is started
 */

// Prevent multiple includes
if (defined('SESSION_CONFIGURED')) {
    return;
}
define('SESSION_CONFIGURED', true);

// Configure session settings BEFORE starting session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1); // Set to 1 if using HTTPS
    ini_set('session.cookie_samesite', 'Lax');
    
    // Start the session
    session_start();
}
?>