<?php
require_once 'config.php';

// Get token from URL
$token = isset($_GET['token']) ? $_GET['token'] : '';

$message = '';
$success = false;

if (!empty($token)) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Find user with this verification token
        $stmt = $pdo->prepare("
            SELECT id, email, is_verified 
            FROM users 
            WHERE verification_token = ? 
            AND is_verified = 0
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Update user as verified
            $updateStmt = $pdo->prepare("
                UPDATE users 
                SET is_verified = 1, 
                    verification_token = NULL,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$user['id']]);
            
            $success = true;
            $message = 'Your email has been successfully verified! You can now login to your account.';
        } else {
            $message = 'Invalid or expired verification link. The link may have already been used or the account is already verified.';
        }
        
    } catch(PDOException $e) {
        $message = 'Database error: ' . $e->getMessage();
    }
} else {
    $message = 'No verification token provided.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - NZQRI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        
        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
        
        .icon.success {
            background: #d4edda;
            color: #28a745;
        }
        
        .icon.error {
            background: #f8d7da;
            color: #dc3545;
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 28px;
        }
        
        .message {
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
            font-size: 16px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $success ? '✓' : '✕'; ?>
        </div>
        
        <h1><?php echo $success ? 'Email Verified!' : 'Verification Failed'; ?></h1>
        
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
        
        <?php if ($success): ?>
            <a href="<?php echo SITE_URL; ?>" class="btn">Go to Login</a>
        <?php else: ?>
            <a href="<?php echo SITE_URL; ?>" class="btn">Go to Homepage</a>
        <?php endif; ?>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> NZQRI. All rights reserved.</p>
        </div>
    </div>
</body>
</html>