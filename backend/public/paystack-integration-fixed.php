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

// Paystack configuration
$paystackSecretKey = $_ENV['PAYSTACK_SECRET_KEY'] ?? '';
$paystackPublicKey = $_ENV['PAYSTACK_PUBLIC_KEY'] ?? '';

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
    
    // Handle requests
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    switch ($method) {
        case 'GET':
            if ($endpoint === 'subscription-plans') {
                getSubscriptionPlans($pdo);
            } elseif ($endpoint === 'payment-status') {
                getPaymentStatus($pdo);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
            }
            break;
            
        case 'POST':
            if ($endpoint === 'initialize-payment') {
                initializePayment($pdo, $auth);
            } elseif ($endpoint === 'verify-payment') {
                verifyPayment($pdo);
            } elseif ($endpoint === 'create-subscription') {
                createSubscription($pdo, $auth);
            } elseif ($endpoint === 'webhook') {
                handleWebhook($pdo);
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
    error_log("Paystack Integration Fixed Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}

function getSubscriptionPlans($pdo) {
    try {
        // Default subscription plans
        $plans = [
            [
                'id' => 'free',
                'name' => 'Free Plan',
                'price' => 0,
                'currency' => 'GHS',
                'billing_cycle' => 'monthly',
                'features' => [
                    'Up to 100 products',
                    'Basic reporting',
                    'Email support',
                    '1 user account'
                ],
                'limits' => [
                    'products' => 100,
                    'users' => 1,
                    'storage' => '1GB'
                ]
            ],
            [
                'id' => 'starter',
                'name' => 'Starter Plan',
                'price' => 5000, // 50 GHS in kobo
                'currency' => 'GHS',
                'billing_cycle' => 'monthly',
                'features' => [
                    'Up to 500 products',
                    'Advanced reporting',
                    'Priority support',
                    'Up to 5 user accounts',
                    'Inventory management',
                    'Customer management'
                ],
                'limits' => [
                    'products' => 500,
                    'users' => 5,
                    'storage' => '5GB'
                ]
            ],
            [
                'id' => 'professional',
                'name' => 'Professional Plan',
                'price' => 15000, // 150 GHS in kobo
                'currency' => 'GHS',
                'billing_cycle' => 'monthly',
                'features' => [
                    'Unlimited products',
                    'Advanced analytics',
                    '24/7 support',
                    'Unlimited user accounts',
                    'Multi-location support',
                    'API access',
                    'Custom integrations'
                ],
                'limits' => [
                    'products' => -1, // Unlimited
                    'users' => -1, // Unlimited
                    'storage' => '50GB'
                ]
            ],
            [
                'id' => 'enterprise',
                'name' => 'Enterprise Plan',
                'price' => 50000, // 500 GHS in kobo
                'currency' => 'GHS',
                'billing_cycle' => 'monthly',
                'features' => [
                    'Everything in Professional',
                    'Dedicated account manager',
                    'Custom development',
                    'White-label options',
                    'Advanced security',
                    'SLA guarantees'
                ],
                'limits' => [
                    'products' => -1, // Unlimited
                    'users' => -1, // Unlimited
                    'storage' => '500GB'
                ]
            ]
        ];
        
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

function initializePayment($pdo, $auth) {
    global $paystackSecretKey;
    
    try {
        // Check authentication
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Token not provided']);
            return;
        }
        
        $token = substr($authHeader, 7);
        $authResult = $auth->verifyToken($token);
        
        if (!$authResult['success']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Token not provided or invalid']);
            return;
        }
        
        $currentUser = $authResult['user'];
        $currentTenant = $authResult['tenant'];
        
        // Super admins cannot make payments
        if ($currentUser['role'] === 'super_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Super admins cannot make payments']);
            return;
        }
        
        if (!$currentTenant) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Tenant not found']);
            return;
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['amount']) || empty($data['email'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Amount and email are required']);
            return;
        }
        
        // Prepare Paystack payment data
        $paymentData = [
            'amount' => $data['amount'] * 100, // Convert to kobo
            'email' => $data['email'],
            'currency' => $data['currency'] ?? 'GHS',
            'reference' => 'ARDENT_' . time() . '_' . rand(1000, 9999),
            'callback_url' => $data['callback_url'] ?? '',
            'metadata' => [
                'tenant_id' => $currentTenant['id'],
                'user_id' => $currentUser['id'],
                'plan_name' => $data['plan_name'] ?? '',
                'custom_fields' => [
                    [
                        'display_name' => 'Tenant',
                        'variable_name' => 'tenant_name',
                        'value' => $currentTenant['name']
                    ]
                ]
            ]
        ];
        
        // Make request to Paystack
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/initialize');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $paystackSecretKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Paystack API error: " . $response);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to initialize payment']);
            return;
        }
        
        $paystackResponse = json_decode($response, true);
        
        if (!$paystackResponse['status']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $paystackResponse['message'] ?? 'Payment initialization failed']);
            return;
        }
        
        // Store payment record in database
        $sql = "
            INSERT INTO payments (id, tenant_id, user_id, reference, amount, currency, status, payment_method, metadata, created_at, updated_at)
            VALUES (uuid_generate_v4(), ?, ?, ?, ?, ?, 'pending', 'paystack', ?, NOW(), NOW())
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $currentTenant['id'],
            $currentUser['id'],
            $paymentData['reference'],
            $data['amount'],
            $data['currency'] ?? 'GHS',
            json_encode($paymentData['metadata'])
        ]);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'authorization_url' => $paystackResponse['data']['authorization_url'],
                'reference' => $paymentData['reference'],
                'access_code' => $paystackResponse['data']['access_code']
            ]
        ]);
    } catch (Exception $e) {
        error_log("Initialize payment error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to initialize payment']);
    }
}

