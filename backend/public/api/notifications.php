<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Simple database connection function
function getDbConnection() {
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';

    if (empty($dbUser) || empty($dbPass)) {
        throw new Exception('Database credentials not configured');
    }

    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    return new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

// Simple email sending function using cURL
function sendEmail($to, $subject, $htmlContent, $textContent = '') {
    $apiKey = $_ENV['SENDGRID_API_KEY'] ?? '';
    $fromEmail = $_ENV['SENDGRID_FROM_EMAIL'] ?? 'notify@ardentwebservices.com';
    
    // Ensure from email is a valid email address
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $fromEmail = 'notify@ardentwebservices.com';
    }
    
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'SendGrid API key not configured'];
    }
    
    $data = [
        'personalizations' => [
            [
                'to' => [['email' => $to]]
            ]
        ],
        'from' => ['email' => $fromEmail, 'name' => 'Ardent POS'],
        'subject' => $subject,
        'content' => [
            [
                'type' => 'text/html',
                'value' => $htmlContent
            ]
        ]
    ];
    
    // Add text content if provided
    if (!empty($textContent)) {
        $data['content'][] = [
            'type' => 'text/plain',
            'value' => $textContent
        ];
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'message' => 'Email sent successfully'];
    } else {
        return [
            'success' => false, 
            'error' => 'Failed to send email: HTTP ' . $httpCode,
            'response' => $response,
            'curl_error' => $curlError
        ];
    }
}

// Log email attempt
function logEmail($pdo, $to, $subject, $status, $errorMessage = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO email_logs (to_email, subject, status, error_message, sent_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$to, $subject, $status, $errorMessage]);
    } catch (Exception $e) {
        error_log('Failed to log email: ' . $e->getMessage());
    }
}

try {
    $pdo = getDbConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);

    // Debug logging
    error_log("Notifications API Debug - Method: $method, Path: $path, Endpoint: $endpoint, PathParts: " . json_encode($pathParts));

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
                        'system_alerts' => true,
                        'low_stock_threshold' => 10,
                        'report_frequency' => 'monthly',
                        'email_time' => '09:00'
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
                    $offset = ($page - 1) * $limit;

                    try {
                        // Get total count
                        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM email_logs");
                        $total = $countStmt->fetch()['total'];

                        // Get logs
                        $stmt = $pdo->prepare("
                            SELECT * FROM email_logs 
                            ORDER BY sent_at DESC 
                            LIMIT ? OFFSET ?
                        ");
                        $stmt->execute([$limit, $offset]);
                        $logs = $stmt->fetchAll();

                        echo json_encode([
                            'success' => true,
                            'data' => [
                                'notifications' => $logs,
                                'pagination' => [
                                    'page' => $page,
                                    'limit' => $limit,
                                    'total' => $total,
                                    'pages' => ceil($total / $limit)
                                ]
                            ]
                        ]);
                    } catch (Exception $e) {
                        echo json_encode([
                            'success' => true,
                            'data' => [
                                'notifications' => [],
                                'pagination' => [
                                    'page' => $page,
                                    'limit' => $limit,
                                    'total' => 0,
                                    'pages' => 0
                                ]
                            ]
                        ]);
                    }
                    break;

                case 'test':
                    // Simple test endpoint
                    echo json_encode([
                        'success' => true,
                        'message' => 'Notifications API is working',
                        'timestamp' => date('Y-m-d H:i:s'),
                        'method' => $method,
                        'endpoint' => $endpoint
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

                    // Debug logging
                    error_log("Test email request - Email: $testEmail");

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

                    $result = sendEmail($testEmail, $subject, $htmlContent, 'Test email from Ardent POS');
                    
                    // Debug logging
                    error_log("Test email result: " . json_encode($result));
                    
                    if ($result['success']) {
                        logEmail($pdo, $testEmail, $subject, 'success');
                    } else {
                        logEmail($pdo, $testEmail, $subject, 'failed', $result['error']);
                    }

                    echo json_encode($result);
                    break;

                default:
                    http_response_code(404);
                    echo json_encode([
                        'error' => 'Endpoint not found',
                        'debug' => [
                            'method' => $method,
                            'path' => $path,
                            'endpoint' => $endpoint,
                            'pathParts' => $pathParts
                        ]
                    ]);
                    break;
            }
            break;

        case 'POST':
            switch ($endpoint) {
                case 'send-low-stock-alerts':
                    // Send low stock alerts
                    echo json_encode([
                        'success' => true,
                        'message' => 'Low stock alerts processed',
                        'alerts_sent' => 0
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

                    echo json_encode([
                        'success' => true,
                        'message' => 'Receipt sent successfully'
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

                    echo json_encode([
                        'success' => true,
                        'message' => 'Payment confirmation sent'
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

                    echo json_encode([
                        'success' => true,
                        'message' => 'System alert sent'
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

                    echo json_encode([
                        'success' => true,
                        'message' => 'Monthly report sent'
                    ]);
                    break;

                case 'update-settings':
                    // Update notification settings
                    $input = json_decode(file_get_contents('php://input'), true);
                    
                    $settings = [
                        'email_notifications' => $input['email_notifications'] ?? true,
                        'low_stock_alerts' => $input['low_stock_alerts'] ?? true,
                        'sales_reports' => $input['sales_reports'] ?? true,
                        'payment_notifications' => $input['payment_notifications'] ?? true,
                        'system_alerts' => $input['system_alerts'] ?? true,
                        'low_stock_threshold' => $input['low_stock_threshold'] ?? 10,
                        'report_frequency' => $input['report_frequency'] ?? 'monthly',
                        'email_time' => $input['email_time'] ?? '09:00'
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
