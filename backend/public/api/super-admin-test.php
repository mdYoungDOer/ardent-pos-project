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
?>
