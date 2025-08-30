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
?>
