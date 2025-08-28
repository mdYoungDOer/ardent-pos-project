<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../middleware/AuthMiddleware.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    
    // Extract the endpoint from the URL path
    // URL format: /api/support-portal/knowledgebase -> endpoint = knowledgebase
    $endpoint = end($pathParts);
    
    // Handle different HTTP methods
    switch ($method) {
        case 'GET':
            handleGetRequest($db, $endpoint, $_GET);
            break;
        case 'POST':
            handlePostRequest($db, $endpoint, $_POST);
            break;
        case 'PUT':
            handlePutRequest($db, $endpoint, $_POST);
            break;
        case 'DELETE':
            handleDeleteRequest($db, $endpoint, $_GET);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Support Portal API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error: ' . $e->getMessage()]);
}

function handleGetRequest($db, $endpoint, $params) {
    switch ($endpoint) {
        case 'knowledgebase':
            getKnowledgebase($db, $params);
            break;
        case 'categories':
            getCategories($db);
            break;
        case 'tickets':
            getTickets($db, $params);
            break;
        case 'chat':
            getChatHistory($db, $params);
            break;
        case 'search':
            searchKnowledgebase($db, $params);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found: ' . $endpoint]);
    }
}

function handlePostRequest($db, $endpoint, $data) {
    switch ($endpoint) {
        case 'tickets':
            createTicket($db, $data);
            break;
        case 'chat':
            sendChatMessage($db, $data);
            break;
        case 'search':
            searchKnowledgebase($db, $data);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found: ' . $endpoint]);
    }
}

function handlePutRequest($db, $endpoint, $data) {
    switch ($endpoint) {
        case 'tickets':
            updateTicket($db, $data);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found: ' . $endpoint]);
    }
}

function handleDeleteRequest($db, $endpoint, $params) {
    switch ($endpoint) {
        case 'tickets':
            deleteTicket($db, $params);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found: ' . $endpoint]);
    }
}

