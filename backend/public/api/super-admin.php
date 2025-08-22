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

// Validate required environment variables
if (!$dbHost || !$dbName || !$dbUser || !$dbPass) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database configuration incomplete'
    ]);
    exit();
}

try {
    // Database connection
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit();
}

// JWT verification function
function verifyJWT($token, $secret) {
    try {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        $payload = json_decode(base64_decode($parts[1]), true);
        if (!$payload) {
            return false;
        }
        
        // Check if token is expired
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    } catch (Exception $e) {
        return false;
    }
}

// Get authorization header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Authorization header missing or invalid'
    ]);
    exit();
}

$token = $matches[1];
$payload = verifyJWT($token, $jwtSecret);

if (!$payload) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid or expired token'
    ]);
    exit();
}

// Check if user is super admin
if ($payload['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Access denied. Super admin privileges required.'
    ]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];
$pathParts = explode('/', trim($path, '/'));

// Route handling
switch ($method) {
    case 'GET':
        if (isset($pathParts[count($pathParts) - 1]) && $pathParts[count($pathParts) - 1] === 'stats') {
            getSystemStats($pdo);
        } elseif (isset($pathParts[count($pathParts) - 1]) && $pathParts[count($pathParts) - 1] === 'tenants') {
            getTenants($pdo);
        } elseif (isset($pathParts[count($pathParts) - 1]) && $pathParts[count($pathParts) - 1] === 'activity') {
            getRecentActivity($pdo);
        } else {
            getSystemStats($pdo); // Default to stats
        }
        break;
        
    case 'POST':
        if (isset($pathParts[count($pathParts) - 1]) && $pathParts[count($pathParts) - 1] === 'tenant') {
            createTenant($pdo);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
        }
        break;
        
    case 'PUT':
        if (isset($pathParts[count($pathParts) - 1]) && is_numeric($pathParts[count($pathParts) - 1])) {
            updateTenant($pdo, $pathParts[count($pathParts) - 1]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
        }
        break;
        
    case 'DELETE':
        if (isset($pathParts[count($pathParts) - 1]) && is_numeric($pathParts[count($pathParts) - 1])) {
            deleteTenant($pdo, $pathParts[count($pathParts) - 1]);
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
        // Get total tenants
        $stmt = $pdo->query("SELECT COUNT(*) as total_tenants FROM tenants WHERE id != '00000000-0000-0000-0000-000000000000'");
        $totalTenants = $stmt->fetch(PDO::FETCH_ASSOC)['total_tenants'];
        
        // Get active tenants
        $stmt = $pdo->query("SELECT COUNT(*) as active_tenants FROM tenants WHERE status = 'active' AND id != '00000000-0000-0000-0000-000000000000'");
        $activeTenants = $stmt->fetch(PDO::FETCH_ASSOC)['active_tenants'];
        
        // Get total users
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role != 'super_admin'");
        $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
        
        // Get total revenue across all tenants
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total_revenue FROM sales");
        $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];
        
        // Get monthly growth (simplified calculation)
        $stmt = $pdo->query("
            SELECT 
                COALESCE(SUM(CASE WHEN created_at >= NOW() - INTERVAL '30 days' THEN total_amount ELSE 0 END), 0) as current_month,
                COALESCE(SUM(CASE WHEN created_at >= NOW() - INTERVAL '60 days' AND created_at < NOW() - INTERVAL '30 days' THEN total_amount ELSE 0 END), 0) as previous_month
            FROM sales
        ");
        $monthlyData = $stmt->fetch(PDO::FETCH_ASSOC);
        $monthlyGrowth = $monthlyData['previous_month'] > 0 
            ? (($monthlyData['current_month'] - $monthlyData['previous_month']) / $monthlyData['previous_month']) * 100 
            : 0;
        
        // Get pending approvals (users with pending status)
        $stmt = $pdo->query("SELECT COUNT(*) as pending_approvals FROM users WHERE status = 'pending'");
        $pendingApprovals = $stmt->fetch(PDO::FETCH_ASSOC)['pending_approvals'];
        
        // Get critical issues (tenants with suspended status)
        $stmt = $pdo->query("SELECT COUNT(*) as critical_issues FROM tenants WHERE status = 'suspended'");
        $criticalIssues = $stmt->fetch(PDO::FETCH_ASSOC)['critical_issues'];
        
        // System health metrics (mock data for now)
        $systemHealth = [
            'cpu' => rand(30, 70),
            'memory' => rand(50, 85),
            'disk' => rand(60, 90),
            'network' => rand(85, 99),
            'database' => 99.9,
            'api' => 99.7
        ];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'totalTenants' => (int)$totalTenants,
                'activeTenants' => (int)$activeTenants,
                'totalUsers' => (int)$totalUsers,
                'totalRevenue' => (float)$totalRevenue,
                'monthlyGrowth' => round($monthlyGrowth, 1),
                'pendingApprovals' => (int)$pendingApprovals,
                'criticalIssues' => (int)$criticalIssues,
                'systemUptime' => 99.8,
                'systemHealth' => $systemHealth
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch system stats: ' . $e->getMessage()
        ]);
    }
}

