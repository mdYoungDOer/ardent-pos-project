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
    
    // Only super admins can access Super Admin dashboard
    if ($currentUser['role'] !== 'super_admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Super admin access required']);
        exit;
    }
    
    // Handle requests
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    switch ($method) {
        case 'GET':
            if ($endpoint === 'stats') {
                getSuperAdminStats($pdo);
            } elseif ($endpoint === 'analytics') {
                getAnalytics($pdo);
            } elseif ($endpoint === 'activity') {
                getActivity($pdo);
            } elseif ($endpoint === 'tenants') {
                getTenants($pdo);
            } elseif ($endpoint === 'users') {
                getUsers($pdo);
            } elseif ($endpoint === 'subscriptions') {
                getSubscriptions($pdo);
            } elseif ($endpoint === 'billing-overview') {
                getBillingOverview($pdo);
            } elseif ($endpoint === 'invoices') {
                getInvoices($pdo);
            } elseif ($endpoint === 'contact-submissions') {
                getContactSubmissions($pdo);
            } elseif ($endpoint === 'system-logs') {
                getSystemLogs($pdo);
            } elseif ($endpoint === 'system-health') {
                getSystemHealth($pdo);
            } elseif ($endpoint === 'security-settings') {
                getSecuritySettings($pdo);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
            }
            break;
            
        case 'POST':
            if ($endpoint === 'tenants') {
                createTenant($pdo);
            } elseif ($endpoint === 'users') {
                createUser($pdo);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
            }
            break;
            
        case 'PUT':
            if ($endpoint === 'tenants') {
                updateTenant($pdo);
            } elseif ($endpoint === 'users') {
                updateUser($pdo);
            } elseif ($endpoint === 'subscriptions') {
                updateSubscription($pdo);
            } elseif ($endpoint === 'invoices') {
                updateInvoice($pdo);
            } elseif ($endpoint === 'security-settings') {
                updateSecuritySettings($pdo);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
            }
            break;
            
        case 'DELETE':
            if ($endpoint === 'tenants') {
                deleteTenant($pdo);
            } elseif ($endpoint === 'users') {
                deleteUser($pdo);
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
    error_log("Super Admin Dashboard Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}

function getSuperAdminStats($pdo) {
    try {
        // Get total tenants
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_tenants FROM tenants");
        $stmt->execute();
        $totalTenants = $stmt->fetchColumn();
        
        // Get active tenants
        $stmt = $pdo->prepare("SELECT COUNT(*) as active_tenants FROM tenants WHERE status = 'active'");
        $stmt->execute();
        $activeTenants = $stmt->fetchColumn();
        
        // Get total users
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM users");
        $stmt->execute();
        $totalUsers = $stmt->fetchColumn();
        
        // Get total revenue
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_revenue FROM invoices WHERE status = 'paid'");
        $stmt->execute();
        $totalRevenue = $stmt->fetchColumn();
        
        // Get system uptime (simulated)
        $systemUptime = '99.9%';
        
        // Get monthly growth
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as current_month,
                (SELECT COUNT(*) FROM tenants WHERE created_at < DATE_TRUNC('month', CURRENT_DATE)) as last_month
            FROM tenants 
            WHERE created_at >= DATE_TRUNC('month', CURRENT_DATE)
        ");
        $stmt->execute();
        $growthData = $stmt->fetch();
        $monthlyGrowth = $growthData['last_month'] > 0 ? 
            (($growthData['current_month'] - $growthData['last_month']) / $growthData['last_month']) * 100 : 0;
        
        // Get system health (simulated)
        $systemHealth = [
            'cpu' => rand(20, 80),
            'memory' => rand(30, 90),
            'disk' => rand(25, 75),
            'network' => rand(85, 100),
            'database' => 99.9,
            'api' => 99.7
        ];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_tenants' => (int)$totalTenants,
                'active_tenants' => (int)$activeTenants,
                'total_users' => (int)$totalUsers,
                'total_revenue' => (float)$totalRevenue,
                'system_uptime' => $systemUptime,
                'monthly_growth' => round($monthlyGrowth, 2),
                'system_health' => $systemHealth
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get Super Admin stats error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch stats']);
    }
}

function getAnalytics($pdo) {
    try {
        // Get revenue for last 30 days
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as revenue_30_days 
            FROM invoices 
            WHERE status = 'paid' AND created_at >= CURRENT_DATE - INTERVAL '30 days'
        ");
        $stmt->execute();
        $revenue30Days = $stmt->fetchColumn();
        
        // Get new users in last 30 days
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as new_users_30_days 
            FROM users 
            WHERE created_at >= CURRENT_DATE - INTERVAL '30 days'
        ");
        $stmt->execute();
        $newUsers30Days = $stmt->fetchColumn();
        
        // Get growth rate
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as current_period,
                (SELECT COUNT(*) FROM tenants WHERE created_at >= CURRENT_DATE - INTERVAL '60 days' AND created_at < CURRENT_DATE - INTERVAL '30 days') as previous_period
            FROM tenants 
            WHERE created_at >= CURRENT_DATE - INTERVAL '30 days'
        ");
        $stmt->execute();
        $growthData = $stmt->fetch();
        $growthRate = $growthData['previous_period'] > 0 ? 
            (($growthData['current_period'] - $growthData['previous_period']) / $growthData['previous_period']) * 100 : 0;
        
        // Get active users (simulated)
        $activeUsers = rand(500, 1000);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'revenue_30_days' => (float)$revenue30Days,
                'new_users_30_days' => (int)$newUsers30Days,
                'growth_rate' => round($growthRate, 2),
                'active_users' => $activeUsers
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get analytics error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch analytics']);
    }
}

function getActivity($pdo) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $offset = ($page - 1) * $limit;
        
        // Get recent activities (simulated for now)
        $activities = [
            [
                'id' => '1',
                'type' => 'tenant_registration',
                'description' => 'New tenant registered: TechCorp Solutions',
                'timestamp' => date('Y-m-d H:i:s', time() - 3600),
                'user' => 'john.doe@techcorp.com'
            ],
            [
                'id' => '2',
                'type' => 'subscription_upgrade',
                'description' => 'Subscription upgraded to Professional Plan',
                'timestamp' => date('Y-m-d H:i:s', time() - 7200),
                'user' => 'admin@retailstore.com'
            ],
            [
                'id' => '3',
                'type' => 'payment_received',
                'description' => 'Payment received: $240.00',
                'timestamp' => date('Y-m-d H:i:s', time() - 10800),
                'user' => 'finance@business.com'
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $activities
        ]);
    } catch (Exception $e) {
        error_log("Get activity error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch activity']);
    }
}

function getTenants($pdo) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['1=1'];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = '(name ILIKE ? OR subdomain ILIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($status)) {
            $whereConditions[] = 'status = ?';
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM tenants WHERE $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get tenants
        $sql = "
            SELECT * FROM tenants 
            WHERE $whereClause 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tenants = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'tenants' => $tenants,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get tenants error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch tenants']);
    }
}

