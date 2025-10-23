<?php
require_once 'config.php';
require_once 'session.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Redirect admin to admin dashboard
if ($_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit();
}

// Get current user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();

// Get user's applications
$stmt = $conn->prepare("SELECT a.*, j.title as job_title, j.location, j.salary_range, c.name as company_name, c.logo as company_logo
                        FROM applications a
                        LEFT JOIN jobs j ON a.job_id = j.id
                        LEFT JOIN companies c ON j.company_id = c.id
                        WHERE a.email = ?
                        ORDER BY a.applied_at DESC");
$stmt->bind_param("s", $current_user['email']);
$stmt->execute();
$applications = $stmt->get_result();

// Get statistics
$total_applications = $conn->query("SELECT COUNT(*) as count FROM applications WHERE email = '{$current_user['email']}'")->fetch_assoc()['count'];
$pending_applications = $conn->query("SELECT COUNT(*) as count FROM applications WHERE email = '{$current_user['email']}' AND status = 'pending'")->fetch_assoc()['count'];
$accepted_applications = $conn->query("SELECT COUNT(*) as count FROM applications WHERE email = '{$current_user['email']}' AND status = 'accepted'")->fetch_assoc()['count'];

// Get recent jobs
$recent_jobs = $conn->query("SELECT j.*, c.name as company_name, c.logo as company_logo 
                             FROM jobs j 
                             LEFT JOIN companies c ON j.company_id = c.id 
                             WHERE j.status = 'active' 
                             ORDER BY j.created_at DESC 
                             LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f5f7fa; }
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
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
        .job-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 1rem 0;
        }
        .job-item:last-child { border-bottom: none; }
        .company-logo-sm {
            width: 40px;
            height: 40px;
            object-fit: contain;
            border-radius: 8px;
            background: #f8f9fa;
            padding: 5px;
        }
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
                    <li class="nav-item">
                        <a class="nav-link" href="jobs.php">
                            <i class="fas fa-search"></i> Browse Jobs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
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

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <h2><i class="fas fa-tachometer-alt"></i> Welcome back, <?php echo htmlspecialchars($current_user['username']); ?>!</h2>
            <p class="mb-0">Track your job applications and discover new opportunities</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-4">
        <!-- Statistics -->
        <div class="row">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted mb-1">Total Applications</div>
                            <h2 class="mb-0"><?php echo $total_applications; ?></h2>
                        </div>
                        <div class="stat-icon" style="background: #e3f2fd; color: #1976d2;">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted mb-1">Pending Review</div>
                            <h2 class="mb-0"><?php echo $pending_applications; ?></h2>
                        </div>
                        <div class="stat-icon" style="background: #fff3e0; color: #f57c00;">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted mb-1">Accepted</div>
                            <h2 class="mb-0"><?php echo $accepted_applications; ?></h2>
                        </div>
                        <div class="stat-icon" style="background: #e8f5e9; color: #388e3c;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- My Applications -->
            <div class="col-md-8">
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4><i class="fas fa-file-alt"></i> My Applications</h4>
                        <a href="profile.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>

                    <?php if ($applications->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Job</th>
                                        <th>Company</th>
                                        <th>Applied</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($app = $applications->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($app['job_title']); ?></strong><br>
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($app['location']); ?>
                                                </small>
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
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No applications yet</h5>
                            <p class="text-muted">Start applying to jobs to see them here</p>
                            <a href="jobs.php" class="btn btn-primary">
                                <i class="fas fa-search"></i> Browse Jobs
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Jobs -->
            <div class="col-md-4">
                <div class="content-card">
                    <h4 class="mb-3"><i class="fas fa-star"></i> Latest Jobs</h4>
                    
                    <?php while ($job = $recent_jobs->fetch_assoc()): ?>
                        <div class="job-item">
                            <div class="d-flex align-items-start gap-2">
                                <?php if ($job['company_logo']): ?>
                                    <img src="<?php echo htmlspecialchars($job['company_logo']); ?>" 
                                         alt="<?php echo htmlspecialchars($job['company_name']); ?>" 
                                         class="company-logo-sm">
                                <?php else: ?>
                                    <div class="company-logo-sm d-flex align-items-center justify-content-center">
                                        <i class="fas fa-building text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <a href="job_detail.php?id=<?php echo $job['id']; ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted d-block">
                                        <?php echo htmlspecialchars($job['company_name']); ?>
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <a href="jobs.php" class="btn btn-outline-primary w-100 mt-3">
                        <i class="fas fa-search"></i> View All Jobs
                    </a>
                </div>

                <!-- Quick Actions -->
                <div class="content-card">
                    <h5 class="mb-3"><i class="fas fa-bolt"></i> Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="jobs.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search Jobs
                        </a>
                        <a href="profile.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-edit"></i> Update Profile
                        </a>
                        <a href="profile.php" class="btn btn-outline-secondary">
                            <i class="fas fa-file-alt"></i> My Applications
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>