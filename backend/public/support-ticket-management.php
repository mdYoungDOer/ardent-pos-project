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
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '5432';
$dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
$dbUser = $_ENV['DB_USERNAME'] ?? '';
$dbPass = $_ENV['DB_PASSWORD'] ?? '';
$jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';

// Function to check authentication
function checkAuth() {
    global $jwtSecret, $dbHost, $dbPort, $dbName, $dbUser, $dbPass;
    
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return null;
    }
    
    $token = $matches[1];
    
    try {
        // Load JWT library
        $autoloaderPaths = [
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/../../vendor/autoload.php',
            '/var/www/html/vendor/autoload.php'
        ];
        
        $autoloaderFound = false;
        foreach ($autoloaderPaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $autoloaderFound = true;
                break;
            }
        }
        
        if (!$autoloaderFound) {
            throw new Exception('JWT library not found');
        }
        
        // Decode token
        $decoded = Firebase\JWT\JWT::decode($token, new Firebase\JWT\Key($jwtSecret, 'HS256'));
        
        // Connect to database
        $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Verify user exists and is active
        $stmt = $pdo->prepare("
            SELECT u.*, t.name as tenant_name 
            FROM users u 
            JOIN tenants t ON u.tenant_id = t.id 
            WHERE u.id = ? AND u.status = 'active' AND t.status = 'active'
        ");
        $stmt->execute([$decoded->user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return null;
        }
        
        return $user;
        
    } catch (Exception $e) {
        error_log("Auth error: " . $e->getMessage());
        return null;
    }
}

try {
    // Check authentication
    $user = checkAuth();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Token not provided or invalid'
        ]);
        exit;
    }
    
    // Connect to database
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Ensure support_tickets table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS support_tickets (
            id SERIAL PRIMARY KEY,
            ticket_number VARCHAR(50) UNIQUE,
            user_id INTEGER,
            tenant_id INTEGER,
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            email VARCHAR(255),
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            priority VARCHAR(20) DEFAULT 'medium',
            category VARCHAR(50) DEFAULT 'general',
            status VARCHAR(20) DEFAULT 'open',
            assigned_to INTEGER,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    // Remove .php extension if present
    $endpoint = str_replace('.php', '', $endpoint);
    
    switch ($method) {
        case 'GET':
            if ($endpoint === 'tickets') {
                handleGetTickets($pdo, $user);
            } elseif ($endpoint === 'stats') {
                handleGetStats($pdo, $user);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            }
            break;
            
        case 'POST':
            if ($endpoint === 'tickets') {
                handleCreateTicket($pdo, $user);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            }
            break;
            
        case 'PUT':
            if ($endpoint === 'tickets') {
                handleUpdateTicket($pdo, $user);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            }
            break;
            
        case 'DELETE':
            if ($endpoint === 'tickets') {
                $ticketId = $_GET['id'] ?? null;
                if ($ticketId) {
                    handleDeleteTicket($pdo, $ticketId, $user);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Ticket ID required']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Support ticket management error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}

function handleGetTickets($pdo, $user) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $priority = isset($_GET['priority']) ? $_GET['priority'] : null;
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause based on user role
    $whereConditions = [];
    $bindParams = [];
    
    if ($user['role'] === 'super_admin') {
        // Super admin can see all tickets
        $whereConditions[] = "1=1";
    } else {
        // Regular users can only see their tenant's tickets
        $whereConditions[] = "t.tenant_id = ?";
        $bindParams[] = $user['tenant_id'];
    }
    
    if ($status) {
        $whereConditions[] = "t.status = ?";
        $bindParams[] = $status;
    }
    
    if ($priority) {
        $whereConditions[] = "t.priority = ?";
        $bindParams[] = $priority;
    }
    
    if ($category) {
        $whereConditions[] = "t.category = ?";
        $bindParams[] = $category;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get tickets with user information
    $sql = "
        SELECT 
            t.*,
            u.first_name as user_first_name,
            u.last_name as user_last_name,
            u.email as user_email,
            tenant.name as tenant_name
        FROM support_tickets t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN tenants tenant ON t.tenant_id = tenant.id
        WHERE $whereClause
        ORDER BY t.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $bindParams[] = $limit;
    $bindParams[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($bindParams);
    $tickets = $stmt->fetchAll();
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM support_tickets t WHERE $whereClause";
    $countBindParams = array_slice($bindParams, 0, -2); // Remove limit and offset
    
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($countBindParams);
    $totalResult = $stmt->fetch();
    $total = $totalResult['total'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'tickets' => $tickets,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]
    ]);
}

function handleCreateTicket($pdo, $user) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }
    
    // Validate required fields
    $requiredFields = ['subject', 'message', 'priority', 'category'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }
    
    // Generate ticket number
    $ticketNumber = 'TKT-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
    
    // Insert ticket
    $stmt = $pdo->prepare("
        INSERT INTO support_tickets (
            ticket_number, user_id, tenant_id, subject, message, 
            priority, category, status, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        RETURNING id
    ");
    
    $stmt->execute([
        $ticketNumber,
        $user['id'],
        $user['tenant_id'],
        $data['subject'],
        $data['message'],
        $data['priority'],
        $data['category'],
        'open'
    ]);
    
    $ticketId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'ticket_id' => $ticketId,
            'ticket_number' => $ticketNumber
        ],
        'message' => 'Support ticket created successfully!'
    ]);
}

