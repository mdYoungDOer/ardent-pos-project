<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

use ArdentPOS\Core\Database;
use ArdentPOS\Core\Config;
use Firebase\JWT\JWT;

try {
    // Load environment variables
    if (class_exists('Dotenv\Dotenv')) {
        $envPath = __DIR__ . '/../';
        if (file_exists($envPath . '.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable($envPath);
            $dotenv->load();
        }
    }

    // Initialize configuration and database
    Config::init();
    Database::init();

    // Test credentials
    $email = 'deyoungdoer@gmail.com';
    $password = '@am171293GH!!';

    echo json_encode([
        'test' => 'Final login test',
        'email' => $email,
        'password_provided' => !empty($password)
    ]);

    // Test database connection
    $connection = Database::getConnection();
    echo json_encode(['database' => 'Connection successful']);
    
    // Test the login logic directly
    $user = Database::fetch(
        'SELECT u.*, t.name as tenant_name FROM users u 
         JOIN tenants t ON u.tenant_id = t.id 
         WHERE u.email = ? AND u.status = ? AND t.status = ?',
        [$email, 'active', 'active']
    );

    if (!$user) {
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    echo json_encode(['user' => 'User found: ' . $user['email']]);

    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode(['error' => 'Invalid password']);
        exit;
    }

    echo json_encode(['password' => 'Password verified successfully']);

    // Generate token
    $payload = [
        'user_id' => $user['id'],
        'tenant_id' => $user['tenant_id'],
        'iat' => time(),
        'exp' => time() + Config::get('jwt.expiry')
    ];

    $token = JWT::encode($payload, Config::get('jwt.secret'), 'HS256');

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
    http_response_code(500);
    echo json_encode([
        'error' => 'Test failed: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
