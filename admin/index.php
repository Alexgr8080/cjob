<?php
require_once '../session.php';
require_once '../config.php';

// Require admin access
requireAdmin();

// Get statistics
try {
    $pdo = getDBConnection();
    
    // Total active jobs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'active'");
    $total_jobs = $stmt->fetch()['count'];
    
    // Total applications
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM applications");
    $total_applications = $stmt->fetch()['count'];
    
    // Pending applications
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM applications WHERE status = 'pending'");
    $pending_applications = $stmt->fetch()['count'];
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
    $total_users = $stmt->fetch()['count'];
    
    // Recent applications (last 10)
    $stmt = $pdo->query("
        SELECT 
            a.*,
            j.title as job_title,
            u.full_name as applicant_name,
            u.email as applicant_email
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON a.user_id = u.id
        ORDER BY a.applied_at DESC
        LIMIT 10
    ");
    $recent_applications = $stmt->fetchAll();
    
    // Recent jobs
    $stmt = $pdo->query("
        SELECT 
            j.*,
            c.name as category_name,
            l.city as location_city
        FROM jobs j
        LEFT JOIN categories c ON j.category_id = c.id
        LEFT JOIN locations l ON j.location_id = l.id
        ORDER BY j.created_at DESC
        LIMIT 5
    ");
    $recent_jobs = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Admin Dashboard Error: " . $e->getMessage());
    $total_jobs = $total_applications = $pending_applications = $total_users = 0;
    $recent_applications = [];
    $recent_jobs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NZQRI Jobs</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        /* Header */
        .admin-header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-name {
            font-weight: 600;
        }
        
        .logout-btn {
            background: #dc2626;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background: #b91c1c;
        }
        
        /* Layout */
        .admin-layout {
            display: flex;
            min-height: calc(100vh - 70px);
        }
        
        /* Sidebar */
        .admin-sidebar {
            width: 250px;
            background: #2d3748;
            color: white;
            padding: 2rem 0;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 0.75rem 1.5rem;
            color: #cbd5e0;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #4a5568;
            color: white;
        }
        
        /* Main Content */
        .admin-content {
            flex: 1;
            padding: 2rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            color: #666;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        
        .stat-icon.blue {
            background: #dbeafe;
        }
        
        .stat-icon.green {
            background: #d1fae5;
        }
        
        .stat-icon.yellow {
            background: #fef3c7;
        }
        
        .stat-icon.purple {
            background: #e9d5ff;
        }
        
        .stat-details h3 {
            font-size: 2rem;
            margin-bottom: 0.25rem;
            color: #333;
        }
        
        .stat-details p {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Tables */
        .content-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-header h2 {
            font-size: 1.5rem;
            color: #333;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f9fafb;
        }
        
        th {
            text-align: left;
            padding: 1rem;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        tr:hover {
            background: #f9fafb;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-reviewed {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-shortlisted {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-closed {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .action-links {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-links a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .action-links a:hover {
            text-decoration: underline;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .admin-layout {
                flex-direction: column;
            }
            
            .admin-sidebar {
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="admin-logo">NZQRI Jobs Admin</div>
        <div class="admin-user">
            <span class="user-name">👤 <?php echo clean($_SESSION['user_name']); ?></span>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <!-- Layout -->
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active">📊 Dashboard</a></li>
                <li><a href="jobs.php">💼 Manage Jobs</a></li>
                <li><a href="applications.php">📝 Applications</a></li>
                <li><a href="users.php">👥 Users</a></li>
                <li><a href="categories.php">📁 Categories</a></li>
                <li><a href="settings.php">⚙️ Settings</a></li>
                <li><a href="../index.php">🏠 View Site</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="page-header">
                <h1>Dashboard</h1>
                <p>Welcome back, <?php echo clean($_SESSION['user_name']); ?>! Here's what's happening today.</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">💼</div>
                    <div class="stat-details">
                        <h3><?php echo $total_jobs; ?></h3>
                        <p>Active Jobs</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">📝</div>
                    <div class="stat-details">
                        <h3><?php echo $total_applications; ?></h3>
                        <p>Total Applications</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon yellow">⏳</div>
                    <div class="stat-details">
                        <h3><?php echo $pending_applications; ?></h3>
                        <p>Pending Applications</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon purple">👥</div>
                    <div class="stat-details">
                        <h3><?php echo $total_users; ?></h3>
                        <p>Registered Users</p>
                    </div>
                </div>
            </div>

            <!-- Recent Applications -->
            <div class="content-section">
                <div class="section-header">
                    <h2>Recent Applications</h2>
                    <a href="applications.php" class="btn-primary">View All</a>
                </div>
                
                <?php if (!empty($recent_applications)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Job Title</th>
                                <th>Applied Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_applications as $app): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo clean($app['applicant_name']); ?></strong><br>
                                        <small><?php echo clean($app['applicant_email']); ?></small>
                                    </td>
                                    <td><?php echo clean($app['job_title']); ?></td>
                                    <td><?php echo formatDate($app['applied_at']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $app['status']; ?>">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-links">
                                            <a href="application_detail.php?id=<?php echo $app['id']; ?>">View</a>
                                            <a href="applications.php?id=<?php echo $app['id']; ?>">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📝</div>
                        <p>No applications yet</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Jobs -->
            <div class="content-section">
                <div class="section-header">
                    <h2>Recent Jobs</h2>
                    <a href="jobs.php?action=create" class="btn-primary">+ Add New Job</a>
                </div>
                
                <?php if (!empty($recent_jobs)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Company</th>
                                <th>Location</th>
                                <th>Applications</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_jobs as $job): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo clean($job['title']); ?></strong><br>
                                        <small><?php echo clean($job['category_name'] ?? 'Uncategorized'); ?></small>
                                    </td>
                                    <td><?php echo clean($job['company']); ?></td>
                                    <td><?php echo clean($job['location_city'] ?? 'Not specified'); ?></td>
                                    <td><?php echo $job['applications_count']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $job['status']; ?>">
                                            <?php echo ucfirst($job['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-links">
                                            <a href="../job_detail.php?id=<?php echo $job['id']; ?>">View</a>
                                            <a href="jobs.php?action=edit&id=<?php echo $job['id']; ?>">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">💼</div>
                        <p>No jobs posted yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>