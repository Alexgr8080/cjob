<?php
/**
 * Simple PHP Test File
 * This file tests basic PHP functionality without dependencies
 */

// Display all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>PHP Test</title>";
echo "<style>
    body { font-family: Arial; padding: 30px; background: #f5f7fa; }
    .box { background: white; padding: 20px; border-radius: 8px; margin: 10px 0; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .success { border-left: 4px solid #28a745; }
    .error { border-left: 4px solid #dc3545; }
    .info { border-left: 4px solid #17a2b8; }
    h1 { color: #333; }
    h2 { color: #666; font-size: 18px; margin-top: 0; }
    code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
</style>";
echo "</head><body>";

echo "<h1>🔍 PHP Configuration Test</h1>";

// Test 1: PHP Version
echo "<div class='box success'>";
echo "<h2>✓ PHP Version</h2>";
echo "<p>PHP Version: <strong>" . phpversion() . "</strong></p>";
echo "</div>";

// Test 2: Required Extensions
echo "<div class='box info'>";
echo "<h2>PHP Extensions</h2>";
$extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'curl'];
foreach ($extensions as $ext) {
    $status = extension_loaded($ext);
    $icon = $status ? '✓' : '✗';
    $color = $status ? '#28a745' : '#dc3545';
    echo "<p style='color: $color;'>$icon <strong>$ext:</strong> " . ($status ? 'Loaded' : 'NOT loaded') . "</p>";
}
echo "</div>";

// Test 3: File System
echo "<div class='box info'>";
echo "<h2>File System</h2>";
echo "<p><strong>Current Directory:</strong> " . __DIR__ . "</p>";
echo "<p><strong>config.php exists:</strong> " . (file_exists('config.php') ? '✓ Yes' : '✗ No') . "</p>";

$dirs = ['admin', 'api', 'includes', 'uploads', 'uploads/cvs', 'logs'];
foreach ($dirs as $dir) {
    $exists = is_dir($dir);
    $writable = $exists && is_writable($dir);
    $icon = $writable ? '✓' : '✗';
    $color = $writable ? '#28a745' : '#dc3545';
    echo "<p style='color: $color;'>$icon <strong>$dir/:</strong> " . 
         ($exists ? ($writable ? 'Exists & Writable' : 'Exists but NOT writable') : 'Does NOT exist') . 
         "</p>";
}
echo "</div>";

// Test 4: Database Configuration (without connecting)
echo "<div class='box info'>";
echo "<h2>Database Configuration Check</h2>";
if (file_exists('config.php')) {
    try {
        // Capture any errors from config.php
        ob_start();
        include 'config.php';
        $output = ob_get_clean();
        
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
            echo "<p style='color: #28a745;'>✓ All database constants are defined</p>";
            echo "<p><strong>DB_HOST:</strong> " . DB_HOST . "</p>";
            echo "<p><strong>DB_NAME:</strong> " . DB_NAME . "</p>";
            echo "<p><strong>DB_USER:</strong> " . DB_USER . "</p>";
            echo "<p><strong>DB_PASS:</strong> " . (strlen(DB_PASS) > 0 ? str_repeat('*', 8) : '<span style="color: red;">EMPTY!</span>') . "</p>";
        } else {
            echo "<p style='color: #dc3545;'>✗ Some database constants are missing in config.php</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: #dc3545;'>✗ Error loading config.php: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: #dc3545;'>✗ config.php file not found!</p>";
}
echo "</div>";

// Test 5: Database Connection
if (file_exists('config.php') && defined('DB_HOST')) {
    echo "<div class='box info'>";
    echo "<h2>Database Connection Test</h2>";
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        echo "<p style='color: #28a745;'>✓ <strong>Database connection successful!</strong></p>";
        
        // Check tables
        $tables = ['users', 'jobs', 'applications'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetchColumn();
                echo "<p style='color: #28a745;'>✓ Table '<strong>$table</strong>': $count records</p>";
            } catch (PDOException $e) {
                echo "<p style='color: #dc3545;'>✗ Table '<strong>$table</strong>': NOT FOUND</p>";
            }
        }
        
        // Check for admin user
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $adminCount = $stmt->fetchColumn();
            if ($adminCount > 0) {
                echo "<p style='color: #28a745;'>✓ Admin user exists ($adminCount found)</p>";
            } else {
                echo "<p style='color: #dc3545;'>✗ No admin user found!</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: #dc3545;'>✗ Error checking admin user</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: #dc3545;'>✗ <strong>Database connection FAILED</strong></p>";
        echo "<p style='color: #666; font-size: 14px;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<hr>";
        echo "<h3>Common Solutions:</h3>";
        echo "<ul>";
        echo "<li>Check if database name is correct (yours is: <code>" . DB_NAME . "</code>)</li>";
        echo "<li>Check if database user exists and has correct permissions</li>";
        echo "<li>Verify the password in config.php</li>";
        echo "<li>Make sure the database exists in cPanel > MySQL Databases</li>";
        echo "</ul>";
    }
    echo "</div>";
}

// Test 6: Server Info
echo "<div class='box info'>";
echo "<h2>Server Information</h2>";
echo "<p><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>PHP SAPI:</strong> " . php_sapi_name() . "</p>";
echo "</div>";

// Final Instructions
echo "<div class='box' style='background: #fff3cd; border-left: 4px solid #ffc107;'>";
echo "<h2>⚠️ Next Steps</h2>";
echo "<ol>";
echo "<li>If database connection failed, update <code>config.php</code> with correct credentials</li>";
echo "<li>If directories are not writable, set permissions to 755 in cPanel File Manager</li>";
echo "<li>If admin user doesn't exist, create one in phpMyAdmin</li>";
echo "<li>Once all tests pass, try accessing <a href='index.php'>index.php</a></li>";
echo "<li><strong>Delete this check.php file after testing!</strong></li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";
?>