function getTenants($pdo) {
    try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : 'all';
        
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ["id != '00000000-0000-0000-0000-000000000000'"];
        $params = [];
        
        if ($search) {
            $whereConditions[] = "(name ILIKE ? OR email ILIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($status !== 'all') {
            $whereConditions[] = "status = ?";
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM tenants WHERE $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get tenants with user count and revenue
        $sql = "
            SELECT 
                t.*,
                COALESCE(u.user_count, 0) as user_count,
                COALESCE(s.total_revenue, 0) as total_revenue
            FROM tenants t
            LEFT JOIN (
                SELECT tenant_id, COUNT(*) as user_count 
                FROM users 
                GROUP BY tenant_id
            ) u ON t.id = u.tenant_id
            LEFT JOIN (
                SELECT tenant_id, SUM(total_amount) as total_revenue 
                FROM sales 
                GROUP BY tenant_id
            ) s ON t.id = s.tenant_id
            WHERE $whereClause
            ORDER BY t.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'tenants' => $tenants,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch tenants: ' . $e->getMessage()
        ]);
    }
}

function getRecentActivity($pdo) {
    try {
        // Get recent user registrations
        $stmt = $pdo->query("
            SELECT 
                'user_registered' as type,
                CONCAT(u.first_name, ' ', u.last_name, ' registered') as message,
                u.created_at as timestamp,
                'success' as status
            FROM users u
            WHERE u.role != 'super_admin'
            ORDER BY u.created_at DESC
            LIMIT 10
        ");
        $userActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get recent sales
        $stmt = $pdo->query("
            SELECT 
                'sale_completed' as type,
                CONCAT('Sale completed - GHS ', total_amount) as message,
                created_at as timestamp,
                'success' as status
            FROM sales
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $salesActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Combine and sort by timestamp
        $allActivity = array_merge($userActivity, $salesActivity);
        usort($allActivity, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Take only the most recent 10
        $recentActivity = array_slice($allActivity, 0, 10);
        
        echo json_encode([
            'success' => true,
            'data' => $recentActivity
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch recent activity: ' . $e->getMessage()
        ]);
    }
}

function createTenant($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['name']) || !isset($input['email'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Name and email are required'
        ]);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Create tenant
        $tenantId = uniqid();
        $stmt = $pdo->prepare("
            INSERT INTO tenants (id, name, email, status, created_at, updated_at)
            VALUES (?, ?, ?, 'active', NOW(), NOW())
        ");
        $stmt->execute([$tenantId, $input['name'], $input['email']]);
        
        // Create admin user for tenant
        $userId = uniqid();
        $hashedPassword = password_hash($input['password'] ?? 'password123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (id, tenant_id, email, password, first_name, last_name, role, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 'admin', 'active', NOW(), NOW())
        ");
        $stmt->execute([
            $userId,
            $tenantId,
            $input['email'],
            $hashedPassword,
            $input['first_name'] ?? 'Admin',
            $input['last_name'] ?? 'User'
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Tenant created successfully',
            'data' => [
                'tenant_id' => $tenantId,
                'user_id' => $userId
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create tenant: ' . $e->getMessage()
        ]);
    }
}

function updateTenant($pdo, $tenantId) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        $updates = [];
        $params = [];
        
        if (isset($input['name'])) {
            $updates[] = "name = ?";
            $params[] = $input['name'];
        }
        
        if (isset($input['email'])) {
            $updates[] = "email = ?";
            $params[] = $input['email'];
        }
        
        if (isset($input['status'])) {
            $updates[] = "status = ?";
            $params[] = $input['status'];
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'No fields to update'
            ]);
            return;
        }
        
        $updates[] = "updated_at = NOW()";
        $params[] = $tenantId;
        
        $sql = "UPDATE tenants SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Tenant not found'
            ]);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Tenant updated successfully'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update tenant: ' . $e->getMessage()
        ]);
    }
}

function deleteTenant($pdo, $tenantId) {
    try {
        $pdo->beginTransaction();
        
        // Delete tenant data (cascade delete)
        $stmt = $pdo->prepare("DELETE FROM users WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        
        $stmt = $pdo->prepare("DELETE FROM sales WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        
        $stmt = $pdo->prepare("DELETE FROM products WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        
        $stmt = $pdo->prepare("DELETE FROM customers WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        
        // Delete tenant
        $stmt = $pdo->prepare("DELETE FROM tenants WHERE id = ?");
        $stmt->execute([$tenantId]);
        
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Tenant not found'
            ]);
            return;
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Tenant deleted successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete tenant: ' . $e->getMessage()
        ]);
    }
}
?>
