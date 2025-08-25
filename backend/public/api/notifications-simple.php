<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);

    // Debug logging
    error_log("Simple Notifications API Debug - Method: $method, Path: $path, Endpoint: $endpoint, PathParts: " . json_encode($pathParts));

    switch ($method) {
        case 'GET':
            switch ($endpoint) {
                case 'test':
                    // Simple test endpoint
                    echo json_encode([
                        'success' => true,
                        'message' => 'Simple notifications API test endpoint working',
                        'timestamp' => date('Y-m-d H:i:s'),
                        'debug' => [
                            'method' => $method,
                            'path' => $path,
                            'endpoint' => $endpoint,
                            'pathParts' => $pathParts
                        ]
                    ]);
                    break;

                default:
                    http_response_code(404);
                    echo json_encode([
                        'error' => 'Endpoint not found',
                        'debug' => [
                            'method' => $method,
                            'path' => $path,
                            'endpoint' => $endpoint,
                            'pathParts' => $pathParts
                        ]
                    ]);
                    break;
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
