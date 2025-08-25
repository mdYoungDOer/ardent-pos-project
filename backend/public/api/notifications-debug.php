<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Simple database connection function
function getDbConnection() {
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';

    if (empty($dbUser) || empty($dbPass)) {
        throw new Exception('Database credentials not configured');
    }

    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    return new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

try {
    echo json_encode([
        'step' => 'starting',
        'message' => 'Notifications API debug started'
    ]);
    
    $pdo = getDbConnection();
    
    echo json_encode([
        'step' => 'database_connected',
        'message' => 'Database connection successful'
    ]);
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);

    echo json_encode([
        'step' => 'request_parsed',
        'message' => 'Request parsed successfully',
        'debug' => [
            'method' => $method,
            'path' => $path,
            'endpoint' => $endpoint,
            'pathParts' => $pathParts
        ]
    ]);

    switch ($method) {
        case 'GET':
            switch ($endpoint) {
                case 'test':
                    echo json_encode([
                        'step' => 'test_endpoint',
                        'message' => 'Test endpoint reached',
                        'success' => true,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                    break;

                default:
                    http_response_code(404);
                    echo json_encode([
                        'step' => 'endpoint_not_found',
                        'error' => 'Endpoint not found',
                        'endpoint' => $endpoint
                    ]);
                    break;
            }
            break;

        default:
            http_response_code(405);
            echo json_encode([
                'step' => 'method_not_allowed',
                'error' => 'Method not allowed',
                'method' => $method
            ]);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'step' => 'error',
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
