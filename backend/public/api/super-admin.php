<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../middleware/AuthMiddleware.php';
require_once '../middleware/SuperAdminMiddleware.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Apply authentication middleware
    $auth = new AuthMiddleware($db);
    $user = $auth->authenticate();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
    // Apply Super Admin middleware
    $superAdmin = new SuperAdminMiddleware($db);
    if (!$superAdmin->isSuperAdmin($user)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied. Super Admin required.']);
        exit();
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    switch ($method) {
        case 'GET':
            handleGetRequest($db, $endpoint, $_GET);
            break;
        case 'POST':
            handlePostRequest($db, $endpoint, $_POST);
            break;
        case 'PUT':
            handlePutRequest($db, $endpoint, $_POST);
            break;
        case 'DELETE':
            handleDeleteRequest($db, $endpoint, $_GET);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Super Admin API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handleGetRequest($db, $endpoint, $params) {
    switch ($endpoint) {
        case 'billing':
            getBillingStats($db);
            break;
        case 'subscriptions':
            getSubscriptions($db, $params);
            break;
        case 'tenants':
            getTenants($db, $params);
            break;
        case 'users':
            getUsers($db, $params);
            break;
        case 'activity':
            getActivity($db, $params);
            break;
        case 'health':
            getSystemHealth($db);
            break;
        case 'logs':
            getSystemLogs($db, $params);
            break;
        case 'security-logs':
            getSecurityLogs($db, $params);
            break;
        case 'analytics':
            getAnalytics($db, $params);
            break;
        default:
            // Default dashboard stats
            getDashboardStats($db);
    }
}

function getDashboardStats($db) {
    try {
        // Get total users
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE deleted_at IS NULL");
        $stmt->execute();
        $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get total tenants
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM tenants WHERE deleted_at IS NULL");
        $stmt->execute();
        $totalTenants = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get total products
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM products WHERE deleted_at IS NULL");
        $stmt->execute();
        $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get total sales
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM sales WHERE deleted_at IS NULL");
        $stmt->execute();
        $totalSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stats = [
            'total_users' => (int)$totalUsers,
            'total_tenants' => (int)$totalTenants,
            'total_products' => (int)$totalProducts,
            'total_sales' => (int)$totalSales,
            'system_health' => 'healthy'
        ];
        
        echo json_encode(['success' => true, 'data' => $stats]);
        
    } catch (Exception $e) {
        error_log("Dashboard Stats Error: " . $e->getMessage());
        echo json_encode(['success' => true, 'data' => [
            'total_users' => 0,
            'total_tenants' => 0,
            'total_products' => 0,
            'total_sales' => 0,
            'system_health' => 'error'
        ]]);
    }
}

function getBillingStats($db) {
    try {
        // Get subscription stats
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_subscriptions,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_subscriptions,
                SUM(CASE WHEN status = 'active' THEN amount ELSE 0 END) as total_revenue
            FROM subscriptions 
            WHERE deleted_at IS NULL
        ");
        $stmt->execute();
        $billingStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get monthly revenue
        $stmt = $db->prepare("
            SELECT SUM(amount) as monthly_revenue
            FROM subscriptions 
            WHERE status = 'active' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND deleted_at IS NULL
        ");
        $stmt->execute();
        $monthlyRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['monthly_revenue'] ?? 0;
        
        $stats = [
            'total_subscriptions' => (int)$billingStats['total_subscriptions'],
            'active_subscriptions' => (int)$billingStats['active_subscriptions'],
            'total_revenue' => (float)$billingStats['total_revenue'],
            'monthly_revenue' => (float)$monthlyRevenue,
            'revenue' => [
                'monthly' => (float)$monthlyRevenue,
                'total' => (float)$billingStats['total_revenue']
            ]
        ];
        
        echo json_encode(['success' => true, 'data' => $stats]);
        
    } catch (Exception $e) {
        error_log("Billing Stats Error: " . $e->getMessage());
        echo json_encode(['success' => true, 'data' => [
            'total_subscriptions' => 0,
            'active_subscriptions' => 0,
            'total_revenue' => 0,
            'monthly_revenue' => 0,
            'revenue' => [
                'monthly' => 0,
                'total' => 0
            ]
        ]]);
    }
}

function getSubscriptions($db, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        // Get subscriptions with tenant info
        $stmt = $db->prepare("
            SELECT s.*, t.name as tenant_name, t.email as tenant_email
            FROM subscriptions s
            LEFT JOIN tenants t ON s.tenant_id = t.id
            WHERE s.deleted_at IS NULL
            ORDER BY s.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM subscriptions WHERE deleted_at IS NULL");
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $result = [
            'subscriptions' => $subscriptions,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ];
        
        echo json_encode(['success' => true, 'data' => $result]);
        
    } catch (Exception $e) {
        error_log("Subscriptions Error: " . $e->getMessage());
        echo json_encode(['success' => true, 'data' => [
            'subscriptions' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 0,
                'pages' => 0
            ]
        ]]);
    }
}

function getTenants($db, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        // Get tenants with subscription info
        $stmt = $db->prepare("
            SELECT t.*, s.status as subscription_status, s.plan_name
            FROM tenants t
            LEFT JOIN subscriptions s ON t.id = s.tenant_id AND s.status = 'active'
            WHERE t.deleted_at IS NULL
            ORDER BY t.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM tenants WHERE deleted_at IS NULL");
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $result = [
            'tenants' => $tenants,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ];
        
        echo json_encode(['success' => true, 'data' => $result]);
        
    } catch (Exception $e) {
        error_log("Tenants Error: " . $e->getMessage());
        echo json_encode(['success' => true, 'data' => [
            'tenants' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 0,
                'pages' => 0
            ]
        ]]);
    }
}

function getUsers($db, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        // Get users with tenant info
        $stmt = $db->prepare("
            SELECT u.*, t.name as tenant_name
            FROM users u
            LEFT JOIN tenants t ON u.tenant_id = t.id
            WHERE u.deleted_at IS NULL
            ORDER BY u.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE deleted_at IS NULL");
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $result = [
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ];
        
        echo json_encode(['success' => true, 'data' => $result]);
        
    } catch (Exception $e) {
        error_log("Users Error: " . $e->getMessage());
        echo json_encode(['success' => true, 'data' => [
            'users' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 0,
                'pages' => 0
            ]
        ]]);
    }
}

