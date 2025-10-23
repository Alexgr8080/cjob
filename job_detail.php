<?php
require_once 'config.php';
require_once 'session.php';

// Get job ID from URL
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($job_id === 0) {
    header('Location: index.php');
    exit();
}

// Fetch job details
$stmt = $conn->prepare("SELECT j.*, c.name as company_name, c.logo as company_logo 
                        FROM jobs j 
                        LEFT JOIN companies c ON j.company_id = c.id 
                        WHERE j.id = ? AND j.status = 'active'");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();

if (!$job) {
    header('Location: index.php');
    exit();
}

// Handle application submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $cover_letter = trim($_POST['cover_letter'] ?? '');
    
    // Handle resume upload
    $resume_path = '';
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (in_array($_FILES['resume']['type'], $allowed_types) && $_FILES['resume']['size'] <= $max_size) {
            $upload_dir = 'uploads/resumes/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
            $resume_filename = uniqid('resume_') . '.' . $file_extension;
            $resume_path = $upload_dir . $resume_filename;
            
            if (!move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path)) {
                $error_message = 'Failed to upload resume. Please try again.';
            }
        } else {
            $error_message = 'Invalid file type or size. Please upload a PDF or Word document under 5MB.';
        }
    } else {
        $error_message = 'Please upload your resume.';
    }
    
    if (empty($error_message)) {
        // Insert application
        $stmt = $conn->prepare("INSERT INTO applications (job_id, full_name, email, phone, resume_path, cover_letter, status, applied_at) 
                                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("isssss", $job_id, $full_name, $email, $phone, $resume_path, $cover_letter);
        
        if ($stmt->execute()) {
            $success_message = 'Your application has been submitted successfully!';
        } else {
            $error_message = 'Failed to submit application. Please try again.';
        }
    }
}

$page_title = $job['title'] . ' - Apply Now';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .job-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
        }
        .company-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            background: white;
            padding: 10px;
            border-radius: 10px;
        }
        .job-detail-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-top: -3rem;
        }
        .info-badge {
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.25rem;
        }
        .application-form {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
            margin-top: 2rem;
        }
        .btn-apply {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-briefcase text-primary"></i> JobPortal
            </a>
            <div class="ms-auto">
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Jobs
                </a>
            </div>
        </div>
    </nav>

    <!-- Job Header -->
    <div class="job-header">
        <div class="container">
            <div class="d-flex align-items-center gap-3">
                <?php if ($job['company_logo']): ?>
                    <img src="<?php echo htmlspecialchars($job['company_logo']); ?>" 
                         alt="<?php echo htmlspecialchars($job['company_name']); ?>" 
                         class="company-logo">
                <?php endif; ?>
                <div>
                    <h1 class="mb-2"><?php echo htmlspecialchars($job['title']); ?></h1>
                    <h5><?php echo htmlspecialchars($job['company_name']); ?></h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Job Details -->
    <div class="container mb-5">
        <div class="job-detail-card">
            <div class="mb-4">
                <span class="info-badge">
                    <i class="fas fa-map-marker-alt text-primary"></i>
                    <?php echo htmlspecialchars($job['location']); ?>
                </span>
                <span class="info-badge">
                    <i class="fas fa-clock text-success"></i>
                    <?php echo htmlspecialchars($job['job_type']); ?>
                </span>
                <span class="info-badge">
                    <i class="fas fa-dollar-sign text-warning"></i>
                    <?php echo htmlspecialchars($job['salary_range']); ?>
                </span>
                <span class="info-badge">
                    <i class="fas fa-calendar text-info"></i>
                    Posted: <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                </span>
            </div>

            <hr>

            <div class="mb-4">
                <h4><i class="fas fa-info-circle text-primary"></i> Job Description</h4>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
            </div>

            <?php if ($job['requirements']): ?>
            <div class="mb-4">
                <h4><i class="fas fa-check-circle text-success"></i> Requirements</h4>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($job['benefits']): ?>
            <div class="mb-4">
                <h4><i class="fas fa-gift text-warning"></i> Benefits</h4>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($job['benefits'])); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Application Form -->
        <div class="application-form">
            <h3 class="mb-4"><i class="fas fa-file-alt"></i> Apply for this Position</h3>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Phone Number *</label>
                    <input type="tel" class="form-control" id="phone" name="phone" required>
                </div>

                <div class="mb-3">
                    <label for="resume" class="form-label">Upload Resume (PDF or Word) *</label>
                    <input type="file" class="form-control" id="resume" name="resume" 
                           accept=".pdf,.doc,.docx" required>
                    <small class="text-muted">Maximum file size: 5MB</small>
                </div>

                <div class="mb-3">
                    <label for="cover_letter" class="form-label">Cover Letter</label>
                    <textarea class="form-control" id="cover_letter" name="cover_letter" 
                              rows="6" placeholder="Tell us why you're a great fit for this position..."></textarea>
                </div>

                <button type="submit" name="submit_application" class="btn btn-primary btn-apply w-100">
                    <i class="fas fa-paper-plane"></i> Submit Application
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>