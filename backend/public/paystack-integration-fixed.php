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
        case 'POST':
            if ($endpoint === 'initialize-payment') {
                handleInitializePayment($pdo, $currentUser, $currentTenant);
            } elseif ($endpoint === 'verify-payment') {
                handleVerifyPayment($pdo, $currentUser, $currentTenant);
            } elseif ($endpoint === 'create-subscription') {
                handleCreateSubscription($pdo, $currentUser, $currentTenant);
            } elseif ($endpoint === 'webhook') {
                handleWebhook($pdo);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
            }
            break;
            
        case 'GET':
            if ($endpoint === 'payment-status') {
                handlePaymentStatus($pdo, $currentUser, $currentTenant);
            } elseif ($endpoint === 'subscription-plans') {
                getSubscriptionPlans($pdo, $currentUser, $currentTenant);
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
    error_log("Paystack integration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}

function handleInitializePayment($pdo, $currentUser, $currentTenant) {
    // Super admins don't need to pay
    if ($currentUser['role'] === 'super_admin') {
        echo json_encode([
            'success' => true,
            'data' => [
                'is_super_admin' => true,
                'no_payment_required' => true,
                'message' => 'Super admins do not require payment'
            ]
        ]);
        return;
    }
    
    // Regular users need a tenant
    if (!$currentTenant) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Tenant required for payment']);
        return;
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || empty($data['amount']) || empty($data['email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Amount and email are required']);
        return;
    }
    
    $amount = $data['amount'] * 100; // Convert to kobo
    $email = $data['email'];
    $reference = 'TXN_' . time() . '_' . rand(1000, 9999);
    $callbackUrl = $data['callback_url'] ?? 'https://ardentpos.com/payment/callback';
    
    // Paystack API call
    $paystackData = [
        'amount' => $amount,
        'email' => $email,
        'reference' => $reference,
        'callback_url' => $callbackUrl,
        'currency' => 'GHS'
    ];
    
    $response = makePaystackRequest('transaction/initialize', $paystackData);
    
    if ($response['status'] === true) {
        // Store payment record with tenant_id
        $stmt = $pdo->prepare("
            INSERT INTO payments (reference, amount, email, tenant_id, status, paystack_data, created_at)
            VALUES (?, ?, ?, ?, 'pending', ?, NOW())
        ");
        $stmt->execute([$reference, $data['amount'], $email, $currentTenant['id'], json_encode($response['data'])]);
        
        echo json_encode([
            'success' => true,
            'data' => $response['data']
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to initialize payment'
        ]);
    }
}

function handleVerifyPayment($pdo, $currentUser, $currentTenant) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || empty($data['reference'])) {
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
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET status = ?, paystack_data = ?, updated_at = NOW()
            WHERE reference = ?
        ");
        $stmt->execute([$transaction['status'], json_encode($transaction), $reference]);
        
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
        echo json_encode([
            'success' => false,
            'error' => 'Failed to verify payment'
        ]);
    }
}

