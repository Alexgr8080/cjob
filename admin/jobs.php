<?php
require_once '../session.php';
require_once '../config.php';

requireAdmin();

$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';
$job_id = $_GET['id'] ?? null;
$error = '';
$success = '';

// Handle Delete
if ($action === 'delete' && $job_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
        $stmt->execute([$job_id]);
        $success = 'Job deleted successfully';
        $action = 'list';
    } catch(PDOException $e) {
        $error = 'Error deleting job: ' . $e->getMessage();
    }
}

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create', 'edit'])) {
    $title = trim($_POST['title'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    $location_id = $_POST['location_id'] ?? null;
    $job_type = $_POST['job_type'] ?? 'full-time';
    $salary_display = trim($_POST['salary_display'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $benefits = trim($_POST['benefits'] ?? '');
    $application_deadline = $_POST['application_deadline'] ?? null;
    $status = $_POST['status'] ?? 'active';
    
    if (empty($title) || empty($company) || empty($description)) {
        $error = 'Please fill in all required fields';
    } else {
        try {
            if ($action === 'create') {
                $stmt = $pdo->prepare("
                    INSERT INTO jobs (title, company, category_id, location_id, job_type, 
                                     salary_display, description, requirements, benefits, 
                                     application_deadline, status, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $title, $company, $category_id, $location_id, $job_type,
                    $salary_display, $description, $requirements, $benefits,
                    $application_deadline ?: null, $status, $_SESSION['user_id']
                ]);
                $success = 'Job posted successfully!';
                $action = 'list';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE jobs SET 
                        title = ?, company = ?, category_id = ?, location_id = ?,
                        job_type = ?, salary_display = ?, description = ?,
                        requirements = ?, benefits = ?, application_deadline = ?,
                        status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $title, $company, $category_id, $location_id, $job_type,
                    $salary_display, $description, $requirements, $benefits,
                    $application_deadline ?: null, $status, $job_id
                ]);
                $success = 'Job updated successfully!';
                $action = 'list';
            }
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get job data for editing
$job = null;
if ($action === 'edit' && $job_id) {
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();
    if (!$job) {
        $error = 'Job not found';
        $action = 'list';
    }
}

// Get all jobs for listing
if ($action === 'list') {
    $stmt = $pdo->query("
        SELECT j.*, c.name as category_name, l.city as location_city
        FROM jobs j
        LEFT JOIN categories c ON j.category_id = c.id
        LEFT JOIN locations l ON j.location_id = l.id
        ORDER BY j.created_at DESC
    ");
    $jobs = $stmt->fetchAll();
}

// Get categories and locations for form
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$locations = $pdo->query("SELECT * FROM locations ORDER BY city")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
        }
        
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
        
        .logout-btn {
            background: #dc2626;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .admin-layout {
            display: flex;
            min-height: calc(100vh - 70px);
        }
        
        .admin-sidebar {
            width: 250px;
            background: #2d3748;
            color: white;
            padding: 2rem 0;
        }
        
        .sidebar-menu {
            list-style: none;
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
        
        .admin-content {
            flex: 1;
            padding: 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 2rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
        }
        
        .btn-secondary {
            background: white;
            color: #667eea;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: 2px solid #667eea;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }
        
        .content-box {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        input[type="text"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
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
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-closed {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-draft {
            background: #e5e7eb;
            color: #374151;
        }
        
        .action-links {
            display: flex;
            gap: 1rem;
        }
        
        .action-links a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .action-links a.delete {
            color: #dc2626;
        }
        
        .required {
            color: #dc2626;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-logo">NZQRI Jobs Admin</div>
        <div class="admin-user">
            <span>👤 <?php echo clean($_SESSION['user_name']); ?></span>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <ul class="sidebar-menu">
                <li><a href="index.php">📊 Dashboard</a></li>
                <li><a href="jobs.php" class="active">💼 Manage Jobs</a></li>
                <li><a href="applications.php">📝 Applications</a></li>
                <li><a href="users.php">👥 Users</a></li>
                <li><a href="../index.php">🏠 View Site</a></li>
            </ul>
        </aside>

        <main class="admin-content">
            <?php if ($action === 'list'): ?>
                <div class="page-header">
                    <h1>Manage Jobs</h1>
                    <a href="?action=create" class="btn-primary">+ Post New Job</a>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo clean($success); ?></div>
                <?php endif; ?>

                <div class="content-box">
                    <?php if (!empty($jobs)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Job Title</th>
                                    <th>Company</th>
                                    <th>Location</th>
                                    <th>Type</th>
                                    <th>Applications</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jobs as $j): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo clean($j['title']); ?></strong><br>
                                            <small><?php echo clean($j['category_name'] ?? 'Uncategorized'); ?></small>
                                        </td>
                                        <td><?php echo clean($j['company']); ?></td>
                                        <td><?php echo clean($j['location_city'] ?? 'Not specified'); ?></td>
                                        <td><?php echo ucfirst($j['job_type']); ?></td>
                                        <td><?php echo $j['applications_count']; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $j['status']; ?>">
                                                <?php echo ucfirst($j['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-links">
                                                <a href="../job_detail.php?id=<?php echo $j['id']; ?>">View</a>
                                                <a href="?action=edit&id=<?php echo $j['id']; ?>">Edit</a>
                                                <a href="?action=delete&id=<?php echo $j['id']; ?>" class="delete" 
                                                   onclick="return confirm('Delete this job?')">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align: center; padding: 3rem; color: #666;">
                            <div style="font-size: 4rem; margin-bottom: 1rem;">💼</div>
                            <p>No jobs posted yet. Click "Post New Job" to get started!</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div class="page-header">
                    <h1><?php echo $action === 'create' ? 'Post New Job' : 'Edit Job'; ?></h1>
                    <a href="?action=list" class="btn-secondary">← Back to Jobs</a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo clean($error); ?></div>
                <?php endif; ?>

                <div class="content-box">
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Job Title <span class="required">*</span></label>
                                <input type="text" name="title" 
                                       value="<?php echo $job ? clean($job['title']) : ''; ?>" 
                                       required>
                            </div>

                            <div class="form-group">
                                <label>Company Name <span class="required">*</span></label>
                                <input type="text" name="company" 
                                       value="<?php echo $job ? clean($job['company']) : ''; ?>" 
                                       required>
                            </div>

                            <div class="form-group">
                                <label>Category</label>
                                <select name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"
                                            <?php echo ($job && $job['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo clean($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Location</label>
                                <select name="location_id">
                                    <option value="">Select Location</option>
                                    <?php foreach ($locations as $loc): ?>
                                        <option value="<?php echo $loc['id']; ?>"
                                            <?php echo ($job && $job['location_id'] == $loc['id']) ? 'selected' : ''; ?>>
                                            <?php echo clean($loc['city']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Job Type</label>
                                <select name="job_type">
                                    <option value="full-time" <?php echo ($job && $job['job_type'] == 'full-time') ? 'selected' : ''; ?>>Full-time</option>
                                    <option value="part-time" <?php echo ($job && $job['job_type'] == 'part-time') ? 'selected' : ''; ?>>Part-time</option>
                                    <option value="contract" <?php echo ($job && $job['job_type'] == 'contract') ? 'selected' : ''; ?>>Contract</option>
                                    <option value="temporary" <?php echo ($job && $job['job_type'] == 'temporary') ? 'selected' : ''; ?>>Temporary</option>
                                    <option value="internship" <?php echo ($job && $job['job_type'] == 'internship') ? 'selected' : ''; ?>>Internship</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Salary</label>
                                <input type="text" name="salary_display" 
                                       placeholder="e.g., $60,000 - $80,000 per year"
                                       value="<?php echo $job ? clean($job['salary_display']) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Application Deadline</label>
                                <input type="date" name="application_deadline" 
                                       value="<?php echo $job ? $job['application_deadline'] : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label>Status</label>
                                <select name="status">
                                    <option value="active" <?php echo ($job && $job['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="closed" <?php echo ($job && $job['status'] == 'closed') ? 'selected' : ''; ?>>Closed</option>
                                    <option value="draft" <?php echo ($job && $job['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                </select>
                            </div>

                            <div class="form-group full-width">
                                <label>Job Description <span class="required">*</span></label>
                                <textarea name="description" required><?php echo $job ? clean($job['description']) : ''; ?></textarea>
                            </div>

                            <div class="form-group full-width">
                                <label>Requirements</label>
                                <textarea name="requirements"><?php echo $job ? clean($job['requirements']) : ''; ?></textarea>
                            </div>

                            <div class="form-group full-width">
                                <label>Benefits</label>
                                <textarea name="benefits"><?php echo $job ? clean($job['benefits']) : ''; ?></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <?php echo $action === 'create' ? 'Post Job' : 'Update Job'; ?>
                            </button>
                            <a href="?action=list" class="btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>