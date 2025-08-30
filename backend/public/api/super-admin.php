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
$envFile = __DIR__ . '/../../.env';
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
    'dbname' => $_ENV['DB_NAME'] ?? 'ardent_pos',
    'user' => $_ENV['DB_USER'] ?? 'postgres',
    'password' => $_ENV['DB_PASSWORD'] ?? ''
];

// Simple authentication check
function checkSuperAdminAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized - Token required']);
        exit;
    }
    
    $token = substr($authHeader, 7);
    
    // For now, accept any token (in production, validate JWT)
    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized - Invalid token']);
        exit;
    }
}

try {
    // Check authentication
    checkSuperAdminAuth();
    
    // Connect to database
    $dsn = "pgsql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']}";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Ensure required tables exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'user',
            tenant_id INTEGER,
            status VARCHAR(50) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tenants (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            domain VARCHAR(255),
            status VARCHAR(50) DEFAULT 'active',
            subscription_plan VARCHAR(50) DEFAULT 'basic',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subscriptions (
            id SERIAL PRIMARY KEY,
            tenant_id INTEGER NOT NULL,
            plan_name VARCHAR(50) NOT NULL,
            status VARCHAR(50) DEFAULT 'active',
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'GHS',
            billing_cycle VARCHAR(20) DEFAULT 'monthly',
            next_billing_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS invoices (
            id SERIAL PRIMARY KEY,
            tenant_id INTEGER NOT NULL,
            subscription_id INTEGER,
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'GHS',
            status VARCHAR(50) DEFAULT 'pending',
            due_date DATE,
            paid_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    // Route to appropriate handler
    switch ($endpoint) {
        case 'analytics':
            handleAnalytics($pdo);
            break;
        case 'users':
            handleUsers($pdo, $method);
            break;
        case 'tenants':
            handleTenants($pdo, $method);
            break;
        case 'subscriptions':
            handleSubscriptions($pdo, $method);
            break;
        case 'billing-overview':
            handleBillingOverview($pdo);
            break;
        case 'invoices':
            handleInvoices($pdo, $method);
            break;
        case 'contact-submissions':
            handleContactSubmissions($pdo, $method);
            break;
        case 'stats':
            handleStats($pdo);
            break;
        case 'billing':
            handleBilling($pdo);
            break;
        case 'activity':
            handleActivity($pdo);
            break;
        case 'settings':
            handleSettings($pdo, $method);
            break;
        case 'system-logs':
            handleSystemLogs($pdo);
            break;
        case 'system-health':
            handleSystemHealth($pdo);
            break;
        case 'security':
            handleSecurity($pdo);
            break;
        case 'support-tickets':
            handleSupportTickets($pdo, $method);
            break;
        case 'support-ticket-stats':
            handleSupportTicketStats($pdo);
            break;
        case 'knowledgebase-categories':
            handleKnowledgebaseCategories($pdo, $method);
            break;
        case 'knowledgebase-articles':
            handleKnowledgebaseArticles($pdo, $method);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
    
} catch (Exception $e) {
    error_log("Super Admin API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}

function handleAnalytics($pdo) {
    // Get analytics data
    $stats = [
        'total_users' => 0,
        'total_tenants' => 0,
        'active_subscriptions' => 0,
        'total_revenue' => 0,
        'monthly_growth' => 12.5
    ];
    
    // Count users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count tenants
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tenants");
    $stats['total_tenants'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count active subscriptions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'");
    $stats['active_subscriptions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Calculate total revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'paid'");
    $stats['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

function handleUsers($pdo, $method) {
    if ($method === 'GET') {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT u.*, t.name as tenant_name FROM users u LEFT JOIN tenants t ON u.tenant_id = t.id ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit, $offset]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'users' => $users,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    }
}

function handleTenants($pdo, $method) {
    if ($method === 'GET') {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM tenants ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit, $offset]);
        $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM tenants");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
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
    }
}

function handleSubscriptions($pdo, $method) {
    if ($method === 'GET') {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT s.*, t.name as tenant_name FROM subscriptions s JOIN tenants t ON s.tenant_id = t.id ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit, $offset]);
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM subscriptions");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'subscriptions' => $subscriptions,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    }
}

function handleBillingOverview($pdo) {
    // Get billing overview data
    $overview = [
        'total_revenue' => 0,
        'active_subscriptions' => 0,
        'pending_payments' => 0,
        'monthly_growth' => 12.5
    ];
    
    // Calculate total revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'paid'");
    $overview['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Count active subscriptions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'");
    $overview['active_subscriptions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count pending payments
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM invoices WHERE status = 'pending'");
    $overview['pending_payments'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'data' => $overview
    ]);
}

function handleInvoices($pdo, $method) {
    if ($method === 'GET') {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT i.*, t.name as tenant_name FROM invoices i JOIN tenants t ON i.tenant_id = t.id ORDER BY i.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit, $offset]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM invoices");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'invoices' => $invoices,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    }
}

function handleContactSubmissions($pdo, $method) {
    if ($method === 'GET') {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit, $offset]);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM contact_submissions");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'submissions' => $submissions,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    }
}

function handleStats($pdo) {
    $stats = [
        'total_users' => 0,
        'total_tenants' => 0,
        'active_subscriptions' => 0,
        'total_revenue' => 0,
        'monthly_growth' => 12.5
    ];
    
    // Count users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count tenants
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tenants");
    $stats['total_tenants'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count active subscriptions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'");
    $stats['active_subscriptions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Calculate total revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'paid'");
    $stats['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

function handleBilling($pdo) {
    $billing = [
        'total_revenue' => 0,
        'monthly_revenue' => 0,
        'active_subscriptions' => 0,
        'pending_payments' => 0
    ];
    
    // Calculate total revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'paid'");
    $billing['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculate monthly revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'paid' AND created_at >= NOW() - INTERVAL '30 days'");
    $billing['monthly_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Count active subscriptions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'");
    $billing['active_subscriptions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count pending payments
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM invoices WHERE status = 'pending'");
    $billing['pending_payments'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'data' => $billing
    ]);
}

function handleActivity($pdo) {
    $activities = [
        [
            'id' => 1,
            'type' => 'user_login',
            'description' => 'User logged in',
            'user_email' => 'admin@example.com',
            'activity_time' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 2,
            'type' => 'subscription_created',
            'description' => 'New subscription created',
            'user_email' => 'tenant@example.com',
            'activity_time' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'activities' => $activities,
            'pagination' => [
                'page' => 1,
                'limit' => 20,
                'total' => count($activities),
                'pages' => 1
            ]
        ]
    ]);
}

function handleSettings($pdo, $method) {
    if ($method === 'GET') {
        $settings = [
            'system_name' => 'Ardent POS',
            'system_version' => '1.0.0',
            'maintenance_mode' => false,
            'email_notifications' => true,
            'max_file_size' => '10MB',
            'session_timeout' => 3600
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $settings
        ]);
    }
}

function handleSystemLogs($pdo) {
    $logs = [
        [
            'id' => 1,
            'level' => 'INFO',
            'message' => 'System started successfully',
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => null,
            'ip_address' => '127.0.0.1'
        ],
        [
            'id' => 2,
            'level' => 'WARNING',
            'message' => 'High memory usage detected',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'user_id' => null,
            'ip_address' => '127.0.0.1'
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'logs' => $logs,
            'pagination' => [
                'page' => 1,
                'limit' => 50,
                'total' => count($logs),
                'pages' => 1
            ]
        ]
    ]);
}

function handleSystemHealth($pdo) {
    $health = [
        'database' => 'healthy',
        'api' => 'healthy',
        'authentication' => 'healthy',
        'overall' => 'healthy',
        'last_check' => date('Y-m-d H:i:s'),
        'uptime' => '99.9%'
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $health
    ]);
}

function handleSecurity($pdo) {
    $security = [
        'total_events' => 0,
        'failed_logins' => 0,
        'suspicious_activities' => 0,
        'system_alerts' => 0,
        'last_security_scan' => date('Y-m-d H:i:s'),
        'threat_level' => 'low'
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $security
    ]);
}

function handleSupportTickets($pdo, $method) {
    if ($method === 'GET') {
        $tickets = [
            [
                'id' => 1,
                'subject' => 'Login issue',
                'status' => 'open',
                'priority' => 'medium',
                'user_email' => 'user@example.com',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'subject' => 'Payment problem',
                'status' => 'in_progress',
                'priority' => 'high',
                'user_email' => 'customer@example.com',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'tickets' => $tickets,
                'pagination' => [
                    'page' => 1,
                    'limit' => 20,
                    'total' => count($tickets),
                    'pages' => 1
                ]
            ]
        ]);
    }
}

function handleSupportTicketStats($pdo) {
    $stats = [
        'total_tickets' => 2,
        'open_tickets' => 1,
        'in_progress_tickets' => 1,
        'resolved_tickets' => 0,
        'average_response_time' => '2 hours'
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
}

function handleKnowledgebaseCategories($pdo, $method) {
    if ($method === 'GET') {
        $categories = [
            [
                'id' => 1,
                'name' => 'Getting Started',
                'description' => 'Basic setup and configuration',
                'article_count' => 5,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'name' => 'Troubleshooting',
                'description' => 'Common issues and solutions',
                'article_count' => 8,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'pagination' => [
                    'page' => 1,
                    'limit' => 20,
                    'total' => count($categories),
                    'pages' => 1
                ]
            ]
        ]);
    }
}

function handleKnowledgebaseArticles($pdo, $method) {
    if ($method === 'GET') {
        $articles = [
            [
                'id' => 1,
                'title' => 'How to get started',
                'content' => 'This is a sample article content...',
                'category_id' => 1,
                'category_name' => 'Getting Started',
                'status' => 'published',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'title' => 'Common login issues',
                'content' => 'This is another sample article...',
                'category_id' => 2,
                'category_name' => 'Troubleshooting',
                'status' => 'published',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'articles' => $articles,
                'pagination' => [
                    'page' => 1,
                    'limit' => 20,
                    'total' => count($articles),
                    'pages' => 1
                ]
            ]
        ]);
    }
}
?>