function handleCreateSubscription($pdo, $currentUser, $currentTenant) {
    // Super admins don't need subscriptions
    if ($currentUser['role'] === 'super_admin') {
        echo json_encode([
            'success' => true,
            'data' => [
                'is_super_admin' => true,
                'no_subscription_required' => true,
                'message' => 'Super admins do not require subscriptions'
            ]
        ]);
        return;
    }
    
    // Regular users need a tenant
    if (!$currentTenant) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Tenant required for subscription']);
        return;
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || empty($data['plan']) || empty($data['email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Plan and email are required']);
        return;
    }
    
    $plan = $data['plan'];
    $email = $data['email'];
    $billingCycle = $data['billing_cycle'] ?? 'monthly';
    
    $planDetails = getPlanDetails($plan, $billingCycle);
    $amount = $planDetails['amount'] * 100; // Convert to kobo
    
    // Create Paystack plan if it doesn't exist
    $planCode = createPaystackPlan($plan, $amount, $billingCycle);
    
    // Create Paystack subscription
    $paystackData = [
        'customer' => $email,
        'plan' => $planCode,
        'start_date' => date('Y-m-d\TH:i:s\Z')
    ];
    
    $response = makePaystackRequest('subscription', $paystackData);
    
    if ($response['status'] === true) {
        $subscription = $response['data'];
        
        // Store subscription record with tenant_id
        $stmt = $pdo->prepare("
            INSERT INTO subscriptions (id, tenant_id, plan_name, status, paystack_subscription_code, amount, currency, billing_cycle, created_at, updated_at)
            VALUES (uuid_generate_v4(), ?, ?, 'active', ?, ?, 'GHS', ?, NOW(), NOW())
        ");
        $stmt->execute([$currentTenant['id'], $plan, $subscription['subscription_code'], $planDetails['amount'], $billingCycle]);
        
        // Update tenant plan
        $updateSql = "UPDATE tenants SET plan = ?, updated_at = NOW() WHERE id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$plan, $currentTenant['id']]);
        
        echo json_encode([
            'success' => true,
            'data' => $subscription
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create subscription'
        ]);
    }
}

function getSubscriptionPlans($pdo, $currentUser, $currentTenant) {
    // Super admins don't need subscription plans
    if ($currentUser['role'] === 'super_admin') {
        echo json_encode([
            'success' => true,
            'data' => [
                'is_super_admin' => true,
                'no_subscription_required' => true,
                'plans' => []
            ]
        ]);
        return;
    }
    
    // Regular users get subscription plans
    $plans = [
        [
            'id' => 'starter',
            'name' => 'Starter Plan',
            'description' => 'Perfect for small businesses',
            'monthly_price' => 120.00,
            'yearly_price' => 1200.00,
            'features' => [
                'Up to 5 users',
                'Basic inventory management',
                'Sales reporting',
                'Email support'
            ]
        ],
        [
            'id' => 'professional',
            'name' => 'Professional Plan',
            'description' => 'Ideal for growing businesses',
            'monthly_price' => 240.00,
            'yearly_price' => 2400.00,
            'features' => [
                'Up to 20 users',
                'Advanced inventory management',
                'Advanced reporting',
                'Priority support',
                'API access'
            ]
        ],
        [
            'id' => 'enterprise',
            'name' => 'Enterprise Plan',
            'description' => 'For large organizations',
            'monthly_price' => 480.00,
            'yearly_price' => 4800.00,
            'features' => [
                'Unlimited users',
                'Full feature access',
                'Custom integrations',
                'Dedicated support',
                'White-label options'
            ]
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'is_super_admin' => false,
            'plans' => $plans
        ]
    ]);
}

function handlePaymentStatus($pdo, $currentUser, $currentTenant) {
    $reference = $_GET['reference'] ?? '';
    
    if (empty($reference)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Reference is required']);
        return;
    }
    
    // Get payment status from database
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE reference = ?");
    $stmt->execute([$reference]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Payment not found']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $payment
    ]);
}

function handleWebhook($pdo) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid webhook data']);
        return;
    }
    
    // Verify webhook signature
    $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
    if (!verifyWebhookSignature($input, $signature)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid signature']);
        return;
    }
    
    $event = $data['event'];
    $transaction = $data['data'];
    
    switch ($event) {
        case 'charge.success':
            handleSuccessfulCharge($pdo, $transaction);
            break;
        case 'subscription.create':
            handleSubscriptionCreated($pdo, $transaction);
            break;
        case 'subscription.disable':
            handleSubscriptionDisabled($pdo, $transaction);
            break;
        default:
            // Log unknown event
            error_log("Unknown Paystack webhook event: $event");
    }
    
    echo json_encode(['success' => true]);
}

