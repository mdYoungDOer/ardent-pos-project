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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Ramsey\Uuid\Uuid;

try {
    // Load environment variables
    if (class_exists('Dotenv\Dotenv')) {
        $envPath = __DIR__ . '/../../';
        if (file_exists($envPath . '.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable($envPath);
            $dotenv->load();
        }
    }

    // Get database configuration
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_DATABASE'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';

    // Read request body
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!$input || empty($input['email']) || empty($input['password']) || 
        empty($input['first_name']) || empty($input['last_name']) || 
        empty($input['business_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields are required']);
        exit;
    }

    // Validate email
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email address']);
        exit;
    }

    // Validate password
    if (strlen($input['password']) < 8) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 8 characters']);
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
    $stmt->execute([$input['email']]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Create tenant
        $tenantId = Uuid::uuid4()->toString();
        $stmt = $pdo->prepare("
            INSERT INTO tenants (id, name, subdomain, plan, status, created_at, updated_at) 
            VALUES (?, ?, ?, 'free', 'active', NOW(), NOW())
        ");
        $stmt->execute([$tenantId, $input['business_name'], strtolower(str_replace(' ', '-', $input['business_name']))]);

        // Create user
        $userId = Uuid::uuid4()->toString();
        $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (id, tenant_id, email, password_hash, first_name, last_name, role, status, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'admin', 'active', NOW(), NOW())
        ");
        $stmt->execute([$userId, $tenantId, $input['email'], $hashedPassword, $input['first_name'], $input['last_name']]);

        // Commit transaction
        $pdo->commit();

        // Generate JWT token
        $payload = [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'email' => $input['email'],
            'role' => 'admin',
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ];

        $token = JWT::encode($payload, $jwtSecret, 'HS256');

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'token' => $token,
            'user' => [
                'id' => $userId,
                'email' => $input['email'],
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'role' => 'admin'
            ],
            'tenant' => [
                'id' => $tenantId,
                'name' => $input['business_name']
            ]
        ]);

    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
