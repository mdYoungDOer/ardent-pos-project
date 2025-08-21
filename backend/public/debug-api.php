<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

use ArdentPOS\Core\Router;
use ArdentPOS\Core\Database;
use ArdentPOS\Core\Config;
use ArdentPOS\Middleware\CorsMiddleware;
use ArdentPOS\Middleware\AuthMiddleware;
use ArdentPOS\Middleware\TenantMiddleware;

// Simulate the exact same request that the frontend makes
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/auth/login';

// Set the input data exactly as the frontend sends it
$input = [
    'email' => 'deyoungdoer@gmail.com',
    'password' => '@am171293GH!!'
];

// Override php://input
$GLOBALS['HTTP_RAW_POST_DATA'] = json_encode($input);

echo json_encode([
    'debug' => 'Starting API debug test...',
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'input' => $input
]);

try {
    echo json_encode(['autoload' => 'Autoload successful']);
    echo json_encode(['classes' => 'Classes loaded']);

    // Load environment variables
    if (class_exists('Dotenv\Dotenv')) {
        $envPath = __DIR__ . '/../';
        if (file_exists($envPath . '.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable($envPath);
            $dotenv->load();
        }
    }

    echo json_encode(['env' => 'Environment loaded']);

    // Initialize configuration
    Config::init();
    echo json_encode(['config' => 'Config initialized']);
    
    // Initialize database connection
    Database::init();
    echo json_encode(['database' => 'Database initialized']);
    
    // Create router instance
    $router = new Router();
    echo json_encode(['router' => 'Router created']);
    
    // Apply CORS middleware
    CorsMiddleware::handle();
    echo json_encode(['cors' => 'CORS middleware applied']);
    
    // Get request method and URI
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = str_replace('/api', '', $uri);
    
    echo json_encode([
        'request' => 'Request processed',
        'method' => $method,
        'uri' => $uri,
        'original_uri' => $_SERVER['REQUEST_URI']
    ]);
    
    // Public routes (no authentication required)
    $router->post('/auth/register', 'AuthController@register');
    $router->post('/auth/login', 'AuthController@login');
    $router->post('/auth/forgot-password', 'AuthController@forgotPassword');
    $router->post('/auth/reset-password', 'AuthController@resetPassword');
    $router->post('/webhooks/paystack', 'PaystackController@webhook');
    $router->get('/health', 'HealthController@check');
    
    echo json_encode(['routes' => 'Routes registered']);
    
    // Dispatch the request
    echo json_encode(['dispatch' => 'About to dispatch request']);
    $router->dispatch($method, $uri);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'API debug failed',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
