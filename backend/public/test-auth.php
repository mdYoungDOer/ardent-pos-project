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

    $test = [
        'timestamp' => date('c'),
        'config' => [
            'jwt_secret' => Config::get('jwt.secret') ? 'set' : 'not_set',
            'jwt_expiry' => Config::get('jwt.expiry'),
            'db_host' => Config::get('db.host'),
            'db_name' => Config::get('db.database')
        ],
        'database_test' => null,
        'user_test' => null,
        'jwt_test' => null
    ];

    // Test database connection
    try {
        $result = Database::query('SELECT 1 as test');
        $test['database_test'] = 'connected';
    } catch (Exception $e) {
        $test['database_test'] = 'error: ' . $e->getMessage();
    }

    // Test user lookup
    try {
        $user = Database::fetch(
            'SELECT u.*, t.name as tenant_name FROM users u 
             JOIN tenants t ON u.tenant_id = t.id 
             WHERE u.email = ? AND u.status = ? AND t.status = ?',
            ['deyoungdoer@gmail.com', 'active', 'active']
        );
        
        if ($user) {
            $test['user_test'] = [
                'found' => true,
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'tenant_name' => $user['tenant_name']
            ];
        } else {
            $test['user_test'] = 'user_not_found';
        }
    } catch (Exception $e) {
        $test['user_test'] = 'error: ' . $e->getMessage();
    }

    // Test JWT generation
    try {
        if ($test['user_test'] && is_array($test['user_test']) && $test['user_test']['found']) {
            $payload = [
                'user_id' => $test['user_test']['id'],
                'tenant_id' => '00000000-0000-0000-0000-000000000000',
                'iat' => time(),
                'exp' => time() + Config::get('jwt.expiry')
            ];
            
            $token = JWT::encode($payload, Config::get('jwt.secret'), 'HS256');
            $test['jwt_test'] = [
                'generated' => true,
                'token_length' => strlen($token)
            ];
        } else {
            $test['jwt_test'] = 'cannot_test_without_user';
        }
    } catch (Exception $e) {
        $test['jwt_test'] = 'error: ' . $e->getMessage();
    }

    echo json_encode($test, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Test failed: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
