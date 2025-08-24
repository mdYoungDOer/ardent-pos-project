<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Login debug test',
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'input' => file_get_contents('php://input'),
    'post_data' => $_POST,
    'env_vars' => [
        'DB_HOST' => $_ENV['DB_HOST'] ?? 'not set',
        'DB_PORT' => $_ENV['DB_PORT'] ?? 'not set',
        'DB_NAME' => $_ENV['DB_NAME'] ?? 'not set',
        'DB_USERNAME' => $_ENV['DB_USERNAME'] ? 'set' : 'not set',
        'DB_PASSWORD' => $_ENV['DB_PASSWORD'] ? 'set' : 'not set',
        'JWT_SECRET' => $_ENV['JWT_SECRET'] ? 'set' : 'not set'
    ]
]);
?>
