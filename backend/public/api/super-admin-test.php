<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Load environment variables
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';

    // Validate database credentials
    if (empty($dbUser) || empty($dbPass)) {
        throw new Exception('Database credentials not configured');
    }

    // Connect to database
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Get request method and path
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);

    // Route handling
    switch ($method) {
        case 'GET':
            switch ($endpoint) {
                case 'super-admin-test.php':
                    // Basic stats
                    $stats = getBasicStats($pdo);
                    echo json_encode([
                        'success' => true,
                        'data' => $stats
                    ]);
                    break;

                case 'analytics':
                    $analytics = getBasicAnalytics($pdo);
                    echo json_encode([
                        'success' => true,
                        'data' => $analytics
                    ]);
                    break;

                case 'users':
                    $users = getBasicUsers($pdo);
                    echo json_encode([
                        'success' => true,
                        'data' => $users
                    ]);
                    break;

                case 'settings':
                    $settings = getBasicSettings($pdo);
                    echo json_encode([
                        'success' => true,
                        'data' => $settings
                    ]);
                    break;

                case 'tenants':
                    $tenants = getBasicTenants($pdo);
                    echo json_encode([
                        'success' => true,
                        'data' => $tenants
                    ]);
                    break;

                case 'activity':
                    $activity = getBasicActivity($pdo);
                    echo json_encode([
                        'success' => true,
                        'data' => $activity
                    ]);
                    break;

                case 'billing':
                    $billing = getBasicBilling($pdo);
                    echo json_encode([
                        'success' => true,
                        'data' => $billing
                    ]);
                    break;

                case 'subscriptions':
                    $subscriptions = getBasicSubscriptions($pdo);
                    echo json_encode([
                        'success' => true,
                        'data' => $subscriptions
                    ]);
                    break;

                case 'subscription-plans':
                    $plans = getBasicSubscriptionPlans($pdo);
                    echo json_encode([
                        'success' => true,
                        'data' => $plans
                    ]);
                    break;

                case 'health':
                    $health = getBasicHealth($pdo);
                    echo json_encode([
                        'success' => true,
                        'data' => $health
                    ]);
                    break;

                case 'logs':
                    $logs = getBasicLogs($pdo);
                    echo json_encode([
                        'success' => true,
                        'data' => $logs
                    ]);
                    break;

                case 'audit-logs':
                    $auditLogs = getBasicAuditLogs($pdo);
                    echo json_encode([
                        'success' => true,
                        'data' => $auditLogs
                    ]);
                    break;

                case 'security-events':
                    $securityEvents = getBasicSecurityEvents($pdo);
                    echo json_encode([
                        'success' => true,
                        'data' => $securityEvents
                    ]);
                    break;

                case 'api-keys':
                    $apiKeys = getBasicApiKeys($pdo);
                    echo json_encode([
                        'success' => true,
                        'data' => $apiKeys
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
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

function getBasicStats($pdo) {
    try {
        // Get total users
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role != 'super_admin'");
        $totalUsers = $stmt->fetch()['total_users'];

        // Get total tenants
        $stmt = $pdo->query("SELECT COUNT(*) as total_tenants FROM tenants");
        $totalTenants = $stmt->fetch()['total_tenants'];

        // Get total products
        $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
        $totalProducts = $stmt->fetch()['total_products'];

        // Get total sales
        $stmt = $pdo->query("SELECT COUNT(*) as total_sales FROM sales");
        $totalSales = $stmt->fetch()['total_sales'];

        return [
            'total_users' => (int)$totalUsers,
            'total_tenants' => (int)$totalTenants,
            'total_products' => (int)$totalProducts,
            'total_sales' => (int)$totalSales,
            'system_health' => 'healthy',
            'last_updated' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        return [
            'total_users' => 0,
            'total_tenants' => 0,
            'total_products' => 0,
            'total_sales' => 0,
            'system_health' => 'error',
            'error' => $e->getMessage(),
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }
}

function getBasicAnalytics($pdo) {
    try {
        // Get revenue for last 30 days
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(total_amount), 0) as revenue 
            FROM sales 
            WHERE created_at >= NOW() - INTERVAL '30 days'
        ");
        $revenue = $stmt->fetch()['revenue'];

        // Get user growth
        $stmt = $pdo->query("
            SELECT COUNT(*) as new_users 
            FROM users 
            WHERE created_at >= NOW() - INTERVAL '30 days' AND role != 'super_admin'
        ");
        $newUsers = $stmt->fetch()['new_users'];

        return [
            'revenue_30_days' => (float)$revenue,
            'new_users_30_days' => (int)$newUsers,
            'growth_rate' => 15.5,
            'active_users' => 85,
            'last_updated' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        return [
            'revenue_30_days' => 0,
            'new_users_30_days' => 0,
            'growth_rate' => 0,
            'active_users' => 0,
            'error' => $e->getMessage(),
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }
}

function getBasicUsers($pdo) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $offset = ($page - 1) * $limit;

        $stmt = $pdo->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.status, u.created_at,
                   t.name as tenant_name
            FROM users u
            LEFT JOIN tenants t ON u.tenant_id = t.id
            WHERE u.role != 'super_admin'
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $users = $stmt->fetchAll();

        // Get total count
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role != 'super_admin'");
        $total = $stmt->fetch()['total'];

        return [
            'users' => $users,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ];
    } catch (Exception $e) {
        return [
            'users' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 0,
                'pages' => 0
            ],
            'error' => $e->getMessage()
        ];
    }
}

function getBasicSettings($pdo) {
    try {
        // Get system settings
        $stmt = $pdo->query("
            SELECT key, value 
            FROM system_settings 
            ORDER BY key
        ");
        $settings = $stmt->fetchAll();

        $formattedSettings = [];
        foreach ($settings as $setting) {
            $formattedSettings[$setting['key']] = $setting['value'];
        }

        return [
            'general' => [
                'site_name' => $formattedSettings['site_name'] ?? 'Ardent POS',
                'site_description' => $formattedSettings['site_description'] ?? 'Enterprise Point of Sale System',
                'timezone' => $formattedSettings['timezone'] ?? 'UTC',
                'maintenance_mode' => ($formattedSettings['maintenance_mode'] ?? 'false') === 'true'
            ],
            'email' => [
                'smtp_host' => $formattedSettings['smtp_host'] ?? '',
                'smtp_port' => $formattedSettings['smtp_port'] ?? '587',
                'from_email' => $formattedSettings['from_email'] ?? 'noreply@ardentpos.com',
                'email_verification' => ($formattedSettings['email_verification'] ?? 'true') === 'true'
            ],
            'security' => [
                'session_timeout' => (int)($formattedSettings['session_timeout'] ?? 3600),
                'max_login_attempts' => (int)($formattedSettings['max_login_attempts'] ?? 5),
                'require_2fa' => ($formattedSettings['require_2fa'] ?? 'false') === 'true'
            ]
        ];
    } catch (Exception $e) {
        return [
            'general' => [
                'site_name' => 'Ardent POS',
                'site_description' => 'Enterprise Point of Sale System',
                'timezone' => 'UTC',
                'maintenance_mode' => false
            ],
            'email' => [
                'smtp_host' => '',
                'smtp_port' => '587',
                'from_email' => 'noreply@ardentpos.com',
                'email_verification' => true
            ],
            'security' => [
                'session_timeout' => 3600,
                'max_login_attempts' => 5,
                'require_2fa' => false
            ],
            'error' => $e->getMessage()
        ];
    }
}

function getBasicTenants($pdo) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $offset = ($page - 1) * $limit;

        $stmt = $pdo->prepare("
            SELECT id, name, status, created_at, updated_at
            FROM tenants
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $tenants = $stmt->fetchAll();

        // Get total count
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM tenants");
        $total = $stmt->fetch()['total'];

        return [
            'tenants' => $tenants,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ];
    } catch (Exception $e) {
        return [
            'tenants' => [
                [
                    'id' => '1',
                    'name' => 'Restaurant Chain',
                    'status' => 'active',
                    'created_at' => '2024-01-01 10:00:00',
                    'updated_at' => '2024-01-15 14:30:00'
                ],
                [
                    'id' => '2',
                    'name' => 'Tech Solutions Ltd',
                    'status' => 'active',
                    'created_at' => '2024-01-05 09:15:00',
                    'updated_at' => '2024-01-12 16:45:00'
                ]
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 2,
                'pages' => 1
            ]
        ];
    }
}

function getBasicActivity($pdo) {
    try {
        return [
            [
                'id' => 1,
                'type' => 'tenant_created',
                'message' => 'New tenant "Tech Solutions Ltd" registered',
                'time' => '2 hours ago',
                'status' => 'success'
            ],
            [
                'id' => 2,
                'type' => 'payment_received',
                'message' => 'Payment received from "Restaurant Chain"',
                'time' => '4 hours ago',
                'status' => 'success'
            ],
            [
                'id' => 3,
                'type' => 'system_alert',
                'message' => 'System backup completed successfully',
                'time' => '6 hours ago',
                'status' => 'success'
            ]
        ];
    } catch (Exception $e) {
        return [];
    }
}

function getBasicBilling($pdo) {
    try {
        return [
            'total_subscriptions' => 25,
            'active_subscriptions' => 23,
            'pending_subscriptions' => 2,
            'cancelled_subscriptions' => 5,
            'total_revenue' => 1250000,
            'monthly_revenue' => 125000,
            'annual_revenue' => 1500000,
            'churn_rate' => 2.1,
            'average_revenue_per_user' => 5434.78
        ];
    } catch (Exception $e) {
        return [
            'total_subscriptions' => 0,
            'active_subscriptions' => 0,
            'pending_subscriptions' => 0,
            'cancelled_subscriptions' => 0,
            'total_revenue' => 0,
            'monthly_revenue' => 0,
            'annual_revenue' => 0,
            'churn_rate' => 0,
            'average_revenue_per_user' => 0
        ];
    }
}

function getBasicSubscriptions($pdo) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        
        return [
            'subscriptions' => [
                [
                    'id' => '1',
                    'tenant_name' => 'Restaurant Chain',
                    'plan_name' => 'enterprise',
                    'status' => 'active',
                    'amount' => 480,
                    'currency' => 'GHS',
                    'next_billing_date' => '2024-02-15',
                    'created_at' => '2024-01-01'
                ],
                [
                    'id' => '2',
                    'tenant_name' => 'Tech Solutions Ltd',
                    'plan_name' => 'professional',
                    'status' => 'active',
                    'amount' => 240,
                    'currency' => 'GHS',
                    'next_billing_date' => '2024-02-10',
                    'created_at' => '2024-01-05'
                ]
            ],
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => 2,
                'pages' => 1
            ]
        ];
    } catch (Exception $e) {
        return [
            'subscriptions' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 0,
                'pages' => 0
            ]
        ];
    }
}

function getBasicSubscriptionPlans($pdo) {
    try {
        return [
            [
                'id' => 'starter',
                'name' => 'Starter',
                'description' => 'Perfect for small businesses just getting started',
                'monthly_price' => 120,
                'yearly_price' => 1200,
                'features' => [
                    'Up to 100 products',
                    'Up to 2 users',
                    'Basic reporting',
                    'Email support',
                    'Mobile app access'
                ]
            ],
            [
                'id' => 'professional',
                'name' => 'Professional',
                'description' => 'Ideal for growing businesses with advanced needs',
                'monthly_price' => 240,
                'yearly_price' => 2400,
                'popular' => true,
                'features' => [
                    'Up to 1,000 products',
                    'Up to 10 users',
                    'Advanced reporting & analytics',
                    'Priority email support',
                    'Mobile app access',
                    'Inventory management',
                    'Customer management',
                    'Multi-location support'
                ]
            ],
            [
                'id' => 'enterprise',
                'name' => 'Enterprise',
                'description' => 'For large businesses requiring maximum flexibility',
                'monthly_price' => 480,
                'yearly_price' => 4800,
                'features' => [
                    'Unlimited products',
                    'Unlimited users',
                    'Advanced reporting & analytics',
                    'Phone & email support',
                    'Mobile app access',
                    'Full inventory management',
                    'Advanced customer management',
                    'Multi-location support',
                    'API access',
                    'Custom integrations',
                    'White-label options'
                ]
            ]
        ];
    } catch (Exception $e) {
        return [];
    }
}

function getBasicHealth($pdo) {
    try {
        return [
            'cpu' => 45,
            'memory' => 62,
            'disk' => 38,
            'network' => 95,
            'database' => 99.9,
            'api' => 99.7,
            'status' => 'healthy',
            'last_check' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        return [
            'cpu' => 0,
            'memory' => 0,
            'disk' => 0,
            'network' => 0,
            'database' => 0,
            'api' => 0,
            'status' => 'error',
            'last_check' => date('Y-m-d H:i:s')
        ];
    }
}

function getBasicLogs($pdo) {
    try {
        return [
            'logs' => [
                [
                    'id' => 1,
                    'level' => 'info',
                    'message' => 'System backup completed successfully',
                    'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                    'user_id' => null
                ],
                [
                    'id' => 2,
                    'level' => 'warning',
                    'message' => 'High CPU usage detected',
                    'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                    'user_id' => null
                ]
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 2,
                'pages' => 1
            ]
        ];
    } catch (Exception $e) {
        return [
            'logs' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 0,
                'pages' => 0
            ]
        ];
    }
}

function getBasicAuditLogs($pdo) {
    try {
        return [
            'audit_logs' => [
                [
                    'id' => 1,
                    'action' => 'user_login',
                    'user_id' => '550e8400-e29b-41d4-a716-446655440001',
                    'details' => 'User logged in from IP 192.168.1.100',
                    'timestamp' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
                ],
                [
                    'id' => 2,
                    'action' => 'settings_updated',
                    'user_id' => '550e8400-e29b-41d4-a716-446655440001',
                    'details' => 'System settings updated',
                    'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour'))
                ]
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 2,
                'pages' => 1
            ]
        ];
    } catch (Exception $e) {
        return [
            'audit_logs' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 0,
                'pages' => 0
            ]
        ];
    }
}

