<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use ArdentPOS\Core\Router;
use ArdentPOS\Core\Database;
use ArdentPOS\Core\Config;
use ArdentPOS\Middleware\CorsMiddleware;
use ArdentPOS\Middleware\AuthMiddleware;
use ArdentPOS\Middleware\TenantMiddleware;

// Load environment variables from system (Digital Ocean App Platform)
// No need to check for .env file as Digital Ocean uses system environment variables
if (class_exists('Dotenv\Dotenv')) {
    $envPath = __DIR__ . '/../../';
    if (file_exists($envPath . '.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable($envPath);
        $dotenv->load();
    }
}

// Set error reporting based on environment
$debug = filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
error_reporting($debug ? E_ALL : 0);
ini_set('display_errors', $debug ? '1' : '0');

// Set content type
header('Content-Type: application/json');

try {
    // Initialize configuration
    Config::init();
    
    // Initialize database connection
    Database::init();
    
    // Create router instance
    $router = new Router();
    
    // Apply CORS middleware
    CorsMiddleware::handle();
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    // Get request method and URI
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = str_replace('/api', '', $uri);
    
    // Debug logging
    error_log("API Request: $method $uri");
    $requestBody = file_get_contents('php://input');
    error_log("Request Body: " . $requestBody);
    
    // Store the request body globally so controllers can access it
    $GLOBALS['REQUEST_BODY'] = $requestBody;
    
    // Public routes (no authentication required)
    $router->post('/auth/register', 'AuthController@register');
    $router->post('/auth/login', 'AuthController@login');
    $router->post('/auth/forgot-password', 'AuthController@forgotPassword');
    $router->post('/auth/reset-password', 'AuthController@resetPassword');
    $router->post('/webhooks/paystack', 'PaystackController@webhook');
    $router->get('/health', 'HealthController@check');
    
    // Support Portal (public access)
    $router->get('/support-portal/knowledgebase', 'SupportPortalController@getKnowledgebase');
    $router->get('/support-portal/categories', 'SupportPortalController@getCategories');
    $router->get('/support-portal/search', 'SupportPortalController@searchKnowledgebase');
    $router->post('/support-portal/chat/session', 'SupportPortalController@createChatSession');
    $router->post('/support-portal/public-tickets', 'SupportPortalController@createPublicTicket');
    $router->get('/support-portal/knowledgebase/{id}', 'SupportPortalController@getKnowledgebaseArticle');
    
    // Paystack configuration (requires auth)
    $router->get('/paystack/config', 'PaystackConfigController@getConfig');
    
    // Protected routes (require authentication)
    $router->group('/auth', function($router) {
        $router->post('/logout', 'AuthController@logout');
        $router->get('/me', 'AuthController@me');
        $router->put('/profile', 'AuthController@updateProfile');
        $router->put('/password', 'AuthController@changePassword');
    }, [AuthMiddleware::class]);
    
    // Tenant-scoped routes
    $router->group('', function($router) {
        // Dashboard
        $router->get('/dashboard', 'DashboardController@index');
        $router->get('/dashboard/stats', 'DashboardController@stats');
        
        // Products
        $router->get('/products', 'ProductController@index');
        $router->post('/products', 'ProductController@store');
        $router->get('/products/{id}', 'ProductController@show');
        $router->put('/products/{id}', 'ProductController@update');
        $router->delete('/products/{id}', 'ProductController@destroy');
        $router->post('/products/import', 'ProductController@import');
        
        // Categories
        $router->get('/categories', 'CategoryController@index');
        $router->post('/categories', 'CategoryController@store');
        $router->put('/categories/{id}', 'CategoryController@update');
        $router->delete('/categories/{id}', 'CategoryController@destroy');
        
        // Inventory
        $router->get('/inventory', 'InventoryController@index');
        $router->post('/inventory/{id}/adjust', 'InventoryController@adjustStock');
        $router->get('/inventory/{id}/history', 'InventoryController@adjustmentHistory');
        $router->get('/inventory/low-stock', 'InventoryController@lowStockReport');
        $router->get('/inventory/valuation', 'InventoryController@stockValuation');
        
        // Sales
        $router->get('/sales', 'SalesController@index');
        $router->post('/sales', 'SalesController@store');
        $router->get('/sales/{id}', 'SalesController@show');
        $router->post('/sales/{id}/refund', 'SalesController@refund');
        $router->get('/sales/daily-summary', 'SalesController@dailySummary');
        
        // Customers
        $router->get('/customers', 'CustomerController@index');
        $router->post('/customers', 'CustomerController@store');
        $router->get('/customers/{id}', 'CustomerController@show');
        $router->put('/customers/{id}', 'CustomerController@update');
        $router->delete('/customers/{id}', 'CustomerController@destroy');
        $router->get('/customers/search', 'CustomerController@search');
        
        // Users (Admin only)
        $router->get('/users', 'UserController@index');
        $router->post('/users', 'UserController@store');
        $router->get('/users/{id}', 'UserController@show');
        $router->put('/users/{id}', 'UserController@update');
        $router->delete('/users/{id}', 'UserController@destroy');
        $router->post('/users/{id}/change-password', 'UserController@changePassword');
        
        // Reports
        $router->get('/reports/sales', 'ReportsController@salesReport');
        $router->get('/reports/inventory', 'ReportsController@inventoryReport');
        $router->get('/reports/customers', 'ReportsController@customerReport');
        $router->get('/reports/profit', 'ReportsController@profitReport');
        $router->get('/reports/export', 'ReportsController@exportReport');
        
        // Settings
        $router->get('/settings', 'SettingsController@index');
        $router->put('/settings', 'SettingsController@update');
        
        // Subscriptions
        $router->get('/subscription', 'SubscriptionController@show');
        $router->post('/subscription/upgrade', 'SubscriptionController@upgrade');
        $router->post('/subscription/cancel', 'SubscriptionController@cancel');
        
        // Notifications
        $router->get('/notifications/settings', 'NotificationController@getNotificationSettings');
        $router->put('/notifications/settings', 'NotificationController@updateNotificationSettings');
        $router->post('/notifications/low-stock-alerts', 'NotificationController@sendLowStockAlerts');
        $router->post('/notifications/sale-receipt/{id}', 'NotificationController@sendSaleReceipt');
        $router->get('/notifications/history', 'NotificationController@getNotificationHistory');
        
        // Support Portal
        $router->get('/support-portal/tickets', 'SupportPortalController@getTickets');
        $router->post('/support-portal/tickets', 'SupportPortalController@createTicket');
        $router->put('/support-portal/tickets/{id}', 'SupportPortalController@updateTicket');
        $router->delete('/support-portal/tickets/{id}', 'SupportPortalController@deleteTicket');
        $router->get('/support-portal/chat', 'SupportPortalController@getChatHistory');
        $router->post('/support-portal/chat', 'SupportPortalController@sendChatMessage');
        
    }, [AuthMiddleware::class, TenantMiddleware::class]);
    
    // Super admin routes
    $router->group('/admin', function($router) {
        $router->get('/tenants', 'Admin\TenantController@index');
        $router->post('/tenants', 'Admin\TenantController@store');
        $router->get('/tenants/{id}', 'Admin\TenantController@show');
        $router->put('/tenants/{id}', 'Admin\TenantController@update');
        $router->delete('/tenants/{id}', 'Admin\TenantController@destroy');
        $router->get('/analytics', 'Admin\AnalyticsController@index');
    }, [AuthMiddleware::class, 'role:super_admin']);
    
    // Dispatch the request
    $router->dispatch($method, $uri);
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("API Error Trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $debug ? $e->getMessage() : 'Something went wrong',
        'trace' => $debug ? $e->getTraceAsString() : null
    ]);
}
