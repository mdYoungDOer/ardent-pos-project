<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Database;
use ArdentPOS\Core\Response;
use ArdentPOS\Middleware\AuthMiddleware;
use Exception;

class SupportPortalController
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getKnowledgebase()
    {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $category = isset($_GET['category']) ? $_GET['category'] : null;
            $search = isset($_GET['search']) ? $_GET['search'] : null;
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
            
            $stmt = $this->db->prepare("
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
            $countStmt = $this->db->prepare("
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
            
            Response::json([
                'success' => true,
                'data' => [
                    'articles' => $articles,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => (int)$total,
                        'pages' => ceil($total / $limit)
                    ]
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Knowledgebase Error: " . $e->getMessage());
            Response::json([
                'success' => false,
                'message' => 'Failed to fetch knowledgebase'
            ], 500);
        }
    }
    
    public function getCategories()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, COUNT(kb.id) as article_count
                FROM knowledgebase_categories c
                LEFT JOIN knowledgebase kb ON c.id = kb.category_id AND kb.published = true
                GROUP BY c.id
                ORDER BY c.sort_order ASC, c.name ASC
            ");
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            Response::json([
                'success' => true,
                'data' => $categories
            ]);
            
        } catch (Exception $e) {
            error_log("Categories Error: " . $e->getMessage());
            Response::json([
                'success' => false,
                'message' => 'Failed to fetch categories'
            ], 500);
        }
    }
    
    public function searchKnowledgebase()
    {
        try {
            $query = isset($_GET['q']) ? $_GET['q'] : '';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            
            if (empty($query)) {
                Response::json([
                    'success' => true,
                    'data' => []
                ]);
                return;
            }
            
            $stmt = $this->db->prepare("
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
            
            Response::json([
                'success' => true,
                'data' => $results
            ]);
            
        } catch (Exception $e) {
            error_log("Search Error: " . $e->getMessage());
            Response::json([
                'success' => false,
                'message' => 'Failed to search knowledgebase'
            ], 500);
        }
    }
    
    public function getTickets()
    {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $status = isset($_GET['status']) ? $_GET['status'] : null;
            $priority = isset($_GET['priority']) ? $_GET['priority'] : null;
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
            
            $stmt = $this->db->prepare("
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
            $countStmt = $this->db->prepare("
                SELECT COUNT(*) as total 
                FROM support_tickets t
                WHERE $whereClause
            ");
            
            foreach ($bindParams as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            Response::json([
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
            error_log("Tickets Error: " . $e->getMessage());
            Response::json([
                'success' => false,
                'message' => 'Failed to fetch tickets'
            ], 500);
        }
    }
    
    public function createTicket()
    {
        try {
            $data = json_decode($GLOBALS['REQUEST_BODY'], true);
            
            $requiredFields = ['subject', 'message', 'priority', 'category'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    Response::json([
                        'success' => false,
                        'message' => "Missing required field: $field"
                    ], 400);
                    return;
                }
            }
            
            // Get user from JWT token
            $auth = new AuthMiddleware($this->db);
            $user = $auth->authenticate();
            
            if (!$user) {
                Response::json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
                return;
            }
            
            $stmt = $this->db->prepare("
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
            
            $ticketId = $this->db->lastInsertId();
            
            Response::json([
                'success' => true,
                'data' => ['ticket_id' => $ticketId]
            ]);
            
        } catch (Exception $e) {
            error_log("Create Ticket Error: " . $e->getMessage());
            Response::json([
                'success' => false,
                'message' => 'Failed to create ticket'
            ], 500);
        }
    }
    
    public function updateTicket($id)
    {
        try {
            $data = json_decode($GLOBALS['REQUEST_BODY'], true);
            
            if (empty($id)) {
                Response::json([
                    'success' => false,
                    'message' => 'Missing ticket ID'
                ], 400);
                return;
            }
            
            $updates = [];
            $bindParams = [':ticket_id' => $id];
            
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
                Response::json([
                    'success' => false,
                    'message' => 'No fields to update'
                ], 400);
                return;
            }
            
            $updates[] = "updated_at = NOW()";
            
            $sql = "UPDATE support_tickets SET " . implode(', ', $updates) . " WHERE id = :ticket_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($bindParams);
            
            Response::json([
                'success' => true,
                'message' => 'Ticket updated successfully'
            ]);
            
        } catch (Exception $e) {
            error_log("Update Ticket Error: " . $e->getMessage());
            Response::json([
                'success' => false,
                'message' => 'Failed to update ticket'
            ], 500);
        }
    }
    
    public function deleteTicket($id)
    {
        try {
            if (empty($id)) {
                Response::json([
                    'success' => false,
                    'message' => 'Missing ticket ID'
                ], 400);
                return;
            }
            
            $stmt = $this->db->prepare("DELETE FROM support_tickets WHERE id = :ticket_id");
            $stmt->execute([':ticket_id' => $id]);
            
            Response::json([
                'success' => true,
                'message' => 'Ticket deleted successfully'
            ]);
            
        } catch (Exception $e) {
            error_log("Delete Ticket Error: " . $e->getMessage());
            Response::json([
                'success' => false,
                'message' => 'Failed to delete ticket'
            ], 500);
        }
    }
    
    public function getChatHistory()
    {
        try {
            $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : null;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            
            if (!$sessionId) {
                Response::json([
                    'success' => true,
                    'data' => []
                ]);
                return;
            }
            
            $stmt = $this->db->prepare("
                SELECT * FROM support_chat_messages
                WHERE session_id = :session_id
                ORDER BY created_at ASC
                LIMIT :limit
            ");
            
            $stmt->bindParam(':session_id', $sessionId);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            Response::json([
                'success' => true,
                'data' => $messages
            ]);
            
        } catch (Exception $e) {
            error_log("Chat History Error: " . $e->getMessage());
            Response::json([
                'success' => false,
                'message' => 'Failed to fetch chat history'
            ], 500);
        }
    }
    
    public function sendChatMessage()
    {
        try {
            $data = json_decode($GLOBALS['REQUEST_BODY'], true);
            
            $requiredFields = ['session_id', 'message', 'sender_type'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    Response::json([
                        'success' => false,
                        'message' => "Missing required field: $field"
                    ], 400);
                    return;
                }
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO support_chat_messages (
                    session_id, message, sender_type, sender_id, created_at
                ) VALUES (
                    :session_id, :message, :sender_type, :sender_id, NOW()
                ) RETURNING id
            ");
            
            $senderId = null;
            if ($data['sender_type'] === 'user') {
                $auth = new AuthMiddleware($this->db);
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
            
            $messageId = $this->db->lastInsertId();
            
            // If it's a user message, try to provide auto-response
            if ($data['sender_type'] === 'user') {
                $autoResponse = $this->generateAutoResponse($data['message']);
                if ($autoResponse) {
                    $stmt = $this->db->prepare("
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
            
            Response::json([
                'success' => true,
                'data' => ['message_id' => $messageId]
            ]);
            
        } catch (Exception $e) {
            error_log("Send Chat Message Error: " . $e->getMessage());
            Response::json([
                'success' => false,
                'message' => 'Failed to send message'
            ], 500);
        }
    }
    
    private function generateAutoResponse($message)
    {
        try {
            // Search knowledgebase for relevant articles
            $stmt = $this->db->prepare("
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
}
