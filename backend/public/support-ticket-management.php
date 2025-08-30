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

// Database configuration
$dbConfig = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '5432',
    'database' => $_ENV['DB_NAME'] ?? 'defaultdb',
    'username' => $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? 'postgres',
    'password' => $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? 'password',
];

// Simple authentication check (you may want to enhance this)
function checkSuperAdminAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    
    // For now, we'll accept any Bearer token - in production, validate the JWT
    return true;
}

try {
    // Create database connection
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['database']
    );
    
    $pdo = new PDO(
        $dsn,
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Check authentication
    checkSuperAdminAuth();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    // Handle different endpoints
    switch ($method) {
        case 'GET':
            handleGetRequest($pdo, $endpoint, $_GET);
            break;
        case 'POST':
            handlePostRequest($pdo, $endpoint, $_POST, file_get_contents('php://input'));
            break;
        case 'PUT':
            handlePutRequest($pdo, $endpoint, file_get_contents('php://input'));
            break;
        case 'DELETE':
            handleDeleteRequest($pdo, $endpoint, $_GET);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Support Ticket Management Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'debug' => $e->getMessage()
    ]);
}

function handleGetRequest($pdo, $endpoint, $params) {
    switch ($endpoint) {
        case 'tickets':
            getTickets($pdo, $params);
            break;
        case 'ticket':
            getTicket($pdo, $params);
            break;
        case 'stats':
            getTicketStats($pdo);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handlePostRequest($pdo, $endpoint, $postData, $rawData) {
    $data = json_decode($rawData, true) ?: $postData;
    
    switch ($endpoint) {
        case 'tickets':
            createTicket($pdo, $data);
            break;
        case 'reply':
            addReply($pdo, $data);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handlePutRequest($pdo, $endpoint, $rawData) {
    $data = json_decode($rawData, true);
    
    switch ($endpoint) {
        case 'tickets':
            updateTicket($pdo, $data);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handleDeleteRequest($pdo, $endpoint, $params) {
    switch ($endpoint) {
        case 'tickets':
            deleteTicket($pdo, $params);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

// Ticket Management Functions
function getTickets($pdo, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        $offset = ($page - 1) * $limit;
        $status = $params['status'] ?? null;
        $priority = $params['priority'] ?? null;
        $search = $params['search'] ?? null;
        $tenantId = $params['tenant_id'] ?? null;
        
        $whereConditions = [];
        $bindParams = [];
        
        if ($status) {
            $whereConditions[] = "st.status = :status";
            $bindParams['status'] = $status;
        }
        
        if ($priority) {
            $whereConditions[] = "st.priority = :priority";
            $bindParams['priority'] = $priority;
        }
        
        if ($tenantId) {
            $whereConditions[] = "st.tenant_id = :tenant_id";
            $bindParams['tenant_id'] = $tenantId;
        }
        
        if ($search) {
            $whereConditions[] = "(st.subject ILIKE :search OR st.description ILIKE :search OR u.name ILIKE :search)";
            $bindParams['search'] = "%$search%";
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "SELECT st.*, u.name as user_name, u.email as user_email, t.name as tenant_name
                FROM support_tickets st 
                LEFT JOIN users u ON st.user_id = u.id 
                LEFT JOIN tenants t ON st.tenant_id = t.id 
                $whereClause 
                ORDER BY st.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        foreach ($bindParams as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $tickets = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM support_tickets st 
                     LEFT JOIN users u ON st.user_id = u.id 
                     LEFT JOIN tenants t ON st.tenant_id = t.id 
                     $whereClause";
        $countStmt = $pdo->prepare($countSql);
        foreach ($bindParams as $key => $value) {
            $countStmt->bindValue(":$key", $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];
        
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
    } catch (Exception $e) {
        error_log("Get tickets error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch tickets']);
    }
}

function getTicket($pdo, $params) {
    try {
        $id = $params['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ticket ID required']);
            return;
        }
        
        // Get ticket details
        $sql = "SELECT st.*, u.name as user_name, u.email as user_email, t.name as tenant_name
                FROM support_tickets st 
                LEFT JOIN users u ON st.user_id = u.id 
                LEFT JOIN tenants t ON st.tenant_id = t.id 
                WHERE st.id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $ticket = $stmt->fetch();
        
        if (!$ticket) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Ticket not found']);
            return;
        }
        
        // Get ticket replies
        $repliesSql = "SELECT sr.*, u.name as user_name, u.email as user_email
                       FROM support_replies sr 
                       LEFT JOIN users u ON sr.user_id = u.id 
                       WHERE sr.ticket_id = :ticket_id 
                       ORDER BY sr.created_at ASC";
        $repliesStmt = $pdo->prepare($repliesSql);
        $repliesStmt->execute(['ticket_id' => $id]);
        $replies = $repliesStmt->fetchAll();
        
        $ticket['replies'] = $replies;
        
        echo json_encode(['success' => true, 'data' => $ticket]);
    } catch (Exception $e) {
        error_log("Get ticket error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch ticket']);
    }
}

function getTicketStats($pdo) {
    try {
        $stats = [
            'total_tickets' => 0,
            'open_tickets' => 0,
            'closed_tickets' => 0,
            'pending_tickets' => 0,
            'high_priority' => 0,
            'medium_priority' => 0,
            'low_priority' => 0,
            'recent_tickets' => 0
        ];
        
        // Get basic counts
        $sql = "SELECT 
                COUNT(*) as total_tickets,
                COUNT(CASE WHEN status = 'open' THEN 1 END) as open_tickets,
                COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_tickets,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_tickets,
                COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority,
                COUNT(CASE WHEN priority = 'medium' THEN 1 END) as medium_priority,
                COUNT(CASE WHEN priority = 'low' THEN 1 END) as low_priority,
                COUNT(CASE WHEN created_at >= NOW() - INTERVAL '7 days' THEN 1 END) as recent_tickets
                FROM support_tickets";
        
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch();
        
        $stats = array_merge($stats, $result);
        
        echo json_encode(['success' => true, 'data' => $stats]);
    } catch (Exception $e) {
        error_log("Get ticket stats error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch ticket stats']);
    }
}

function createTicket($pdo, $data) {
    try {
        $requiredFields = ['subject', 'description', 'priority'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
                return;
            }
        }
        
        $sql = "INSERT INTO support_tickets (user_id, tenant_id, subject, description, priority, status, created_at, updated_at) 
                VALUES (:user_id, :tenant_id, :subject, :description, :priority, :status, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'] ?? null,
            'tenant_id' => $data['tenant_id'] ?? null,
            'subject' => $data['subject'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'status' => $data['status'] ?? 'open'
        ]);
        
        $ticketId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Ticket created successfully',
            'data' => ['id' => $ticketId]
        ]);
    } catch (Exception $e) {
        error_log("Create ticket error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create ticket']);
    }
}

function updateTicket($pdo, $data) {
    try {
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ticket ID required']);
            return;
        }
        
        $sql = "UPDATE support_tickets SET 
                subject = :subject, 
                description = :description, 
                priority = :priority, 
                status = :status, 
                assigned_to = :assigned_to, 
                updated_at = NOW() 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $data['id'],
            'subject' => $data['subject'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'status' => $data['status'],
            'assigned_to' => $data['assigned_to'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Ticket updated successfully'
        ]);
    } catch (Exception $e) {
        error_log("Update ticket error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update ticket']);
    }
}

function deleteTicket($pdo, $params) {
    try {
        $id = $params['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ticket ID required']);
            return;
        }
        
        // Delete ticket replies first
        $deleteRepliesSql = "DELETE FROM support_replies WHERE ticket_id = :id";
        $deleteRepliesStmt = $pdo->prepare($deleteRepliesSql);
        $deleteRepliesStmt->execute(['id' => $id]);
        
        // Delete the ticket
        $sql = "DELETE FROM support_tickets WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Ticket deleted successfully'
        ]);
    } catch (Exception $e) {
        error_log("Delete ticket error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete ticket']);
    }
}

function addReply($pdo, $data) {
    try {
        $requiredFields = ['ticket_id', 'message'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
                return;
            }
        }
        
        $sql = "INSERT INTO support_replies (ticket_id, user_id, message, created_at) 
                VALUES (:ticket_id, :user_id, :message, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'ticket_id' => $data['ticket_id'],
            'user_id' => $data['user_id'] ?? null,
            'message' => $data['message']
        ]);
        
        $replyId = $pdo->lastInsertId();
        
        // Update ticket status to 'pending' if it was 'open'
        $updateTicketSql = "UPDATE support_tickets SET status = 'pending', updated_at = NOW() 
                           WHERE id = :ticket_id AND status = 'open'";
        $updateTicketStmt = $pdo->prepare($updateTicketSql);
        $updateTicketStmt->execute(['ticket_id' => $data['ticket_id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Reply added successfully',
            'data' => ['id' => $replyId]
        ]);
    } catch (Exception $e) {
        error_log("Add reply error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to add reply']);
    }
}
