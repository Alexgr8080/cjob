<?php
/**
 * NZQRI System Diagnostic Tool
 * This file tests your installation and identifies issues
 * 
 * IMPORTANT: Delete this file after testing for security!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$results = [];
$overallStatus = true;

// Test 1: Check if config.php exists
$results[] = [
    'test' => 'Config File Exists',
    'status' => file_exists('config.php'),
    'message' => file_exists('config.php') ? 'config.php found' : 'config.php is missing!'
];

// Test 2: Load config file
if (file_exists('config.php')) {
    try {
        require_once 'config.php';
        $results[] = [
            'test' => 'Config File Loads',
            'status' => true,
            'message' => 'config.php loaded successfully'
        ];
    } catch (Exception $e) {
        $results[] = [
            'test' => 'Config File Loads',
            'status' => false,
            'message' => 'Error loading config.php: ' . $e->getMessage()
        ];
        $overallStatus = false;
    }
} else {
    $overallStatus = false;
}

// Test 3: Check database constants
$dbConstants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($dbConstants as $const) {
    $exists = defined($const);
    $results[] = [
        'test' => "Constant: $const",
        'status' => $exists,
        'message' => $exists ? "$const is defined" : "$const is not defined in config.php"
    ];
    if (!$exists) $overallStatus = false;
}

// Test 4: Database connection
if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, 
            DB_USER, 
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $results[] = [
            'test' => 'Database Connection',
            'status' => true,
            'message' => 'Successfully connected to database: ' . DB_NAME
        ];
        
        // Test 5: Check if tables exist
        $tables = ['users', 'jobs', 'applications'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                $results[] = [
                    'test' => "Table: $table",
                    'status' => true,
                    'message' => "Table '$table' exists with $count records"
                ];
            } catch (PDOException $e) {
                $results[] = [
                    'test' => "Table: $table",
                    'status' => false,
                    'message' => "Table '$table' not found or error: " . $e->getMessage()
                ];
                $overallStatus = false;
            }
        }
        
        // Test 6: Check for admin user
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
            $adminCount = $stmt->fetchColumn();
            $results[] = [
                'test' => 'Admin User Exists',
                'status' => $adminCount > 0,
                'message' => $adminCount > 0 ? "Found $adminCount admin user(s)" : 'No admin users found! You need to create one.'
            ];
            if ($adminCount == 0) $overallStatus = false;
        } catch (PDOException $e) {
            $results[] = [
                'test' => 'Admin User Check',
                'status' => false,
                'message' => 'Error checking admin users: ' . $e->getMessage()
            ];
        }
        
    } catch (PDOException $e) {
        $results[] = [
            'test' => 'Database Connection',
            'status' => false,
            'message' => 'Failed to connect: ' . $e->getMessage()
        ];
        $overallStatus = false;
    }
}

// Test 7: Check required directories
$directories = [
    'api' => 'API directory',
    'admin' => 'Admin directory',
    'includes' => 'Includes directory',
    'uploads' => 'Uploads directory',
    'uploads/cvs' => 'CV uploads directory'
];

foreach ($directories as $dir => $name) {
    $exists = is_dir($dir);
    $writable = $exists ? is_writable($dir) : false;
    
    $results[] = [
        'test' => $name,
        'status' => $exists && $writable,
        'message' => $exists 
            ? ($writable ? "$name exists and is writable" : "$name exists but is NOT writable (check permissions)")
            : "$name does not exist"
    ];
    
    if (!$exists || !$writable) $overallStatus = false;
}

// Test 8: Check required files
$files = [
    'index.php' => 'Main index file',
    'admin.php' => 'Admin login file',
    'verify-email.php' => 'Email verification file',
    'reset-password.php' => 'Password reset file',
    'api/auth.php' => 'Auth API',
    'api/jobs.php' => 'Jobs API',
    'api/applications.php' => 'Applications API',
    'api/admin.php' => 'Admin API',
    'admin/index.php' => 'Admin dashboard',
    'includes/email.php' => 'Email functions'
];

foreach ($files as $file => $name) {
    $exists = file_exists($file);
    $results[] = [
        'test' => $name,
        'status' => $exists,
        'message' => $exists ? "$name exists" : "$name is missing"
    ];
    if (!$exists) $overallStatus = false;
}

// Test 9: PHP Version
$phpVersion = phpversion();
$phpOk = version_compare($phpVersion, '7.0.0', '>=');
$results[] = [
    'test' => 'PHP Version',
    'status' => $phpOk,
    'message' => "PHP version: $phpVersion " . ($phpOk ? '(OK)' : '(Need 7.0 or higher)')
];

// Test 10: Required PHP Extensions
$extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $results[] = [
        'test' => "PHP Extension: $ext",
        'status' => $loaded,
        'message' => $loaded ? "$ext is loaded" : "$ext is NOT loaded (required!)"
    ];
    if (!$loaded) $overallStatus = false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NZQRI System Diagnostic</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: <?php echo $overallStatus ? 'linear-gradient(135deg, #28a745 0%, #20c997 100%)' : 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)'; ?>;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            background: rgba(255,255,255,0.3);
        }
        
        .content {
            padding: 30px;
        }
        
        .test-result {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid;
        }
        
        .test-result.pass {
            background: #d4edda;
            border-color: #28a745;
        }
        
        .test-result.fail {
            background: #f8d7da;
            border-color: #dc3545;
        }
        
        .test-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .test-message {
            font-size: 14px;
            color: #666;
        }
        
        .test-icon {
            font-size: 24px;
        }
        
        .summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .summary h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            color: #856404;
        }
        
        .warning strong {
            display: block;
            margin-bottom: 5px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo $overallStatus ? '✓' : '✕'; ?> System Diagnostic</h1>
            <div class="status-badge">
                <?php echo $overallStatus ? 'All Tests Passed' : 'Issues Found'; ?>
            </div>
        </div>
        
        <div class="content">
            <div class="summary">
                <h2>Diagnostic Summary</h2>
                <p>This tool checks your NZQRI installation for common issues.</p>
                <?php
                $passed = count(array_filter($results, function($r) { return $r['status']; }));
                $total = count($results);
                echo "<p><strong>$passed of $total tests passed</strong></p>";
                ?>
            </div>
            
            <?php foreach ($results as $result): ?>
                <div class="test-result <?php echo $result['status'] ? 'pass' : 'fail'; ?>">
                    <div>
                        <div class="test-name"><?php echo htmlspecialchars($result['test']); ?></div>
                        <div class="test-message"><?php echo htmlspecialchars($result['message']); ?></div>
                    </div>
                    <div class="test-icon"><?php echo $result['status'] ? '✓' : '✕'; ?></div>
                </div>
            <?php endforeach; ?>
            
            <div class="warning">
                <strong>⚠️ Security Warning</strong>
                Please delete this test-setup.php file after fixing any issues. It contains sensitive information about your system configuration.
            </div>
            
            <?php if ($overallStatus): ?>
                <a href="index.php" class="btn">Go to Homepage</a>
                <a href="admin.php" class="btn">Go to Admin Login</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>