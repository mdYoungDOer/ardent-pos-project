<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Test with hardcoded credentials
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        // Get database configuration
        $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
        $dbPort = $_ENV['DB_PORT'] ?? '5432';
        $dbName = $_ENV['DB_DATABASE'] ?? 'defaultdb';
        $dbUser = $_ENV['DB_USERNAME'] ?? '';
        $dbPass = $_ENV['DB_PASSWORD'] ?? '';
        $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
        
        if (empty($dbUser) || empty($dbPass)) {
            echo json_encode([
                'error' => 'Database credentials not configured',
                'solution' => 'Set DB_USERNAME and DB_PASSWORD in Digital Ocean environment variables'
            ]);
            exit;
        }
        
        // Connect to database
        $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Test with hardcoded credentials
        $email = 'deyoungdoer@gmail.com';
        $password = '@am171293GH!!';
        
        // Find user
        $stmt = $pdo->prepare("
            SELECT u.*, t.name as tenant_name 
            FROM users u 
            JOIN tenants t ON u.tenant_id = t.id 
            WHERE u.email = ? AND u.status = 'active' AND t.status = 'active'
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            echo json_encode(['error' => 'Invalid password']);
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
        
        echo json_encode([
            'success' => true,
            'message' => 'Login test successful',
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
        echo json_encode([
            'error' => 'Test failed',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
} else {
    echo json_encode(['error' => 'Method not allowed', 'method' => $_SERVER['REQUEST_METHOD']]);
}
