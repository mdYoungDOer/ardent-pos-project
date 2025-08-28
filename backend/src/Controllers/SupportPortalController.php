<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Database;
use ArdentPOS\Core\Response;
use ArdentPOS\Middleware\AuthMiddleware;
use Exception;

class SupportPortalController
{
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
                $bindParams['category_id'] = $category;
            }
            
            if ($search) {
                $whereConditions[] = "(title ILIKE :search OR content ILIKE :search OR tags ILIKE :search)";
                $bindParams['search'] = "%$search%";
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            $sql = "
                SELECT kb.*, c.name as category_name, c.slug as category_slug
                FROM knowledgebase kb
                LEFT JOIN knowledgebase_categories c ON kb.category_id = c.id
                WHERE $whereClause
                ORDER BY kb.created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $articles = Database::fetchAll($sql, array_merge($bindParams, [
                'limit' => $limit,
                'offset' => $offset
            ]));
            
            // Get total count
            $countSql = "
                SELECT COUNT(*) as total 
                FROM knowledgebase kb
                LEFT JOIN knowledgebase_categories c ON kb.category_id = c.id
                WHERE $whereClause
            ";
            
            $totalResult = Database::fetch($countSql, $bindParams);
            $total = $totalResult['total'] ?? 0;
            
            echo json_encode([
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
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch knowledgebase'
            ]);
        }
    }
    
    public function getCategories()
    {
        try {
            $sql = "
                SELECT c.*, COUNT(kb.id) as article_count
                FROM knowledgebase_categories c
                LEFT JOIN knowledgebase kb ON c.id = kb.category_id AND kb.published = true
                GROUP BY c.id
                ORDER BY c.sort_order ASC, c.name ASC
            ";
            
            $categories = Database::fetchAll($sql);
            
            echo json_encode([
                'success' => true,
                'data' => $categories
            ]);
            
        } catch (Exception $e) {
            error_log("Categories Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch categories'
            ]);
        }
    }
    
    public function searchKnowledgebase()
    {
        try {
            $query = isset($_GET['q']) ? $_GET['q'] : '';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            
            if (empty($query)) {
                echo json_encode([
                    'success' => true,
                    'data' => []
                ]);
                return;
            }
            
            $sql = "
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
            ";
            
            $results = Database::fetchAll($sql, [
                'query' => "%$query%",
                'limit' => $limit
            ]);
            
            echo json_encode([
                'success' => true,
                'data' => $results
            ]);
            
        } catch (Exception $e) {
            error_log("Search Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to search knowledgebase'
            ]);
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
                $bindParams['status'] = $status;
            }
            
            if ($priority) {
                $whereConditions[] = "t.priority = :priority";
                $bindParams['priority'] = $priority;
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            $sql = "
                SELECT t.*, u.first_name, u.last_name, u.email, u.tenant_id,
                       tenant.name as tenant_name
                FROM support_tickets t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN tenants tenant ON u.tenant_id = tenant.id
                WHERE $whereClause
                ORDER BY t.updated_at DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $tickets = Database::fetchAll($sql, array_merge($bindParams, [
                'limit' => $limit,
                'offset' => $offset
            ]));
            
            // Get total count
            $countSql = "
                SELECT COUNT(*) as total 
                FROM support_tickets t
                WHERE $whereClause
            ";
            
            $totalResult = Database::fetch($countSql, $bindParams);
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
            
        } catch (Exception $e) {
            error_log("Tickets Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch tickets'
            ]);
        }
    }
    
    public function createTicket()
    {
        try {
            $data = json_decode($GLOBALS['REQUEST_BODY'], true);
            
            $requiredFields = ['subject', 'message', 'priority', 'category'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => "Missing required field: $field"
                    ]);
                    return;
                }
            }
            
            // Get user from JWT token - user should already be authenticated by middleware
            $user = $GLOBALS['current_user'] ?? null;
            
            if (!$user) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'User not authenticated'
                ]);
                return;
            }
            
            $ticketData = [
                'user_id' => $user['id'],
                'tenant_id' => $user['tenant_id'],
                'subject' => $data['subject'],
                'message' => $data['message'],
                'priority' => $data['priority'],
                'category' => $data['category'],
                'status' => 'open',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $ticketId = Database::insert('support_tickets', $ticketData);
            
            echo json_encode([
                'success' => true,
                'data' => ['ticket_id' => $ticketId]
            ]);
            
        } catch (Exception $e) {
            error_log("Create Ticket Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create ticket'
            ]);
        }
    }

    public function createPublicTicket()
    {
        try {
            $data = json_decode($GLOBALS['REQUEST_BODY'], true);
            
            $requiredFields = ['subject', 'message', 'priority', 'category', 'first_name', 'last_name', 'email'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => "Missing required field: $field"
                    ]);
                    return;
                }
            }
            
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid email format'
                ]);
                return;
            }
            
            // Generate ticket number
            $ticketNumber = 'TKT-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            
            $ticketData = [
                'ticket_number' => $ticketNumber,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'subject' => $data['subject'],
                'message' => $data['message'],
                'priority' => $data['priority'],
                'category' => $data['category'],
                'status' => 'open',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $ticketId = Database::insert('support_tickets', $ticketData);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'ticket_id' => $ticketId,
                    'ticket_number' => $ticketNumber
                ],
                'message' => 'Support ticket created successfully! We will contact you soon.'
            ]);
            
        } catch (Exception $e) {
            error_log("Create Public Ticket Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create ticket. Please try again later.'
            ]);
        }
    }

    public function getKnowledgebaseArticle($id)
    {
        try {
            $sql = "
                SELECT kb.*, c.name as category_name 
                FROM knowledgebase kb
                LEFT JOIN knowledgebase_categories c ON kb.category_id = c.id
                WHERE kb.id = :id AND kb.published = true
            ";
            
            $article = Database::fetch($sql, ['id' => $id]);
            
            if (!$article) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Article not found'
                ]);
                return;
            }
            
            // Increment view count
            Database::execute("UPDATE knowledgebase SET view_count = view_count + 1 WHERE id = :id", ['id' => $id]);
            
            echo json_encode([
                'success' => true,
                'data' => $article
            ]);
            
        } catch (Exception $e) {
            error_log("Get Article Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch article'
            ]);
        }
    }
    
    public function updateTicket($id)
    {
        try {
            $data = json_decode($GLOBALS['REQUEST_BODY'], true);
            
            if (empty($id)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing ticket ID'
                ]);
                return;
            }
            
            $updates = [];
            
            if (isset($data['status'])) {
                $updates['status'] = $data['status'];
            }
            
            if (isset($data['priority'])) {
                $updates['priority'] = $data['priority'];
            }
            
            if (isset($data['assigned_to'])) {
                $updates['assigned_to'] = $data['assigned_to'];
            }
            
            if (empty($updates)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'No fields to update'
                ]);
                return;
            }
            
            $updates['updated_at'] = date('Y-m-d H:i:s');
            
            $affected = Database::update('support_tickets', $updates, 'id = :id', ['id' => $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Ticket updated successfully'
            ]);
            
        } catch (Exception $e) {
            error_log("Update Ticket Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update ticket'
            ]);
        }
    }
    
    public function deleteTicket($id)
    {
        try {
            if (empty($id)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Missing ticket ID'
                ]);
                return;
            }
            
            Database::query("DELETE FROM support_tickets WHERE id = :id", ['id' => $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Ticket deleted successfully'
            ]);
            
        } catch (Exception $e) {
            error_log("Delete Ticket Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete ticket'
            ]);
        }
    }
    
    public function getChatHistory()
    {
        try {
            $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : null;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            
            if (!$sessionId) {
                echo json_encode([
                    'success' => true,
                    'data' => []
                ]);
                return;
            }
            
            $sql = "
                SELECT * FROM support_chat_messages
                WHERE session_id = :session_id
                ORDER BY created_at ASC
                LIMIT :limit
            ";
            
            $messages = Database::fetchAll($sql, [
                'session_id' => $sessionId,
                'limit' => $limit
            ]);
            
            echo json_encode([
                'success' => true,
                'data' => $messages
            ]);
            
        } catch (Exception $e) {
            error_log("Chat History Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch chat history'
            ]);
        }
    }
    
    public function sendChatMessage()
    {
        try {
            $data = json_decode($GLOBALS['REQUEST_BODY'], true);
            
            $requiredFields = ['session_id', 'message', 'sender_type'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => "Missing required field: $field"
                    ]);
                    return;
                }
            }
            
            $senderId = null;
            if ($data['sender_type'] === 'user') {
                // Get user from JWT token - user should already be authenticated by middleware
                $user = $GLOBALS['current_user'] ?? null;
                if ($user) {
                    $senderId = $user['id'];
                }
            }
            
            $messageData = [
                'session_id' => $data['session_id'],
                'message' => $data['message'],
                'sender_type' => $data['sender_type'],
                'sender_id' => $senderId,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $messageId = Database::insert('support_chat_messages', $messageData);
            
            // If it's a user message, try to provide auto-response
            if ($data['sender_type'] === 'user') {
                $autoResponse = $this->generateAutoResponse($data['message']);
                if ($autoResponse) {
                    $botMessageData = [
                        'session_id' => $data['session_id'],
                        'message' => $autoResponse,
                        'sender_type' => 'bot',
                        'sender_id' => null,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    Database::insert('support_chat_messages', $botMessageData);
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => ['message_id' => $messageId]
            ]);
            
        } catch (Exception $e) {
            error_log("Send Chat Message Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send message'
            ]);
        }
    }
    
    public function createChatSession()
    {
        try {
            // Generate a unique session ID
            $sessionId = uniqid('chat_', true);
            
            // Get user from JWT token if available (optional for public chat)
            $user = $GLOBALS['current_user'] ?? null;
            
            $sessionData = [
                'session_id' => $sessionId,
                'user_id' => $user ? $user['id'] : null,
                'tenant_id' => $user ? $user['tenant_id'] : null,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Insert session into database
            $sessionDbId = Database::insert('support_chat_sessions', $sessionData);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'session_id' => $sessionId,
                    'messages' => []
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Create Chat Session Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create chat session'
            ]);
        }
    }
    
    private function generateAutoResponse($message)
    {
        try {
            // Search knowledgebase for relevant articles
            $sql = "
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
            ";
            
            $article = Database::fetch($sql, ['query' => "%$message%"]);
            
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