// Knowledgebase Functions
function getKnowledgebase($db, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
        $category = isset($params['category']) ? $params['category'] : null;
        $search = isset($params['search']) ? $params['search'] : null;
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ["published = true"];
        $bindParams = [];
        
        if ($category) {
            $whereConditions[] = "category_id = :category_id";
            $bindParams[':category_id'] = $category;
        }
        
        if ($search) {
            $whereConditions[] = "(title ILIKE :search OR content ILIKE :search OR tags ILIKE :search)";
            $bindParams[':search'] = "%$search%";
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $stmt = $db->prepare("
            SELECT kb.*, c.name as category_name, c.slug as category_slug
            FROM knowledgebase kb
            LEFT JOIN knowledgebase_categories c ON kb.category_id = c.id
            WHERE $whereClause
            ORDER BY kb.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($bindParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countStmt = $db->prepare("
            SELECT COUNT(*) as total 
            FROM knowledgebase kb
            LEFT JOIN knowledgebase_categories c ON kb.category_id = c.id
            WHERE $whereClause
        ");
        
        foreach ($bindParams as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode(['success' => true, 'data' => [
            'articles' => $articles,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]]);
        
    } catch (Exception $e) {
        error_log("Knowledgebase Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to fetch knowledgebase: ' . $e->getMessage()]);
    }
}

function getCategories($db) {
    try {
        $stmt = $db->prepare("
            SELECT c.*, COUNT(kb.id) as article_count
            FROM knowledgebase_categories c
            LEFT JOIN knowledgebase kb ON c.id = kb.category_id AND kb.published = true
            GROUP BY c.id
            ORDER BY c.sort_order ASC, c.name ASC
        ");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $categories]);
        
    } catch (Exception $e) {
        error_log("Categories Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to fetch categories: ' . $e->getMessage()]);
    }
}

function searchKnowledgebase($db, $data) {
    try {
        $query = isset($data['query']) ? $data['query'] : (isset($data['q']) ? $data['q'] : '');
        $limit = isset($data['limit']) ? (int)$data['limit'] : 5;
        
        if (empty($query)) {
            echo json_encode(['success' => true, 'data' => []]);
            return;
        }
        
        $stmt = $db->prepare("
            SELECT kb.*, c.name as category_name
            FROM knowledgebase kb
            LEFT JOIN knowledgebase_categories c ON kb.category_id = c.id
            WHERE kb.published = true 
            AND (kb.title ILIKE :query OR kb.content ILIKE :query OR kb.tags ILIKE :query)
            ORDER BY 
                CASE 
                    WHEN kb.title ILIKE :query THEN 1
                    WHEN kb.tags ILIKE :query THEN 2
                    ELSE 3
                END,
                kb.created_at DESC
            LIMIT :limit
        ");
        
        $stmt->bindParam(':query', "%$query%");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $results]);
        
    } catch (Exception $e) {
        error_log("Search Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to search knowledgebase: ' . $e->getMessage()]);
    }
}

// Ticket Functions
function getTickets($db, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
        $status = isset($params['status']) ? $params['status'] : null;
        $priority = isset($params['priority']) ? $params['priority'] : null;
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ["1=1"];
        $bindParams = [];
        
        if ($status) {
            $whereConditions[] = "t.status = :status";
            $bindParams[':status'] = $status;
        }
        
        if ($priority) {
            $whereConditions[] = "t.priority = :priority";
            $bindParams[':priority'] = $priority;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $stmt = $db->prepare("
            SELECT t.*, u.first_name, u.last_name, u.email, u.tenant_id,
                   tenant.name as tenant_name
            FROM support_tickets t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN tenants tenant ON u.tenant_id = tenant.id
            WHERE $whereClause
            ORDER BY t.updated_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($bindParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countStmt = $db->prepare("
            SELECT COUNT(*) as total 
            FROM support_tickets t
            WHERE $whereClause
        ");
        
        foreach ($bindParams as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode(['success' => true, 'data' => [
            'tickets' => $tickets,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]]);
        
    } catch (Exception $e) {
        error_log("Tickets Error: " . $e->getMessage());
        echo json_encode(['success' => true, 'data' => [
            'tickets' => [],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 0,
                'pages' => 0
            ]
        ]]);
    }
}

function createTicket($db, $data) {
    try {
        $requiredFields = ['subject', 'message', 'priority', 'category'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                return;
            }
        }
        
        // Get user from JWT token
        $auth = new AuthMiddleware($db);
        $user = $auth->authenticate();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not authenticated']);
            return;
        }
        
        $stmt = $db->prepare("
            INSERT INTO support_tickets (
                user_id, tenant_id, subject, message, priority, category, status, created_at, updated_at
            ) VALUES (
                :user_id, :tenant_id, :subject, :message, :priority, :category, 'open', NOW(), NOW()
            ) RETURNING id
        ");
        
        $stmt->execute([
            ':user_id' => $user['id'],
            ':tenant_id' => $user['tenant_id'],
            ':subject' => $data['subject'],
            ':message' => $data['message'],
            ':priority' => $data['priority'],
            ':category' => $data['category']
        ]);
        
        $ticketId = $db->lastInsertId();
        
        // Send email notification
        sendTicketNotification($db, $ticketId, 'created');
        
        echo json_encode(['success' => true, 'data' => ['ticket_id' => $ticketId]]);
        
    } catch (Exception $e) {
        error_log("Create Ticket Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to create ticket']);
    }
}

function updateTicket($db, $data) {
    try {
        if (empty($data['ticket_id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing ticket ID']);
            return;
        }
        
        $updates = [];
        $bindParams = [':ticket_id' => $data['ticket_id']];
        
        if (isset($data['status'])) {
            $updates[] = "status = :status";
            $bindParams[':status'] = $data['status'];
        }
        
        if (isset($data['priority'])) {
            $updates[] = "priority = :priority";
            $bindParams[':priority'] = $data['priority'];
        }
        
        if (isset($data['assigned_to'])) {
            $updates[] = "assigned_to = :assigned_to";
            $bindParams[':assigned_to'] = $data['assigned_to'];
        }
        
        if (empty($updates)) {
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }
        
        $updates[] = "updated_at = NOW()";
        
        $sql = "UPDATE support_tickets SET " . implode(', ', $updates) . " WHERE id = :ticket_id";
        $stmt = $db->prepare($sql);
        $stmt->execute($bindParams);
        
        // Send email notification
        sendTicketNotification($db, $data['ticket_id'], 'updated');
        
        echo json_encode(['success' => true, 'message' => 'Ticket updated successfully']);
        
    } catch (Exception $e) {
        error_log("Update Ticket Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update ticket']);
    }
}

function deleteTicket($db, $params) {
    try {
        if (empty($params['ticket_id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing ticket ID']);
            return;
        }
        
        $stmt = $db->prepare("DELETE FROM support_tickets WHERE id = :ticket_id");
        $stmt->execute([':ticket_id' => $params['ticket_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Ticket deleted successfully']);
        
    } catch (Exception $e) {
        error_log("Delete Ticket Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete ticket']);
    }
}

// Chat Functions
function getChatHistory($db, $params) {
    try {
        $sessionId = isset($params['session_id']) ? $params['session_id'] : null;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        
        if (!$sessionId) {
            echo json_encode(['success' => true, 'data' => []]);
            return;
        }
        
        $stmt = $db->prepare("
            SELECT * FROM support_chat_messages
            WHERE session_id = :session_id
            ORDER BY created_at ASC
            LIMIT :limit
        ");
        
        $stmt->bindParam(':session_id', $sessionId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $messages]);
        
    } catch (Exception $e) {
        error_log("Chat History Error: " . $e->getMessage());
        echo json_encode(['success' => true, 'data' => []]);
    }
}

function sendChatMessage($db, $data) {
    try {
        $requiredFields = ['session_id', 'message', 'sender_type'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                return;
            }
        }
        
        $stmt = $db->prepare("
            INSERT INTO support_chat_messages (
                session_id, message, sender_type, sender_id, created_at
            ) VALUES (
                :session_id, :message, :sender_type, :sender_id, NOW()
            ) RETURNING id
        ");
        
        $senderId = null;
        if ($data['sender_type'] === 'user') {
            $auth = new AuthMiddleware($db);
            $user = $auth->authenticate();
            if ($user) {
                $senderId = $user['id'];
            }
        }
        
        $stmt->execute([
            ':session_id' => $data['session_id'],
            ':message' => $data['message'],
            ':sender_type' => $data['sender_type'],
            ':sender_id' => $senderId
        ]);
        
        $messageId = $db->lastInsertId();
        
        // If it's a user message, try to provide auto-response
        if ($data['sender_type'] === 'user') {
            $autoResponse = generateAutoResponse($db, $data['message']);
            if ($autoResponse) {
                $stmt = $db->prepare("
                    INSERT INTO support_chat_messages (
                        session_id, message, sender_type, sender_id, created_at
                    ) VALUES (
                        :session_id, :message, 'bot', NULL, NOW()
                    )
                ");
                
                $stmt->execute([
                    ':session_id' => $data['session_id'],
                    ':message' => $autoResponse
                ]);
            }
        }
        
        echo json_encode(['success' => true, 'data' => ['message_id' => $messageId]]);
        
    } catch (Exception $e) {
        error_log("Send Chat Message Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
}

function generateAutoResponse($db, $message) {
    try {
        // Search knowledgebase for relevant articles
        $stmt = $db->prepare("
            SELECT title, content 
            FROM knowledgebase 
            WHERE published = true 
            AND (title ILIKE :query OR content ILIKE :query OR tags ILIKE :query)
            ORDER BY 
                CASE 
                    WHEN title ILIKE :query THEN 1
                    WHEN tags ILIKE :query THEN 2
                    ELSE 3
                END
            LIMIT 1
        ");
        
        $stmt->bindParam(':query', "%$message%");
        $stmt->execute();
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($article) {
            return "I found a relevant article that might help: **{$article['title']}**\n\n" . 
                   substr(strip_tags($article['content']), 0, 200) . "...\n\n" .
                   "Would you like me to create a support ticket for further assistance?";
        }
        
        return "I couldn't find a specific answer to your question. Would you like me to create a support ticket so our team can help you?";
        
    } catch (Exception $e) {
        error_log("Auto Response Error: " . $e->getMessage());
        return "I'm here to help! Would you like me to create a support ticket for you?";
    }
}

function sendTicketNotification($db, $ticketId, $action) {
    try {
        // Get ticket details
        $stmt = $db->prepare("
            SELECT t.*, u.email as user_email, u.first_name, u.last_name
            FROM support_tickets t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.id = :ticket_id
        ");
        $stmt->execute([':ticket_id' => $ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ticket) {
            return;
        }
        
        // Send email notification (implement with your SendGrid integration)
        $subject = "Support Ticket #$ticketId - " . ucfirst($action);
        $message = "Your support ticket has been $action. We'll get back to you soon.";
        
        // TODO: Implement SendGrid email sending here
        error_log("Email notification: $subject to {$ticket['user_email']}");
        
    } catch (Exception $e) {
        error_log("Send Notification Error: " . $e->getMessage());
    }
}
?>
