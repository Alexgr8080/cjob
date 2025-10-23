<?php
require_once 'config.php';
require_once 'session.php';

// Get filter parameters
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$job_type = isset($_GET['type']) ? $_GET['type'] : '';

// Build query
$query = "SELECT j.*, c.name as company_name, c.logo as company_logo 
          FROM jobs j 
          LEFT JOIN companies c ON j.company_id = c.id 
          WHERE j.status = 'active'";

$params = [];
$types = '';

if ($category > 0) {
    $query .= " AND j.category_id = ?";
    $params[] = $category;
    $types .= 'i';
}

if ($search) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if ($location) {
    $query .= " AND j.location LIKE ?";
    $location_param = "%$location%";
    $params[] = $location_param;
    $types .= 's';
}

if ($job_type) {
    $query .= " AND j.job_type = ?";
    $params[] = $job_type;
    $types .= 's';
}

$query .= " ORDER BY j.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$jobs = $stmt->get_result();

// Get category name if filtering by category
$category_name = 'All Jobs';
if ($category > 0) {
    $cat_stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
    $cat_stmt->bind_param("i", $category);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    if ($cat_row = $cat_result->fetch_assoc()) {
        $category_name = $cat_row['name'];
    }
}

// Get all categories for filter
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category_name); ?> - JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f5f7fa; }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0 2rem;
        }
        .job-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .company-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 10px;
            background: #f8f9fa;
            padding: 5px;
        }
        .job-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-right: 0.5rem;
        }
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="jobs.php">Jobs</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/dashboard.php">Dashboard</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-search"></i> <?php echo htmlspecialchars($category_name); ?></h1>
            <p class="mb-0">Find your dream job from thousands of opportunities</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-md-3">
                <div class="filter-card mb-4">
                    <h5 class="mb-3"><i class="fas fa-filter"></i> Filters</h5>
                    <form method="GET">
                        <div class="mb-3">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Job title or keyword..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" 
                                   placeholder="City or country..." 
                                   value="<?php echo htmlspecialchars($location); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <option value="0">All Categories</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Job Type</label>
                            <select class="form-select" name="type">
                                <option value="">All Types</option>
                                <option value="Full-time" <?php echo $job_type === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                                <option value="Part-time" <?php echo $job_type === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                <option value="Contract" <?php echo $job_type === 'Contract' ? 'selected' : ''; ?>>Contract</option>
                                <option value="Remote" <?php echo $job_type === 'Remote' ? 'selected' : ''; ?>>Remote</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <a href="jobs.php" class="btn btn-secondary w-100">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </form>
                </div>
            </div>

            <!-- Jobs List -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><?php echo $jobs->num_rows; ?> Jobs Found</h4>
                </div>

                <?php if ($jobs->num_rows > 0): ?>
                    <?php while ($job = $jobs->fetch_assoc()): ?>
                        <div class="job-card">
                            <div class="row align-items-center">
                                <div class="col-md-2 text-center">
                                    <?php if ($job['company_logo']): ?>
                                        <img src="<?php echo htmlspecialchars($job['company_logo']); ?>" 
                                             alt="<?php echo htmlspecialchars($job['company_name']); ?>" 
                                             class="company-logo">
                                    <?php else: ?>
                                        <div class="company-logo d-flex align-items-center justify-content-center">
                                            <i class="fas fa-building fa-2x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-7">
                                    <h5 class="mb-2">
                                        <a href="job_detail.php?id=<?php echo $job['id']; ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </a>
                                    </h5>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($job['company_name']); ?>
                                    </p>
                                    <div>
                                        <span class="job-badge" style="background: #e3f2fd; color: #1976d2;">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?>
                                        </span>
                                        <span class="job-badge" style="background: #f3e5f5; color: #7b1fa2;">
                                            <i class="fas fa-clock"></i> <?php echo htmlspecialchars($job['job_type']); ?>
                                        </span>
                                        <span class="job-badge" style="background: #fff3e0; color: #f57c00;">
                                            <i class="fas fa-dollar-sign"></i> <?php echo htmlspecialchars($job['salary_range']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-3 text-end">
                                    <a href="job_detail.php?id=<?php echo $job['id']; ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-arrow-right"></i> Apply Now
                                    </a>
                                    <small class="d-block text-muted mt-2">
                                        Posted <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No jobs found</h4>
                        <p class="text-muted">Try adjusting your filters or search criteria</p>
                        <a href="jobs.php" class="btn btn-primary">
                            <i class="fas fa-redo"></i> View All Jobs
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>