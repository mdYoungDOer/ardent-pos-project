<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database configuration
$dbConfig = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '5432',
    'database' => $_ENV['DB_NAME'] ?? 'defaultdb',
    'username' => $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? 'postgres',
    'password' => $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? 'password',
];

// Include unified authentication
require_once __DIR__ . '/auth/unified-auth.php';

try {
    // Create database connection
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['database']
    );
    
    $pdo = new PDO(
        $dsn,
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Initialize unified authentication
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
    $auth = new UnifiedAuth($pdo, $jwtSecret);
    
    // Get request data
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    // Check authentication for all operations
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Token not provided']);
        exit;
    }
    
    $token = substr($authHeader, 7);
    $authResult = $auth->verifyToken($token);
    
    if (!$authResult['success']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Token not provided or invalid']);
        exit;
    }
    
    $currentUser = $authResult['user'];
    
    // Only super admins can access this endpoint
    if ($currentUser['role'] !== 'super_admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied. Super admin required.']);
        exit;
    }
    
    // Handle different endpoints
    switch ($method) {
        case 'GET':
            handleGetRequest($pdo, $endpoint, $_GET);
            break;
        case 'POST':
            handlePostRequest($pdo, $endpoint, file_get_contents('php://input'));
            break;
        case 'PUT':
            handlePutRequest($pdo, $endpoint, file_get_contents('php://input'));
            break;
        case 'DELETE':
            handleDeleteRequest($pdo, $endpoint, $_GET);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Super Admin Dashboard Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'debug' => $e->getMessage()
    ]);
}