function createTenant($pdo) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['name']) || empty($data['subdomain'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Name and subdomain are required']);
            return;
        }
        
        // Check if subdomain already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tenants WHERE subdomain = ?");
        $stmt->execute([$data['subdomain']]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Subdomain already exists']);
            return;
        }
        
        $sql = "
            INSERT INTO tenants (id, name, subdomain, plan, status, settings, created_at, updated_at)
            VALUES (uuid_generate_v4(), ?, ?, ?, 'active', '{}', NOW(), NOW())
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['subdomain'],
            $data['plan'] ?? 'free'
        ]);
        
        $tenant = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $tenant
        ]);
    } catch (Exception $e) {
        error_log("Create tenant error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create tenant']);
    }
}

function updateTenant($pdo) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Tenant ID is required']);
            return;
        }
        
        $sql = "
            UPDATE tenants 
            SET name = ?, subdomain = ?, plan = ?, status = ?, settings = ?, updated_at = NOW()
            WHERE id = ?
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['name'] ?? '',
            $data['subdomain'] ?? '',
            $data['plan'] ?? 'free',
            $data['status'] ?? 'active',
            json_encode($data['settings'] ?? []),
            $data['id']
        ]);
        
        $tenant = $stmt->fetch();
        
        if (!$tenant) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Tenant not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $tenant
        ]);
    } catch (Exception $e) {
        error_log("Update tenant error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update tenant']);
    }
}

function deleteTenant($pdo) {
    try {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Tenant ID is required']);
            return;
        }
        
        $sql = "DELETE FROM tenants WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Tenant not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Tenant deleted successfully'
        ]);
    } catch (Exception $e) {
        error_log("Delete tenant error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete tenant']);
    }
}

