<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Simple response function
function response($data, $status = 200) {
    http_response_code($status);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// Get endpoint from URL
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$endpoint = end($segments);

// If endpoint is the file name, default to stats
if ($endpoint === 'super-admin.php') {
    $endpoint = 'stats';
}

// Route requests
switch ($endpoint) {
    case 'stats':
        response([
            'total_users' => 25,
            'total_tenants' => 5,
            'total_products' => 150,
            'total_sales' => 1250,
            'system_health' => 'healthy',
            'last_updated' => date('Y-m-d H:i:s')
        ]);
        break;
        
    case 'analytics':
        response([
            'revenue_30_days' => 125000,
            'new_users_30_days' => 15,
            'growth_rate' => 15.5,
            'active_users' => 85,
            'last_updated' => date('Y-m-d H:i:s')
        ]);
        break;
        
    case 'tenants':
        response([
            'tenants' => [
                ['id' => '1', 'name' => 'Restaurant Chain', 'status' => 'active', 'created_at' => '2024-01-01'],
                ['id' => '2', 'name' => 'Tech Solutions Ltd', 'status' => 'active', 'created_at' => '2024-01-05']
            ],
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 2, 'pages' => 1]
        ]);
        break;
        
    case 'users':
        response([
            'users' => [
                ['id' => '1', 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@restaurant.com', 'role' => 'admin', 'status' => 'active'],
                ['id' => '2', 'first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@tech.com', 'role' => 'manager', 'status' => 'active']
            ],
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 2, 'pages' => 1]
        ]);
        break;
        
    case 'settings':
        response([
            'general' => ['site_name' => 'Ardent POS', 'timezone' => 'UTC', 'maintenance_mode' => false],
            'email' => ['smtp_host' => '', 'smtp_port' => '587', 'from_email' => 'noreply@ardentpos.com'],
            'security' => ['session_timeout' => 3600, 'max_login_attempts' => 5, 'require_2fa' => false]
        ]);
        break;
        
    case 'activity':
        response([
            ['id' => 1, 'type' => 'tenant_created', 'message' => 'New tenant registered', 'time' => '2 hours ago'],
            ['id' => 2, 'type' => 'payment_received', 'message' => 'Payment received', 'time' => '4 hours ago']
        ]);
        break;
        
    case 'billing':
        response([
            'total_subscriptions' => 25,
            'active_subscriptions' => 23,
            'total_revenue' => 1250000,
            'monthly_revenue' => 125000
        ]);
        break;
        
    case 'subscriptions':
        response([
            'subscriptions' => [
                ['id' => '1', 'tenant_name' => 'Restaurant Chain', 'plan_name' => 'enterprise', 'status' => 'active'],
                ['id' => '2', 'tenant_name' => 'Tech Solutions Ltd', 'plan_name' => 'professional', 'status' => 'active']
            ],
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 2, 'pages' => 1]
        ]);
        break;
        
    case 'subscription-plans':
        response([
            ['id' => 'starter', 'name' => 'Starter', 'monthly_price' => 120, 'yearly_price' => 1200],
            ['id' => 'professional', 'name' => 'Professional', 'monthly_price' => 240, 'yearly_price' => 2400],
            ['id' => 'enterprise', 'name' => 'Enterprise', 'monthly_price' => 480, 'yearly_price' => 4800]
        ]);
        break;
        
    case 'health':
        response([
            'cpu' => 45, 'memory' => 62, 'disk' => 38, 'network' => 95,
            'database' => 99.9, 'api' => 99.7, 'status' => 'healthy'
        ]);
        break;
        
    case 'logs':
        response([
            'logs' => [
                ['id' => 1, 'level' => 'info', 'message' => 'System backup completed', 'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
                ['id' => 2, 'level' => 'warning', 'message' => 'High CPU usage detected', 'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours'))]
            ],
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 2, 'pages' => 1]
        ]);
        break;
        
    case 'audit-logs':
        response([
            'audit_logs' => [
                ['id' => 1, 'action' => 'user_login', 'details' => 'User logged in', 'timestamp' => date('Y-m-d H:i:s', strtotime('-30 minutes'))],
                ['id' => 2, 'action' => 'settings_updated', 'details' => 'Settings updated', 'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour'))]
            ],
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 2, 'pages' => 1]
        ]);
        break;
        
    case 'security-events':
        response([
            'security_events' => [
                ['id' => 1, 'type' => 'failed_login', 'severity' => 'medium', 'description' => 'Failed login attempts', 'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
                ['id' => 2, 'type' => 'suspicious_activity', 'severity' => 'low', 'description' => 'Unusual access pattern', 'timestamp' => date('Y-m-d H:i:s', strtotime('-4 hours'))]
            ],
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 2, 'pages' => 1]
        ]);
        break;
        
    case 'api-keys':
        response([
            'api_keys' => [
                ['id' => 1, 'name' => 'Production API Key', 'key' => 'pk_live_...', 'status' => 'active'],
                ['id' => 2, 'name' => 'Development API Key', 'key' => 'pk_test_...', 'status' => 'active']
            ],
            'pagination' => ['page' => 1, 'limit' => 10, 'total' => 2, 'pages' => 1]
        ]);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
        break;
}
?>
