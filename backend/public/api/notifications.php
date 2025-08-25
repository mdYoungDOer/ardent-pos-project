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

use ArdentPOS\Services\NotificationService;
use ArdentPOS\Services\EmailService;
use ArdentPOS\Services\PaymentService;

try {
    $notificationService = new NotificationService();
    $emailService = new EmailService();
    $paymentService = new PaymentService();

    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);

    switch ($method) {
        case 'GET':
            switch ($endpoint) {
                case 'notifications':
                    // Get notification settings
                    $tenantId = $_GET['tenant_id'] ?? null;
                    if (!$tenantId) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Tenant ID required']);
                        exit;
                    }

                    $settings = [
                        'email_notifications' => true,
                        'low_stock_alerts' => true,
                        'sales_reports' => true,
                        'payment_notifications' => true,
                        'system_alerts' => true
                    ];

                    echo json_encode([
                        'success' => true,
                        'data' => $settings
                    ]);
                    break;

                case 'logs':
                    // Get notification logs
                    $page = (int)($_GET['page'] ?? 1);
                    $limit = (int)($_GET['limit'] ?? 20);
                    $type = $_GET['type'] ?? '';

                    // This would typically fetch from database
                    $logs = [
                        'notifications' => [],
                        'pagination' => [
                            'page' => $page,
                            'limit' => $limit,
                            'total' => 0,
                            'pages' => 0
                        ]
                    ];

                    echo json_encode([
                        'success' => true,
                        'data' => $logs
                    ]);
                    break;

                case 'test-email':
                    // Test email configuration
                    $testEmail = $_GET['email'] ?? '';
                    if (empty($testEmail)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Email address required']);
                        exit;
                    }

                    $subject = 'Test Email - Ardent POS';
                    $htmlContent = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: linear-gradient(135deg, #E72F7C, #9a0864); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>Test Email</h1>
                            </div>
                            <div class='content'>
                                <h2>Hello!</h2>
                                <p>This is a test email from Ardent POS to verify that the email notification system is working correctly.</p>
                                <p>If you received this email, it means:</p>
                                <ul>
                                    <li>SendGrid is properly configured</li>
                                    <li>Email templates are working</li>
                                    <li>Notification system is functional</li>
                                </ul>
                                <p>Timestamp: " . date('Y-m-d H:i:s') . "</p>
                            </div>
                            <div class='footer'>
                                <p>© 2024 Ardent POS. All rights reserved.</p>
                            </div>
                        </div>
                    </body>
                    </html>";

                    $success = $emailService->sendEmail($testEmail, $subject, $htmlContent, 'Test email from Ardent POS');

                    echo json_encode([
                        'success' => $success,
                        'message' => $success ? 'Test email sent successfully' : 'Failed to send test email'
                    ]);
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint not found']);
                    break;
            }
            break;

        case 'POST':
            switch ($endpoint) {
                case 'send-low-stock-alerts':
                    // Send low stock alerts
                    $alertsSent = $notificationService->checkAndSendLowStockAlerts();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Low stock alerts processed',
                        'alerts_sent' => $alertsSent
                    ]);
                    break;

                case 'send-sale-receipt':
                    // Send sale receipt
                    $input = json_decode(file_get_contents('php://input'), true);
                    $saleId = $input['sale_id'] ?? null;

                    if (!$saleId) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Sale ID required']);
                        exit;
                    }

                    $success = $notificationService->sendSaleReceiptNotification($saleId);
                    
                    echo json_encode([
                        'success' => $success,
                        'message' => $success ? 'Receipt sent successfully' : 'Failed to send receipt'
                    ]);
                    break;

                case 'send-payment-confirmation':
                    // Send payment confirmation
                    $input = json_decode(file_get_contents('php://input'), true);
                    $paymentId = $input['payment_id'] ?? null;

                    if (!$paymentId) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Payment ID required']);
                        exit;
                    }

                    $success = $notificationService->sendPaymentConfirmationNotification($paymentId);
                    
                    echo json_encode([
                        'success' => $success,
                        'message' => $success ? 'Payment confirmation sent' : 'Failed to send payment confirmation'
                    ]);
                    break;

                case 'send-system-alert':
                    // Send system alert
                    $input = json_decode(file_get_contents('php://input'), true);
                    $type = $input['type'] ?? '';
                    $message = $input['message'] ?? '';
                    $recipients = $input['recipients'] ?? [];

                    if (empty($type) || empty($message)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Type and message required']);
                        exit;
                    }

                    $success = $notificationService->sendSystemAlert($type, $message, $recipients);
                    
                    echo json_encode([
                        'success' => $success,
                        'message' => $success ? 'System alert sent' : 'Failed to send system alert'
                    ]);
                    break;

                case 'send-monthly-report':
                    // Send monthly report
                    $input = json_decode(file_get_contents('php://input'), true);
                    $tenantId = $input['tenant_id'] ?? null;
                    $month = $input['month'] ?? null;

                    if (!$tenantId) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Tenant ID required']);
                        exit;
                    }

                    $success = $notificationService->sendMonthlyReport($tenantId, $month);
                    
                    echo json_encode([
                        'success' => $success,
                        'message' => $success ? 'Monthly report sent' : 'Failed to send monthly report'
                    ]);
                    break;

                case 'update-settings':
                    // Update notification settings
                    $input = json_decode(file_get_contents('php://input'), true);
                    
                    // This would typically update database settings
                    $settings = [
                        'email_notifications' => $input['email_notifications'] ?? true,
                        'low_stock_alerts' => $input['low_stock_alerts'] ?? true,
                        'sales_reports' => $input['sales_reports'] ?? true,
                        'payment_notifications' => $input['payment_notifications'] ?? true,
                        'system_alerts' => $input['system_alerts'] ?? true
                    ];

                    echo json_encode([
                        'success' => true,
                        'message' => 'Settings updated successfully',
                        'data' => $settings
                    ]);
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
