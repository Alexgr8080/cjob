<?php
require_once '../session.php';
require_once '../config.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $app_id = (int)$_POST['application_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $app_id);
    $stmt->execute();
    
    header('Location: applications.php?updated=1');
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $app_id = (int)$_GET['delete'];
    
    // Get resume path to delete file
    $stmt = $conn->prepare("SELECT resume_path FROM applications WHERE id = ?");
    $stmt->bind_param("i", $app_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $app = $result->fetch_assoc();
    
    if ($app && file_exists('../' . $app['resume_path'])) {
        unlink('../' . $app['resume_path']);
    }
    
    $stmt = $conn->prepare("DELETE FROM applications WHERE id = ?");
    $stmt->bind_param("i", $app_id);
    $stmt->execute();
    
    header('Location: applications.php?deleted=1');
    exit();
}

// Fetch applications with job details
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "SELECT a.*, j.title as job_title, c.name as company_name 
          FROM applications a
          LEFT JOIN jobs j ON a.job_id = j.id
          LEFT JOIN companies c ON j.company_id = c.id
          WHERE 1=1";

$params = [];
$types = '';

if ($filter_status) {
    $query .= " AND a.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if ($search) {
    $query .= " AND (a.full_name LIKE ? OR a.email LIKE ? OR j.title LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$query .= " ORDER BY a.applied_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$applications = $stmt->get_result();

// Get statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM applications")->fetch_assoc()['count'],
    'pending' => $conn->query("SELECT COUNT(*) as count FROM applications WHERE status = 'pending'")->fetch_assoc()['count'],
    'reviewed' => $conn->query("SELECT COUNT(*) as count FROM applications WHERE status = 'reviewed'")->fetch_assoc()['count'],
    'accepted' => $conn->query("SELECT COUNT(*) as count FROM applications WHERE status = 'accepted'")->fetch_assoc()['count'],
    'rejected' => $conn->query("SELECT COUNT(*) as count FROM applications WHERE status = 'rejected'")->fetch_assoc()['count']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f5f7fa; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .sidebar a { color: white; text-decoration: none; padding: 1rem; display: block; transition: 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.1); padding-left: 1.5rem; }
        .sidebar a.active { background: rgba(255,255,255,0.2); border-left: 4px solid white; }
        .stat-card { background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .status-badge { padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-reviewed { background: #cfe2ff; color: #084298; }
        .status-accepted { background: #d1e7dd; color: #0a3622; }
        .status-rejected { background: #f8d7da; color: #58151c; }
        .table-actions { display: flex; gap: 0.5rem; }
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
                    <a href="applications.php" class="active"><i class="fas fa-file-alt"></i> Applications</a>
                    <a href="companies.php"><i class="fas fa-building"></i> Companies</a>
                    <a href="users.php"><i class="fas fa-users"></i> Users</a>
                    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-file-alt"></i> Manage Applications</h2>
                    <div>
                        <span class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </div>
                </div>

                <?php if (isset($_GET['updated'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        Application status updated successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        Application deleted successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">Total</div>
                                    <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                                </div>
                                <div class="stat-icon" style="background: #e3f2fd; color: #1976d2;">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">Pending</div>
                                    <h3 class="mb-0"><?php echo $stats['pending']; ?></h3>
                                </div>
                                <div class="stat-icon" style="background: #fff3e0; color: #f57c00;">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">Reviewed</div>
                                    <h3 class="mb-0"><?php echo $stats['reviewed']; ?></h3>
                                </div>
                                <div class="stat-icon" style="background: #e8eaf6; color: #5e35b1;">
                                    <i class="fas fa-eye"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">Accepted</div>
                                    <h3 class="mb-0"><?php echo $stats['accepted']; ?></h3>
                                </div>
                                <div class="stat-icon" style="background: #e8f5e9; color: #388e3c;">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">Rejected</div>
                                    <h3 class="mb-0"><?php echo $stats['rejected']; ?></h3>
                                </div>
                                <div class="stat-icon" style="background: #ffebee; color: #d32f2f;">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search by name, email, or job title..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="reviewed" <?php echo $filter_status === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                    <option value="accepted" <?php echo $filter_status === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                    <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                            <div class="col-md-3">
                                <a href="applications.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Applications Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Applicant</th>
                                        <th>Job Title</th>
                                        <th>Company</th>
                                        <th>Applied Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($applications->num_rows > 0): ?>
                                        <?php while ($app = $applications->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $app['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($app['full_name']); ?></strong><br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($app['email']); ?><br>
                                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($app['phone']); ?>
                                                    </small>
                                                </td>
                                                <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                                <td><?php echo htmlspecialchars($app['company_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $app['status']; ?>">
                                                        <?php echo ucfirst($app['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <button class="btn btn-sm btn-info" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#viewModal<?php echo $app['id']; ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-warning" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#statusModal<?php echo $app['id']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="?delete=<?php echo $app['id']; ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('Delete this application?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- View Modal -->
                                            <div class="modal fade" id="viewModal<?php echo $app['id']; ?>">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Application Details</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <h6>Applicant Information</h6>
                                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($app['full_name']); ?></p>
                                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($app['email']); ?></p>
                                                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($app['phone']); ?></p>
                                                            
                                                            <hr>
                                                            
                                                            <h6>Job Details</h6>
                                                            <p><strong>Position:</strong> <?php echo htmlspecialchars($app['job_title']); ?></p>
                                                            <p><strong>Company:</strong> <?php echo htmlspecialchars($app['company_name']); ?></p>
                                                            
                                                            <hr>
                                                            
                                                            <h6>Cover Letter</h6>
                                                            <p><?php echo nl2br(htmlspecialchars($app['cover_letter'] ?: 'No cover letter provided')); ?></p>
                                                            
                                                            <hr>
                                                            
                                                            <h6>Resume</h6>
                                                            <a href="../<?php echo htmlspecialchars($app['resume_path']); ?>" 
                                                               class="btn btn-primary" target="_blank">
                                                                <i class="fas fa-download"></i> Download Resume
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Status Modal -->
                                            <div class="modal fade" id="statusModal<?php echo $app['id']; ?>">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Update Application Status</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Current Status: 
                                                                        <span class="status-badge status-<?php echo $app['status']; ?>">
                                                                            <?php echo ucfirst($app['status']); ?>
                                                                        </span>
                                                                    </label>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">New Status</label>
                                                                    <select class="form-select" name="status" required>
                                                                        <option value="pending" <?php echo $app['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                        <option value="reviewed" <?php echo $app['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                                                        <option value="accepted" <?php echo $app['status'] === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                                                        <option value="rejected" <?php echo $app['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No applications found</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>