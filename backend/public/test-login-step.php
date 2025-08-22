<?php
header('Content-Type: application/json');

// Simulate a direct POST request to test login-basic.php logic
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Set the request body
$testData = [
    'email' => 'deyoungdoer@gmail.com',
    'password' => '@am171293GH!!'
];

// Simulate the raw input
$rawInput = json_encode($testData);

// Capture output
ob_start();

try {
    // Step 1: Test basic PHP functionality
    echo json_encode(['step' => '1', 'status' => 'PHP working']) . "\n";
    
    // Step 2: Test environment variables
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';
    
    if (empty($dbUser) || empty($dbPass)) {
        throw new Exception('Database credentials not configured');
    }
    
    echo json_encode(['step' => '2', 'status' => 'Environment variables loaded']) . "\n";
    
    // Step 3: Test request data
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
    
    echo json_encode(['step' => '3', 'status' => 'Request data parsed', 'email' => $email]) . "\n";
    
    // Step 4: Test database connection
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo json_encode(['step' => '4', 'status' => 'Database connected']) . "\n";
    
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
        echo json_encode(['step' => '5', 'status' => 'FAILED', 'error' => 'User not found']) . "\n";
        exit;
    }
    
    echo json_encode(['step' => '5', 'status' => 'User found', 'user_id' => $user['id']]) . "\n";
    
    // Step 6: Test password verification
    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode(['step' => '6', 'status' => 'FAILED', 'error' => 'Password verification failed']) . "\n";
        exit;
    }
    
    echo json_encode(['step' => '6', 'status' => 'Password verified']) . "\n";
    
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
    
    echo json_encode(['step' => '7', 'status' => 'JWT library loaded']) . "\n";
    
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
    
    echo json_encode(['step' => '8', 'status' => 'Token generated']) . "\n";
    
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
    
    echo json_encode($response) . "\n";
    
} catch (Exception $e) {
    echo json_encode([
        'step' => 'ERROR',
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]) . "\n";
}

$output = ob_get_clean();
echo $output;
?>
