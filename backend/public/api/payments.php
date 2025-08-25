<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load autoloader
$autoloaderPaths = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    '/var/www/html/vendor/autoload.php',
    '/var/www/html/backend/vendor/autoload.php'
];

$autoloaderFound = false;
foreach ($autoloaderPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloaderFound = true;
        break;
    }
}

if (!$autoloaderFound) {
    http_response_code(500);
    echo json_encode(['error' => 'Autoloader not found']);
    exit;
}

use ArdentPOS\Services\PaymentService;
use ArdentPOS\Services\NotificationService;

try {
    $paymentService = new PaymentService();
    $notificationService = new NotificationService();

    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);

    switch ($method) {
        case 'GET':
            switch ($endpoint) {
                case 'payments':
                    // Get payment configuration
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'public_key' => $paymentService->getPublicKey(),
                            'is_configured' => $paymentService->isConfigured(),
                            'currency' => 'GHS',
                            'supported_methods' => ['card', 'bank', 'mobile_money']
                        ]
                    ]);
                    break;

                case 'banks':
                    // Get list of banks
                    $banks = $paymentService->getBankList();
                    echo json_encode($banks);
                    break;

                case 'resolve-account':
                    // Resolve bank account number
                    $accountNumber = $_GET['account_number'] ?? '';
                    $bankCode = $_GET['bank_code'] ?? '';

                    if (empty($accountNumber) || empty($bankCode)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Account number and bank code required']);
                        exit;
                    }

                    $result = $paymentService->resolveAccountNumber($accountNumber, $bankCode);
                    echo json_encode($result);
                    break;

                case 'transaction-history':
                    // Get transaction history
                    $customerCode = $_GET['customer_code'] ?? null;
                    $page = (int)($_GET['page'] ?? 1);

                    $result = $paymentService->getTransactionHistory($customerCode, $page);
                    echo json_encode($result);
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint not found']);
                    break;
            }
            break;

        case 'POST':
            switch ($endpoint) {
                case 'initialize':
                    // Initialize payment transaction
                    $input = json_decode(file_get_contents('php://input'), true);
                    
                    $requiredFields = ['email', 'amount'];
                    foreach ($requiredFields as $field) {
                        if (empty($input[$field])) {
                            http_response_code(400);
                            echo json_encode(['error' => "$field is required"]);
                            exit;
                        }
                    }

                    $result = $paymentService->initializeTransaction($input);
                    echo json_encode($result);
                    break;

                case 'verify':
                    // Verify payment transaction
                    $input = json_decode(file_get_contents('php://input'), true);
                    $reference = $input['reference'] ?? '';

                    if (empty($reference)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Reference is required']);
                        exit;
                    }

                    $result = $paymentService->verifyTransaction($reference);
                    
                    // If payment is successful, send notification
                    if ($result['success']) {
                        // This would typically create a payment record in the database
                        // and then send a notification
                        $paymentData = [
                            'transaction_id' => $result['transaction_id'],
                            'amount' => $result['amount'],
                            'status' => $result['status']
                        ];
                        
                        // For demo purposes, we'll just log the success
                        error_log('Payment successful: ' . json_encode($paymentData));
                    }

                    echo json_encode($result);
                    break;

                case 'refund':
                    // Refund transaction
                    $input = json_decode(file_get_contents('php://input'), true);
                    $reference = $input['reference'] ?? '';
                    $amount = $input['amount'] ?? null;

                    if (empty($reference)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Reference is required']);
                        exit;
                    }

                    $result = $paymentService->refundTransaction($reference, $amount);
                    echo json_encode($result);
                    break;

                case 'create-customer':
                    // Create Paystack customer
                    $input = json_decode(file_get_contents('php://input'), true);
                    
                    $requiredFields = ['email'];
                    foreach ($requiredFields as $field) {
                        if (empty($input[$field])) {
                            http_response_code(400);
                            echo json_encode(['error' => "$field is required"]);
                            exit;
                        }
                    }

                    $result = $paymentService->createCustomer($input);
                    echo json_encode($result);
                    break;

                case 'create-plan':
                    // Create subscription plan
                    $input = json_decode(file_get_contents('php://input'), true);
                    
                    $requiredFields = ['name', 'amount'];
                    foreach ($requiredFields as $field) {
                        if (empty($input[$field])) {
                            http_response_code(400);
                            echo json_encode(['error' => "$field is required"]);
                            exit;
                        }
                    }

                    $result = $paymentService->createPlan($input);
                    echo json_encode($result);
                    break;

                case 'create-subscription':
                    // Create subscription
                    $input = json_decode(file_get_contents('php://input'), true);
                    
                    $requiredFields = ['customer_code', 'plan_code'];
                    foreach ($requiredFields as $field) {
                        if (empty($input[$field])) {
                            http_response_code(400);
                            echo json_encode(['error' => "$field is required"]);
                            exit;
                        }
                    }

                    $result = $paymentService->createSubscription($input);
                    echo json_encode($result);
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint not found']);
                    break;
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
