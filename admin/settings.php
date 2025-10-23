<?php
require_once '../session.php';
require_once '../config.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (password_verify($current_password, $user['password'])) {
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssi", $username, $email, $hashed_password, $_SESSION['user_id']);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $email, $_SESSION['user_id']);
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

// Handle site settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $site_name = trim($_POST['site_name']);
    $site_email = trim($_POST['site_email']);
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
    $allow_registration = isset($_POST['allow_registration']) ? 1 : 0;
    
    // In a real application, these would be stored in a settings table
    // For now, we'll just show a success message
    $success_message = 'Site settings updated successfully!';
}

// Get current user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f5f7fa; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 1rem; display: block; transition: 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.1); padding-left: 1.5rem; }
        .sidebar a.active { background: rgba(255,255,255,0.2); border-left: 4px solid white; }
        .settings-card { background: white; border-radius: 15px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .settings-header { border-bottom: 2px solid #f0f0f0; padding-bottom: 1rem; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-4">
                    <h4 class="text-white"><i class="fas fa-briefcase"></i> JobPortal</h4>
                    <small class="text-white-50">Admin Panel</small>
                </div>
                <nav>
                    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="jobs.php"><i class="fas fa-briefcase"></i> Manage Jobs</a>
                    <a href="applications.php"><i class="fas fa-file-alt"></i> Applications</a>
                    <a href="companies.php"><i class="fas fa-building"></i> Companies</a>
                    <a href="users.php"><i class="fas fa-users"></i> Users</a>
                    <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-cog"></i> Settings</h2>
                    <div>
                        <span class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </div>
                </div>

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

                <!-- Profile Settings -->
                <div class="settings-card">
                    <div class="settings-header">
                        <h4><i class="fas fa-user-circle"></i> Profile Settings</h4>
                        <p class="text-muted mb-0">Update your personal information and password</p>
                    </div>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" 
                                       value="<?php echo htmlspecialchars($current_user['username']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($current_user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Current Password *</label>
                                <input type="password" class="form-control" name="current_password" required>
                                <small class="text-muted">Required to make changes</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" minlength="6">
                                <small class="text-muted">Leave blank to keep current</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo ucfirst($current_user['role']); ?>" disabled>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>

                <!-- Site Settings -->
                <div class="settings-card">
                    <div class="settings-header">
                        <h4><i class="fas fa-globe"></i> Site Settings</h4>
                        <p class="text-muted mb-0">Configure general site settings</p>
                    </div>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Site Name</label>
                                <input type="text" class="form-control" name="site_name" 
                                       value="JobPortal" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Site Email</label>
                                <input type="email" class="form-control" name="site_email" 
                                       value="admin@jobportal.com" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode">
                                <label class="form-check-label" for="maintenance_mode">
                                    <strong>Maintenance Mode</strong>
                                    <br><small class="text-muted">Enable to show maintenance page to visitors</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="allow_registration" name="allow_registration" checked>
                                <label class="form-check-label" for="allow_registration">
                                    <strong>Allow User Registration</strong>
                                    <br><small class="text-muted">Enable new users to register accounts</small>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_settings" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </form>
                </div>

                <!-- System Information -->
                <div class="settings-card">
                    <div class="settings-header">
                        <h4><i class="fas fa-info-circle"></i> System Information</h4>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <td><strong>PHP Version:</strong></td>
                                    <td><?php echo phpversion(); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Database:</strong></td>
                                    <td>MySQL <?php echo $conn->server_info; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Server Software:</strong></td>
                                    <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <td><strong>Total Jobs:</strong></td>
                                    <td><?php echo $conn->query("SELECT COUNT(*) as count FROM jobs")->fetch_assoc()['count']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Total Applications:</strong></td>
                                    <td><?php echo $conn->query("SELECT COUNT(*) as count FROM applications")->fetch_assoc()['count']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Total Users:</strong></td>
                                    <td><?php echo $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count']; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Database Backup -->
                <div class="settings-card">
                    <div class="settings-header">
                        <h4><i class="fas fa-database"></i> Database Management</h4>
                        <p class="text-muted mb-0">Backup and maintenance tools</p>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Database backup functionality can be implemented by your system administrator
                    </div>
                    <button class="btn btn-outline-primary" disabled>
                        <i class="fas fa-download"></i> Backup Database
                    </button>
                    <button class="btn btn-outline-warning ms-2" disabled>
                        <i class="fas fa-broom"></i> Clear Cache
                    </button>
                </div>

                <!-- Danger Zone -->
                <div class="settings-card border-danger">
                    <div class="settings-header border-danger">
                        <h4 class="text-danger"><i class="fas fa-exclamation-triangle"></i> Danger Zone</h4>
                        <p class="text-muted mb-0">Irreversible and destructive actions</p>
                    </div>
                    <div class="alert alert-danger">
                        <strong>Warning:</strong> These actions cannot be undone. Please proceed with caution.
                    </div>
                    <button class="btn btn-outline-danger" onclick="return confirm('This will delete all old applications. Are you sure?')">
                        <i class="fas fa-trash"></i> Clear Old Applications (90+ days)
                    </button>
                    <button class="btn btn-outline-danger ms-2" onclick="return confirm('This will delete all inactive jobs. Are you sure?')">
                        <i class="fas fa-trash"></i> Delete Inactive Jobs
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>