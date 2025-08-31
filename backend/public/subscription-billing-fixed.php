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

// Paystack configuration
$paystackSecretKey = $_ENV['PAYSTACK_SECRET_KEY'] ?? '';
$paystackPublicKey = $_ENV['PAYSTACK_PUBLIC_KEY'] ?? '';

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
    error_log("Subscription Billing Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'debug' => $e->getMessage()
    ]);
}

function handleGetRequest($pdo, $endpoint, $params, $currentUser, $currentTenant) {
    switch ($endpoint) {
        case 'plans':
            getSubscriptionPlans($pdo, $params);
            break;
        case 'current':
            getCurrentSubscription($pdo, $currentUser, $currentTenant);
            break;
        case 'invoices':
            getInvoices($pdo, $params, $currentUser, $currentTenant);
            break;
        case 'usage':
            getUsageStats($pdo, $currentUser, $currentTenant);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handlePostRequest($pdo, $endpoint, $rawData, $currentUser, $currentTenant) {
    $data = json_decode($rawData, true);
    
    switch ($endpoint) {
        case 'upgrade':
            upgradeSubscription($pdo, $data, $currentUser, $currentTenant);
            break;
        case 'cancel':
            cancelSubscription($pdo, $data, $currentUser, $currentTenant);
            break;
        case 'initialize-payment':
            initializePayment($pdo, $data, $currentUser, $currentTenant);
            break;
        case 'verify-payment':
            verifyPayment($pdo, $data);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handlePutRequest($pdo, $endpoint, $rawData, $currentUser, $currentTenant) {
    $data = json_decode($rawData, true);
    
    switch ($endpoint) {
        case 'update-billing':
            updateBillingInfo($pdo, $data, $currentUser, $currentTenant);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handleDeleteRequest($pdo, $endpoint, $params, $currentUser, $currentTenant) {
    switch ($endpoint) {
        case 'cancel':
            cancelSubscription($pdo, [], $currentUser, $currentTenant);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

// Core functions will be added in the next part
function getSubscriptionPlans($pdo, $params) {
    try {
        $sql = "SELECT * FROM subscription_plans WHERE is_active = true ORDER BY monthly_price ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $plans = $stmt->fetchAll();
        
        // Parse JSON fields
        foreach ($plans as &$plan) {
            $plan['features'] = json_decode($plan['features'], true) ?? [];
            $plan['limits'] = json_decode($plan['limits'], true) ?? [];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $plans
        ]);
    } catch (Exception $e) {
        error_log("Get subscription plans error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch subscription plans']);
    }
}

function getCurrentSubscription($pdo, $currentUser, $currentTenant) {
    try {
        $sql = "SELECT s.*, sp.name as plan_name, sp.description as plan_description 
                FROM subscriptions s
                LEFT JOIN subscription_plans sp ON s.plan_id = sp.plan_id
                WHERE s.tenant_id = ?
                ORDER BY s.created_at DESC
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentTenant['id']]);
        $subscription = $stmt->fetch();
        
        if (!$subscription) {
            // Create default free subscription
            $subscription = [
                'id' => null,
                'plan_id' => 'free',
                'plan_name' => 'Free Plan',
                'plan_description' => 'Basic free plan for new users',
                'status' => 'active',
                'amount' => 0.00,
                'currency' => 'GHS',
                'billing_cycle' => 'monthly',
                'created_at' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $subscription
        ]);
    } catch (Exception $e) {
        error_log("Get current subscription error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch current subscription']);
    }
}

function getInvoices($pdo, $params, $currentUser, $currentTenant) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 20;
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT i.*, s.plan_id, s.plan_name
                FROM invoices i
                LEFT JOIN subscriptions s ON i.subscription_id = s.id
                WHERE i.tenant_id = ?
                ORDER BY i.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute([$currentTenant['id']]);
        $invoices = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM invoices WHERE tenant_id = ?";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([$currentTenant['id']]);
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

function getUsageStats($pdo, $currentUser, $currentTenant) {
    try {
        $currentMonth = date('Y-m-01');
        
        // Get current subscription
        $subscriptionSql = "SELECT s.*, sp.limits 
                           FROM subscriptions s
                           LEFT JOIN subscription_plans sp ON s.plan_id = sp.plan_id
                           WHERE s.tenant_id = ?
                           ORDER BY s.created_at DESC
                           LIMIT 1";
        $subscriptionStmt = $pdo->prepare($subscriptionSql);
        $subscriptionStmt->execute([$currentTenant['id']]);
        $subscription = $subscriptionStmt->fetch();
        
        $limits = json_decode($subscription['limits'] ?? '{}', true) ?? [];
        
        // Calculate usage
        $usage = [
            'products' => 0,
            'users' => 0,
            'sales_this_month' => 0,
            'storage_used_mb' => 0
        ];
        
        // Count products
        $productsSql = "SELECT COUNT(*) as count FROM products WHERE tenant_id = ?";
        $productsStmt = $pdo->prepare($productsSql);
        $productsStmt->execute([$currentTenant['id']]);
        $usage['products'] = (int)$productsStmt->fetch()['count'];
        
        // Count users
        $usersSql = "SELECT COUNT(*) as count FROM users WHERE tenant_id = ?";
        $usersStmt = $pdo->prepare($usersSql);
        $usersStmt->execute([$currentTenant['id']]);
        $usage['users'] = (int)$usersStmt->fetch()['count'];
        
        // Count sales this month
        $salesSql = "SELECT COUNT(*) as count FROM sales WHERE tenant_id = ? AND created_at >= ?";
        $salesStmt = $pdo->prepare($salesSql);
        $salesStmt->execute([$currentTenant['id'], $currentMonth]);
        $usage['sales_this_month'] = (int)$salesStmt->fetch()['count'];
        
        // Calculate limits
        $limits = [
            'products' => $limits['products'] ?? 100,
            'users' => $limits['users'] ?? 3,
            'sales_per_month' => $limits['transactions_per_month'] ?? 1000,
            'storage_gb' => $limits['storage_gb'] ?? 5
        ];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'usage' => $usage,
                'limits' => $limits,
                'subscription' => $subscription
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get usage stats error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch usage stats']);
    }
}

function upgradeSubscription($pdo, $data, $currentUser, $currentTenant) {
    try {
        if (empty($data['plan_id']) || empty($data['billing_cycle'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Plan ID and billing cycle are required']);
            return;
        }
        
        // Get plan details
        $planSql = "SELECT * FROM subscription_plans WHERE plan_id = ? AND is_active = true";
        $planStmt = $pdo->prepare($planSql);
        $planStmt->execute([$data['plan_id']]);
        $plan = $planStmt->fetch();
        
        if (!$plan) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid plan selected']);
            return;
        }
        
        // Calculate amount based on billing cycle
        $amount = $data['billing_cycle'] === 'yearly' ? $plan['yearly_price'] : $plan['monthly_price'];
        
        // Create new subscription record
        $subscriptionSql = "INSERT INTO subscriptions (id, tenant_id, plan_id, status, amount, currency, billing_cycle, created_at, updated_at) 
                           VALUES (uuid_generate_v4(), ?, ?, 'pending', ?, 'GHS', ?, NOW(), NOW()) RETURNING id";
        $subscriptionStmt = $pdo->prepare($subscriptionSql);
        $subscriptionStmt->execute([
            $currentTenant['id'],
            $data['plan_id'],
            $amount,
            $data['billing_cycle']
        ]);
        
        $subscriptionId = $subscriptionStmt->fetch()['id'];
        
        // Initialize Paystack payment
        $paymentData = [
            'email' => $currentUser['email'],
            'amount' => $amount * 100, // Convert to kobo
            'currency' => 'GHS',
            'reference' => 'sub_' . $subscriptionId . '_' . time(),
            'callback_url' => $_ENV['APP_URL'] . '/subscription/success',
            'metadata' => [
                'subscription_id' => $subscriptionId,
                'tenant_id' => $currentTenant['id'],
                'plan_id' => $data['plan_id'],
                'billing_cycle' => $data['billing_cycle']
            ]
        ];
        
        $paystackResponse = makePaystackRequest('transaction/initialize', $paymentData);
        
        if ($paystackResponse['status'] === true) {
            echo json_encode([
                'success' => true,
                'message' => 'Subscription upgrade initiated',
                'data' => [
                    'subscription_id' => $subscriptionId,
                    'authorization_url' => $paystackResponse['data']['authorization_url'],
                    'reference' => $paystackResponse['data']['reference']
                ]
            ]);
        } else {
            // Rollback subscription creation
            $pdo->prepare("DELETE FROM subscriptions WHERE id = ?")->execute([$subscriptionId]);
            
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Failed to initialize payment']);
        }
        
    } catch (Exception $e) {
        error_log("Upgrade subscription error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to upgrade subscription']);
    }
}

function cancelSubscription($pdo, $data, $currentUser, $currentTenant) {
    try {
        // Get current subscription
        $subscriptionSql = "SELECT * FROM subscriptions WHERE tenant_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1";
        $subscriptionStmt = $pdo->prepare($subscriptionSql);
        $subscriptionStmt->execute([$currentTenant['id']]);
        $subscription = $subscriptionStmt->fetch();
        
        if (!$subscription) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'No active subscription found']);
            return;
        }
        
        // Update subscription status
        $updateSql = "UPDATE subscriptions SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$subscription['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Subscription cancelled successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Cancel subscription error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to cancel subscription']);
    }
}

function initializePayment($pdo, $data, $currentUser, $currentTenant) {
    try {
        if (empty($data['amount']) || empty($data['email'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Amount and email are required']);
            return;
        }
        
        $amount = $data['amount'] * 100; // Convert to kobo
        $email = $data['email'];
        $reference = 'TXN_' . time() . '_' . rand(1000, 9999);
        $callbackUrl = $data['callback_url'] ?? $_ENV['APP_URL'] . '/payment/callback';
        
        $paymentData = [
            'amount' => $amount,
            'email' => $email,
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'currency' => 'GHS',
            'metadata' => [
                'tenant_id' => $currentTenant['id'],
                'user_id' => $currentUser['id']
            ]
        ];
        
        $response = makePaystackRequest('transaction/initialize', $paymentData);
        
        if ($response['status'] === true) {
            // Store payment record
            $paymentSql = "INSERT INTO payments (id, tenant_id, reference, amount, email, status, gateway, created_at) 
                          VALUES (uuid_generate_v4(), ?, ?, ?, ?, 'pending', 'paystack', NOW())";
            $paymentStmt = $pdo->prepare($paymentSql);
            $paymentStmt->execute([
                $currentTenant['id'],
                $reference,
                $data['amount'],
                $email
            ]);
            
            echo json_encode([
                'success' => true,
                'data' => $response['data']
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Failed to initialize payment']);
        }
        
    } catch (Exception $e) {
        error_log("Initialize payment error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to initialize payment']);
    }
}

function verifyPayment($pdo, $data) {
    try {
        if (empty($data['reference'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Reference is required']);
            return;
        }
        
        $reference = $data['reference'];
        
        // Verify with Paystack
        $response = makePaystackRequest("transaction/verify/$reference", [], 'GET');
        
        if ($response['status'] === true) {
            $transaction = $response['data'];
            
            // Update payment record
            $updateSql = "UPDATE payments SET status = ?, gateway_response = ?, updated_at = NOW() WHERE reference = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                $transaction['status'],
                json_encode($transaction),
                $reference
            ]);
            
            // If payment is successful, update subscription
            if ($transaction['status'] === 'success') {
                updateSubscriptionFromPayment($pdo, $reference, $transaction);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $transaction
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Failed to verify payment']);
        }
        
    } catch (Exception $e) {
        error_log("Verify payment error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to verify payment']);
    }
}

function updateBillingInfo($pdo, $data, $currentUser, $currentTenant) {
    try {
        // Update tenant billing information
        $updateSql = "UPDATE tenants SET 
                     billing_email = ?, 
                     billing_address = ?, 
                     billing_city = ?, 
                     billing_country = ?, 
                     updated_at = NOW() 
                     WHERE id = ?";
        
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            $data['billing_email'] ?? $currentTenant['billing_email'],
            $data['billing_address'] ?? $currentTenant['billing_address'],
            $data['billing_city'] ?? $currentTenant['billing_city'],
            $data['billing_country'] ?? $currentTenant['billing_country'],
            $currentTenant['id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Billing information updated successfully'
        ]);
        
    } catch (Exception $e) {
        error_log("Update billing info error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update billing information']);
    }
}

function updateSubscriptionFromPayment($pdo, $reference, $transaction) {
    try {
        // Find the payment record
        $paymentSql = "SELECT * FROM payments WHERE reference = ?";
        $paymentStmt = $pdo->prepare($paymentSql);
        $paymentStmt->execute([$reference]);
        $payment = $paymentStmt->fetch();
        
        if (!$payment) {
            return;
        }
        
        // Update subscription status
        $subscriptionSql = "UPDATE subscriptions SET status = 'active', updated_at = NOW() WHERE tenant_id = ? AND status = 'pending'";
        $subscriptionStmt = $pdo->prepare($subscriptionSql);
        $subscriptionStmt->execute([$payment['tenant_id']]);
        
        // Create invoice record
        $invoiceNumber = generateInvoiceNumber();
        $invoiceSql = "INSERT INTO invoices (id, tenant_id, subscription_id, invoice_number, amount, currency, status, gateway_reference, paid_at, created_at) 
                      VALUES (uuid_generate_v4(), ?, ?, ?, ?, 'GHS', 'paid', ?, NOW(), NOW())";
        $invoiceStmt = $pdo->prepare($invoiceSql);
        $invoiceStmt->execute([
            $payment['tenant_id'],
            $payment['subscription_id'] ?? null,
            $invoiceNumber,
            $payment['amount'],
            $reference
        ]);
        
    } catch (Exception $e) {
        error_log("Update subscription from payment error: " . $e->getMessage());
    }
}

function makePaystackRequest($endpoint, $data = [], $method = 'POST') {
    global $paystackSecretKey;
    
    $url = "https://api.paystack.co/$endpoint";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $paystackSecretKey,
        'Content-Type: application/json'
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false) {
        throw new Exception('Failed to connect to Paystack');
    }
    
    return json_decode($response, true);
}

function generateInvoiceNumber() {
    return 'INV-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
}
