<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Simulate the exact path parsing from notifications API
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$endpoint = end($pathParts);

echo json_encode([
    'success' => true,
    'message' => 'Path parsing test',
    'debug' => [
        'method' => $method,
        'path' => $path,
        'pathParts' => $pathParts,
        'endpoint' => $endpoint,
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set',
        'count' => count($pathParts)
    ]
]);
?>
