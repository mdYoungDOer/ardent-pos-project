<?php
// Simple, bulletproof registration endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed', 'method' => $_SERVER['REQUEST_METHOD']]);
    exit;
}

try {
    // Load Composer autoloader
    require_once __DIR__ . '/../../vendor/autoload.php';
    
    // Load environment variables
    $envFile = __DIR__ . '/../../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
    }
    
    // Get database configuration
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_DATABASE'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
    
    // Validate database credentials
    if (empty($dbUser) || empty($dbPass)) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Database credentials not configured',
            'message' => 'DB_USERNAME and DB_PASSWORD environment variables are missing',
            'solution' => 'Set these variables in Digital Ocean App Platform environment settings'
        ]);
        exit;
    }
    
    // Get request body
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        exit;
    }
    
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $firstName = $data['first_name'] ?? '';
    $lastName = $data['last_name'] ?? '';
    $businessName = $data['business_name'] ?? '';
    
    // Validate required fields
    if (empty($email) || empty($password) || empty($firstName) || empty($lastName) || empty($businessName)) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields are required: email, password, first_name, last_name, business_name']);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    }
    
    // Validate password strength
    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 8 characters long']);
        exit;
    }
    
    // Connect to database
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Create tenant
        $tenantId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $stmt = $pdo->prepare("
            INSERT INTO tenants (id, name, status, created_at, updated_at)
            VALUES (?, ?, 'active', NOW(), NOW())
        ");
        $stmt->execute([$tenantId, $businessName]);
        
        // Create user
        $userId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (id, tenant_id, email, password_hash, first_name, last_name, role, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 'admin', 'active', NOW(), NOW())
        ");
        $stmt->execute([$userId, $tenantId, $email, $passwordHash, $firstName, $lastName]);
        
        // Commit transaction
        $pdo->commit();
        
        // Generate JWT token
        $payload = [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'email' => $email,
            'role' => 'admin',
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ];
        
        $token = Firebase\JWT\JWT::encode($payload, $jwtSecret, 'HS256');
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'token' => $token,
            'user' => [
                'id' => $userId,
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'role' => 'admin'
            ],
            'tenant' => [
                'id' => $tenantId,
                'name' => $businessName
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Registration failed',
        'message' => $e->getMessage()
    ]);
}
?>
