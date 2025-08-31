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
            } elseif ($endpoint === 'subscriptions') {
                getSubscriptions($pdo, $currentUser, $currentTenant);
            } elseif ($endpoint === 'invoices') {
                getInvoices($pdo, $currentUser, $currentTenant);
            } elseif ($endpoint === 'billing-overview') {
                getBillingOverview($pdo, $currentUser, $currentTenant);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
            }
            break;
            
        case 'POST':
            if ($endpoint === 'upgrade-subscription') {
                upgradeSubscription($pdo, $currentUser, $currentTenant);
            } elseif ($endpoint === 'cancel-subscription') {
                cancelSubscription($pdo, $currentUser, $currentTenant);
            } elseif ($endpoint === 'generate-invoice') {
                generateInvoice($pdo, $currentUser, $currentTenant);
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
    error_log("Tenancy Management Simple Error: " . $e->getMessage());
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

function getSubscriptions($pdo, $currentUser, $currentTenant) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $offset = ($page - 1) * $limit;
        
        if ($currentUser['role'] === 'super_admin') {
            // Super admins can see all subscriptions
            $whereConditions = ['1=1'];
            $params = [];
        } else {
            // Regular users can only see their own subscriptions
            if (!$currentTenant) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Tenant not found']);
                return;
            }
            $whereConditions = ['s.tenant_id = ?'];
            $params = [$currentTenant['id']];
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM subscriptions s WHERE $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get subscriptions with tenant info
        $sql = "
            SELECT s.*, t.name as tenant_name 
            FROM subscriptions s 
            LEFT JOIN tenants t ON s.tenant_id = t.id 
            WHERE $whereClause 
            ORDER BY s.created_at DESC 
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $subscriptions = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'subscriptions' => $subscriptions,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
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

function getInvoices($pdo, $currentUser, $currentTenant) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $offset = ($page - 1) * $limit;
        
        if ($currentUser['role'] === 'super_admin') {
            // Super admins can see all invoices
            $whereConditions = ['1=1'];
            $params = [];
        } else {
            // Regular users can only see their own invoices
            if (!$currentTenant) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Tenant not found']);
                return;
            }
            $whereConditions = ['i.tenant_id = ?'];
            $params = [$currentTenant['id']];
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM invoices i WHERE $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get invoices with tenant info
        $sql = "
            SELECT i.*, t.name as tenant_name 
            FROM invoices i 
            LEFT JOIN tenants t ON i.tenant_id = t.id 
            WHERE $whereClause 
            ORDER BY i.created_at DESC 
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $invoices = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'invoices' => $invoices,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
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

function getBillingOverview($pdo, $currentUser, $currentTenant) {
    try {
        if ($currentUser['role'] === 'super_admin') {
            // Super admins get system-wide billing overview
            $sql = "
                SELECT 
                    COALESCE(SUM(amount), 0) as total_revenue,
                    COUNT(*) as total_invoices,
                    COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_invoices,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_invoices
                FROM invoices
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $overview = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'is_super_admin' => true,
                    'total_revenue' => (float)$overview['total_revenue'],
                    'total_invoices' => (int)$overview['total_invoices'],
                    'paid_invoices' => (int)$overview['paid_invoices'],
                    'pending_invoices' => (int)$overview['pending_invoices']
                ]
            ]);
        } else {
            // Regular users get their own billing overview
            if (!$currentTenant) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Tenant not found']);
                return;
            }
            
            $sql = "
                SELECT 
                    COALESCE(SUM(amount), 0) as total_paid,
                    COUNT(*) as total_invoices,
                    COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_invoices,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_invoices
                FROM invoices 
                WHERE tenant_id = ?
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$currentTenant['id']]);
            $overview = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'is_super_admin' => false,
                    'total_paid' => (float)$overview['total_paid'],
                    'total_invoices' => (int)$overview['total_invoices'],
                    'paid_invoices' => (int)$overview['paid_invoices'],
                    'pending_invoices' => (int)$overview['pending_invoices']
                ]
            ]);
        }
    } catch (Exception $e) {
        error_log("Get billing overview error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch billing overview']);
    }
}

function upgradeSubscription($pdo, $currentUser, $currentTenant) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['plan_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Plan name is required']);
            return;
        }
        
        if ($currentUser['role'] === 'super_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Super admins cannot upgrade subscriptions']);
            return;
        }
        
        if (!$currentTenant) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Tenant not found']);
            return;
        }
        
        // Cancel current active subscription
        $cancelSql = "UPDATE subscriptions SET status = 'cancelled', updated_at = NOW() WHERE tenant_id = ? AND status = 'active'";
        $stmt = $pdo->prepare($cancelSql);
        $stmt->execute([$currentTenant['id']]);
        
        // Create new subscription
        $sql = "
            INSERT INTO subscriptions (id, tenant_id, plan_name, amount, billing_cycle, status, created_at, updated_at)
            VALUES (uuid_generate_v4(), ?, ?, ?, ?, 'active', NOW(), NOW())
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $currentTenant['id'],
            $data['plan_name'],
            $data['amount'] ?? 0,
            $data['billing_cycle'] ?? 'monthly'
        ]);
        
        $subscription = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $subscription
        ]);
    } catch (Exception $e) {
        error_log("Upgrade subscription error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to upgrade subscription']);
    }
}

function cancelSubscription($pdo, $currentUser, $currentTenant) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if ($currentUser['role'] === 'super_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Super admins cannot cancel subscriptions']);
            return;
        }
        
        if (!$currentTenant) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Tenant not found']);
            return;
        }
        
        $sql = "
            UPDATE subscriptions 
            SET status = 'cancelled', updated_at = NOW()
            WHERE tenant_id = ? AND status = 'active'
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentTenant['id']]);
        
        $subscription = $stmt->fetch();
        
        if (!$subscription) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'No active subscription found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $subscription
        ]);
    } catch (Exception $e) {
        error_log("Cancel subscription error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to cancel subscription']);
    }
}

function generateInvoice($pdo, $currentUser, $currentTenant) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['amount'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Amount is required']);
            return;
        }
        
        if ($currentUser['role'] === 'super_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Super admins cannot generate invoices']);
            return;
        }
        
        if (!$currentTenant) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Tenant not found']);
            return;
        }
        
        $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        
        $sql = "
            INSERT INTO invoices (id, tenant_id, invoice_number, amount, currency, status, description, created_at, updated_at)
            VALUES (uuid_generate_v4(), ?, ?, ?, ?, 'pending', ?, NOW(), NOW())
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $currentTenant['id'],
            $invoiceNumber,
            $data['amount'],
            $data['currency'] ?? 'GHS',
            $data['description'] ?? 'Subscription payment'
        ]);
        
        $invoice = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $invoice
        ]);
    } catch (Exception $e) {
        error_log("Generate invoice error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to generate invoice']);
    }
}
