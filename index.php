<?php
require_once 'session.php';
require_once 'config.php';

// Get statistics
try {
    $pdo = getDBConnection();
    
    // Count active jobs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'active'");
    $total_jobs = $stmt->fetch()['count'] ?? 0;
    
    // Count categories with jobs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories WHERE job_count > 0");
    $total_categories = $stmt->fetch()['count'] ?? 0;
    
    // Get featured jobs (latest 6)
    $stmt = $pdo->query("
        SELECT j.*, c.name as category_name, l.city as location_city 
        FROM jobs j
        LEFT JOIN categories c ON j.category_id = c.id
        LEFT JOIN locations l ON j.location_id = l.id
        WHERE j.status = 'active'
        ORDER BY j.created_at DESC
        LIMIT 6
    ");
    $featured_jobs = $stmt->fetchAll();
    
    // Get popular categories
    $stmt = $pdo->query("
        SELECT * FROM categories 
        WHERE job_count > 0 
        ORDER BY job_count DESC 
        LIMIT 8
    ");
    $popular_categories = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Homepage Error: " . $e->getMessage());
    $total_jobs = 0;
    $total_categories = 0;
    $featured_jobs = [];
    $popular_categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NZQRI Jobs - Find Your Dream Job in New Zealand</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        /* Header & Navigation */
        header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #667eea;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        .btn-secondary {
            background: white;
            color: #667eea !important;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            border: 2px solid #667eea;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary:hover {
            background: #f0f0f0;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 800;
        }
        
        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }
        
        .search-box {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            gap: 1rem;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .search-box input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .search-box button {
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .search-box button:hover {
            opacity: 0.9;
        }
        
        /* Stats Section */
        .stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: white;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2rem;
        }
        
        .section-title {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 700;
        }
        
        .section-subtitle {
            color: #666;
            margin-bottom: 2rem;
        }
        
        /* Categories Grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .category-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        
        .category-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .category-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .category-count {
            color: #667eea;
            font-weight: 600;
        }
        
        /* Jobs Grid */
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .job-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        
        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .job-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .job-company {
            color: #666;
            font-size: 0.95rem;
        }
        
        .job-type-badge {
            background: #e7f3ff;
            color: #0066cc;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .job-details {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
            flex-wrap: wrap;
        }
        
        .job-detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        .job-salary {
            color: #10b981;
            font-weight: 600;
            margin: 0.5rem 0;
        }
        
        .job-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e0e0e0;
        }
        
        .job-date {
            font-size: 0.85rem;
            color: #999;
        }
        
        .apply-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
        }
        
        .apply-btn:hover {
            opacity: 0.9;
        }
        
        /* Call to Action */
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            border-radius: 16px;
            margin: 4rem 2rem;
        }
        
        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .cta-section p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-white {
            background: white;
            color: #667eea;
            padding: 1rem 2rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            font-size: 1rem;
        }
        
        .btn-white:hover {
            background: #f0f0f0;
        }
        
        /* Footer */
        footer {
            background: #2d3748;
            color: white;
            padding: 3rem 2rem 1rem;
            margin-top: 4rem;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-section h3 {
            margin-bottom: 1rem;
            color: #667eea;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 0.5rem;
        }
        
        .footer-section a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-section a:hover {
            color: white;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #4a5568;
            color: #ccc;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .search-box {
                flex-direction: column;
            }
            
            .stats {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-links {
                display: none; /* You might want to add a mobile menu */
            }
            
            .jobs-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #666;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <a href="index.php" class="logo">NZQRI Jobs</a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="jobs.php">Browse Jobs</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="logout.php" class="btn-secondary">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn-secondary">Login</a>
                    <a href="register.php" class="btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Find Your Dream Job in New Zealand</h1>
            <p>Connect with top employers and discover opportunities across the country</p>
            
            <form action="jobs.php" method="GET" class="search-box">
                <input type="text" name="search" placeholder="Job title, keywords, or company" />
                <input type="text" name="location" placeholder="City or region" />
                <button type="submit">Search Jobs</button>
            </form>
            
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total_jobs); ?>+</div>
                    <div class="stat-label">Active Jobs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total_categories); ?>+</div>
                    <div class="stat-label">Categories</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">1000+</div>
                    <div class="stat-label">Happy Candidates</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Categories -->
    <div class="container">
        <h2 class="section-title">Popular Categories</h2>
        <p class="section-subtitle">Explore jobs by industry and find your perfect match</p>
        
        <?php if (!empty($popular_categories)): ?>
            <div class="categories-grid">
                <?php foreach ($popular_categories as $category): ?>
                    <a href="jobs.php?category=<?php echo $category['id']; ?>" class="category-card">
                        <div class="category-icon"><?php echo clean($category['icon'] ?? '📁'); ?></div>
                        <div class="category-name"><?php echo clean($category['name']); ?></div>
                        <div class="category-count"><?php echo $category['job_count']; ?> Jobs</div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📂</div>
                <p>No categories available yet. Check back soon!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Featured Jobs -->
    <div class="container">
        <h2 class="section-title">Featured Jobs</h2>
        <p class="section-subtitle">Latest opportunities from top employers</p>
        
        <?php if (!empty($featured_jobs)): ?>
            <div class="jobs-grid">
                <?php foreach ($featured_jobs as $job): ?>
                    <a href="job_detail.php?id=<?php echo $job['id']; ?>" class="job-card">
                        <div class="job-header">
                            <div>
                                <div class="job-title"><?php echo clean($job['title']); ?></div>
                                <div class="job-company"><?php echo clean($job['company']); ?></div>
                            </div>
                            <span class="job-type-badge"><?php echo ucfirst(clean($job['job_type'])); ?></span>
                        </div>
                        
                        <div class="job-details">
                            <?php if ($job['location_city']): ?>
                                <div class="job-detail-item">
                                    📍 <?php echo clean($job['location_city']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($job['category_name']): ?>
                                <div class="job-detail-item">
                                    💼 <?php echo clean($job['category_name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($job['salary_display']): ?>
                            <div class="job-salary">💰 <?php echo clean($job['salary_display']); ?></div>
                        <?php endif; ?>
                        
                        <div class="job-footer">
                            <div class="job-date">Posted <?php echo formatDate($job['created_at']); ?></div>
                            <span class="apply-btn">View Details →</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="jobs.php" class="btn-primary" style="padding: 1rem 2rem;">View All Jobs</a>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">💼</div>
                <p>No jobs available yet. Check back soon for new opportunities!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Call to Action -->
    <div class="cta-section">
        <h2>Ready to Start Your Journey?</h2>
        <p>Join thousands of job seekers finding their dream careers</p>
        <div class="cta-buttons">
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn-white">Create Free Account</a>
                <a href="jobs.php" class="btn-white">Browse All Jobs</a>
            <?php else: ?>
                <a href="jobs.php" class="btn-white">Browse All Jobs</a>
                <a href="dashboard.php" class="btn-white">View Dashboard</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>NZQRI Jobs</h3>
                <p>Connecting talent with opportunity across New Zealand.</p>
            </div>
            <div class="footer-section">
                <h3>For Job Seekers</h3>
                <ul>
                    <li><a href="jobs.php">Browse Jobs</a></li>
                    <li><a href="register.php">Create Account</a></li>
                    <li><a href="dashboard.php">My Applications</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>For Employers</h3>
                <ul>
                    <li><a href="admin.php">Employer Login</a></li>
                    <li><a href="contact.php">Post a Job</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Company</h3>
                <ul>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> NZQRI Jobs. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>