function verifyPayment($pdo) {
    global $paystackSecretKey;
    
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['reference'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Reference is required']);
            return;
        }
        
        // Verify with Paystack
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/verify/' . $data['reference']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $paystackSecretKey
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Paystack verification error: " . $response);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to verify payment']);
            return;
        }
        
        $paystackResponse = json_decode($response, true);
        
        if (!$paystackResponse['status']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $paystackResponse['message'] ?? 'Payment verification failed']);
            return;
        }
        
        $transaction = $paystackResponse['data'];
        
        // Update payment record
        $sql = "
            UPDATE payments 
            SET status = ?, gateway_response = ?, updated_at = NOW()
            WHERE reference = ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $transaction['status'] === 'success' ? 'completed' : 'failed',
            json_encode($transaction),
            $data['reference']
        ]);
        
        if ($transaction['status'] === 'success') {
            // Get payment record
            $sql = "SELECT * FROM payments WHERE reference = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data['reference']]);
            $payment = $stmt->fetch();
            
            if ($payment) {
                // Create or update subscription
                $metadata = json_decode($payment['metadata'], true);
                $planName = $metadata['plan_name'] ?? 'starter';
                
                // Cancel current subscription
                $cancelSql = "UPDATE subscriptions SET status = 'cancelled', updated_at = NOW() WHERE tenant_id = ? AND status = 'active'";
                $stmt = $pdo->prepare($cancelSql);
                $stmt->execute([$payment['tenant_id']]);
                
                // Create new subscription
                $subscriptionSql = "
                    INSERT INTO subscriptions (id, tenant_id, plan_name, amount, billing_cycle, status, payment_reference, created_at, updated_at)
                    VALUES (uuid_generate_v4(), ?, ?, ?, 'monthly', 'active', ?, NOW(), NOW())
                    RETURNING *
                ";
                
                $stmt = $pdo->prepare($subscriptionSql);
                $stmt->execute([
                    $payment['tenant_id'],
                    $planName,
                    $payment['amount'],
                    $payment['reference']
                ]);
                
                $subscription = $stmt->fetch();
                
                // Create invoice
                $invoiceSql = "
                    INSERT INTO invoices (id, tenant_id, invoice_number, amount, currency, status, description, payment_reference, created_at, updated_at)
                    VALUES (uuid_generate_v4(), ?, ?, ?, ?, 'paid', ?, ?, NOW(), NOW())
                    RETURNING *
                ";
                
                $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare($invoiceSql);
                $stmt->execute([
                    $payment['tenant_id'],
                    $invoiceNumber,
                    $payment['amount'],
                    $payment['currency'],
                    'Subscription payment for ' . $planName . ' plan',
                    $payment['reference']
                ]);
                
                $invoice = $stmt->fetch();
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'status' => $transaction['status'],
                'reference' => $data['reference'],
                'amount' => $transaction['amount'] / 100, // Convert from kobo
                'currency' => $transaction['currency'],
                'gateway_ref' => $transaction['gateway_ref'] ?? null
            ]
        ]);
    } catch (Exception $e) {
        error_log("Verify payment error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to verify payment']);
    }
}

