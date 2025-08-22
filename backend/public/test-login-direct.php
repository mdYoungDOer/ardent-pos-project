<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Simulate a POST request to test the login logic
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Set test data
$testData = [
    'email' => 'deyoungdoer@gmail.com',
    'password' => '@am171293GH!!'
];

// Simulate the request body
$input = json_encode($testData);

try {
    // Load autoloader
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
    
    // Parse the test data
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Failed to parse JSON data: ' . json_last_error_msg());
    }
    
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        throw new Exception('Email and password are required');
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
        throw new Exception('User not found');
    }
    
    if ($user['tenant_status'] !== 'active') {
        throw new Exception('Account is inactive');
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        throw new Exception('Invalid password');
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
        'message' => 'Direct login test successful',
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
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
