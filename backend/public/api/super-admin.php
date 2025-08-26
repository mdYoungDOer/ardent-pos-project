<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the endpoint from the URL
$requestUri = $_SERVER['REQUEST_URI'];
$pathParts = explode('/', trim($requestUri, '/'));
$endpoint = '';

// Find the endpoint after super-admin.php
foreach ($pathParts as $index => $part) {
    if ($part === 'super-admin.php') {
        $endpoint = $pathParts[$index + 1] ?? '';
        break;
    }
}

// Route handling
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        switch ($endpoint) {
            case 'analytics':
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'revenue_30_days' => 125000,
                        'new_users_30_days' => 15,
                        'growth_rate' => 15.5,
                        'active_users' => 85,
                        'last_updated' => date('Y-m-d H:i:s')
                    ]
                ]);
                break;

            case 'tenants':
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'tenants' => [
                            [
                                'id' => '1',
                                'name' => 'Restaurant Chain',
                                'status' => 'active',
                                'created_at' => '2024-01-01 10:00:00',
                                'updated_at' => '2024-01-15 14:30:00'
                            ],
                            [
                                'id' => '2',
                                'name' => 'Tech Solutions Ltd',
                                'status' => 'active',
                                'created_at' => '2024-01-05 09:15:00',
                                'updated_at' => '2024-01-12 16:45:00'
                            ]
                        ],
                        'pagination' => [
                            'page' => 1,
                            'limit' => 10,
                            'total' => 2,
                            'pages' => 1
                        ]
                    ]
                ]);
                break;

            case 'users':
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'users' => [
                            [
                                'id' => '1',
                                'first_name' => 'John',
                                'last_name' => 'Doe',
                                'email' => 'john@restaurant.com',
                                'role' => 'admin',
                                'status' => 'active',
                                'tenant_name' => 'Restaurant Chain',
                                'created_at' => '2024-01-01 10:00:00'
                            ],
                            [
                                'id' => '2',
                                'first_name' => 'Jane',
                                'last_name' => 'Smith',
                                'email' => 'jane@tech.com',
                                'role' => 'manager',
                                'status' => 'active',
                                'tenant_name' => 'Tech Solutions Ltd',
                                'created_at' => '2024-01-05 09:15:00'
                            ]
                        ],
                        'pagination' => [
                            'page' => 1,
                            'limit' => 10,
                            'total' => 2,
                            'pages' => 1
                        ]
                    ]
                ]);
                break;

            case 'settings':
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'general' => [
                            'site_name' => 'Ardent POS',
                            'site_description' => 'Enterprise Point of Sale System',
                            'timezone' => 'UTC',
                            'maintenance_mode' => false
                        ],
                        'email' => [
                            'smtp_host' => '',
                            'smtp_port' => '587',
                            'from_email' => 'noreply@ardentpos.com',
                            'email_verification' => true
                        ],
                        'security' => [
                            'session_timeout' => 3600,
                            'max_login_attempts' => 5,
                            'require_2fa' => false
                        ]
                    ]
                ]);
                break;

            case 'activity':
                echo json_encode([
                    'success' => true,
                    'data' => [
                        [
                            'id' => 1,
                            'type' => 'tenant_created',
                            'message' => 'New tenant "Tech Solutions Ltd" registered',
                            'time' => '2 hours ago',
                            'status' => 'success'
                        ],
                        [
                            'id' => 2,
                            'type' => 'payment_received',
                            'message' => 'Payment received from "Restaurant Chain"',
                            'time' => '4 hours ago',
                            'status' => 'success'
                        ],
                        [
                            'id' => 3,
                            'type' => 'system_alert',
                            'message' => 'System backup completed successfully',
                            'time' => '6 hours ago',
                            'status' => 'success'
                        ]
                    ]
                ]);
                break;

            case 'billing':
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'total_subscriptions' => 25,
                        'active_subscriptions' => 23,
                        'pending_subscriptions' => 2,
                        'cancelled_subscriptions' => 5,
                        'total_revenue' => 1250000,
                        'monthly_revenue' => 125000,
                        'annual_revenue' => 1500000,
                        'churn_rate' => 2.1,
                        'average_revenue_per_user' => 5434.78
                    ]
                ]);
                break;

            case 'subscriptions':
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'subscriptions' => [
                            [
                                'id' => '1',
                                'tenant_name' => 'Restaurant Chain',
                                'plan_name' => 'enterprise',
                                'status' => 'active',
                                'amount' => 480,
                                'currency' => 'GHS',
                                'next_billing_date' => '2024-02-15',
                                'created_at' => '2024-01-01'
                            ],
                            [
                                'id' => '2',
                                'tenant_name' => 'Tech Solutions Ltd',
                                'plan_name' => 'professional',
                                'status' => 'active',
                                'amount' => 240,
                                'currency' => 'GHS',
                                'next_billing_date' => '2024-02-10',
                                'created_at' => '2024-01-05'
                            ]
                        ],
                        'pagination' => [
                            'page' => 1,
                            'limit' => 10,
                            'total' => 2,
                            'pages' => 1
                        ]
                    ]
                ]);
                break;

            case 'subscription-plans':
                echo json_encode([
                    'success' => true,
                    'data' => [
                        [
                            'id' => 'starter',
                            'name' => 'Starter',
                            'description' => 'Perfect for small businesses just getting started',
                            'monthly_price' => 120,
                            'yearly_price' => 1200,
                            'features' => [
                                'Up to 100 products',
                                'Up to 2 users',
                                'Basic reporting',
                                'Email support',
                                'Mobile app access'
                            ]
                        ],
                        [
                            'id' => 'professional',
                            'name' => 'Professional',
                            'description' => 'Ideal for growing businesses with advanced needs',
                            'monthly_price' => 240,
                            'yearly_price' => 2400,
                            'popular' => true,
                            'features' => [
                                'Up to 1,000 products',
                                'Up to 10 users',
                                'Advanced reporting & analytics',
                                'Priority email support',
                                'Mobile app access',
                                'Inventory management',
                                'Customer management',
                                'Multi-location support'
                            ]
                        ],
                        [
                            'id' => 'enterprise',
                            'name' => 'Enterprise',
                            'description' => 'For large businesses requiring maximum flexibility',
                            'monthly_price' => 480,
                            'yearly_price' => 4800,
                            'features' => [
                                'Unlimited products',
                                'Unlimited users',
                                'Advanced reporting & analytics',
                                'Phone & email support',
                                'Mobile app access',
                                'Full inventory management',
                                'Advanced customer management',
                                'Multi-location support',
                                'API access',
                                'Custom integrations',
                                'White-label options'
                            ]
                        ]
                    ]
                ]);
                break;

            case 'health':
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'cpu' => 45,
                        'memory' => 62,
                        'disk' => 38,
                        'network' => 95,
                        'database' => 99.9,
                        'api' => 99.7,
                        'status' => 'healthy',
                        'last_check' => date('Y-m-d H:i:s')
                    ]
                ]);
                break;

            case 'logs':
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'logs' => [
                            [
                                'id' => 1,
                                'level' => 'info',
                                'message' => 'System backup completed successfully',
                                'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                                'user_id' => null
                            ],
                            [
                                'id' => 2,
                                'level' => 'warning',
                                'message' => 'High CPU usage detected',
                                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                                'user_id' => null
                            ]
                        ],
                        'pagination' => [
                            'page' => 1,
                            'limit' => 10,
                            'total' => 2,
                            'pages' => 1
                        ]
                    ]
                ]);
                break;

            case 'audit-logs':
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'audit_logs' => [
                            [
                                'id' => 1,
                                'action' => 'user_login',
                                'user_id' => '550e8400-e29b-41d4-a716-446655440001',
                                'details' => 'User logged in from IP 192.168.1.100',
                                'timestamp' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
                            ],
                            [
                                'id' => 2,
                                'action' => 'settings_updated',
                                'user_id' => '550e8400-e29b-41d4-a716-446655440001',
                                'details' => 'System settings updated',
                                'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour'))
                            ]
                        ],
                        'pagination' => [
                            'page' => 1,
                            'limit' => 10,
                            'total' => 2,
                            'pages' => 1
                        ]
                    ]
                ]);
                break;

            case 'security-events':
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'security_events' => [
                            [
                                'id' => 1,
                                'type' => 'failed_login',
                                'severity' => 'medium',
                                'description' => 'Multiple failed login attempts detected',
                                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                                'ip_address' => '192.168.1.100'
                            ],
                            [
                                'id' => 2,
                                'type' => 'suspicious_activity',
                                'severity' => 'low',
                                'description' => 'Unusual access pattern detected',
                                'timestamp' => date('Y-m-d H:i:s', strtotime('-4 hours')),
                                'ip_address' => '192.168.1.101'
                            ]
                        ],
                        'pagination' => [
                            'page' => 1,
                            'limit' => 10,
                            'total' => 2,
                            'pages' => 1
                        ]
                    ]
                ]);
                break;

            case 'api-keys':
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'api_keys' => [
                            [
                                'id' => 1,
                                'name' => 'Production API Key',
                                'key' => 'pk_live_...',
                                'status' => 'active',
                                'created_at' => '2024-01-01 10:00:00',
                                'last_used' => '2024-01-15 14:30:00'
                            ],
                            [
                                'id' => 2,
                                'name' => 'Development API Key',
                                'key' => 'pk_test_...',
                                'status' => 'active',
                                'created_at' => '2024-01-05 09:15:00',
                                'last_used' => '2024-01-12 16:45:00'
                            ]
                        ],
                        'pagination' => [
                            'page' => 1,
                            'limit' => 10,
                            'total' => 2,
                            'pages' => 1
                        ]
                    ]
                ]);
                break;

            default:
                // Default to stats
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'total_users' => 25,
                        'total_tenants' => 5,
                        'total_products' => 150,
                        'total_sales' => 1250,
                        'system_health' => 'healthy',
                        'last_updated' => date('Y-m-d H:i:s')
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
?>
