<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

use ArdentPOS\Core\Database;
use ArdentPOS\Core\Config;
use Firebase\JWT\JWT;

// Simulate a real HTTP POST request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/auth/login';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Set the input data
$input = [
    'email' => 'deyoungdoer@gmail.com',
    'password' => '@am171293GH!!'
];

// Create a temporary file to simulate the request body
$tempFile = tmpfile();
fwrite($tempFile, json_encode($input));
rewind($tempFile);

// Override the php://input stream
$GLOBALS['HTTP_RAW_POST_DATA'] = json_encode($input);

// Test the input reading
$rawInput = file_get_contents('php://input');
$decodedInput = json_decode($rawInput, true);

echo json_encode([
    'test' => 'Testing real login simulation',
    'input_data' => $input,
    'raw_input' => $rawInput,
    'decoded_input' => $decodedInput,
    'input_is_valid' => !empty($decodedInput['email']) && !empty($decodedInput['password'])
]);

// If input is valid, proceed with login
if (!empty($decodedInput['email']) && !empty($decodedInput['password'])) {
    echo json_encode(['status' => 'Input is valid, proceeding with login...']);
    
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
        
        // Test the login logic directly
        $user = Database::fetch(
            'SELECT u.*, t.name as tenant_name FROM users u 
             JOIN tenants t ON u.tenant_id = t.id 
             WHERE u.email = ? AND u.status = ? AND t.status = ?',
            [$decodedInput['email'], 'active', 'active']
        );
        
        if (!$user) {
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        
        if (!password_verify($decodedInput['password'], $user['password_hash'])) {
            echo json_encode(['error' => 'Invalid password']);
            exit;
        }
        
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
} else {
    echo json_encode(['error' => 'Input data is not valid']);
}
