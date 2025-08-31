<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Simple authentication check - just verify token exists
function checkAuth() {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return false;
    }
    
    $token = $matches[1];
    
    // For now, just check if token exists (we'll implement proper JWT validation later)
    if (empty($token)) {
        return false;
    }
    
    return true;
}

try {
    // Check authentication
    if (!checkAuth()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Token not provided or invalid'
        ]);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    // Remove .php extension if present
    $endpoint = str_replace('.php', '', $endpoint);
    
    switch ($method) {
        case 'GET':
            if ($endpoint === 'tickets') {
                // Return demo tickets for now
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'tickets' => [
                            [
                                'id' => 1,
                                'ticket_number' => 'TKT-2025-000001',
                                'subject' => 'System Login Issue',
                                'message' => 'Unable to login to the system',
                                'priority' => 'high',
                                'category' => 'technical',
                                'status' => 'open',
                                'created_at' => '2025-01-31 10:00:00',
                                'user_first_name' => 'John',
                                'user_last_name' => 'Doe',
                                'user_email' => 'john@example.com',
                                'tenant_name' => 'Demo Company'
                            ],
                            [
                                'id' => 2,
                                'ticket_number' => 'TKT-2025-000002',
                                'subject' => 'Payment Processing Error',
                                'message' => 'Payments are not being processed correctly',
                                'priority' => 'medium',
                                'category' => 'billing',
                                'status' => 'in_progress',
                                'created_at' => '2025-01-30 15:30:00',
                                'user_first_name' => 'Jane',
                                'user_last_name' => 'Smith',
                                'user_email' => 'jane@example.com',
                                'tenant_name' => 'Demo Company'
                            ]
                        ],
                        'pagination' => [
                            'page' => 1,
                            'limit' => 20,
                            'total' => 2,
                            'pages' => 1
                        ]
                    ]
                ]);
            } elseif ($endpoint === 'stats') {
                // Return demo stats
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'total_tickets' => 2,
                        'status_breakdown' => [
                            ['status' => 'open', 'count' => 1],
                            ['status' => 'in_progress', 'count' => 1],
                            ['status' => 'closed', 'count' => 0]
                        ]
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
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'ticket_id' => rand(1000, 9999),
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