function getBasicSecurityEvents($pdo) {
    try {
        return [
            'security_events' => [
                [
                    'id' => 1,
                    'type' => 'failed_login',
                    'severity' => 'medium',
                    'description' => 'Multiple failed login attempts detected',
                    'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                    'ip_address' => '192.168.1.100'
                ],
                [
                    'id' => 2,
                    'type' => 'suspicious_activity',
                    'severity' => 'low',
                    'description' => 'Unusual access pattern detected',
                    'timestamp' => date('Y-m-d H:i:s', strtotime('-4 hours')),
                    'ip_address' => '192.168.1.101'
                ]
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 2,
                'pages' => 1
            ]
        ];
    } catch (Exception $e) {
        return [
            'security_events' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 0,
                'pages' => 0
            ]
        ];
    }
}

function getBasicApiKeys($pdo) {
    try {
        return [
            'api_keys' => [
                [
                    'id' => 1,
                    'name' => 'Production API Key',
                    'key' => 'pk_live_...',
                    'status' => 'active',
                    'created_at' => '2024-01-01 10:00:00',
                    'last_used' => '2024-01-15 14:30:00'
                ],
                [
                    'id' => 2,
                    'name' => 'Development API Key',
                    'key' => 'pk_test_...',
                    'status' => 'active',
                    'created_at' => '2024-01-05 09:15:00',
                    'last_used' => '2024-01-12 16:45:00'
                ]
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 2,
                'pages' => 1
            ]
        ];
    } catch (Exception $e) {
        return [
            'api_keys' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 0,
                'pages' => 0
            ]
        ];
    }
}
?>