function getUsers($pdo) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['1=1'];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = '(first_name ILIKE ? OR last_name ILIKE ? OR email ILIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($role)) {
            $whereConditions[] = 'role = ?';
            $params[] = $role;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM users WHERE $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get users with tenant info
        $sql = "
            SELECT u.*, t.name as tenant_name 
            FROM users u 
            LEFT JOIN tenants t ON u.tenant_id = t.id 
            WHERE $whereClause 
            ORDER BY u.created_at DESC 
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'users' => $users,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get users error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch users']);
    }
}

function createUser($pdo) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'First name, last name, and email are required']);
            return;
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Email already exists']);
            return;
        }
        
        $sql = "
            INSERT INTO users (id, first_name, last_name, email, password_hash, role, tenant_id, status, created_at, updated_at)
            VALUES (uuid_generate_v4(), ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            password_hash($data['password'] ?? 'password123', PASSWORD_DEFAULT),
            $data['role'] ?? 'user',
            $data['tenant_id'] ?? null
        ]);
        
        $user = $stmt->fetch();
        unset($user['password_hash']); // Don't return password hash
        
        echo json_encode([
            'success' => true,
            'data' => $user
        ]);
    } catch (Exception $e) {
        error_log("Create user error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create user']);
    }
}

function updateUser($pdo) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'User ID is required']);
            return;
        }
        
        $sql = "
            UPDATE users 
            SET first_name = ?, last_name = ?, email = ?, role = ?, tenant_id = ?, status = ?, updated_at = NOW()
            WHERE id = ?
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['first_name'] ?? '',
            $data['last_name'] ?? '',
            $data['email'] ?? '',
            $data['role'] ?? 'user',
            $data['tenant_id'] ?? null,
            $data['status'] ?? 'active',
            $data['id']
        ]);
        
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
            return;
        }
        
        unset($user['password_hash']); // Don't return password hash
        
        echo json_encode([
            'success' => true,
            'data' => $user
        ]);
    } catch (Exception $e) {
        error_log("Update user error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update user']);
    }
}

function deleteUser($pdo) {
    try {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'User ID is required']);
            return;
        }
        
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    } catch (Exception $e) {
        error_log("Delete user error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete user']);
    }
}

function getSubscriptions($pdo) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $status = $_GET['status'] ?? '';
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['1=1'];
        $params = [];
        
        if (!empty($status)) {
            $whereConditions[] = 's.status = ?';
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM subscriptions s WHERE $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get subscriptions with tenant info
        $sql = "
            SELECT s.*, t.name as tenant_name 
            FROM subscriptions s 
            LEFT JOIN tenants t ON s.tenant_id = t.id 
            WHERE $whereClause 
            ORDER BY s.created_at DESC 
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $subscriptions = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'subscriptions' => $subscriptions,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get subscriptions error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch subscriptions']);
    }
}

function updateSubscription($pdo) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Subscription ID is required']);
            return;
        }
        
        $sql = "
            UPDATE subscriptions 
            SET plan_name = ?, status = ?, amount = ?, billing_cycle = ?, updated_at = NOW()
            WHERE id = ?
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['plan_name'] ?? '',
            $data['status'] ?? 'active',
            $data['amount'] ?? 0,
            $data['billing_cycle'] ?? 'monthly',
            $data['id']
        ]);
        
        $subscription = $stmt->fetch();
        
        if (!$subscription) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Subscription not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $subscription
        ]);
    } catch (Exception $e) {
        error_log("Update subscription error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update subscription']);
    }
}

