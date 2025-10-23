<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gotoa957_jobs');  // Your database name (visible in your screenshot)
define('DB_USER', 'gotoa957_goalsadi');  // Your database username (check cPanel MySQL)
define('DB_PASS', 'password');  // Your database password


// Site Configuration
define('SITE_NAME', 'NZQRI Jobs');
define('SITE_URL', 'https://jobs.gotoaus.com');
define('ADMIN_EMAIL', 'admin@gotoaus.com');

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx']);

// Pagination
define('JOBS_PER_PAGE', 10);
define('APPLICATIONS_PER_PAGE', 20);

// Application Status
define('STATUS_PENDING', 'pending');
define('STATUS_REVIEWED', 'reviewed');
define('STATUS_SHORTLISTED', 'shortlisted');
define('STATUS_INTERVIEWED', 'interviewed');
define('STATUS_OFFERED', 'offered');
define('STATUS_REJECTED', 'rejected');

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Pacific/Auckland');

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Database Connection Function
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch(PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

// Sanitize output
function clean($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Format date
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

// Format datetime
function formatDateTime($datetime) {
    return date('d M Y H:i', strtotime($datetime));
}
?>