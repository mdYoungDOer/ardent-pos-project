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
    
    // Handle requests
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    switch ($method) {
        case 'GET':
            if ($endpoint === 'subscription-plans') {
                getSubscriptionPlans($pdo);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
            }
            break;
            
        case 'POST':
            if ($endpoint === 'subscription-plans') {
                createSubscriptionPlan($pdo, $currentUser);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
            }
            break;
            
        case 'PUT':
            if ($endpoint === 'subscription-plans') {
                updateSubscriptionPlan($pdo, $currentUser);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
            }
            break;
            
        case 'DELETE':
            if ($endpoint === 'subscription-plans') {
                deleteSubscriptionPlan($pdo, $currentUser);
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
    error_log("Subscription Plans Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}

function getSubscriptionPlans($pdo) {
    try {
        // Default subscription plans (hardcoded for now)
        $plans = [
            [
                'id' => 'free',
                'name' => 'Free Plan',
                'description' => 'Perfect for small businesses just getting started',
                'price' => 0,
                'currency' => 'GHS',
                'billing_cycle' => 'monthly',
                'features' => [
                    'Up to 100 products',
                    'Basic reporting',
                    'Email support',
                    '1 user account',
                    'Basic inventory management'
                ],
                'limits' => [
                    'products' => 100,
                    'users' => 1,
                    'storage' => '1GB',
                    'api_calls' => 1000
                ],
                'status' => 'active',
                'is_popular' => false,
                'is_enterprise' => false
            ],
            [
                'id' => 'starter',
                'name' => 'Starter Plan',
                'description' => 'Great for growing businesses with basic needs',
                'price' => 50,
                'currency' => 'GHS',
                'billing_cycle' => 'monthly',
                'features' => [
                    'Up to 500 products',
                    'Advanced reporting',
                    'Priority support',
                    'Up to 5 user accounts',
                    'Inventory management',
                    'Customer management',
                    'Basic analytics'
                ],
                'limits' => [
                    'products' => 500,
                    'users' => 5,
                    'storage' => '5GB',
                    'api_calls' => 5000
                ],
                'status' => 'active',
                'is_popular' => true,
                'is_enterprise' => false
            ],
            [
                'id' => 'professional',
                'name' => 'Professional Plan',
                'description' => 'Advanced features for established businesses',
                'price' => 150,
                'currency' => 'GHS',
                'billing_cycle' => 'monthly',
                'features' => [
                    'Unlimited products',
                    'Advanced analytics',
                    '24/7 support',
                    'Unlimited user accounts',
                    'Multi-location support',
                    'API access',
                    'Custom integrations',
                    'Advanced reporting',
                    'White-label options'
                ],
                'limits' => [
                    'products' => -1, // Unlimited
                    'users' => -1, // Unlimited
                    'storage' => '50GB',
                    'api_calls' => 50000
                ],
                'status' => 'active',
                'is_popular' => false,
                'is_enterprise' => false
            ],
            [
                'id' => 'enterprise',
                'name' => 'Enterprise Plan',
                'description' => 'Custom solutions for large organizations',
                'price' => 500,
                'currency' => 'GHS',
                'billing_cycle' => 'monthly',
                'features' => [
                    'Everything in Professional',
                    'Dedicated account manager',
                    'Custom development',
                    'White-label options',
                    'Advanced security',
                    'SLA guarantees',
                    'On-premise deployment',
                    'Custom integrations',
                    'Priority support'
                ],
                'limits' => [
                    'products' => -1, // Unlimited
                    'users' => -1, // Unlimited
                    'storage' => '500GB',
                    'api_calls' => -1 // Unlimited
                ],
                'status' => 'active',
                'is_popular' => false,
                'is_enterprise' => true
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

function createSubscriptionPlan($pdo, $currentUser) {
    try {
        // Only super admins can create subscription plans
        if ($currentUser['role'] !== 'super_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Super admin access required']);
            return;
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['name']) || !isset($data['price'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Name and price are required']);
            return;
        }
        
        // In a real implementation, you would save this to a database
        // For now, we'll just return success
        
        echo json_encode([
            'success' => true,
            'message' => 'Subscription plan created successfully',
            'data' => [
                'id' => 'plan_' . time(),
                'name' => $data['name'],
                'price' => $data['price'],
                'currency' => $data['currency'] ?? 'GHS',
                'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                'features' => $data['features'] ?? [],
                'limits' => $data['limits'] ?? [],
                'status' => 'active'
            ]
        ]);
    } catch (Exception $e) {
        error_log("Create subscription plan error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create subscription plan']);
    }
}

function updateSubscriptionPlan($pdo, $currentUser) {
    try {
        // Only super admins can update subscription plans
        if ($currentUser['role'] !== 'super_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Super admin access required']);
            return;
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Plan ID is required']);
            return;
        }
        
        // In a real implementation, you would update this in a database
        // For now, we'll just return success
        
        echo json_encode([
            'success' => true,
            'message' => 'Subscription plan updated successfully',
            'data' => $data
        ]);
    } catch (Exception $e) {
        error_log("Update subscription plan error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update subscription plan']);
    }
}

function deleteSubscriptionPlan($pdo, $currentUser) {
    try {
        // Only super admins can delete subscription plans
        if ($currentUser['role'] !== 'super_admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Super admin access required']);
            return;
        }
        
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Plan ID is required']);
            return;
        }
        
        // In a real implementation, you would delete this from a database
        // For now, we'll just return success
        
        echo json_encode([
            'success' => true,
            'message' => 'Subscription plan deleted successfully'
        ]);
    } catch (Exception $e) {
        error_log("Delete subscription plan error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete subscription plan']);
    }
}
