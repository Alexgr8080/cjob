<?php
header('Content-Type: application/json');
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$userId = $_SESSION['user_id'];
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch($action) {
        case 'submit':
            // Submit a job application
            $jobId = $_POST['job_id'] ?? 0;
            $coverLetter = $_POST['cover_letter'] ?? '';
            
            // Validate job exists
            $stmt = $pdo->prepare("SELECT id, title FROM jobs WHERE id = ? AND status = 'active'");
            $stmt->execute([$jobId]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$job) {
                echo json_encode(['success' => false, 'message' => 'Job not found or no longer active']);
                exit();
            }
            
            // Check if already applied
            $stmt = $pdo->prepare("SELECT id FROM applications WHERE user_id = ? AND job_id = ?");
            $stmt->execute([$userId, $jobId]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'You have already applied for this job']);
                exit();
            }
            
            // Handle CV upload
            $cvPath = null;
            if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/cvs/';
                
                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileExtension = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['pdf', 'doc', 'docx'];
                
                if (!in_array($fileExtension, $allowedExtensions)) {
                    echo json_encode(['success' => false, 'message' => 'Only PDF, DOC, and DOCX files are allowed']);
                    exit();
                }
                
                if ($_FILES['cv']['size'] > MAX_FILE_SIZE) {
                    echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
                    exit();
                }
                
                $fileName = 'cv_' . $userId . '_' . $jobId . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['cv']['tmp_name'], $uploadPath)) {
                    $cvPath = 'uploads/cvs/' . $fileName;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to upload CV']);
                    exit();
                }
            }
            
            // Insert application
            $stmt = $pdo->prepare("
                INSERT INTO applications (user_id, job_id, cover_letter, cv_path, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$userId, $jobId, $coverLetter, $cvPath]);
            $applicationId = $pdo->lastInsertId();
            
            // Send confirmation email
            if (file_exists('../includes/email.php')) {
                require_once '../includes/email.php';
                $stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    sendApplicationConfirmation($user['email'], $user['full_name'], $job['title'], $applicationId);
                    notifyAdminNewApplication($job['title'], $user['full_name'], $user['email'], $applicationId);
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Application submitted successfully',
                'application_id' => $applicationId
            ]);
            break;
            
        case 'my_applications':
            // Get user's applications
            $stmt = $pdo->prepare("
                SELECT a.*, j.title as job_title, j.location, j.job_type
                FROM applications a
                JOIN jobs j ON a.job_id = j.id
                WHERE a.user_id = ?
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([$userId]);
            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'applications' => $applications
            ]);
            break;
            
        case 'get_application':
            // Get specific application details
            $appId = $_GET['id'] ?? 0;
            
            $stmt = $pdo->prepare("
                SELECT a.*, j.title as job_title, j.description, j.location, j.job_type, j.salary_range
                FROM applications a
                JOIN jobs j ON a.job_id = j.id
                WHERE a.id = ? AND a.user_id = ?
            ");
            $stmt->execute([$appId, $userId]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($application) {
                echo json_encode([
                    'success' => true,
                    'application' => $application
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Application not found'
                ]);
            }
            break;
            
        case 'withdraw':
            // Withdraw application
            $appId = $_POST['application_id'] ?? 0;
            
            $stmt = $pdo->prepare("
                UPDATE applications 
                SET status = 'withdrawn'
                WHERE id = ? AND user_id = ? AND status = 'pending'
            ");
            $stmt->execute([$appId, $userId]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Application withdrawn successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Unable to withdraw application. It may have already been reviewed.'
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }
    
} catch(PDOException $e) {
    error_log("Application API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>