function getActivity($db, $params) {
    try {
        $limit = isset($params['limit']) ? (int)$params['limit'] : 20;
        
        // Get recent activity (logins, actions, etc.)
        $stmt = $db->prepare("
            SELECT 'login' as type, u.email, u.first_name, u.last_name, u.last_login_at as timestamp
            FROM users u
            WHERE u.last_login_at IS NOT NULL
            UNION ALL
            SELECT 'subscription' as type, t.email, t.name, '', s.created_at as timestamp
            FROM subscriptions s
            JOIN tenants t ON s.tenant_id = t.id
            ORDER BY timestamp DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $activity]);
        
    } catch (Exception $e) {
        error_log("Activity Error: " . $e->getMessage());
        echo json_encode(['success' => true, 'data' => []]);
    }
}

function getSystemHealth($db) {
    try {
        // Check database connection
        $db->query("SELECT 1");
        $dbStatus = 'healthy';
        
        // Get basic system info
        $stmt = $db->prepare("SELECT COUNT(*) as total_users FROM users WHERE deleted_at IS NULL");
        $stmt->execute();
        $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
        
        $health = [
            'status' => 'healthy',
            'database' => $dbStatus,
            'total_users' => (int)$totalUsers,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode(['success' => true, 'data' => $health]);
        
    } catch (Exception $e) {
        error_log("System Health Error: " . $e->getMessage());
        echo json_encode(['success' => true, 'data' => [
            'status' => 'error',
            'database' => 'error',
            'total_users' => 0,
            'timestamp' => date('Y-m-d H:i:s')
        ]]);
    }
}

function getSystemLogs($db, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        $offset = ($page - 1) * $limit;
        
        // Get system logs (if logs table exists)
        $logs = [];
        
        // For now, return empty logs with pagination
        $result = [
            'logs' => $logs,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => 0,
                'pages' => 0
            ]
        ];
        
        echo json_encode(['success' => true, 'data' => $result]);
        
    } catch (Exception $e) {
        error_log("System Logs Error: " . $e->getMessage());
        echo json_encode(['success' => true, 'data' => [
            'logs' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 50,
                'total' => 0,
                'pages' => 0
            ]
        ]]);
    }
}

function getSecurityLogs($db, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        $offset = ($page - 1) * $limit;
        
        // Get security logs (if security_logs table exists)
        $logs = [];
        
        // For now, return empty logs with pagination
        $result = [
            'logs' => $logs,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => 0,
                'pages' => 0
            ]
        ];
        
        echo json_encode(['success' => true, 'data' => $result]);
        
    } catch (Exception $e) {
        error_log("Security Logs Error: " . $e->getMessage());
        echo json_encode(['success' => true, 'data' => [
            'logs' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 50,
                'total' => 0,
                'pages' => 0
            ]
        ]]);
    }
}

function getAnalytics($db, $params) {
    try {
        // Get analytics data
        $analytics = [
            'revenue_30_days' => 0,
            'new_users_30_days' => 0,
            'growth_rate' => 0,
            'active_users' => 0
        ];
        
        // Calculate 30-day revenue
        $stmt = $db->prepare("
            SELECT SUM(amount) as revenue
            FROM subscriptions 
            WHERE status = 'active' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND deleted_at IS NULL
        ");
        $stmt->execute();
        $revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;
        $analytics['revenue_30_days'] = (float)$revenue;
        
        // Calculate new users in 30 days
        $stmt = $db->prepare("
            SELECT COUNT(*) as new_users
            FROM users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND deleted_at IS NULL
        ");
        $stmt->execute();
        $newUsers = $stmt->fetch(PDO::FETCH_ASSOC)['new_users'] ?? 0;
        $analytics['new_users_30_days'] = (int)$newUsers;
        
        // Calculate active users (users with recent login)
        $stmt = $db->prepare("
            SELECT COUNT(*) as active_users
            FROM users 
            WHERE last_login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND deleted_at IS NULL
        ");
        $stmt->execute();
        $activeUsers = $stmt->fetch(PDO::FETCH_ASSOC)['active_users'] ?? 0;
        $analytics['active_users'] = (int)$activeUsers;
        
        echo json_encode(['success' => true, 'data' => $analytics]);
        
    } catch (Exception $e) {
        error_log("Analytics Error: " . $e->getMessage());
        echo json_encode(['success' => true, 'data' => [
            'revenue_30_days' => 0,
            'new_users_30_days' => 0,
            'growth_rate' => 0,
            'active_users' => 0
        ]]);
    }
}

function handlePostRequest($db, $endpoint, $data) {
    // Handle POST requests for creating new records
    echo json_encode(['success' => true, 'message' => 'POST endpoint not implemented yet']);
}

function handlePutRequest($db, $endpoint, $data) {
    // Handle PUT requests for updating records
    echo json_encode(['success' => true, 'message' => 'PUT endpoint not implemented yet']);
}

function handleDeleteRequest($db, $endpoint, $params) {
    // Handle DELETE requests for removing records
    echo json_encode(['success' => true, 'message' => 'DELETE endpoint not implemented yet']);
}
?>
