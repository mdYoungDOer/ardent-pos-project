<?php
// Basic login endpoint - minimal dependencies
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Start output buffering
ob_start();

try {
    // Step 1: Test basic PHP functionality
    echo json_encode(['step' => '1', 'status' => 'PHP working']);
    ob_clean();
    
    // Step 2: Test environment variables
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';
    
    if (empty($dbUser) || empty($dbPass)) {
        throw new Exception('Database credentials not configured');
    }
    
    echo json_encode(['step' => '2', 'status' => 'Environment variables loaded']);
    ob_clean();
    
    // Step 3: Test request data
    $rawInput = file_get_contents('php://input');
    if (empty($rawInput)) {
        throw new Exception('No request body received');
    }
    
    $data = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        throw new Exception('Email and password are required');
    }
    
    echo json_encode(['step' => '3', 'status' => 'Request data parsed', 'email' => $email]);
    ob_clean();
    
    // Step 4: Test database connection
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo json_encode(['step' => '4', 'status' => 'Database connected']);
    ob_clean();
    
    // Step 5: Test user lookup
    $stmt = $pdo->prepare("
        SELECT u.*, t.name as tenant_name, t.status as tenant_status
        FROM users u 
        JOIN tenants t ON u.tenant_id = t.id 
        WHERE u.email = ? AND u.status = 'active'
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }
    
    echo json_encode(['step' => '5', 'status' => 'User found', 'user_id' => $user['id']]);
    ob_clean();
    
    // Step 6: Test password verification
    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }
    
    echo json_encode(['step' => '6', 'status' => 'Password verified']);
    ob_clean();
    
    // Step 7: Test JWT library
    $autoloaderPaths = [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/vendor/autoload.php',
        '/var/www/html/vendor/autoload.php',
        '/var/www/html/backend/vendor/autoload.php'
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
        throw new Exception('Autoloader not found');
    }
    
    if (!class_exists('Firebase\JWT\JWT')) {
        throw new Exception('JWT library not available');
    }
    
    echo json_encode(['step' => '7', 'status' => 'JWT library loaded']);
    ob_clean();
    
    // Step 8: Generate token
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
    $payload = [
        'user_id' => $user['id'],
        'tenant_id' => $user['tenant_id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'iat' => time(),
        'exp' => time() + (24 * 60 * 60)
    ];
    
    $token = Firebase\JWT\JWT::encode($payload, $jwtSecret, 'HS256');
    
    echo json_encode(['step' => '8', 'status' => 'Token generated']);
    ob_clean();
    
    // Step 9: Return success
    $response = [
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role']
        ],
        'tenant' => [
            'id' => $user['tenant_id'],
            'name' => $user['tenant_name']
        ]
    ];
    
    ob_clean();
    echo json_encode($response);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

ob_end_flush();
?>
