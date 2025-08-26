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

// Debug logging
error_log("Super Admin Login Debug - Starting...");

try {
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    error_log("Super Admin Login Debug - Input: " . json_encode($input));

    if (!$input || !isset($input['email']) || !isset($input['password'])) {
        error_log("Super Admin Login Debug - Missing email or password");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Email and password are required'
        ]);
        exit();
    }

    $email = trim($input['email']);
    $password = $input['password'];

    error_log("Super Admin Login Debug - Email: $email");

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("Super Admin Login Debug - Invalid email format");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid email format'
        ]);
        exit();
    }

    // Check environment variables
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';

    error_log("Super Admin Login Debug - DB Host: $dbHost");
    error_log("Super Admin Login Debug - DB Port: $dbPort");
    error_log("Super Admin Login Debug - DB Name: $dbName");
    error_log("Super Admin Login Debug - DB User: $dbUser");
    error_log("Super Admin Login Debug - DB Pass: " . (empty($dbPass) ? 'EMPTY' : 'SET'));
    error_log("Super Admin Login Debug - JWT Secret: " . (empty($jwtSecret) ? 'EMPTY' : 'SET'));

    if (empty($dbUser) || empty($dbPass)) {
        error_log("Super Admin Login Debug - Database credentials not configured");
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database credentials not configured'
        ]);
        exit();
    }

    // Database connection
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    error_log("Super Admin Login Debug - Attempting DB connection with DSN: $dsn");
    
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    error_log("Super Admin Login Debug - Database connection successful");
    
    // Check if user exists and is a super admin
    $stmt = $pdo->prepare("
        SELECT u.*, t.name as tenant_name 
        FROM users u 
        LEFT JOIN tenants t ON u.tenant_id = t.id 
        WHERE u.email = ? AND u.role = 'super_admin' AND u.status = 'active'
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    error_log("Super Admin Login Debug - User query result: " . json_encode($user));

    if (!$user) {
        error_log("Super Admin Login Debug - User not found or not super admin");
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Super admin access denied - user not found or not super admin'
        ]);
        exit();
    }

    // Verify password
    $passwordValid = password_verify($password, $user['password_hash']);
    error_log("Super Admin Login Debug - Password verification result: " . ($passwordValid ? 'TRUE' : 'FALSE'));

    if (!$passwordValid) {
        error_log("Super Admin Login Debug - Invalid password");
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid credentials'
        ]);
        exit();
    }

    error_log("Super Admin Login Debug - Password verified successfully");

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

    error_log("Super Admin Login Debug - JWT token generated successfully");

    // Update last login
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);

    error_log("Super Admin Login Debug - Last login updated");

    // Return success response
    $response = [
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
    ];

    error_log("Super Admin Login Debug - Sending success response");
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Super Admin Login Debug - Exception: " . $e->getMessage());
    error_log("Super Admin Login Debug - Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Login failed: ' . $e->getMessage()
    ]);
}
?>
