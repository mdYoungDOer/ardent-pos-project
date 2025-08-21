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

$debug = [
    'debug' => 'Starting API debug test...',
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'input' => $input,
    'steps' => []
];

try {
    $debug['steps'][] = ['autoload' => 'Autoload successful'];
    $debug['steps'][] = ['classes' => 'Classes loaded'];

    // Load environment variables
    if (class_exists('Dotenv\Dotenv')) {
        $envPath = __DIR__ . '/../';
        if (file_exists($envPath . '.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable($envPath);
            $dotenv->load();
        }
    }

    $debug['steps'][] = ['env' => 'Environment loaded'];

    // Initialize configuration
    Config::init();
    $debug['steps'][] = ['config' => 'Config initialized'];
    
    // Initialize database connection
    Database::init();
    $debug['steps'][] = ['database' => 'Database initialized'];
    
    // Create router instance
    $router = new Router();
    $debug['steps'][] = ['router' => 'Router created'];
    
    // Apply CORS middleware
    CorsMiddleware::handle();
    $debug['steps'][] = ['cors' => 'CORS middleware applied'];
    
    // Get request method and URI
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = str_replace('/api', '', $uri);
    
    $debug['steps'][] = [
        'request' => 'Request processed',
        'method' => $method,
        'uri' => $uri,
        'original_uri' => $_SERVER['REQUEST_URI']
    ];
    
    // Public routes (no authentication required)
    $router->post('/auth/register', 'AuthController@register');
    $router->post('/auth/login', 'AuthController@login');
    $router->post('/auth/forgot-password', 'AuthController@forgotPassword');
    $router->post('/auth/reset-password', 'AuthController@resetPassword');
    $router->post('/webhooks/paystack', 'PaystackController@webhook');
    $router->get('/health', 'HealthController@check');
    
    $debug['steps'][] = ['routes' => 'Routes registered'];
    
    // Dispatch the request
    $debug['steps'][] = ['dispatch' => 'About to dispatch request'];
    
    // Capture the output from the router
    ob_start();
    $router->dispatch($method, $uri);
    $routerOutput = ob_get_clean();
    
    $debug['steps'][] = ['dispatch' => 'Request dispatched successfully'];
    $debug['router_output'] = $routerOutput;
    
    echo json_encode($debug);
    
} catch (Exception $e) {
    $debug['error'] = 'API debug failed';
    $debug['message'] = $e->getMessage();
    $debug['trace'] = $e->getTraceAsString();
    $debug['file'] = $e->getFile();
    $debug['line'] = $e->getLine();
    
    echo json_encode($debug);
}
