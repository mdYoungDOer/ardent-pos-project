<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'message' => 'Super Admin API is working correctly',
    'timestamp' => date('Y-m-d H:i:s'),
    'endpoints' => [
        'stats' => '/super-admin.php',
        'analytics' => '/super-admin.php/analytics',
        'tenants' => '/super-admin.php/tenants',
        'users' => '/super-admin.php/users',
        'settings' => '/super-admin.php/settings',
        'activity' => '/super-admin.php/activity',
        'billing' => '/super-admin.php/billing',
        'subscriptions' => '/super-admin.php/subscriptions',
        'health' => '/super-admin.php/health',
        'logs' => '/super-admin.php/logs',
        'audit-logs' => '/super-admin.php/audit-logs',
        'security-events' => '/super-admin.php/security-events',
        'api-keys' => '/super-admin.php/api-keys'
    ]
]);
?>
