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
$dbHost = $_ENV['DB_HOST'] ?? 'db-postgresql-nyc3-77594-ardent-pos-do-user-24545475-0.g.db.ondigitalocean.com';
$dbPort = $_ENV['DB_PORT'] ?? '25060';
$dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
$dbUser = $_ENV['DB_USER'] ?? $_ENV['DB_USERNAME'] ?? 'doadmin';
$dbPass = $_ENV['DB_PASS'] ?? $_ENV['DB_PASSWORD'] ?? '';
$paystackSecretKey = $_ENV['PAYSTACK_SECRET_KEY'] ?? '';
$paystackPublicKey = $_ENV['PAYSTACK_PUBLIC_KEY'] ?? '';

// Simple authentication check
function checkAuth() {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return false;
    }
    
    $token = $matches[1];
    return !empty($token);
}

try {
    // Connect to database
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    // Remove .php extension if present
    $endpoint = str_replace('.php', '', $endpoint);
    
    switch ($method) {
        case 'POST':
            if ($endpoint === 'initialize-payment') {
                handleInitializePayment($pdo);
            } elseif ($endpoint === 'verify-payment') {
                handleVerifyPayment($pdo);
            } elseif ($endpoint === 'create-subscription') {
                handleCreateSubscription($pdo);
            } elseif ($endpoint === 'webhook') {
                handleWebhook($pdo);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            }
            break;
            
        case 'GET':
            if ($endpoint === 'payment-status') {
                handlePaymentStatus($pdo);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Paystack integration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}

function handleInitializePayment($pdo) {
    if (!checkAuth()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || empty($data['amount']) || empty($data['email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Amount and email are required']);
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
        // Store payment record
        $stmt = $pdo->prepare("
            INSERT INTO payments (reference, amount, email, status, paystack_data, created_at)
            VALUES (?, ?, ?, 'pending', ?, NOW())
        ");
        $stmt->execute([$reference, $data['amount'], $email, json_encode($response['data'])]);
        
        echo json_encode([
            'success' => true,
            'data' => $response['data']
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to initialize payment'
        ]);
    }
}

function handleVerifyPayment($pdo) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || empty($data['reference'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Reference is required']);
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
            'message' => 'Failed to verify payment'
        ]);
    }
}

function handleCreateSubscription($pdo) {
    if (!checkAuth()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || empty($data['plan']) || empty($data['email'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Plan and email are required']);
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
        
        // Store subscription record
        $stmt = $pdo->prepare("
            INSERT INTO subscriptions (plan, status, paystack_subscription_code, amount, currency, billing_cycle, created_at)
            VALUES (?, 'active', ?, ?, 'GHS', ?, NOW())
        ");
        $stmt->execute([$plan, $subscription['subscription_code'], $planDetails['amount'], $billingCycle]);
        
        echo json_encode([
            'success' => true,
            'data' => $subscription
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create subscription'
        ]);
    }
}

function handleWebhook($pdo) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid webhook data']);
        return;
    }
    
    // Verify webhook signature
    $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
    if (!verifyWebhookSignature($input, $signature)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid signature']);
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

function handlePaymentStatus($pdo) {
    $reference = $_GET['reference'] ?? '';
    
    if (empty($reference)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Reference is required']);
        return;
    }
    
    // Get payment status from database
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE reference = ?");
    $stmt->execute([$reference]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $payment
    ]);
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
        WHERE id = (SELECT subscription_id FROM payments WHERE reference = ?)
    ");
    $stmt->execute([$reference, $reference]);
    
    // Create invoice record
    $invoiceNumber = generateInvoiceNumber();
    $stmt = $pdo->prepare("
        INSERT INTO invoices (subscription_id, invoice_number, amount, currency, status, paystack_reference, paid_at, created_at)
        VALUES ((SELECT subscription_id FROM payments WHERE reference = ?), ?, ?, 'GHS', 'paid', ?, NOW(), NOW())
    ");
    $stmt->execute([$reference, $invoiceNumber, $payment['amount'], $reference]);
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
    global $paystackSecretKey;
    
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
?>