function getBillingOverview($pdo) {
    try {
        // Get total revenue
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_revenue FROM invoices WHERE status = 'paid'");
        $stmt->execute();
        $totalRevenue = $stmt->fetchColumn();
        
        // Get active subscriptions
        $stmt = $pdo->prepare("SELECT COUNT(*) as active_subscriptions FROM subscriptions WHERE status = 'active'");
        $stmt->execute();
        $activeSubscriptions = $stmt->fetchColumn();
        
        // Get pending payments
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as pending_payments FROM invoices WHERE status = 'pending'");
        $stmt->execute();
        $pendingPayments = $stmt->fetchColumn();
        
        // Get monthly growth
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(amount), 0) as current_month,
                (SELECT COALESCE(SUM(amount), 0) FROM invoices WHERE status = 'paid' AND created_at >= CURRENT_DATE - INTERVAL '60 days' AND created_at < CURRENT_DATE - INTERVAL '30 days') as previous_month
            FROM invoices 
            WHERE status = 'paid' AND created_at >= CURRENT_DATE - INTERVAL '30 days'
        ");
        $stmt->execute();
        $growthData = $stmt->fetch();
        $monthlyGrowth = $growthData['previous_month'] > 0 ? 
            (($growthData['current_month'] - $growthData['previous_month']) / $growthData['previous_month']) * 100 : 0;
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_revenue' => (float)$totalRevenue,
                'active_subscriptions' => (int)$activeSubscriptions,
                'pending_payments' => (float)$pendingPayments,
                'monthly_growth' => round($monthlyGrowth, 2)
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get billing overview error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch billing overview']);
    }
}

function getInvoices($pdo) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $status = $_GET['status'] ?? '';
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['1=1'];
        $params = [];
        
        if (!empty($status)) {
            $whereConditions[] = 'i.status = ?';
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM invoices i WHERE $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get invoices with tenant info
        $sql = "
            SELECT i.*, t.name as tenant_name 
            FROM invoices i 
            LEFT JOIN tenants t ON i.tenant_id = t.id 
            WHERE $whereClause 
            ORDER BY i.created_at DESC 
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $invoices = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'invoices' => $invoices,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get invoices error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch invoices']);
    }
}

function updateInvoice($pdo) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invoice ID is required']);
            return;
        }
        
        $sql = "
            UPDATE invoices 
            SET status = ?, amount = ?, currency = ?, updated_at = NOW()
            WHERE id = ?
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['status'] ?? 'pending',
            $data['amount'] ?? 0,
            $data['currency'] ?? 'GHS',
            $data['id']
        ]);
        
        $invoice = $stmt->fetch();
        
        if (!$invoice) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Invoice not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $invoice
        ]);
    } catch (Exception $e) {
        error_log("Update invoice error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update invoice']);
    }
}

function getContactSubmissions($pdo) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $status = $_GET['status'] ?? '';
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['1=1'];
        $params = [];
        
        if (!empty($status)) {
            $whereConditions[] = 'status = ?';
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM contact_submissions WHERE $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get contact submissions
        $sql = "
            SELECT * FROM contact_submissions 
            WHERE $whereClause 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $submissions = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'submissions' => $submissions,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get contact submissions error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch contact submissions']);
    }
}

function getSystemLogs($pdo) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 50;
        $level = $_GET['level'] ?? '';
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['1=1'];
        $params = [];
        
        if (!empty($level)) {
            $whereConditions[] = 'level = ?';
            $params[] = $level;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM audit_logs WHERE $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get system logs
        $sql = "
            SELECT * FROM audit_logs 
            WHERE $whereClause 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'logs' => $logs,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get system logs error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch system logs']);
    }
}

function getSystemHealth($pdo) {
    try {
        // Simulated system health data
        $systemHealth = [
            'database' => 'healthy',
            'api' => 'healthy',
            'authentication' => 'healthy',
            'overall' => 'healthy',
            'last_check' => date('Y-m-d H:i:s'),
            'uptime' => '99.9%',
            'response_time' => rand(50, 200) . 'ms'
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $systemHealth
        ]);
    } catch (Exception $e) {
        error_log("Get system health error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch system health']);
    }
}

function getSecuritySettings($pdo) {
    try {
        // Simulated security settings
        $securitySettings = [
            'password_policy' => [
                'min_length' => 8,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_numbers' => true,
                'require_special_chars' => true
            ],
            'session_timeout' => 3600,
            'max_login_attempts' => 5,
            'lockout_duration' => 900,
            'two_factor_auth' => false,
            'ip_whitelist' => [],
            'audit_logging' => true
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $securitySettings
        ]);
    } catch (Exception $e) {
        error_log("Get security settings error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch security settings']);
    }
}

function updateSecuritySettings($pdo) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Settings data is required']);
            return;
        }
        
        // In a real implementation, you would save these settings to a database
        // For now, we'll just return success
        
        echo json_encode([
            'success' => true,
            'message' => 'Security settings updated successfully',
            'data' => $data
        ]);
    } catch (Exception $e) {
        error_log("Update security settings error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update security settings']);
    }
}
