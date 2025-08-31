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

// Include unified authentication
require_once __DIR__ . '/auth/unified-auth.php';

try {
    // Database connection
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_PORT'] ?? '5432',
        $_ENV['DB_NAME'] ?? 'defaultdb'
    );
    
    $pdo = new PDO(
        $dsn,
        $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? 'postgres',
        $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? 'password',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Initialize unified authentication
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
    $auth = new UnifiedAuth($pdo, $jwtSecret);
    
    // Check authentication
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
    
    // Handle requests
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    switch ($method) {
        case 'GET':
            if ($endpoint === 'subscription-status') {
                getSubscriptionStatus($pdo, $currentUser, $currentTenant);
            } elseif ($endpoint === 'billing-info') {
                getBillingInfo($pdo, $currentUser, $currentTenant);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Tenancy Management Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}

function getSubscriptionStatus($pdo, $currentUser, $currentTenant) {
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
    
    try {
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
    
    try {
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
