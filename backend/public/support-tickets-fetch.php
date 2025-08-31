<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
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
    
    // Get query parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $priority = isset($_GET['priority']) ? $_GET['priority'] : null;
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause
    $whereConditions = ["tenant_id = ?"];
    $bindParams = [$user['tenant_id']];
    
    if ($status) {
        $whereConditions[] = "status = ?";
        $bindParams[] = $status;
    }
    
    if ($priority) {
        $whereConditions[] = "priority = ?";
        $bindParams[] = $priority;
    }
    
    if ($category) {
        $whereConditions[] = "category = ?";
        $bindParams[] = $category;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get tickets
    $sql = "
        SELECT 
            t.*,
            u.first_name as user_first_name,
            u.last_name as user_last_name,
            u.email as user_email
        FROM support_tickets t
        LEFT JOIN users u ON t.user_id = u.id
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
    $countSql = "SELECT COUNT(*) as total FROM support_tickets WHERE $whereClause";
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
    
} catch (Exception $e) {
    error_log("Support tickets fetch error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch support tickets. Please try again later.'
    ]);
}
?>
