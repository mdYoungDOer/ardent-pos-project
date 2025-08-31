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

// Database configuration
$host = $_ENV['DB_HOST'] ?? '';
$port = $_ENV['DB_PORT'] ?? '25060';
$dbname = $_ENV['DB_NAME'] ?? '';
$user = $_ENV['DB_USER'] ?? $_ENV['DB_USERNAME'] ?? '';
$password = $_ENV['DB_PASS'] ?? $_ENV['DB_PASSWORD'] ?? '';
$jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';

if (empty($host) || empty($dbname) || empty($user) || empty($password)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database configuration incomplete']);
    exit;
}

try {
    // Create PDO connection
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Initialize unified authentication
    $auth = new UnifiedAuth($pdo, $jwtSecret);
    
    // Check authentication
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token not provided']);
        exit;
    }
    
    $token = $matches[1];
    $authResult = $auth->verifyToken($token);
    
    if (!$authResult['success']) {
        http_response_code(401);
        echo json_encode($authResult);
        exit;
    }
    
    $currentUser = $authResult['user'];
    $currentTenant = $authResult['tenant'];
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    // Remove .php extension if present
    $endpoint = str_replace('.php', '', $endpoint);
    
    switch ($method) {
        case 'GET':
            if ($endpoint === 'tickets') {
                // Get query parameters
                $page = max(1, intval($_GET['page'] ?? 1));
                $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
                $offset = ($page - 1) * $limit;
                $status = $_GET['status'] ?? null;
                $priority = $_GET['priority'] ?? null;
                $category = $_GET['category'] ?? null;
                
                // Build WHERE clause
                $whereConditions = ['1=1'];
                $params = [];
                
                // Filter by tenant (except for super admin)
                if ($currentUser['role'] !== 'super_admin') {
                    $whereConditions[] = 'st.tenant_id = ?';
                    $params[] = $currentTenant['id'];
                }
                
                if ($status) {
                    $whereConditions[] = 'st.status = ?';
                    $params[] = $status;
                }
                
                if ($priority) {
                    $whereConditions[] = 'st.priority = ?';
                    $params[] = $priority;
                }
                
                if ($category) {
                    $whereConditions[] = 'st.category = ?';
                    $params[] = $category;
                }
                
                $whereClause = implode(' AND ', $whereConditions);
                
                // Get total count
                $countSql = "SELECT COUNT(*) FROM support_tickets st WHERE $whereClause";
                $stmt = $pdo->prepare($countSql);
                $stmt->execute($params);
                $total = $stmt->fetchColumn();
                
                // Get tickets
                $sql = "
                    SELECT 
                        st.*,
                        u.first_name as user_first_name,
                        u.last_name as user_last_name,
                        u.email as user_email,
                        t.name as tenant_name
                    FROM support_tickets st
                    LEFT JOIN users u ON st.user_id = u.id
                    LEFT JOIN tenants t ON st.tenant_id = t.id
                    WHERE $whereClause
                    ORDER BY st.created_at DESC
                    LIMIT ? OFFSET ?
                ";
                
                $params[] = $limit;
                $params[] = $offset;
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $tickets = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'tickets' => $tickets,
                        'pagination' => [
                            'page' => $page,
                            'limit' => $limit,
                            'total' => $total,
                            'pages' => ceil($total / $limit)
                        ]
                    ]
                ]);
                
            } elseif ($endpoint === 'stats') {
                // Build WHERE clause for stats
                $whereConditions = ['1=1'];
                $params = [];
                
                // Filter by tenant (except for super admin)
                if ($currentUser['role'] !== 'super_admin') {
                    $whereConditions[] = 'tenant_id = ?';
                    $params[] = $currentTenant['id'];
                }
                
                $whereClause = implode(' AND ', $whereConditions);
                
                // Get total tickets
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM support_tickets WHERE $whereClause");
                $stmt->execute($params);
                $totalTickets = $stmt->fetchColumn();
                
                // Get status breakdown
                $stmt = $pdo->prepare("
                    SELECT status, COUNT(*) as count 
                    FROM support_tickets 
                    WHERE $whereClause 
                    GROUP BY status
                ");
                $stmt->execute($params);
                $statusBreakdown = $stmt->fetchAll();
                
                // Get priority breakdown
                $stmt = $pdo->prepare("
                    SELECT priority, COUNT(*) as count 
                    FROM support_tickets 
                    WHERE $whereClause 
                    GROUP BY priority
                ");
                $stmt->execute($params);
                $priorityBreakdown = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'total_tickets' => $totalTickets,
                        'status_breakdown' => $statusBreakdown,
                        'priority_breakdown' => $priorityBreakdown
                    ]
                ]);
                
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            }
            break;
            
        case 'POST':
            if ($endpoint === 'tickets') {
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                if (!$data) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
                    exit;
                }
                
                // Validate required fields
                $requiredFields = ['subject', 'message', 'priority', 'category'];
                foreach ($requiredFields as $field) {
                    if (empty($data[$field])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                        exit;
                    }
                }
                
                // Generate ticket number
                $ticketNumber = 'TKT-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
                
                // Insert ticket
                $stmt = $pdo->prepare("
                    INSERT INTO support_tickets (
                        ticket_number, user_id, tenant_id, subject, message, 
                        category, priority, status, ip_address, user_agent
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'open', ?, ?)
                    RETURNING id
                ");
                
                $stmt->execute([
                    $ticketNumber,
                    $currentUser['id'],
                    $currentTenant['id'],
                    $data['subject'],
                    $data['message'],
                    $data['category'],
                    $data['priority'],
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                $ticketId = $stmt->fetchColumn();
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'ticket_id' => $ticketId,
                        'ticket_number' => $ticketNumber
                    ],
                    'message' => 'Support ticket created successfully!'
                ]);
                
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            }
            break;
            
        case 'PUT':
            if ($endpoint === 'tickets') {
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                if (!$data || empty($data['id'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Ticket ID required']);
                    exit;
                }
                
                $ticketId = $data['id'];
                
                // Check if user can update this ticket
                $whereConditions = ['st.id = ?'];
                $params = [$ticketId];
                
                if ($currentUser['role'] !== 'super_admin') {
                    $whereConditions[] = 'st.tenant_id = ?';
                    $params[] = $currentTenant['id'];
                }
                
                $whereClause = implode(' AND ', $whereConditions);
                
                $stmt = $pdo->prepare("SELECT id FROM support_tickets st WHERE $whereClause");
                $stmt->execute($params);
                
                if (!$stmt->fetch()) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Ticket not found']);
                    exit;
                }
                
                // Build update query
                $updateFields = [];
                $updateParams = [];
                
                $allowedFields = ['subject', 'message', 'priority', 'category', 'status'];
                foreach ($allowedFields as $field) {
                    if (isset($data[$field])) {
                        $updateFields[] = "$field = ?";
                        $updateParams[] = $data[$field];
                    }
                }
                
                if (empty($updateFields)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'No fields to update']);
                    exit;
                }
                
                $updateParams[] = $ticketId;
                
                $sql = "UPDATE support_tickets SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($updateParams);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Ticket updated successfully!'
                ]);
                
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            }
            break;
            
        case 'DELETE':
            if ($endpoint === 'tickets') {
                $ticketId = $_GET['id'] ?? null;
                if (!$ticketId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Ticket ID required']);
                    exit;
                }
                
                // Check if user can delete this ticket
                $whereConditions = ['st.id = ?'];
                $params = [$ticketId];
                
                if ($currentUser['role'] !== 'super_admin') {
                    $whereConditions[] = 'st.tenant_id = ?';
                    $params[] = $currentTenant['id'];
                }
                
                $whereClause = implode(' AND ', $whereConditions);
                
                $stmt = $pdo->prepare("SELECT id FROM support_tickets st WHERE $whereClause");
                $stmt->execute($params);
                
                if (!$stmt->fetch()) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Ticket not found']);
                    exit;
                }
                
                // Delete ticket
                $stmt = $pdo->prepare("DELETE FROM support_tickets WHERE id = ?");
                $stmt->execute([$ticketId]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Ticket deleted successfully!'
                ]);
                
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
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>
