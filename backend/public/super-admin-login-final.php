<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

try {
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Email and password are required',
            'debug' => [
                'input_received' => $input,
                'raw_input' => file_get_contents('php://input')
            ]
        ]);
        exit();
    }

    $email = trim($input['email']);
    $password = $input['password'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid email format'
        ]);
        exit();
    }

    // Load environment variables
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
                putenv(trim($key) . '=' . trim($value));
            }
        }
    }

    // Database configuration
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';

    if (empty($dbUser) || empty($dbPass)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database credentials not configured',
            'debug' => [
                'dbHost' => $dbHost,
                'dbPort' => $dbPort,
                'dbName' => $dbName,
                'dbUser' => $dbUser,
                'dbPass' => empty($dbPass) ? 'EMPTY' : 'SET'
            ]
        ]);
        exit();
    }

    // Database connection
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database connection failed: ' . $e->getMessage(),
            'debug' => [
                'dsn' => $dsn,
                'dbUser' => $dbUser
            ]
        ]);
        exit();
    }
    
    // Check if user exists and is a super admin
    $stmt = $pdo->prepare("
        SELECT u.*, t.name as tenant_name 
        FROM users u 
        LEFT JOIN tenants t ON u.tenant_id = t.id 
        WHERE u.email = ? AND u.role = 'super_admin' AND u.status = 'active'
    ");
    
    try {
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database query failed: ' . $e->getMessage()
        ]);
        exit();
    }

    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Super admin access denied - user not found or not super admin'
        ]);
        exit();
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid credentials'
        ]);
        exit();
    }

    // Generate JWT token
    $payload = [
        'user_id' => $user['id'],
        'tenant_id' => $user['tenant_id'],
        'role' => $user['role'],
        'iat' => time(),
        'exp' => time() + (60 * 60) // 1 hour
    ];

    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload_encoded = json_encode($payload);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload_encoded));
    
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $jwtSecret, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    $token = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

    // Update last login
    try {
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
    } catch (PDOException $e) {
        // Log error but don't fail the login
        error_log("Failed to update last login: " . $e->getMessage());
    }

    // Clear any unexpected output
    ob_clean();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Super admin login successful',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'],
            'status' => $user['status']
        ],
        'tenant' => [
            'id' => $user['tenant_id'],
            'name' => $user['tenant_name'] ?? 'Super Admin'
        ]
    ]);

} catch (Exception $e) {
    // Clear any unexpected output
    ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Login failed: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}

// End output buffering
ob_end_flush();
?>
