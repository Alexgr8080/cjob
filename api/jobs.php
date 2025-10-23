<?php
/**
 * NZQRI Job Management System
 * Jobs API
 * Handles: Get Jobs, Job Details, Create/Update/Delete Jobs (Admin)
 */

define('NZQRI_ACCESS', true);
require_once '../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$action = isset($_GET['action']) ? $_GET['action'] : '';
$jobId = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    switch ($action) {
        case 'list':
            handleGetJobs();
            break;
            
        case 'detail':
            handleGetJobDetail($jobId);
            break;
            
        case 'create':
            requireAdmin();
            handleCreateJob($input);
            break;
            
        case 'update':
            requireAdmin();
            handleUpdateJob($jobId, $input);
            break;
            
        case 'delete':
            requireAdmin();
            handleDeleteJob($jobId);
            break;
            
        case 'toggle-status':
            requireAdmin();
            handleToggleJobStatus($jobId);
            break;
            
        case 'categories':
            handleGetCategories();
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    error_log("Jobs API Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An error occurred. Please try again.'], 500);
}

/**
 * Get Jobs List with Filters
 */
function handleGetJobs() {
    $db = getDB();
    
    // Get filter parameters
    $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
    $type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
    $location = isset($_GET['location']) ? sanitize($_GET['location']) : '';
    $category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : JOBS_PER_PAGE;
    $offset = ($page - 1) * $limit;
    
    // Build query
    $where = ["active = 1", "application_deadline >= CURDATE()"];
    $params = [];
    
    if (!empty($search)) {
        $where[] = "(title LIKE ? OR company LIKE ? OR description LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($type) && $type !== 'all') {
        $where[] = "job_type = ?";
        $params[] = $type;
    }
    
    if (!empty($location) && $location !== 'all') {
        $where[] = "location LIKE ?";
        $params[] = "%$location%";
    }
    
    if (!empty($category) && $category !== 'all') {
        $where[] = "category = ?";
        $params[] = $category;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM jobs WHERE $whereClause";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Get jobs
    $sql = "
        SELECT id, title, job_type, company, location, salary_display, 
               description, requirements, category, application_deadline, views, created_at
        FROM jobs 
        WHERE $whereClause
        ORDER BY created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'jobs' => $jobs,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get Job Detail
 */
function handleGetJobDetail($jobId) {
    if ($jobId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid job ID'], 400);
    }
    
    $db = getDB();
    
    // Get job details
    $stmt = $db->prepare("
        SELECT * FROM jobs WHERE id = ? AND active = 1
    ");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    
    if (!$job) {
        jsonResponse(['success' => false, 'message' => 'Job not found'], 404);
    }
    
    // Increment views
    $stmt = $db->prepare("UPDATE jobs SET views = views + 1 WHERE id = ?");
    $stmt->execute([$jobId]);
    
    // Check if user has applied (if logged in)
    $hasApplied = false;
    if (isLoggedIn()) {
        $stmt = $db->prepare("SELECT id FROM applications WHERE job_id = ? AND user_id = ?");
        $stmt->execute([$jobId, $_SESSION['user_id']]);
        $hasApplied = $stmt->fetch() !== false;
    }
    
    jsonResponse([
        'success' => true,
        'job' => $job,
        'hasApplied' => $hasApplied
    ]);
}

/**
 * Create New Job (Admin Only)
 */
function handleCreateJob($data) {
    // Validate required fields
    $required = ['title', 'job_type', 'company', 'location', 'description', 'requirements', 'category', 'application_deadline'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            jsonResponse(['success' => false, 'message' => "Field '$field' is required"], 400);
        }
    }
    
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO jobs (
            title, job_type, company, location, salary_display, 
            description, requirements, responsibilities, benefits, 
            category, application_deadline, created_by, active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    $result = $stmt->execute([
        sanitize($data['title']),
        sanitize($data['job_type']),
        sanitize($data['company']),
        sanitize($data['location']),
        isset($data['salary_display']) ? sanitize($data['salary_display']) : null,
        sanitize($data['description']),
        sanitize($data['requirements']),
        isset($data['responsibilities']) ? sanitize($data['responsibilities']) : null,
        isset($data['benefits']) ? sanitize($data['benefits']) : null,
        sanitize($data['category']),
        $data['application_deadline'],
        $_SESSION['user_id']
    ]);
    
    if ($result) {
        $jobId = $db->lastInsertId();
        jsonResponse([
            'success' => true,
            'message' => 'Job created successfully',
            'job_id' => $jobId
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to create job'], 500);
    }
}

/**
 * Update Job (Admin Only)
 */
function handleUpdateJob($jobId, $data) {
    if ($jobId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid job ID'], 400);
    }
    
    $db = getDB();
    
    // Check if job exists
    $stmt = $db->prepare("SELECT id FROM jobs WHERE id = ?");
    $stmt->execute([$jobId]);
    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Job not found'], 404);
    }
    
    $stmt = $db->prepare("
        UPDATE jobs SET
            title = ?, job_type = ?, company = ?, location = ?, 
            salary_display = ?, description = ?, requirements = ?,
            responsibilities = ?, benefits = ?, category = ?, 
            application_deadline = ?
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        sanitize($data['title']),
        sanitize($data['job_type']),
        sanitize($data['company']),
        sanitize($data['location']),
        isset($data['salary_display']) ? sanitize($data['salary_display']) : null,
        sanitize($data['description']),
        sanitize($data['requirements']),
        isset($data['responsibilities']) ? sanitize($data['responsibilities']) : null,
        isset($data['benefits']) ? sanitize($data['benefits']) : null,
        sanitize($data['category']),
        $data['application_deadline'],
        $jobId
    ]);
    
    if ($result) {
        jsonResponse(['success' => true, 'message' => 'Job updated successfully']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to update job'], 500);
    }
}

/**
 * Delete Job (Admin Only)
 */
function handleDeleteJob($jobId) {
    if ($jobId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid job ID'], 400);
    }
    
    $db = getDB();
    
    // Soft delete - just set to inactive
    $stmt = $db->prepare("UPDATE jobs SET active = 0 WHERE id = ?");
    
    if ($stmt->execute([$jobId])) {
        jsonResponse(['success' => true, 'message' => 'Job deleted successfully']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to delete job'], 500);
    }
}

/**
 * Toggle Job Status (Admin Only)
 */
function handleToggleJobStatus($jobId) {
    if ($jobId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid job ID'], 400);
    }
    
    $db = getDB();
    
    $stmt = $db->prepare("UPDATE jobs SET active = NOT active WHERE id = ?");
    
    if ($stmt->execute([$jobId])) {
        jsonResponse(['success' => true, 'message' => 'Job status updated successfully']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to update job status'], 500);
    }
}

/**
 * Get Categories List
 */
function handleGetCategories() {
    $db = getDB();
    
    $stmt = $db->query("
        SELECT DISTINCT category, COUNT(*) as count 
        FROM jobs 
        WHERE active = 1 
        GROUP BY category 
        ORDER BY category
    ");
    
    $categories = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'categories' => $categories
    ]);
}
?>