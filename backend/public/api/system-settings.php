<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection
function getDatabaseConnection() {
    try {
        $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
        $dbPort = $_ENV['DB_PORT'] ?? '5432';
        $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
        $dbUser = $_ENV['DB_USERNAME'] ?? '';
        $dbPass = $_ENV['DB_PASSWORD'] ?? '';

        if (empty($dbUser) || empty($dbPass)) {
            throw new Exception('Database credentials not configured');
        }

        $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        return $pdo;
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

// Response functions
function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function sendError($message, $status = 500) {
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

$pdo = getDatabaseConnection();

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            if ($pdo) {
                // Get all system settings from database
                $stmt = $pdo->query("SELECT key, value FROM system_settings ORDER BY key");
                $settings = $stmt->fetchAll();
                
                $formattedSettings = [];
                foreach ($settings as $setting) {
                    $formattedSettings[$setting['key']] = $setting['value'];
                }
                
                $data = [
                    'general' => [
                        'site_name' => $formattedSettings['site_name'] ?? 'Ardent POS',
                        'site_description' => $formattedSettings['site_description'] ?? 'Enterprise Point of Sale System',
                        'timezone' => $formattedSettings['timezone'] ?? 'UTC',
                        'maintenance_mode' => ($formattedSettings['maintenance_mode'] ?? 'false') === 'true'
                    ],
                    'email' => [
                        'smtp_host' => $formattedSettings['smtp_host'] ?? '',
                        'smtp_port' => $formattedSettings['smtp_port'] ?? '587',
                        'smtp_username' => $formattedSettings['smtp_username'] ?? '',
                        'smtp_password' => $formattedSettings['smtp_password'] ?? '',
                        'from_email' => $formattedSettings['from_email'] ?? 'noreply@ardentpos.com',
                        'from_name' => $formattedSettings['from_name'] ?? 'Ardent POS',
                        'email_verification' => ($formattedSettings['email_verification'] ?? 'true') === 'true'
                    ],
                    'payment' => [
                        'paystack_public_key' => $formattedSettings['paystack_public_key'] ?? '',
                        'paystack_secret_key' => $formattedSettings['paystack_secret_key'] ?? '',
                        'paystack_webhook_secret' => $formattedSettings['paystack_webhook_secret'] ?? '',
                        'currency' => $formattedSettings['currency'] ?? 'GHS',
                        'currency_symbol' => $formattedSettings['currency_symbol'] ?? '₵'
                    ],
                    'security' => [
                        'session_timeout' => (int)($formattedSettings['session_timeout'] ?? 3600),
                        'max_login_attempts' => (int)($formattedSettings['max_login_attempts'] ?? 5),
                        'require_2fa' => ($formattedSettings['require_2fa'] ?? 'false') === 'true',
                        'password_min_length' => (int)($formattedSettings['password_min_length'] ?? 8),
                        'password_require_special' => ($formattedSettings['password_require_special'] ?? 'true') === 'true'
                    ],
                    'notifications' => [
                        'email_notifications' => ($formattedSettings['email_notifications'] ?? 'true') === 'true',
                        'push_notifications' => ($formattedSettings['push_notifications'] ?? 'true') === 'true',
                        'sms_notifications' => ($formattedSettings['sms_notifications'] ?? 'false') === 'true'
                    ]
                ];
            } else {
                // Fallback data
                $data = [
                    'general' => [
                        'site_name' => 'Ardent POS',
                        'site_description' => 'Enterprise Point of Sale System',
                        'timezone' => 'UTC',
                        'maintenance_mode' => false
                    ],
                    'email' => [
                        'smtp_host' => '',
                        'smtp_port' => '587',
                        'smtp_username' => '',
                        'smtp_password' => '',
                        'from_email' => 'noreply@ardentpos.com',
                        'from_name' => 'Ardent POS',
                        'email_verification' => true
                    ],
                    'payment' => [
                        'paystack_public_key' => '',
                        'paystack_secret_key' => '',
                        'paystack_webhook_secret' => '',
                        'currency' => 'GHS',
                        'currency_symbol' => '₵'
                    ],
                    'security' => [
                        'session_timeout' => 3600,
                        'max_login_attempts' => 5,
                        'require_2fa' => false,
                        'password_min_length' => 8,
                        'password_require_special' => true
                    ],
                    'notifications' => [
                        'email_notifications' => true,
                        'push_notifications' => true,
                        'sms_notifications' => false
                    ]
                ];
            }
            sendResponse($data);
        } catch (Exception $e) {
            sendError('Error fetching settings: ' . $e->getMessage());
        }
        break;
        
    case 'POST':
    case 'PUT':
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                sendError('Invalid JSON input');
            }
            
            if ($pdo) {
                // Update settings in database
                foreach ($input as $category => $settings) {
                    if (is_array($settings)) {
                        foreach ($settings as $key => $value) {
                            $settingKey = $category . '_' . $key;
                            $stmt = $pdo->prepare("
                                INSERT INTO system_settings (key, value) 
                                VALUES (?, ?) 
                                ON CONFLICT (key) 
                                DO UPDATE SET value = EXCLUDED.value
                            ");
                            $stmt->execute([$settingKey, (string)$value]);
                        }
                    }
                }
                
                sendResponse(['message' => 'Settings updated successfully']);
            } else {
                sendError('Database connection failed');
            }
        } catch (Exception $e) {
            sendError('Error updating settings: ' . $e->getMessage());
        }
        break;
        
    default:
        sendError('Method not allowed', 405);
        break;
}
?>
