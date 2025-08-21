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
use Firebase\JWT\Key;

try {
    // Load environment variables
    if (class_exists('Dotenv\Dotenv')) {
        $envPath = __DIR__ . '/../../';
        if (file_exists($envPath . '.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable($envPath);
            $dotenv->load();
        }
    }

    // Get JWT secret
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';

    // Get token from Authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['error' => 'No token provided']);
        exit;
    }

    $token = $matches[1];

    try {
        // Decode and verify token
        $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));
        
        // Get database configuration
        $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
        $dbPort = $_ENV['DB_PORT'] ?? '5432';
        $dbName = $_ENV['DB_DATABASE'] ?? 'defaultdb';
        $dbUser = $_ENV['DB_USERNAME'] ?? '';
        $dbPass = $_ENV['DB_PASSWORD'] ?? '';

        // Connect to database
        $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Get user and tenant information
        $stmt = $pdo->prepare("
            SELECT u.*, t.name as tenant_name 
            FROM users u 
            JOIN tenants t ON u.tenant_id = t.id 
            WHERE u.id = ? AND u.status = 'active' AND t.status = 'active'
        ");
        $stmt->execute([$decoded->user_id]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        // Return user information
        echo json_encode([
            'success' => true,
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
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
    }

} catch (Exception $e) {
    error_log("Token verification error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
