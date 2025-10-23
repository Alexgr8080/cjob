<?php
/**
 * NZQRI Job Management System
 * Authentication API
 * Handles: Login, Register, Logout, Password Reset, Email Verification
 */

define('NZQRI_ACCESS', true);
require_once '../config.php';
require_once '../includes/email.php';

header('Content-Type: application/json');

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'register':
            handleRegister($input);
            break;
            
        case 'login':
            handleLogin($input);
            break;
            
        case 'logout':
            handleLogout();
            break;
            
        case 'verify-email':
            handleEmailVerification();
            break;
            
        case 'forgot-password':
            handleForgotPassword($input);
            break;
            
        case 'reset-password':
            handleResetPassword($input);
            break;
            
        case 'check-session':
            handleCheckSession();
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    error_log("Auth API Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An error occurred. Please try again.'], 500);
}

/**
 * Handle User Registration
 */
function handleRegister($data) {
    // Validate input
    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        jsonResponse(['success' => false, 'message' => 'All fields are required'], 400);
    }
    
    $name = sanitize($data['name']);
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $password = $data['password'];
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'Invalid email format'], 400);
    }
    
    // Validate password
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        jsonResponse(['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'], 400);
    }
    
    $db = getDB();
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Email already registered'], 400);
    }
    
    // Hash password and generate verification token
    $hashedPassword = hashPassword($password);
    $verificationToken = generateToken();
    
    // Insert user
    $stmt = $db->prepare("
        INSERT INTO users (name, email, password, verification_token, role, verified, status) 
        VALUES (?, ?, ?, ?, 'user', 0, 'active')
    ");
    
    if ($stmt->execute([$name, $email, $hashedPassword, $verificationToken])) {
        $userId = $db->lastInsertId();
        
        // Send verification email
        $verificationLink = SITE_URL . "/verify-email.php?token=" . $verificationToken;
        sendVerificationEmail($email, $name, $verificationLink);
        
        jsonResponse([
            'success' => true, 
            'message' => 'Registration successful! Please check your email to verify your account.'
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Registration failed. Please try again.'], 500);
    }
}

/**
 * Handle User Login
 */
function handleLogin($data) {
    if (empty($data['email']) || empty($data['password'])) {
        jsonResponse(['success' => false, 'message' => 'Email and password are required'], 400);
    }
    
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $password = $data['password'];
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !verifyPassword($password, $user['password'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
    }
    
    // Check if email is verified
    if (!$user['verified']) {
        jsonResponse(['success' => false, 'message' => 'Please verify your email before logging in'], 403);
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    
    jsonResponse([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'verified' => $user['verified']
        ]
    ]);
}

/**
 * Handle User Logout
 */
function handleLogout() {
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Logged out successfully']);
}

/**
 * Handle Email Verification
 */
function handleEmailVerification() {
    if (empty($_GET['token'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid verification token'], 400);
    }
    
    $token = sanitize($_GET['token']);
    $db = getDB();
    
    $stmt = $db->prepare("SELECT id, email, name FROM users WHERE verification_token = ? AND verified = 0");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Invalid or expired verification token'], 400);
    }
    
    // Update user as verified
    $stmt = $db->prepare("UPDATE users SET verified = 1, verification_token = NULL WHERE id = ?");
    if ($stmt->execute([$user['id']])) {
        // Send welcome email
        sendWelcomeEmail($user['email'], $user['name']);
        
        jsonResponse([
            'success' => true,
            'message' => 'Email verified successfully! You can now login.'
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Verification failed. Please try again.'], 500);
    }
}

/**
 * Handle Forgot Password
 */
function handleForgotPassword($data) {
    if (empty($data['email'])) {
        jsonResponse(['success' => false, 'message' => 'Email is required'], 400);
    }
    
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $db = getDB();
    
    $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate reset token
        $resetToken = generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Save token to database
        $stmt = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $resetToken, $expiresAt]);
        
        // Send reset email
        $resetLink = SITE_URL . "/reset-password.php?token=" . $resetToken;
        sendPasswordResetEmail($email, $user['name'], $resetLink);
    }
    
    // Always return success to prevent email enumeration
    jsonResponse([
        'success' => true,
        'message' => 'If your email is registered, you will receive a password reset link.'
    ]);
}

/**
 * Handle Password Reset
 */
function handleResetPassword($data) {
    if (empty($data['token']) || empty($data['password'])) {
        jsonResponse(['success' => false, 'message' => 'Token and password are required'], 400);
    }
    
    $token = sanitize($data['token']);
    $password = $data['password'];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        jsonResponse(['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'], 400);
    }
    
    $db = getDB();
    
    // Find valid token
    $stmt = $db->prepare("
        SELECT email FROM password_resets 
        WHERE token = ? AND used = 0 AND expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();
    
    if (!$reset) {
        jsonResponse(['success' => false, 'message' => 'Invalid or expired reset token'], 400);
    }
    
    // Update password
    $hashedPassword = hashPassword($password);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$hashedPassword, $reset['email']]);
    
    // Mark token as used
    $stmt = $db->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
    $stmt->execute([$token]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Password reset successfully! You can now login with your new password.'
    ]);
}

/**
 * Check Session Status
 */
function handleCheckSession() {
    if (isLoggedIn()) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, email, role, verified FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            jsonResponse([
                'success' => true,
                'authenticated' => true,
                'user' => $user
            ]);
        }
    }
    
    jsonResponse([
        'success' => true,
        'authenticated' => false
    ]);
}
?>