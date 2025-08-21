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

    $result = [
        'test' => 'Final login test',
        'email' => $email,
        'password_provided' => !empty($password),
        'steps' => []
    ];

    // Test database connection
    $connection = Database::getConnection();
    $result['steps'][] = ['database' => 'Connection successful'];
    
    // Test the login logic directly
    $user = Database::fetch(
        'SELECT u.*, t.name as tenant_name FROM users u 
         JOIN tenants t ON u.tenant_id = t.id 
         WHERE u.email = ? AND u.status = ? AND t.status = ?',
        [$email, 'active', 'active']
    );

    if (!$user) {
        $result['error'] = 'User not found';
        echo json_encode($result);
        exit;
    }

    $result['steps'][] = ['user' => 'User found: ' . $user['email']];

    if (!password_verify($password, $user['password_hash'])) {
        $result['error'] = 'Invalid password';
        echo json_encode($result);
        exit;
    }

    $result['steps'][] = ['password' => 'Password verified successfully'];

    // Generate token
    $payload = [
        'user_id' => $user['id'],
        'tenant_id' => $user['tenant_id'],
        'iat' => time(),
        'exp' => time() + Config::get('jwt.expiry')
    ];

    $token = JWT::encode($payload, Config::get('jwt.secret'), 'HS256');

    $result['success'] = true;
    $result['message'] = 'Login test successful';
    $result['token'] = $token;
    $result['user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'role' => $user['role']
    ];
    $result['tenant'] = [
        'id' => $user['tenant_id'],
        'name' => $user['tenant_name']
    ];

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Test failed: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
