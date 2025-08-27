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

function getClientIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Handle contact form submission (public endpoint)
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (empty($input['first_name']) || empty($input['last_name']) || empty($input['email']) || empty($input['subject']) || empty($input['message'])) {
                sendError('Missing required fields');
                exit;
            }
            
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                sendError('Invalid email format');
                exit;
            }
            
            // Get database connection
            $pdo = getDatabaseConnection();
            if (!$pdo) {
                sendError('Database connection failed', 500);
                exit;
            }
            
            // Insert submission
            $stmt = $pdo->prepare("
                INSERT INTO contact_submissions (
                    first_name, last_name, email, phone, company, subject, message, 
                    status, ip_address, user_agent, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $input['first_name'],
                $input['last_name'],
                $input['email'],
                $input['phone'] ?? null,
                $input['company'] ?? null,
                $input['subject'],
                $input['message'],
                'new',
                getClientIP(),
                getUserAgent()
            ]);
            
            $submissionId = $pdo->lastInsertId();
            
            // Send email notification (optional)
            // TODO: Implement email notification to admin
            
            sendResponse([
                'message' => 'Contact form submitted successfully',
                'id' => $submissionId
            ]);
            
        } catch (Exception $e) {
            error_log("Contact submission error: " . $e->getMessage());
            sendError('Error submitting contact form');
        }
        break;
        
    case 'GET':
        // Get contact submissions (requires authentication)
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

        // Get user and check if super admin
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

        if ($user['role'] !== 'super_admin') {
            sendError('Insufficient permissions', 403);
            exit;
        }

        try {
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT * FROM contact_submissions WHERE 1=1";
            $params = [];
            
            if (!empty($search)) {
                $query .= " AND (first_name ILIKE ? OR last_name ILIKE ? OR email ILIKE ? OR subject ILIKE ? OR company ILIKE ?)";
                $searchTerm = "%$search%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
            
            if (!empty($status)) {
                $query .= " AND status = ?";
                $params[] = $status;
            }
            
            $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $submissions = $stmt->fetchAll();
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM contact_submissions WHERE 1=1";
            $countParams = [];
            
            if (!empty($search)) {
                $countQuery .= " AND (first_name ILIKE ? OR last_name ILIKE ? OR email ILIKE ? OR subject ILIKE ? OR company ILIKE ?)";
                $searchTerm = "%$search%";
                $countParams = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
            }
            
            if (!empty($status)) {
                $countQuery .= " AND status = ?";
                $countParams[] = $status;
            }
            
            $stmt = $pdo->prepare($countQuery);
            $stmt->execute($countParams);
            $total = $stmt->fetch()['total'];
            
            sendResponse([
                'submissions' => $submissions,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error fetching contact submissions: " . $e->getMessage());
            sendError('Error fetching contact submissions');
        }
        break;
        
    case 'PUT':
        // Update contact submission status (requires authentication)
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

        // Get user and check if super admin
        $stmt = $pdo->prepare("
            SELECT u.*, t.name as tenant_name, t.status as tenant_status
            FROM users u 
            JOIN tenants t ON u.tenant_id = t.id 
            WHERE u.id = ? AND u.status = 'active'
        ");
        $stmt->execute([$decoded->user_id]);
        $user = $stmt->fetch();

        if (!$user || $user['role'] !== 'super_admin') {
            sendError('Insufficient permissions', 403);
            exit;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $submissionId = $_GET['id'] ?? null;
            
            if (!$submissionId) {
                sendError('Submission ID is required');
                exit;
            }
            
            // Check if submission exists
            $stmt = $pdo->prepare("SELECT id FROM contact_submissions WHERE id = ?");
            $stmt->execute([$submissionId]);
            if (!$stmt->fetch()) {
                sendError('Submission not found', 404);
                exit;
            }
            
            // Update submission
            $updateFields = [];
            $params = [];
            
            if (isset($input['status'])) {
                $updateFields[] = "status = ?";
                $params[] = $input['status'];
            }
            
            if (!empty($updateFields)) {
                $updateFields[] = "updated_at = NOW()";
                $params[] = $submissionId;
                
                $query = "UPDATE contact_submissions SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                
                sendResponse(['message' => 'Submission updated successfully']);
            } else {
                sendError('No fields to update');
            }
            
        } catch (Exception $e) {
            error_log("Error updating contact submission: " . $e->getMessage());
            sendError('Error updating submission');
        }
        break;
        
    case 'DELETE':
        // Delete contact submission (requires authentication)
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

        // Get user and check if super admin
        $stmt = $pdo->prepare("
            SELECT u.*, t.name as tenant_name, t.status as tenant_status
            FROM users u 
            JOIN tenants t ON u.tenant_id = t.id 
            WHERE u.id = ? AND u.status = 'active'
        ");
        $stmt->execute([$decoded->user_id]);
        $user = $stmt->fetch();

        if (!$user || $user['role'] !== 'super_admin') {
            sendError('Insufficient permissions', 403);
            exit;
        }

        try {
            $submissionId = $_GET['id'] ?? null;
            
            if (!$submissionId) {
                sendError('Submission ID is required');
                exit;
            }
            
            // Check if submission exists
            $stmt = $pdo->prepare("SELECT id FROM contact_submissions WHERE id = ?");
            $stmt->execute([$submissionId]);
            if (!$stmt->fetch()) {
                sendError('Submission not found', 404);
                exit;
            }
            
            // Delete submission
            $stmt = $pdo->prepare("DELETE FROM contact_submissions WHERE id = ?");
            $stmt->execute([$submissionId]);
            
            sendResponse(['message' => 'Submission deleted successfully']);
            
        } catch (Exception $e) {
            error_log("Error deleting contact submission: " . $e->getMessage());
            sendError('Error deleting submission');
        }
        break;
        
    default:
        sendError('Method not allowed', 405);
        break;
}
?>