function handleUpdateTicket($pdo, $user) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ticket ID required']);
        return;
    }
    
    $ticketId = $data['id'];
    
    // Check if user has access to this ticket
    $accessSql = "SELECT tenant_id FROM support_tickets WHERE id = ?";
    $stmt = $pdo->prepare($accessSql);
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ticket not found']);
        return;
    }
    
    if ($user['role'] !== 'super_admin' && $ticket['tenant_id'] != $user['tenant_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    // Update ticket
    $updateFields = [];
    $bindParams = [];
    
    if (isset($data['status'])) {
        $updateFields[] = "status = ?";
        $bindParams[] = $data['status'];
    }
    
    if (isset($data['priority'])) {
        $updateFields[] = "priority = ?";
        $bindParams[] = $data['priority'];
    }
    
    if (isset($data['category'])) {
        $updateFields[] = "category = ?";
        $bindParams[] = $data['category'];
    }
    
    if (isset($data['assigned_to'])) {
        $updateFields[] = "assigned_to = ?";
        $bindParams[] = $data['assigned_to'];
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        return;
    }
    
    $updateFields[] = "updated_at = NOW()";
    $bindParams[] = $ticketId;
    
    $sql = "UPDATE support_tickets SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($bindParams);
    
    echo json_encode([
        'success' => true,
        'message' => 'Ticket updated successfully!'
    ]);
}

function handleDeleteTicket($pdo, $ticketId, $user) {
    // Check if user has access to this ticket
    $accessSql = "SELECT tenant_id FROM support_tickets WHERE id = ?";
    $stmt = $pdo->prepare($accessSql);
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ticket not found']);
        return;
    }
    
    if ($user['role'] !== 'super_admin' && $ticket['tenant_id'] != $user['tenant_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    // Delete ticket
    $stmt = $pdo->prepare("DELETE FROM support_tickets WHERE id = ?");
    $stmt->execute([$ticketId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Ticket deleted successfully!'
    ]);
}

function handleGetStats($pdo, $user) {
    $whereClause = "1=1";
    $bindParams = [];
    
    if ($user['role'] !== 'super_admin') {
        $whereClause = "tenant_id = ?";
        $bindParams = [$user['tenant_id']];
    }
    
    // Get ticket counts by status
    $statusSql = "
        SELECT 
            status,
            COUNT(*) as count
        FROM support_tickets 
        WHERE $whereClause
        GROUP BY status
    ";
    
    $stmt = $pdo->prepare($statusSql);
    $stmt->execute($bindParams);
    $statusStats = $stmt->fetchAll();
    
    // Get total tickets
    $totalSql = "SELECT COUNT(*) as total FROM support_tickets WHERE $whereClause";
    $stmt = $pdo->prepare($totalSql);
    $stmt->execute($bindParams);
    $totalResult = $stmt->fetch();
    $total = $totalResult['total'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_tickets' => (int)$total,
            'status_breakdown' => $statusStats
        ]
    ]);
}
?>