function createSubscription($pdo, $auth) {
    try {
        // Check authentication
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Token not provided']);
            return;
        }
        
        $token = substr($authHeader, 7);
        $authResult = $auth->verifyToken($token);
        
        if (!$authResult['success']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Token not provided or invalid']);
            return;
        }
        
        $currentUser = $authResult['user'];
        $currentTenant = $authResult['tenant'];
        
        // Super admins cannot create subscriptions
        if ($currentUser['role'] === 'super_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Super admins cannot create subscriptions']);
            return;
        }
        
        if (!$currentTenant) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Tenant not found']);
            return;
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['plan_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Plan name is required']);
            return;
        }
        
        // Cancel current subscription
        $cancelSql = "UPDATE subscriptions SET status = 'cancelled', updated_at = NOW() WHERE tenant_id = ? AND status = 'active'";
        $stmt = $pdo->prepare($cancelSql);
        $stmt->execute([$currentTenant['id']]);
        
        // Create new subscription
        $sql = "
            INSERT INTO subscriptions (id, tenant_id, plan_name, amount, billing_cycle, status, created_at, updated_at)
            VALUES (uuid_generate_v4(), ?, ?, ?, 'monthly', 'active', NOW(), NOW())
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $currentTenant['id'],
            $data['plan_name'],
            $data['amount'] ?? 0
        ]);
        
        $subscription = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $subscription
        ]);
    } catch (Exception $e) {
        error_log("Create subscription error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create subscription']);
    }
}

function getPaymentStatus($pdo) {
    try {
        $reference = $_GET['reference'] ?? '';
        
        if (empty($reference)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Reference is required']);
            return;
        }
        
        $sql = "SELECT * FROM payments WHERE reference = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$reference]);
        $payment = $stmt->fetch();
        
        if (!$payment) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Payment not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'reference' => $payment['reference'],
                'status' => $payment['status'],
                'amount' => $payment['amount'],
                'currency' => $payment['currency'],
                'created_at' => $payment['created_at']
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get payment status error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to get payment status']);
    }
}

function handleWebhook($pdo) {
    global $paystackSecretKey;
    
    try {
        // Verify webhook signature
        $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
        $payload = file_get_contents('php://input');
        
        if (empty($signature)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing signature']);
            return;
        }
        
        $expectedSignature = hash_hmac('sha512', $payload, $paystackSecretKey);
        
        if (!hash_equals($expectedSignature, $signature)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid signature']);
            return;
        }
        
        $data = json_decode($payload, true);
        
        if (!$data || empty($data['event'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid webhook data']);
            return;
        }
        
        $event = $data['event'];
        $transaction = $data['data'];
        
        switch ($event) {
            case 'charge.success':
                handleChargeSuccess($pdo, $transaction);
                break;
            case 'subscription.create':
                handleSubscriptionCreate($pdo, $transaction);
                break;
            case 'subscription.disable':
                handleSubscriptionDisable($pdo, $transaction);
                break;
            case 'invoice.create':
                handleInvoiceCreate($pdo, $transaction);
                break;
            case 'invoice.payment_failed':
                handleInvoicePaymentFailed($pdo, $transaction);
                break;
            default:
                error_log("Unhandled Paystack webhook event: " . $event);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Webhook error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Webhook processing failed']);
    }
}

function handleChargeSuccess($pdo, $transaction) {
    try {
        $reference = $transaction['reference'];
        
        // Update payment status
        $sql = "UPDATE payments SET status = 'completed', gateway_response = ?, updated_at = NOW() WHERE reference = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([json_encode($transaction), $reference]);
        
        // Get payment record
        $sql = "SELECT * FROM payments WHERE reference = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$reference]);
        $payment = $stmt->fetch();
        
        if ($payment) {
            // Update subscription if this is a subscription payment
            $metadata = json_decode($payment['metadata'], true);
            if (isset($metadata['plan_name'])) {
                $sql = "
                    UPDATE subscriptions 
                    SET status = 'active', payment_reference = ?, updated_at = NOW()
                    WHERE tenant_id = ? AND plan_name = ?
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$reference, $payment['tenant_id'], $metadata['plan_name']]);
            }
        }
    } catch (Exception $e) {
        error_log("Handle charge success error: " . $e->getMessage());
    }
}

function handleSubscriptionCreate($pdo, $transaction) {
    try {
        // Handle subscription creation
        error_log("Subscription created: " . json_encode($transaction));
    } catch (Exception $e) {
        error_log("Handle subscription create error: " . $e->getMessage());
    }
}

function handleSubscriptionDisable($pdo, $transaction) {
    try {
        // Handle subscription disable
        error_log("Subscription disabled: " . json_encode($transaction));
    } catch (Exception $e) {
        error_log("Handle subscription disable error: " . $e->getMessage());
    }
}

function handleInvoiceCreate($pdo, $transaction) {
    try {
        // Handle invoice creation
        error_log("Invoice created: " . json_encode($transaction));
    } catch (Exception $e) {
        error_log("Handle invoice create error: " . $e->getMessage());
    }
}

function handleInvoicePaymentFailed($pdo, $transaction) {
    try {
        // Handle invoice payment failure
        error_log("Invoice payment failed: " . json_encode($transaction));
    } catch (Exception $e) {
        error_log("Handle invoice payment failed error: " . $e->getMessage());
    }
}
