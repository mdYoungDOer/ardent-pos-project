<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../vendor/autoload.php';

use ArdentPOS\Core\Config;
use ArdentPOS\Core\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Load environment variables
$jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '5432';
$dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
$dbUser = $_ENV['DB_USERNAME'] ?? '';
$dbPass = $_ENV['DB_PASSWORD'] ?? '';

function getDatabaseConnection() {
    global $dbHost, $dbPort, $dbName, $dbUser, $dbPass;
    
    try {
        $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

function verifyToken($token) {
    global $jwtSecret;
    
    try {
        $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));
        return $decoded;
    } catch (Exception $e) {
        return null;
    }
}

function sendResponse($data) {
    echo json_encode(['success' => true, 'data' => $data]);
}

function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
}

// Get authorization header
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = null;

if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $token = $matches[1];
}

if (!$token) {
    sendError('No token provided', 401);
    exit;
}

// Verify token
$decoded = verifyToken($token);
if (!$decoded) {
    sendError('Invalid token', 401);
    exit;
}

// Get database connection
$pdo = getDatabaseConnection();
if (!$pdo) {
    sendError('Database connection failed', 500);
    exit;
}

// Get user and tenant info
$stmt = $pdo->prepare("
    SELECT u.*, t.name as tenant_name, t.status as tenant_status
    FROM users u 
    JOIN tenants t ON u.tenant_id = t.id 
    WHERE u.id = ? AND u.status = 'active'
");
$stmt->execute([$decoded->user_id]);
$user = $stmt->fetch();

if (!$user) {
    sendError('User not found', 401);
    exit;
}

if ($user['tenant_status'] !== 'active') {
    sendError('Account is inactive', 401);
    exit;
}

// Check if user has admin role
if (!in_array($user['role'], ['admin', 'super_admin'])) {
    sendError('Insufficient permissions', 403);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$tenantId = $user['tenant_id'];

switch ($method) {
    case 'GET':
        // Get users list
        try {
            $search = $_GET['search'] ?? '';
            $role = $_GET['role'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $query = "
                SELECT 
                    id, first_name, last_name, email, role, status, 
                    last_login, created_at
                FROM users 
                WHERE tenant_id = ?
            ";
            
            $params = [$tenantId];
            
            if (!empty($search)) {
                $query .= " AND (first_name ILIKE ? OR last_name ILIKE ? OR email ILIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if (!empty($role)) {
                $query .= " AND role = ?";
                $params[] = $role;
            }
            
            if (!empty($status)) {
                $query .= " AND status = ?";
                $params[] = $status;
            }
            
            $query .= " ORDER BY first_name ASC";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $users = $stmt->fetchAll();
            
            sendResponse($users);
        } catch (Exception $e) {
            sendError('Error fetching users: ' . $e->getMessage());
        }
        break;
        
    case 'POST':
        // Create new user
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate input
            if (empty($input['first_name']) || empty($input['last_name']) || empty($input['email']) || empty($input['password'])) {
                sendError('Missing required fields');
                exit;
            }
            
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                sendError('Invalid email format');
                exit;
            }
            
            if (strlen($input['password']) < 8) {
                sendError('Password must be at least 8 characters');
                exit;
            }
            
            // Check for duplicate email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND tenant_id = ?");
            $stmt->execute([$input['email'], $tenantId]);
            if ($stmt->fetch()) {
                sendError('User with this email already exists');
                exit;
            }
            
            // Insert new user
            $stmt = $pdo->prepare("
                INSERT INTO users (tenant_id, first_name, last_name, email, password_hash, role, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $tenantId,
                $input['first_name'],
                $input['last_name'],
                $input['email'],
                password_hash($input['password'], PASSWORD_DEFAULT),
                $input['role'] ?? 'cashier',
                $input['status'] ?? 'active'
            ]);
            
            $userId = $pdo->lastInsertId();
            
            sendResponse([
                'message' => 'User created successfully',
                'id' => $userId
            ]);
        } catch (Exception $e) {
            sendError('Error creating user: ' . $e->getMessage());
        }
        break;
        
    case 'PUT':
        // Update user
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = $_GET['id'] ?? null;
            
            if (!$userId) {
                sendError('User ID is required');
                exit;
            }
            
            // Check if user exists and belongs to tenant
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$userId, $tenantId]);
            if (!$stmt->fetch()) {
                sendError('User not found', 404);
                exit;
            }
            
            // Build update query
            $updateFields = [];
            $params = [];
            
            if (!empty($input['first_name'])) {
                $updateFields[] = "first_name = ?";
                $params[] = $input['first_name'];
            }
            
            if (!empty($input['last_name'])) {
                $updateFields[] = "last_name = ?";
                $params[] = $input['last_name'];
            }
            
            if (!empty($input['email'])) {
                // Check for duplicate email
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND tenant_id = ? AND id != ?");
                $stmt->execute([$input['email'], $tenantId, $userId]);
                if ($stmt->fetch()) {
                    sendError('User with this email already exists');
                    exit;
                }
                
                $updateFields[] = "email = ?";
                $params[] = $input['email'];
            }
            
            if (!empty($input['password'])) {
                if (strlen($input['password']) < 8) {
                    sendError('Password must be at least 8 characters');
                    exit;
                }
                $updateFields[] = "password_hash = ?";
                $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
            }
            
            if (isset($input['role'])) {
                $updateFields[] = "role = ?";
                $params[] = $input['role'];
            }
            
            if (isset($input['status'])) {
                $updateFields[] = "status = ?";
                $params[] = $input['status'];
            }
            
            if (empty($updateFields)) {
                sendError('No fields to update');
                exit;
            }
            
            $params[] = $userId;
            $params[] = $tenantId;
            
            $query = "UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ? AND tenant_id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            sendResponse(['message' => 'User updated successfully']);
        } catch (Exception $e) {
            sendError('Error updating user: ' . $e->getMessage());
        }
        break;
        
    case 'DELETE':
        // Delete user
        try {
            $userId = $_GET['id'] ?? null;
            
            if (!$userId) {
                sendError('User ID is required');
                exit;
            }
            
            // Prevent deleting own account
            if ($userId == $decoded->user_id) {
                sendError('Cannot delete your own account');
                exit;
            }
            
            // Check if user exists and belongs to tenant
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$userId, $tenantId]);
            if (!$stmt->fetch()) {
                sendError('User not found', 404);
                exit;
            }
            
            // Check if user has sales records
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM sales WHERE cashier_id = ?");
            $stmt->execute([$userId]);
            $hasSales = $stmt->fetch()['count'] > 0;
            
            if ($hasSales) {
                // Deactivate instead of delete
                $stmt = $pdo->prepare("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$userId, $tenantId]);
                sendResponse(['message' => 'User deactivated successfully (has sales history)']);
            } else {
                // Delete user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$userId, $tenantId]);
                sendResponse(['message' => 'User deleted successfully']);
            }
        } catch (Exception $e) {
            sendError('Error deleting user: ' . $e->getMessage());
        }
        break;
        
    default:
        sendError('Method not allowed', 405);
        break;
}
?>
