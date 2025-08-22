<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'error' => 'Method not allowed', 
        'method' => $_SERVER['REQUEST_METHOD'],
        'expected' => 'POST'
    ]);
    exit;
}

try {
    // Load autoloader
    $autoloaderPaths = [
        __DIR__ . '/../../vendor/autoload.php',
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
    
    // Get environment variables
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
    
    // Connect to database
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Get request data - try multiple sources
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // If JSON parsing failed, try $_POST
    if (!$data && !empty($_POST)) {
        $data = $_POST;
    }
    
    // If still no data, try $_REQUEST
    if (!$data && !empty($_REQUEST)) {
        $data = $_REQUEST;
    }
    
    if (!$data) {
        http_response_code(400);
        echo json_encode([
            'error' => 'No request data received',
            'raw_input' => $input,
            'post_data' => $_POST,
            'request_data' => $_REQUEST
        ]);
        exit;
    }
    
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Email and password are required',
            'received_data' => $data
        ]);
        exit;
    }
    
    // Find user
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
    
    if ($user['tenant_status'] !== 'active') {
        http_response_code(401);
        echo json_encode(['error' => 'Account is inactive']);
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }
    
    // Generate JWT token
    $payload = [
        'user_id' => $user['id'],
        'tenant_id' => $user['tenant_id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'iat' => time(),
        'exp' => time() + (24 * 60 * 60)
    ];
    
    $token = Firebase\JWT\JWT::encode($payload, $jwtSecret, 'HS256');
    
    // Return success response
    echo json_encode([
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
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
