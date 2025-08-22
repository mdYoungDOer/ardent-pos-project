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

// Load environment variables - try multiple paths
$envPaths = [
    __DIR__ . '/../.env',
    __DIR__ . '/../../.env',
    '/var/www/html/.env',
    '/var/www/html/backend/.env'
];

foreach ($envPaths as $envPath) {
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
                putenv(trim($key) . '=' . trim($value));
            }
        }
        break;
    }
}

// Database configuration - try multiple environment variable names
$dbHost = $_ENV['DB_HOST'] ?? $_ENV['DATABASE_HOST'] ?? getenv('DB_HOST') ?? getenv('DATABASE_HOST') ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? $_ENV['DATABASE_PORT'] ?? getenv('DB_PORT') ?? getenv('DATABASE_PORT') ?? '5432';
$dbName = $_ENV['DB_NAME'] ?? $_ENV['DATABASE_NAME'] ?? getenv('DB_NAME') ?? getenv('DATABASE_NAME') ?? 'defaultdb';
$dbUser = $_ENV['DB_USER'] ?? $_ENV['DATABASE_USER'] ?? $_ENV['DB_USERNAME'] ?? $_ENV['DATABASE_USERNAME'] ?? getenv('DB_USER') ?? getenv('DATABASE_USER') ?? getenv('DB_USERNAME') ?? getenv('DATABASE_USERNAME') ?? '';
$dbPass = $_ENV['DB_PASS'] ?? $_ENV['DATABASE_PASS'] ?? $_ENV['DB_PASSWORD'] ?? $_ENV['DATABASE_PASSWORD'] ?? getenv('DB_PASS') ?? getenv('DATABASE_PASS') ?? getenv('DB_PASSWORD') ?? getenv('DATABASE_PASSWORD') ?? '';
$jwtSecret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?? 'your-secret-key';

// Debug: Log the database configuration (without password)
error_log("DB Host: " . $dbHost);
error_log("DB Port: " . $dbPort);
error_log("DB Name: " . $dbName);
error_log("DB User: " . $dbUser);
error_log("DB Pass: " . (empty($dbPass) ? 'EMPTY' : 'SET'));
error_log("JWT Secret: " . (empty($jwtSecret) ? 'EMPTY' : 'SET'));

// Validate database credentials
if (empty($dbUser) || empty($dbPass)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database credentials not configured. Please check environment variables.',
        'debug' => [
            'dbHost' => $dbHost,
            'dbPort' => $dbPort,
            'dbName' => $dbName,
            'dbUser' => $dbUser,
            'dbPass' => empty($dbPass) ? 'EMPTY' : 'SET',
            'envVars' => array_keys($_ENV)
        ]
    ]);
    exit();
}

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
        'error' => 'Login failed: ' . $e->getMessage(),
        'debug' => [
            'dbHost' => $dbHost,
            'dbPort' => $dbPort,
            'dbName' => $dbName,
            'dbUser' => $dbUser,
            'dbPass' => empty($dbPass) ? 'EMPTY' : 'SET'
        ]
    ]);
}
?>