function makePaystackRequest($endpoint, $data = [], $method = 'POST') {
    $paystackSecretKey = $_ENV['PAYSTACK_SECRET_KEY'] ?? '';
    
    if (empty($paystackSecretKey)) {
        throw new Exception('Paystack secret key not configured');
    }
    
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

function createPaystackPlan($plan, $amount, $billingCycle) {
    $planName = ucfirst($plan) . ' Plan';
    $interval = $billingCycle === 'yearly' ? 'annually' : 'monthly';
    
    $planData = [
        'name' => $planName,
        'amount' => $amount,
        'interval' => $interval,
        'currency' => 'GHS'
    ];
    
    $response = makePaystackRequest('plan', $planData);
    
    if ($response['status'] === true) {
        return $response['data']['plan_code'];
    } else {
        // If plan creation fails, return a default plan code
        return 'PLN_' . strtoupper($plan) . '_' . strtoupper($billingCycle);
    }
}

function updateSubscriptionFromPayment($pdo, $reference, $transaction) {
    // Find the payment record
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE reference = ?");
    $stmt->execute([$reference]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        return;
    }
    
    // Update subscription status
    $stmt = $pdo->prepare("
        UPDATE subscriptions 
        SET status = 'active', paystack_reference = ?, updated_at = NOW()
        WHERE tenant_id = ?
    ");
    $stmt->execute([$reference, $payment['tenant_id']]);
    
    // Create invoice record
    $invoiceNumber = generateInvoiceNumber();
    $stmt = $pdo->prepare("
        INSERT INTO invoices (id, tenant_id, subscription_id, invoice_number, amount, currency, status, paystack_reference, paid_at, created_at, updated_at)
        VALUES (uuid_generate_v4(), ?, (SELECT id FROM subscriptions WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 1), ?, ?, 'GHS', 'paid', ?, NOW(), NOW(), NOW())
    ");
    $stmt->execute([$payment['tenant_id'], $payment['tenant_id'], $invoiceNumber, $payment['amount'], $reference]);
}

function handleSuccessfulCharge($pdo, $transaction) {
    $reference = $transaction['reference'];
    
    // Update payment status
    $stmt = $pdo->prepare("
        UPDATE payments 
        SET status = 'success', paystack_data = ?, updated_at = NOW()
        WHERE reference = ?
    ");
    $stmt->execute([json_encode($transaction), $reference]);
    
    // Update subscription if this is a subscription payment
    updateSubscriptionFromPayment($pdo, $reference, $transaction);
}

function handleSubscriptionCreated($pdo, $subscription) {
    // Update subscription with Paystack data
    $stmt = $pdo->prepare("
        UPDATE subscriptions 
        SET paystack_subscription_code = ?, paystack_data = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$subscription['subscription_code'], json_encode($subscription), $subscription['id']]);
}

function handleSubscriptionDisabled($pdo, $subscription) {
    // Update subscription status
    $stmt = $pdo->prepare("
        UPDATE subscriptions 
        SET status = 'cancelled', updated_at = NOW()
        WHERE paystack_subscription_code = ?
    ");
    $stmt->execute([$subscription['subscription_code']]);
}

function verifyWebhookSignature($payload, $signature) {
    $paystackSecretKey = $_ENV['PAYSTACK_SECRET_KEY'] ?? '';
    
    if (empty($paystackSecretKey)) {
        return false;
    }
    
    $expectedSignature = hash_hmac('sha512', $payload, $paystackSecretKey);
    return hash_equals($expectedSignature, $signature);
}

function getPlanDetails($plan, $billingCycle) {
    $plans = [
        'starter' => [
            'monthly' => ['amount' => 120.00],
            'yearly' => ['amount' => 1200.00]
        ],
        'professional' => [
            'monthly' => ['amount' => 240.00],
            'yearly' => ['amount' => 2400.00]
        ],
        'enterprise' => [
            'monthly' => ['amount' => 480.00],
            'yearly' => ['amount' => 4800.00]
        ]
    ];
    
    return $plans[$plan][$billingCycle] ?? ['amount' => 0.00];
}

function generateInvoiceNumber() {
    return 'INV-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
}
