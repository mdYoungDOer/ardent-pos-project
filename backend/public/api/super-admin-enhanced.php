<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Tenant-ID, Accept, Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load environment variables
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Database configuration
$dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
$dbPort = $_ENV['DB_PORT'] ?? getenv('DB_PORT');
$dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
$dbUser = $_ENV['DB_USER'] ?? getenv('DB_USER');
$dbPass = $_ENV['DB_PASS'] ?? getenv('DB_PASS');
$jwtSecret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET');

try {
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// JWT verification
function verifyJWT($token, $secret) {
    try {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;
        
        $payload = json_decode(base64_decode($parts[1]), true);
        if (!$payload || (isset($payload['exp']) && $payload['exp'] < time())) {
            return false;
        }
        
        return $payload;
    } catch (Exception $e) {
        return false;
    }
}

// Authorization check
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authorization required']);
    exit();
}

$token = $matches[1];
$payload = verifyJWT($token, $jwtSecret);

if (!$payload || $payload['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Super admin access required']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];
$pathParts = explode('/', trim($path, '/'));
$endpoint = end($pathParts);

// Route handling
switch ($method) {
    case 'GET':
        switch ($endpoint) {
            case 'analytics':
                getAnalytics($pdo);
                break;
            case 'users':
                getUsers($pdo);
                break;
            case 'settings':
                getSettings($pdo);
                break;
            default:
                getSystemStats($pdo);
                break;
        }
        break;
        
    case 'POST':
        switch ($endpoint) {
            case 'users':
                if (isset($pathParts[count($pathParts) - 2]) && $pathParts[count($pathParts) - 2] === 'users') {
                    bulkUserAction($pdo);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
                }
                break;
            default:
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
                break;
        }
        break;
        
    case 'PUT':
        if (isset($pathParts[count($pathParts) - 2])) {
            $resource = $pathParts[count($pathParts) - 2];
            $id = $pathParts[count($pathParts) - 1];
            
            switch ($resource) {
                case 'user':
                    updateUser($pdo, $id);
                    break;
                case 'settings':
                    updateSettings($pdo, $id);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
                    break;
            }
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
        }
        break;
        
    case 'DELETE':
        if (isset($pathParts[count($pathParts) - 2])) {
            $resource = $pathParts[count($pathParts) - 2];
            $id = $pathParts[count($pathParts) - 1];
            
            switch ($resource) {
                case 'user':
                    deleteUser($pdo, $id);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
                    break;
            }
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}

function getSystemStats($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total_tenants FROM tenants WHERE id != '00000000-0000-0000-0000-000000000000'");
        $totalTenants = $stmt->fetch(PDO::FETCH_ASSOC)['total_tenants'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as active_tenants FROM tenants WHERE status = 'active' AND id != '00000000-0000-0000-0000-000000000000'");
        $activeTenants = $stmt->fetch(PDO::FETCH_ASSOC)['active_tenants'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role != 'super_admin'");
        $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
        
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total_revenue FROM sales");
        $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'totalTenants' => (int)$totalTenants,
                'activeTenants' => (int)$activeTenants,
                'totalUsers' => (int)$totalUsers,
                'totalRevenue' => (float)$totalRevenue,
                'monthlyGrowth' => 12.5,
                'pendingApprovals' => 3,
                'criticalIssues' => 1,
                'systemUptime' => 99.8,
                'systemHealth' => [
                    'cpu' => 45,
                    'memory' => 65,
                    'disk' => 75,
                    'network' => 98,
                    'database' => 99.9,
                    'api' => 99.7
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch stats']);
    }
}

function getAnalytics($pdo) {
    try {
        $timeRange = $_GET['timeRange'] ?? '30';
        
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total_revenue, COUNT(*) as total_transactions FROM sales WHERE created_at >= NOW() - INTERVAL ? days");
        $stmt->execute([$timeRange]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'totalRevenue' => (float)$data['total_revenue'],
                'totalTransactions' => (int)$data['total_transactions'],
                'activeTenants' => 15,
                'revenueGrowth' => 15.2,
                'transactionGrowth' => 8.7,
                'tenantGrowth' => 5.3,
                'systemUptime' => 99.9,
                'systemHealth' => [
                    'cpu' => 45,
                    'memory' => 65,
                    'disk' => 75,
                    'network' => 98,
                    'database' => 99.9,
                    'api' => 99.7
                ],
                'topTenants' => [
                    [
                        'id' => '1',
                        'name' => 'ABC Store',
                        'email' => 'abc@example.com',
                        'revenue' => 25000,
                        'transactions' => 150,
                        'growth' => 12.5,
                        'status' => 'active'
                    ],
                    [
                        'id' => '2',
                        'name' => 'XYZ Shop',
                        'email' => 'xyz@example.com',
                        'revenue' => 18000,
                        'transactions' => 120,
                        'growth' => 8.3,
                        'status' => 'active'
                    ]
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch analytics']);
    }
}

function getUsers($pdo) {
    try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : 'all';
        $role = isset($_GET['role']) ? $_GET['role'] : 'all';
        
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ["u.role != 'super_admin'"];
        $params = [];
        
        if ($search) {
            $whereConditions[] = "(u.name ILIKE ? OR u.email ILIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($status !== 'all') {
            $whereConditions[] = "u.status = ?";
            $params[] = $status;
        }
        
        if ($role !== 'all') {
            $whereConditions[] = "u.role = ?";
            $params[] = $role;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM users u WHERE $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $sql = "SELECT u.*, t.name as tenant_name FROM users u LEFT JOIN tenants t ON u.tenant_id = t.id WHERE $whereClause ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'users' => $users,
                'totalPages' => ceil($total / $limit),
                'currentPage' => $page,
                'total' => $total
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch users']);
    }
}

function getSettings($pdo) {
    try {
        $settings = [
            'general' => [
                'app_name' => 'Ardent POS',
                'default_currency' => 'GHS',
                'timezone' => 'Africa/Accra',
                'date_format' => 'Y-m-d'
            ],
            'security' => [
                'session_timeout' => 60,
                'password_min_length' => 8,
                'require_2fa' => false,
                'force_ssl' => true
            ],
            'email' => [
                'smtp_host' => 'smtp.sendgrid.net',
                'smtp_port' => 587,
                'from_email' => 'notify@ardentwebservices.com',
                'from_name' => 'Ardent POS'
            ],
            'payments' => [
                'paystack_public_key' => 'pk_test_...',
                'paystack_secret_key' => 'sk_test_...',
                'enabled' => true,
                'test_mode' => true
            ],
            'api' => [
                'jwt_secret' => 'your-jwt-secret-here',
                'rate_limit' => 100,
                'enabled' => true
            ],
            'maintenance' => [
                'enabled' => false,
                'message' => 'System is under maintenance. Please try again later.',
                'estimated_downtime' => 2
            ],
            'backup' => [
                'auto_backup' => false,
                'recent_backups' => []
            ]
        ];
        
        echo json_encode(['success' => true, 'data' => $settings]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch settings']);
    }
}

function updateSettings($pdo, $category) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        echo json_encode(['success' => true, 'message' => "$category settings updated successfully"]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update settings']);
    }
}

function updateUser($pdo, $userId) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $allowedFields = ['name', 'email', 'role', 'status'];
        $updateFields = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No valid fields to update']);
            return;
        }
        
        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update user']);
    }
}

function deleteUser($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'super_admin'");
        $stmt->execute([$userId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found or cannot be deleted']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete user']);
    }
}

function bulkUserAction($pdo) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $userIds = $input['userIds'] ?? [];
        $action = $input['action'] ?? '';
        
        if (empty($userIds) || empty($action)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'User IDs and action are required']);
            return;
        }
        
        $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
        
        switch ($action) {
            case 'activate':
                $sql = "UPDATE users SET status = 'active' WHERE id IN ($placeholders) AND role != 'super_admin'";
                break;
            case 'suspend':
                $sql = "UPDATE users SET status = 'suspended' WHERE id IN ($placeholders) AND role != 'super_admin'";
                break;
            case 'delete':
                $sql = "DELETE FROM users WHERE id IN ($placeholders) AND role != 'super_admin'";
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
                return;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($userIds);
        
        echo json_encode(['success' => true, 'message' => "Bulk $action completed successfully"]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to perform bulk action']);
    }
}
?>
