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
    $currentTenant = $authResult['tenant'];
    
    // Handle different endpoints
    switch ($method) {
        case 'GET':
            handleGetRequest($pdo, $endpoint, $_GET, $currentUser, $currentTenant);
            break;
        case 'POST':
            handlePostRequest($pdo, $endpoint, file_get_contents('php://input'), $currentUser, $currentTenant);
            break;
        case 'PUT':
            handlePutRequest($pdo, $endpoint, file_get_contents('php://input'), $currentUser, $currentTenant);
            break;
        case 'DELETE':
            handleDeleteRequest($pdo, $endpoint, $_GET, $currentUser, $currentTenant);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Tenancy Management Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'debug' => $e->getMessage()
    ]);
}

function handleGetRequest($pdo, $endpoint, $params, $currentUser, $currentTenant) {
    switch ($endpoint) {
        case 'tenants':
            getTenants($pdo, $params, $currentUser);
            break;
        case 'tenant-info':
            getTenantInfo($pdo, $currentUser, $currentTenant);
            break;
        case 'subscription-status':
            getSubscriptionStatus($pdo, $currentUser, $currentTenant);
            break;
        case 'billing-info':
            getBillingInfo($pdo, $currentUser, $currentTenant);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handlePostRequest($pdo, $endpoint, $rawData, $currentUser, $currentTenant) {
    $data = json_decode($rawData, true);
    
    switch ($endpoint) {
        case 'tenants':
            createTenant($pdo, $data, $currentUser);
            break;
        case 'upgrade-subscription':
            upgradeSubscription($pdo, $data, $currentUser, $currentTenant);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handlePutRequest($pdo, $endpoint, $rawData, $currentUser, $currentTenant) {
    $data = json_decode($rawData, true);
    
    switch ($endpoint) {
        case 'tenants':
            updateTenant($pdo, $data, $currentUser);
            break;
        case 'tenant-settings':
            updateTenantSettings($pdo, $data, $currentUser, $currentTenant);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handleDeleteRequest($pdo, $endpoint, $params, $currentUser, $currentTenant) {
    switch ($endpoint) {
        case 'tenants':
            deleteTenant($pdo, $params, $currentUser);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

// Tenancy Functions
function getTenants($pdo, $params, $currentUser) {
    try {
        // Only super admins can view all tenants
        if ($currentUser['role'] !== 'super_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied. Super admin required.']);
            return;
        }
        
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

function getTenantInfo($pdo, $currentUser, $currentTenant) {
    try {
        // Super admins don't have tenant info
        if ($currentUser['role'] === 'super_admin') {
            echo json_encode([
                'success' => true,
                'data' => [
                    'is_super_admin' => true,
                    'tenant' => null,
                    'subscription' => null,
                    'billing' => null
                ]
            ]);
            return;
        }
        
        // Regular users get their tenant info
        if (!$currentTenant) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Tenant not found']);
            return;
        }
        
        // Get subscription info
        $subscriptionSql = "SELECT * FROM subscriptions WHERE tenant_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1";
        $subscriptionStmt = $pdo->prepare($subscriptionSql);
        $subscriptionStmt->execute([$currentTenant['id']]);
        $subscription = $subscriptionStmt->fetch();
        
        // Get billing info
        $billingSql = "SELECT * FROM invoices WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 5";
        $billingStmt = $pdo->prepare($billingSql);
        $billingStmt->execute([$currentTenant['id']]);
        $invoices = $billingStmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'is_super_admin' => false,
                'tenant' => $currentTenant,
                'subscription' => $subscription,
                'billing' => [
                    'invoices' => $invoices,
                    'total_paid' => array_sum(array_column($invoices, 'amount'))
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get tenant info error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch tenant info']);
    }
}

function getSubscriptionStatus($pdo, $currentUser, $currentTenant) {
    try {
        // Super admins don't have subscriptions
        if ($currentUser['role'] === 'super_admin') {
            echo json_encode([
                'success' => true,
                'data' => [
                    'is_super_admin' => true,
                    'subscription' => null,
                    'plan' => 'enterprise',
                    'status' => 'active',
                    'no_payment_required' => true
                ]
            ]);
            return;
        }
        
        // Regular users get their subscription status
        if (!$currentTenant) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Tenant not found']);
            return;
        }
        
        $sql = "SELECT * FROM subscriptions WHERE tenant_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentTenant['id']]);
        $subscription = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'is_super_admin' => false,
                'subscription' => $subscription,
                'plan' => $subscription ? $subscription['plan_name'] : 'free',
                'status' => $subscription ? $subscription['status'] : 'active',
                'no_payment_required' => false
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get subscription status error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch subscription status']);
    }
}

function getBillingInfo($pdo, $currentUser, $currentTenant) {
    try {
        // Super admins don't have billing info
        if ($currentUser['role'] === 'super_admin') {
            echo json_encode([
                'success' => true,
                'data' => [
                    'is_super_admin' => true,
                    'billing' => null,
                    'invoices' => [],
                    'total_paid' => 0,
                    'no_billing_required' => true
                ]
            ]);
            return;
        }
        
        // Regular users get their billing info
        if (!$currentTenant) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Tenant not found']);
            return;
        }
        
        $sql = "SELECT * FROM invoices WHERE tenant_id = ? ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentTenant['id']]);
        $invoices = $stmt->fetchAll();
        
        $totalPaid = array_sum(array_column(array_filter($invoices, function($inv) {
            return $inv['status'] === 'paid';
        }), 'amount'));
        
        echo json_encode([
            'success' => true,
            'data' => [
                'is_super_admin' => false,
                'billing' => [
                    'invoices' => $invoices,
                    'total_paid' => $totalPaid,
                    'pending_amount' => array_sum(array_column(array_filter($invoices, function($inv) {
                        return $inv['status'] === 'pending';
                    }), 'amount'))
                ],
                'no_billing_required' => false
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get billing info error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch billing info']);
    }
}

function createTenant($pdo, $data, $currentUser) {
    try {
        // Only super admins can create tenants
        if ($currentUser['role'] !== 'super_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied. Super admin required.']);
            return;
        }
        
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

function updateTenant($pdo, $data, $currentUser) {
    try {
        // Only super admins can update tenants
        if ($currentUser['role'] !== 'super_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied. Super admin required.']);
            return;
        }
        
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

function deleteTenant($pdo, $params, $currentUser) {
    try {
        // Only super admins can delete tenants
        if ($currentUser['role'] !== 'super_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied. Super admin required.']);
            return;
        }
        
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

function upgradeSubscription($pdo, $data, $currentUser, $currentTenant) {
    try {
        // Super admins don't need subscriptions
        if ($currentUser['role'] === 'super_admin') {
            echo json_encode([
                'success' => true,
                'message' => 'Super admins do not require subscriptions',
                'data' => [
                    'is_super_admin' => true,
                    'no_payment_required' => true
                ]
            ]);
            return;
        }
        
        // Regular users can upgrade their subscription
        if (!$currentTenant) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Tenant not found']);
            return;
        }
        
        if (empty($data['plan_name']) || empty($data['amount'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Plan name and amount are required']);
            return;
        }
        
        // Create new subscription
        $sql = "INSERT INTO subscriptions (id, tenant_id, plan_name, status, amount, currency, billing_cycle, created_at, updated_at) 
                VALUES (uuid_generate_v4(), ?, ?, 'active', ?, 'GHS', 'monthly', NOW(), NOW()) RETURNING id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $currentTenant['id'],
            $data['plan_name'],
            $data['amount']
        ]);
        
        $subscriptionId = $stmt->fetch()['id'];
        
        // Update tenant plan
        $updateSql = "UPDATE tenants SET plan = ?, updated_at = NOW() WHERE id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$data['plan_name'], $currentTenant['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Subscription upgraded successfully',
            'data' => ['subscription_id' => $subscriptionId]
        ]);
    } catch (Exception $e) {
        error_log("Upgrade subscription error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to upgrade subscription']);
    }
}

function updateTenantSettings($pdo, $data, $currentUser, $currentTenant) {
    try {
        // Super admins don't have tenant settings
        if ($currentUser['role'] === 'super_admin') {
            echo json_encode([
                'success' => true,
                'message' => 'Super admins do not have tenant settings',
                'data' => ['is_super_admin' => true]
            ]);
            return;
        }
        
        // Regular users can update their tenant settings
        if (!$currentTenant) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Tenant not found']);
            return;
        }
        
        $sql = "UPDATE tenants SET settings = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            json_encode($data['settings'] ?? []),
            $currentTenant['id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Tenant settings updated successfully'
        ]);
    } catch (Exception $e) {
        error_log("Update tenant settings error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update tenant settings']);
    }
}
