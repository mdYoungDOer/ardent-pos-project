<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test the exact path that the notifications API should receive
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$endpoint = end($pathParts);

echo json_encode([
    'success' => true,
    'message' => 'Testing notifications API path',
    'debug' => [
        'method' => $method,
        'path' => $path,
        'pathParts' => $pathParts,
        'endpoint' => $endpoint,
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set',
        'count' => count($pathParts)
    ],
    'test' => [
        'expected_for_notifications_test' => [
            'path' => '/api/notifications/test',
            'pathParts' => ['api', 'notifications', 'test'],
            'endpoint' => 'test'
        ]
    ]
]);
?>
