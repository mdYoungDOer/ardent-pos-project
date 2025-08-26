<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection function
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

// Response function
function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// Error response function
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
                $stmt = $pdo->query("SELECT * FROM subscription_plans ORDER BY monthly_price ASC");
                $plans = $stmt->fetchAll();
                
                // Parse JSON fields
                foreach ($plans as &$plan) {
                    $plan['features'] = json_decode($plan['features'], true);
                    $plan['limits'] = json_decode($plan['limits'], true);
                }
                
                sendResponse($plans);
            } else {
                // Fallback data
                $fallbackPlans = [
                    [
                        'id' => 1,
                        'plan_id' => 'starter',
                        'name' => 'Starter',
                        'description' => 'Perfect for small businesses and startups getting started with POS',
                        'monthly_price' => 120.00,
                        'yearly_price' => 1200.00,
                        'currency' => 'GHS',
                        'features' => [
                            'Basic POS functionality',
                            'Up to 2 locations',
                            'Basic inventory management',
                            'Sales reports',
                            'Customer management',
                            'Basic analytics',
                            'Email support',
                            'Mobile app access',
                            'Receipt printing',
                            'Basic tax calculations'
                        ],
                        'limits' => [
                            'locations' => 2,
                            'users' => 3,
                            'products' => 500,
                            'customers' => 1000,
                            'transactions_per_month' => 1000,
                            'storage_gb' => 5,
                            'api_calls_per_month' => 10000,
                            'backup_retention_days' => 30
                        ],
                        'is_active' => true,
                        'is_popular' => false
                    ],
                    [
                        'id' => 2,
                        'plan_id' => 'professional',
                        'name' => 'Professional',
                        'description' => 'Ideal for growing businesses with multiple locations',
                        'monthly_price' => 240.00,
                        'yearly_price' => 2400.00,
                        'currency' => 'GHS',
                        'features' => [
                            'Everything in Starter',
                            'Up to 5 locations',
                            'Advanced inventory management',
                            'Advanced analytics & reporting',
                            'Multi-user access',
                            'Customer loyalty program',
                            'Discount & coupon management',
                            'Advanced tax management',
                            'Integration with accounting software',
                            'Priority email support',
                            'Advanced security features',
                            'Data export capabilities'
                        ],
                        'limits' => [
                            'locations' => 5,
                            'users' => 10,
                            'products' => 2000,
                            'customers' => 5000,
                            'transactions_per_month' => 5000,
                            'storage_gb' => 20,
                            'api_calls_per_month' => 50000,
                            'backup_retention_days' => 90
                        ],
                        'is_active' => true,
                        'is_popular' => true
                    ],
                    [
                        'id' => 3,
                        'plan_id' => 'business',
                        'name' => 'Business',
                        'description' => 'Comprehensive solution for established businesses',
                        'monthly_price' => 360.00,
                        'yearly_price' => 3600.00,
                        'currency' => 'GHS',
                        'features' => [
                            'Everything in Professional',
                            'Up to 10 locations',
                            'Advanced customer analytics',
                            'Multi-currency support',
                            'Advanced reporting suite',
                            'Inventory forecasting',
                            'Supplier management',
                            'Advanced user permissions',
                            'API access',
                            'Custom integrations',
                            'Phone & email support',
                            'Advanced security & compliance',
                            'Data migration assistance'
                        ],
                        'limits' => [
                            'locations' => 10,
                            'users' => 25,
                            'products' => 10000,
                            'customers' => 25000,
                            'transactions_per_month' => 25000,
                            'storage_gb' => 50,
                            'api_calls_per_month' => 100000,
                            'backup_retention_days' => 180
                        ],
                        'is_active' => true,
                        'is_popular' => false
                    ],
                    [
                        'id' => 4,
                        'plan_id' => 'enterprise',
                        'name' => 'Enterprise',
                        'description' => 'Full-featured solution for large enterprises and chains',
                        'monthly_price' => 480.00,
                        'yearly_price' => 4800.00,
                        'currency' => 'GHS',
                        'features' => [
                            'Everything in Business',
                            'Unlimited locations',
                            'Advanced business intelligence',
                            'Custom reporting',
                            'White-label solutions',
                            'Advanced API access',
                            'Custom integrations',
                            'Dedicated account manager',
                            '24/7 priority support',
                            'Advanced security & compliance',
                            'Custom training sessions',
                            'SLA guarantees',
                            'Advanced backup & recovery'
                        ],
                        'limits' => [
                            'locations' => -1,
                            'users' => 100,
                            'products' => -1,
                            'customers' => -1,
                            'transactions_per_month' => -1,
                            'storage_gb' => 200,
                            'api_calls_per_month' => 500000,
                            'backup_retention_days' => 365
                        ],
                        'is_active' => true,
                        'is_popular' => false
                    ],
                    [
                        'id' => 5,
                        'plan_id' => 'premium',
                        'name' => 'Premium',
                        'description' => 'Ultimate solution with custom features and dedicated support',
                        'monthly_price' => 600.00,
                        'yearly_price' => 6000.00,
                        'currency' => 'GHS',
                        'features' => [
                            'Everything in Enterprise',
                            'Custom feature development',
                            'Dedicated support team',
                            'Custom integrations',
                            'Advanced analytics & AI',
                            'Multi-brand management',
                            'Advanced security features',
                            'Custom training programs',
                            'Performance optimization',
                            'Custom SLA agreements',
                            'On-site support available',
                            'Custom backup solutions'
                        ],
                        'limits' => [
                            'locations' => -1,
                            'users' => -1,
                            'products' => -1,
                            'customers' => -1,
                            'transactions_per_month' => -1,
                            'storage_gb' => 500,
                            'api_calls_per_month' => 1000000,
                            'backup_retention_days' => 730
                        ],
                        'is_active' => true,
                        'is_popular' => false
                    ]
                ];
                sendResponse($fallbackPlans);
            }
        } catch (Exception $e) {
            sendError('Error fetching subscription plans: ' . $e->getMessage());
        }
        break;
        
    case 'POST':
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                sendError('Invalid JSON input');
            }
            
            if ($pdo) {
                $stmt = $pdo->prepare("
                    INSERT INTO subscription_plans (plan_id, name, description, monthly_price, yearly_price, features, limits, is_active, is_popular)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $input['plan_id'],
                    $input['name'],
                    $input['description'],
                    $input['monthly_price'],
                    $input['yearly_price'],
                    json_encode($input['features']),
                    json_encode($input['limits']),
                    $input['is_active'] ? 1 : 0,
                    $input['is_popular'] ? 1 : 0
                ]);
                
                sendResponse(['message' => 'Subscription plan created successfully']);
            } else {
                sendError('Database connection failed');
            }
        } catch (Exception $e) {
            sendError('Error creating subscription plan: ' . $e->getMessage());
        }
        break;
        
    case 'PUT':
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                sendError('Invalid JSON input');
            }
            
            // Get plan ID from URL
            $uri = $_SERVER['REQUEST_URI'];
            $path = parse_url($uri, PHP_URL_PATH);
            $segments = explode('/', trim($path, '/'));
            $planId = end($segments);
            
            if ($pdo) {
                $stmt = $pdo->prepare("
                    UPDATE subscription_plans 
                    SET plan_id = ?, name = ?, description = ?, monthly_price = ?, yearly_price = ?, 
                        features = ?, limits = ?, is_active = ?, is_popular = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $input['plan_id'],
                    $input['name'],
                    $input['description'],
                    $input['monthly_price'],
                    $input['yearly_price'],
                    json_encode($input['features']),
                    json_encode($input['limits']),
                    $input['is_active'] ? 1 : 0,
                    $input['is_popular'] ? 1 : 0,
                    $planId
                ]);
                
                sendResponse(['message' => 'Subscription plan updated successfully']);
            } else {
                sendError('Database connection failed');
            }
        } catch (Exception $e) {
            sendError('Error updating subscription plan: ' . $e->getMessage());
        }
        break;
        
    case 'DELETE':
        try {
            // Get plan ID from URL
            $uri = $_SERVER['REQUEST_URI'];
            $path = parse_url($uri, PHP_URL_PATH);
            $segments = explode('/', trim($path, '/'));
            $planId = end($segments);
            
            if ($pdo) {
                $stmt = $pdo->prepare("DELETE FROM subscription_plans WHERE id = ?");
                $stmt->execute([$planId]);
                
                sendResponse(['message' => 'Subscription plan deleted successfully']);
            } else {
                sendError('Database connection failed');
            }
        } catch (Exception $e) {
            sendError('Error deleting subscription plan: ' . $e->getMessage());
        }
        break;
        
    default:
        sendError('Method not allowed', 405);
        break;
}
?>
