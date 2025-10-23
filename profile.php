<?php
require_once 'session.php';
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (password_verify($current_password, $user['password'])) {
        // Check if users table has profile columns, if not add them
        $columns = [];
        $result = $conn->query("SHOW COLUMNS FROM users");
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        // Add columns if they don't exist
        if (!in_array('full_name', $columns)) {
            $conn->query("ALTER TABLE users ADD COLUMN full_name VARCHAR(255) DEFAULT NULL AFTER email");
        }
        if (!in_array('phone', $columns)) {
            $conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER full_name");
        }
        if (!in_array('location', $columns)) {
            $conn->query("ALTER TABLE users ADD COLUMN location VARCHAR(255) DEFAULT NULL AFTER phone");
        }
        if (!in_array('bio', $columns)) {
            $conn->query("ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL AFTER location");
        }
        
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, phone = ?, location = ?, bio = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssssssi", $username, $email, $full_name, $phone, $location, $bio, $hashed_password, $_SESSION['user_id']);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, phone = ?, location = ?, bio = ? WHERE id = ?");
            $stmt->bind_param("ssssssi", $username, $email, $full_name, $phone, $location, $bio, $_SESSION['user_id']);
        }
        
        if ($stmt->execute()) {
            $_SESSION['username'] = $username;
            $success_message = 'Profile updated successfully!';
        } else {
            $error_message = 'Failed to update profile.';
        }
    } else {
        $error_message = 'Current password is incorrect.';
    }
}

// Get current user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();

// Get user's applications
$stmt = $conn->prepare("SELECT a.*, j.title as job_title, c.name as company_name 
                        FROM applications a
                        LEFT JOIN jobs j ON a.job_id = j.id
                        LEFT JOIN companies c ON j.company_id = c.id
                        WHERE a.email = ?
                        ORDER BY a.applied_at DESC
                        LIMIT 10");
$stmt->bind_param("s", $current_user['email']);
$stmt->execute();
$applications = $stmt->get_result();

$page_title = 'My Profile';
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
        body { background: #f5f7fa; }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #667eea;
            margin: 0 auto;
        }
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-top: -3rem;
        }
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-reviewed { background: #cfe2ff; color: #084298; }
        .status-accepted { background: #d1e7dd; color: #0a3622; }
        .status-rejected { background: #f8d7da; color: #58151c; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-briefcase text-primary"></i> JobPortal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container text-center">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h2 class="mt-3"><?php echo htmlspecialchars($current_user['username']); ?></h2>
            <p class="mb-0">
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($current_user['email']); ?>
            </p>
            <span class="badge bg-light text-dark mt-2">
                <i class="fas fa-shield-alt"></i> <?php echo ucfirst($current_user['role']); ?>
            </span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mb-5">
        <div class="profile-card">
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

            <!-- Nav Tabs -->
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#profile-tab">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#applications-tab">
                        <i class="fas fa-file-alt"></i> My Applications
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile-tab">
                    <h4 class="mb-4">Profile Information</h4>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" class="form-control" name="username" 
                                       value="<?php echo htmlspecialchars($current_user['username']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address *</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($current_user['email']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="full_name" 
                                       value="<?php echo htmlspecialchars($current_user['full_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" 
                                   placeholder="City, Country"
                                   value="<?php echo htmlspecialchars($current_user['location'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Bio</label>
                            <textarea class="form-control" name="bio" rows="4" 
                                      placeholder="Tell us about yourself..."><?php echo htmlspecialchars($current_user['bio'] ?? ''); ?></textarea>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">Change Password</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Current Password *</label>
                                <input type="password" class="form-control" name="current_password" required>
                                <small class="text-muted">Required to update profile</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" minlength="6">
                                <small class="text-muted">Leave blank to keep current</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Account Role</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo ucfirst($current_user['role']); ?>" disabled>
                            </div>
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>

                <!-- Applications Tab -->
                <div class="tab-pane fade" id="applications-tab">
                    <h4 class="mb-4">My Job Applications</h4>
                    
                    <?php if ($applications->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Job Title</th>
                                        <th>Company</th>
                                        <th>Applied Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($app = $applications->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($app['job_title']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($app['company_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $app['status']; ?>">
                                                    <?php echo ucfirst($app['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No applications yet</h5>
                            <p class="text-muted">Start applying to jobs to see your applications here</p>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-search"></i> Browse Jobs
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>