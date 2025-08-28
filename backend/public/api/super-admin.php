<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../middleware/AuthMiddleware.php';
require_once '../middleware/SuperAdminMiddleware.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $auth = new AuthMiddleware($db);
    $user = $auth->authenticate();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    
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
        case 'contact-submissions':
            getContactSubmissions($db, $params);
            break;
        default:
            getDashboardStats($db);
    }
}

function getDashboardStats($db) {
    try {
        $stats = [
            'total_users' => 0,
            'total_tenants' => 0,
            'total_products' => 0,
            'total_sales' => 0,
            'system_health' => 'healthy'
        ];
        
        // Get total users
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE deleted_at IS NULL");
            $stmt->execute();
            $stats['total_users'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            error_log("Users count failed: " . $e->getMessage());
        }
        
        // Get total tenants
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM tenants WHERE deleted_at IS NULL");
            $stmt->execute();
            $stats['total_tenants'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            error_log("Tenants count failed: " . $e->getMessage());
        }
        
        // Get total products
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM products WHERE deleted_at IS NULL");
            $stmt->execute();
            $stats['total_products'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            error_log("Products count failed: " . $e->getMessage());
        }
        
        // Get total sales
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM sales WHERE deleted_at IS NULL");
            $stmt->execute();
            $stats['total_sales'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            error_log("Sales count failed: " . $e->getMessage());
        }
        
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
        $stats = [
            'total_subscriptions' => 0,
            'active_subscriptions' => 0,
            'total_revenue' => 0,
            'monthly_revenue' => 0,
            'revenue' => [
                'monthly' => 0,
                'total' => 0
            ]
        ];
        
        try {
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_subscriptions,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_subscriptions,
                    COALESCE(SUM(CASE WHEN status = 'active' THEN amount ELSE 0 END), 0) as total_revenue
                FROM subscriptions 
                WHERE deleted_at IS NULL
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stats['total_subscriptions'] = (int)$result['total_subscriptions'];
            $stats['active_subscriptions'] = (int)$result['active_subscriptions'];
            $stats['total_revenue'] = (float)$result['total_revenue'];
            $stats['revenue']['total'] = (float)$result['total_revenue'];
            
            // Monthly revenue
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) as monthly_revenue
                FROM subscriptions 
                WHERE status = 'active' 
                AND created_at >= NOW() - INTERVAL '30 days'
                AND deleted_at IS NULL
            ");
            $stmt->execute();
            $monthlyRevenue = (float)$stmt->fetch(PDO::FETCH_ASSOC)['monthly_revenue'];
            $stats['monthly_revenue'] = $monthlyRevenue;
            $stats['revenue']['monthly'] = $monthlyRevenue;
            
        } catch (Exception $e) {
            error_log("Billing query failed: " . $e->getMessage());
        }
        
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
        
        $subscriptions = [];
        $total = 0;
        
        try {
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
            
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM subscriptions WHERE deleted_at IS NULL");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
        } catch (Exception $e) {
            error_log("Subscriptions query failed: " . $e->getMessage());
        }
        
        echo json_encode(['success' => true, 'data' => [
            'subscriptions' => $subscriptions,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]]);
        
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
        
        $tenants = [];
        $total = 0;
        
        try {
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
            
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM tenants WHERE deleted_at IS NULL");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
        } catch (Exception $e) {
            error_log("Tenants query failed: " . $e->getMessage());
        }
        
        echo json_encode(['success' => true, 'data' => [
            'tenants' => $tenants,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]]);
        
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
        
        $users = [];
        $total = 0;
        
        try {
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
            
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE deleted_at IS NULL");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
        } catch (Exception $e) {
            error_log("Users query failed: " . $e->getMessage());
        }
        
        echo json_encode(['success' => true, 'data' => [
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]]);
        
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
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        $activities = [];
        $total = 0;
        
        try {
            // Get recent user activities
            $stmt = $db->prepare("
                SELECT 
                    'user_login' as type,
                    u.email as user_email,
                    u.last_activity_at as activity_time,
                    'User logged in' as description
                FROM users u
                WHERE u.last_activity_at IS NOT NULL
                AND u.deleted_at IS NULL
                ORDER BY u.last_activity_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total = count($activities);
            
        } catch (Exception $e) {
            error_log("Activity query failed: " . $e->getMessage());
        }
        
        echo json_encode(['success' => true, 'data' => [
            'activities' => $activities,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]]);
        
    } catch (Exception $e) {
        error_log("Activity Error: " . $e->getMessage());
        echo json_encode(['success' => true, 'data' => [
            'activities' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 0,
                'pages' => 0
            ]
        ]]);
    }
}

function getSystemHealth($db) {
    try {
        $health = [
            'database' => 'healthy',
            'api' => 'healthy',
            'authentication' => 'healthy',
            'overall' => 'healthy'
        ];
        
        // Test database connection
        try {
            $stmt = $db->prepare("SELECT 1");
            $stmt->execute();
            $health['database'] = 'healthy';
        } catch (Exception $e) {
            $health['database'] = 'error';
            $health['overall'] = 'degraded';
        }
        
        echo json_encode(['success' => true, 'data' => $health]);
        
    } catch (Exception $e) {
        error_log("System Health Error: " . $e->getMessage());
        echo json_encode(['success' => true, 'data' => [
            'database' => 'error',
            'api' => 'error',
            'authentication' => 'error',
            'overall' => 'error'
        ]]);
    }
}

function getSystemLogs($db, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        $offset = ($page - 1) * $limit;
        
        // Return empty logs for now
        $logs = [];
        
        echo json_encode(['success' => true, 'data' => [
            'logs' => $logs,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => 0,
                'pages' => 0
            ]
        ]]);
        
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

function getContactSubmissions($db, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        $submissions = [];
        $total = 0;
        
        try {
            $stmt = $db->prepare("
                SELECT * FROM contact_submissions 
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM contact_submissions");
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
        } catch (Exception $e) {
            error_log("Contact submissions query failed: " . $e->getMessage());
        }
        
        echo json_encode(['success' => true, 'data' => [
            'submissions' => $submissions,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]]);
        
    } catch (Exception $e) {
        error_log("Contact Submissions Error: " . $e->getMessage());
        echo json_encode(['success' => true, 'data' => [
            'submissions' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 0,
                'pages' => 0
            ]
        ]]);
    }
}
?>
