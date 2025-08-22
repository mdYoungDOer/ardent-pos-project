<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
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
$dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
$dbPort = $_ENV['DB_PORT'] ?? getenv('DB_PORT');
$dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
$dbUser = $_ENV['DB_USER'] ?? getenv('DB_USER');
$dbPass = $_ENV['DB_PASS'] ?? getenv('DB_PASS');
$jwtSecret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET');

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['email']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Email and password are required'
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

try {
    // Database connection
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if user exists and is a super admin
    $stmt = $pdo->prepare("
        SELECT u.*, t.name as tenant_name 
        FROM users u 
        LEFT JOIN tenants t ON u.tenant_id = t.id 
        WHERE u.email = ? AND u.role = 'super_admin' AND u.status = 'active'
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

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

    // Check if user is active
    if ($user['status'] !== 'active') {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Account is not active'
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
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);

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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Login failed: ' . $e->getMessage()
    ]);
}
?>
