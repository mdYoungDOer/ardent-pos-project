<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'message' => 'API test file working',
    'timestamp' => date('Y-m-d H:i:s'),
    'debug' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set'
    ]
]);
?>