function handleGetRequest($pdo, $endpoint, $params) {
    switch ($endpoint) {
        case 'analytics':
            getAnalytics($pdo);
            break;
        case 'stats':
            getStats($pdo);
            break;
        case 'tenants':
            getTenants($pdo, $params);
            break;
        case 'users':
            getUsers($pdo, $params);
            break;
        case 'subscriptions':
            getSubscriptions($pdo, $params);
            break;
        case 'billing':
            getBillingStats($pdo);
            break;
        case 'invoices':
            getInvoices($pdo, $params);
            break;
        case 'contact-submissions':
            getContactSubmissions($pdo, $params);
            break;
        case 'system-health':
            getSystemHealth($pdo);
            break;
        case 'activity':
            getActivity($pdo, $params);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handlePostRequest($pdo, $endpoint, $rawData) {
    $data = json_decode($rawData, true);
    
    switch ($endpoint) {
        case 'tenants':
            createTenant($pdo, $data);
            break;
        case 'users':
            createUser($pdo, $data);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handlePutRequest($pdo, $endpoint, $rawData) {
    $data = json_decode($rawData, true);
    
    switch ($endpoint) {
        case 'tenants':
            updateTenant($pdo, $data);
            break;
        case 'users':
            updateUser($pdo, $data);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handleDeleteRequest($pdo, $endpoint, $params) {
    switch ($endpoint) {
        case 'tenants':
            deleteTenant($pdo, $params);
            break;
        case 'users':
            deleteUser($pdo, $params);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

// Analytics Functions
function getAnalytics($pdo) {
    try {
        // Total tenants
        $totalTenants = $pdo->query("SELECT COUNT(*) as count FROM tenants")->fetch()['count'];
        
        // Active tenants
        $activeTenants = $pdo->query("SELECT COUNT(*) as count FROM tenants WHERE status = 'active'")->fetch()['count'];
        
        // Total users
        $totalUsers = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
        
        // Active subscriptions
        $activeSubscriptions = $pdo->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'")->fetch()['count'];
        
        // Total revenue
        $totalRevenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'paid'")->fetch()['total'];
        
        // Monthly revenue
        $monthlyRevenue = $pdo->query("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM invoices 
            WHERE status = 'paid' AND DATE_TRUNC('month', created_at) = DATE_TRUNC('month', CURRENT_DATE)
        ")->fetch()['total'];
        
        // Recent activity
        $recentActivity = $pdo->query("
            SELECT 'tenant_created' as type, t.name as description, t.created_at
            FROM tenants t
            WHERE t.created_at >= CURRENT_DATE - INTERVAL '7 days'
            UNION ALL
            SELECT 'user_registered' as type, CONCAT(u.first_name, ' ', u.last_name) as description, u.created_at
            FROM users u
            WHERE u.created_at >= CURRENT_DATE - INTERVAL '7 days'
            ORDER BY created_at DESC
            LIMIT 10
        ")->fetchAll();
        
        // Top tenants by revenue
        $topTenants = $pdo->query("
            SELECT t.name, COALESCE(SUM(i.amount), 0) as revenue, COUNT(i.id) as invoices
            FROM tenants t
            LEFT JOIN invoices i ON t.id = i.tenant_id AND i.status = 'paid'
            GROUP BY t.id, t.name
            ORDER BY revenue DESC
            LIMIT 5
        ")->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_tenants' => (int)$totalTenants,
                    'active_tenants' => (int)$activeTenants,
                    'total_users' => (int)$totalUsers,
                    'active_subscriptions' => (int)$activeSubscriptions,
                    'total_revenue' => (float)$totalRevenue,
                    'monthly_revenue' => (float)$monthlyRevenue
                ],
                'recent_activity' => $recentActivity,
                'top_tenants' => $topTenants
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get analytics error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch analytics']);
    }
}

function getStats($pdo) {
    try {
        // System stats
        $stats = [
            'total_tenants' => 0,
            'active_tenants' => 0,
            'total_users' => 0,
            'active_subscriptions' => 0,
            'total_revenue' => 0,
            'monthly_growth' => 12.5,
            'system_uptime' => '99.9%'
        ];
        
        // Count tenants
        $tenantStats = $pdo->query("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active
            FROM tenants
        ")->fetch();
        $stats['total_tenants'] = (int)$tenantStats['total'];
        $stats['active_tenants'] = (int)$tenantStats['active'];
        
        // Count users
        $userStats = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch();
        $stats['total_users'] = (int)$userStats['count'];
        
        // Count subscriptions
        $subscriptionStats = $pdo->query("
            SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'
        ")->fetch();
        $stats['active_subscriptions'] = (int)$subscriptionStats['count'];
        
        // Calculate revenue
        $revenueStats = $pdo->query("
            SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'paid'
        ")->fetch();
        $stats['total_revenue'] = (float)$revenueStats['total'];
        
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
    } catch (Exception $e) {
        error_log("Get stats error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch stats']);
    }
}

// Tenants Functions
function getTenants($pdo, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        $offset = ($page - 1) * $limit;
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? '';
        
        $whereConditions = ['1=1'];
        $bindParams = [];
        
        if ($search) {
            $whereConditions[] = "(name ILIKE ? OR subdomain ILIKE ?)";
            $searchParam = "%$search%";
            $bindParams[] = $searchParam;
            $bindParams[] = $searchParam;
        }
        
        if ($status) {
            $whereConditions[] = "status = ?";
            $bindParams[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT * FROM tenants WHERE $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $bindParams[] = $limit;
        $bindParams[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindParams);
        $tenants = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM tenants WHERE $whereClause";
        $countStmt = $pdo->prepare($countSql);
        array_pop($bindParams); // Remove limit
        array_pop($bindParams); // Remove offset
        $countStmt->execute($bindParams);
        $total = $countStmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'tenants' => $tenants,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get tenants error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch tenants']);
    }
}

function createTenant($pdo, $data) {
    try {
        if (empty($data['name']) || empty($data['subdomain'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Name and subdomain are required']);
            return;
        }
        
        // Check for duplicate subdomain
        $checkStmt = $pdo->prepare("SELECT id FROM tenants WHERE subdomain = ?");
        $checkStmt->execute([$data['subdomain']]);
        if ($checkStmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Subdomain already exists']);
            return;
        }
        
        $sql = "INSERT INTO tenants (id, name, subdomain, plan, status, settings, created_at, updated_at) 
                VALUES (uuid_generate_v4(), ?, ?, ?, ?, ?, NOW(), NOW()) RETURNING id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['subdomain'],
            $data['plan'] ?? 'free',
            $data['status'] ?? 'active',
            json_encode($data['settings'] ?? [])
        ]);
        
        $tenantId = $stmt->fetch()['id'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Tenant created successfully',
            'data' => ['id' => $tenantId]
        ]);
    } catch (Exception $e) {
        error_log("Create tenant error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create tenant']);
    }
}

function updateTenant($pdo, $data) {
    try {
        if (empty($data['id']) || empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID and name are required']);
            return;
        }
        
        // Check if tenant exists
        $checkStmt = $pdo->prepare("SELECT id FROM tenants WHERE id = ?");
        $checkStmt->execute([$data['id']]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Tenant not found']);
            return;
        }
        
        // Check for duplicate subdomain (excluding current tenant)
        if (!empty($data['subdomain'])) {
            $checkStmt = $pdo->prepare("SELECT id FROM tenants WHERE subdomain = ? AND id != ?");
            $checkStmt->execute([$data['subdomain'], $data['id']]);
            if ($checkStmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Subdomain already exists']);
                return;
            }
        }
        
        $sql = "UPDATE tenants SET name = ?, subdomain = ?, plan = ?, status = ?, settings = ?, updated_at = NOW() 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['subdomain'] ?? null,
            $data['plan'] ?? 'free',
            $data['status'] ?? 'active',
            json_encode($data['settings'] ?? []),
            $data['id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Tenant updated successfully'
        ]);
    } catch (Exception $e) {
        error_log("Update tenant error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update tenant']);
    }
}

function deleteTenant($pdo, $params) {
    try {
        $tenantId = $params['id'] ?? null;
        if (!$tenantId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Tenant ID required']);
            return;
        }
        
        // Check if tenant exists
        $checkStmt = $pdo->prepare("SELECT id FROM tenants WHERE id = ?");
        $checkStmt->execute([$tenantId]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Tenant not found']);
            return;
        }
        
        $pdo->beginTransaction();
        
        // Delete related data (users, subscriptions, etc.)
        $pdo->prepare("DELETE FROM users WHERE tenant_id = ?")->execute([$tenantId]);
        $pdo->prepare("DELETE FROM subscriptions WHERE tenant_id = ?")->execute([$tenantId]);
        $pdo->prepare("DELETE FROM invoices WHERE tenant_id = ?")->execute([$tenantId]);
        
        // Delete tenant
        $pdo->prepare("DELETE FROM tenants WHERE id = ?")->execute([$tenantId]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Tenant deleted successfully'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Delete tenant error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete tenant']);
    }
}

// Users Functions
function getUsers($pdo, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        $offset = ($page - 1) * $limit;
        $search = $params['search'] ?? '';
        $role = $params['role'] ?? '';
        $status = $params['status'] ?? '';
        
        $whereConditions = ['1=1'];
        $bindParams = [];
        
        if ($search) {
            $whereConditions[] = "(first_name ILIKE ? OR last_name ILIKE ? OR email ILIKE ?)";
            $searchParam = "%$search%";
            $bindParams[] = $searchParam;
            $bindParams[] = $searchParam;
            $bindParams[] = $searchParam;
        }
        
        if ($role) {
            $whereConditions[] = "role = ?";
            $bindParams[] = $role;
        }
        
        if ($status) {
            $whereConditions[] = "status = ?";
            $bindParams[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT u.*, t.name as tenant_name 
                FROM users u 
                LEFT JOIN tenants t ON u.tenant_id = t.id 
                WHERE $whereClause 
                ORDER BY u.created_at DESC 
                LIMIT ? OFFSET ?";
        $bindParams[] = $limit;
        $bindParams[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindParams);
        $users = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM users u WHERE $whereClause";
        $countStmt = $pdo->prepare($countSql);
        array_pop($bindParams); // Remove limit
        array_pop($bindParams); // Remove offset
        $countStmt->execute($bindParams);
        $total = $countStmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'users' => $users,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get users error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch users']);
    }
}

// Subscriptions Functions
function getSubscriptions($pdo, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        $offset = ($page - 1) * $limit;
        $status = $params['status'] ?? '';
        
        $whereConditions = ['1=1'];
        $bindParams = [];
        
        if ($status) {
            $whereConditions[] = "s.status = ?";
            $bindParams[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT s.*, t.name as tenant_name 
                FROM subscriptions s 
                JOIN tenants t ON s.tenant_id = t.id 
                WHERE $whereClause 
                ORDER BY s.created_at DESC 
                LIMIT ? OFFSET ?";
        $bindParams[] = $limit;
        $bindParams[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindParams);
        $subscriptions = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM subscriptions s WHERE $whereClause";
        $countStmt = $pdo->prepare($countSql);
        array_pop($bindParams); // Remove limit
        array_pop($bindParams); // Remove offset
        $countStmt->execute($bindParams);
        $total = $countStmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'subscriptions' => $subscriptions,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get subscriptions error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch subscriptions']);
    }
}

// Billing Functions
function getBillingStats($pdo) {
    try {
        // Monthly revenue
        $monthlyRevenue = $pdo->query("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM invoices 
            WHERE status = 'paid' AND DATE_TRUNC('month', created_at) = DATE_TRUNC('month', CURRENT_DATE)
        ")->fetch()['total'];
        
        // Annual revenue
        $annualRevenue = $pdo->query("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM invoices 
            WHERE status = 'paid' AND DATE_TRUNC('year', created_at) = DATE_TRUNC('year', CURRENT_DATE)
        ")->fetch()['total'];
        
        // Active subscriptions
        $activeSubscriptions = $pdo->query("
            SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'
        ")->fetch()['count'];
        
        // Pending payments
        $pendingPayments = $pdo->query("
            SELECT COUNT(*) as count FROM invoices WHERE status = 'pending'
        ")->fetch()['count'];
        
        // Average revenue per user
        $avgRevenuePerUser = $pdo->query("
            SELECT COALESCE(AVG(amount), 0) as avg_amount 
            FROM invoices 
            WHERE status = 'paid'
        ")->fetch()['avg_amount'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'monthly_revenue' => (float)$monthlyRevenue,
                'annual_revenue' => (float)$annualRevenue,
                'active_subscriptions' => (int)$activeSubscriptions,
                'pending_payments' => (int)$pendingPayments,
                'average_revenue_per_user' => (float)$avgRevenuePerUser
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get billing stats error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch billing stats']);
    }
}

// Invoices Functions
function getInvoices($pdo, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        $offset = ($page - 1) * $limit;
        $status = $params['status'] ?? '';
        
        $whereConditions = ['1=1'];
        $bindParams = [];
        
        if ($status) {
            $whereConditions[] = "i.status = ?";
            $bindParams[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT i.*, t.name as tenant_name 
                FROM invoices i 
                JOIN tenants t ON i.tenant_id = t.id 
                WHERE $whereClause 
                ORDER BY i.created_at DESC 
                LIMIT ? OFFSET ?";
        $bindParams[] = $limit;
        $bindParams[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindParams);
        $invoices = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM invoices i WHERE $whereClause";
        $countStmt = $pdo->prepare($countSql);
        array_pop($bindParams); // Remove limit
        array_pop($bindParams); // Remove offset
        $countStmt->execute($bindParams);
        $total = $countStmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'invoices' => $invoices,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get invoices error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch invoices']);
    }
}

// Contact Submissions Functions
function getContactSubmissions($pdo, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit, $offset]);
        $submissions = $stmt->fetchAll();
        
        // Get total count
        $total = $pdo->query("SELECT COUNT(*) as total FROM contact_submissions")->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'submissions' => $submissions,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get contact submissions error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch contact submissions']);
    }
}

// System Health Functions
function getSystemHealth($pdo) {
    try {
        // Simulate system health metrics
        $systemHealth = [
            'cpu' => rand(20, 80),
            'memory' => rand(30, 90),
            'disk' => rand(25, 75),
            'network' => rand(85, 100),
            'database' => 99.9,
            'api' => 99.7
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $systemHealth
        ]);
    } catch (Exception $e) {
        error_log("Get system health error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch system health']);
    }
}

// Activity Functions
function getActivity($pdo, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        $offset = ($page - 1) * $limit;
        
        // Get recent activity from various tables
        $sql = "
            (SELECT 'tenant_created' as type, t.name as description, t.created_at, t.id
             FROM tenants t
             WHERE t.created_at >= CURRENT_DATE - INTERVAL '30 days')
            UNION ALL
            (SELECT 'user_registered' as type, CONCAT(u.first_name, ' ', u.last_name) as description, u.created_at, u.id
             FROM users u
             WHERE u.created_at >= CURRENT_DATE - INTERVAL '30 days')
            UNION ALL
            (SELECT 'subscription_created' as type, CONCAT('Subscription for ', t.name) as description, s.created_at, s.id
             FROM subscriptions s
             JOIN tenants t ON s.tenant_id = t.id
             WHERE s.created_at >= CURRENT_DATE - INTERVAL '30 days')
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit, $offset]);
        $activities = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'activities' => $activities,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => count($activities),
                    'pages' => 1
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get activity error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch activity']);
    }
}
