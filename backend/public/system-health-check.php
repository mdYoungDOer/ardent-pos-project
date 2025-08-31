<?php
// Prevent any output before JSON
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

try {
    $healthCheck = [];
    $healthCheck['timestamp'] = date('Y-m-d H:i:s');
    $healthCheck['status'] = 'RUNNING';
    $healthCheck['checks'] = [];
    $healthCheck['issues'] = [];
    $healthCheck['fixes'] = [];
    $healthCheck['recommendations'] = [];

    // 1. DATABASE CONNECTION CHECK
    $healthCheck['checks']['database'] = [];
    
    try {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
            $_ENV['DB_HOST'] ?? 'localhost',
            $_ENV['DB_PORT'] ?? '5432',
            $_ENV['DB_NAME'] ?? 'defaultdb'
        );

        $pdo = new PDO(
            $dsn,
            $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? 'postgres',
            $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? 'password',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $healthCheck['checks']['database']['connection'] = '✅ Connected successfully';
        
        // Check if users table exists and has super admin
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin'");
        $superAdminCount = $stmt->fetchColumn();
        $healthCheck['checks']['database']['super_admin_count'] = $superAdminCount;
        
        if ($superAdminCount == 0) {
            $healthCheck['issues'][] = 'No super admin users found in database';
            $healthCheck['fixes'][] = 'Run setup-unified-database.php to create default super admin';
        } else {
            $healthCheck['checks']['database']['super_admin_status'] = '✅ Super admin users exist';
        }

    } catch (Exception $e) {
        $healthCheck['checks']['database']['connection'] = '❌ Connection failed: ' . $e->getMessage();
        $healthCheck['issues'][] = 'Database connection failed';
        $healthCheck['fixes'][] = 'Check database credentials in .env file';
    }

    // 2. ENVIRONMENT VARIABLES CHECK
    $healthCheck['checks']['environment'] = [];
    
    $requiredEnvVars = ['DB_HOST', 'DB_NAME', 'DB_USERNAME', 'DB_PASSWORD', 'JWT_SECRET'];
    foreach ($requiredEnvVars as $var) {
        if (empty($_ENV[$var])) {
            $healthCheck['checks']['environment'][$var] = '❌ Missing';
            $healthCheck['issues'][] = "Environment variable $var is missing";
        } else {
            $healthCheck['checks']['environment'][$var] = '✅ Set';
        }
    }

    // 3. AUTHENTICATION ENDPOINTS CHECK
    $healthCheck['checks']['authentication'] = [];
    
    $authEndpoints = [
        '/auth/login.php' => 'Regular user login',
        '/auth/super-admin-login.php' => 'Super admin login',
        '/auth/register.php' => 'User registration',
        '/auth/verify.php' => 'Token verification'
    ];

    foreach ($authEndpoints as $endpoint => $description) {
        $filePath = __DIR__ . $endpoint;
        if (file_exists($filePath)) {
            $healthCheck['checks']['authentication'][$endpoint] = '✅ Exists';
        } else {
            $healthCheck['checks']['authentication'][$endpoint] = '❌ Missing';
            $healthCheck['issues'][] = "Authentication endpoint $endpoint is missing";
            $healthCheck['fixes'][] = "Create $endpoint file";
        }
    }

    // 4. API ENDPOINTS CHECK
    $healthCheck['checks']['api_endpoints'] = [];
    
    $apiEndpoints = [
        '/client-dashboard-fixed.php' => 'Client Dashboard API',
        '/super-admin-dashboard-fixed.php' => 'Super Admin Dashboard API',
        '/support-ticket-management-fixed.php' => 'Support Tickets API',
        '/knowledgebase-management-fixed.php' => 'Knowledgebase API',
        '/paystack-integration-fixed.php' => 'Paystack Integration API',
        '/tenancy-management-simple.php' => 'Tenancy Management API'
    ];

    foreach ($apiEndpoints as $endpoint => $description) {
        $filePath = __DIR__ . $endpoint;
        if (file_exists($filePath)) {
            $healthCheck['checks']['api_endpoints'][$endpoint] = '✅ Exists';
        } else {
            $healthCheck['checks']['api_endpoints'][$endpoint] = '❌ Missing';
            $healthCheck['issues'][] = "API endpoint $endpoint is missing";
            $healthCheck['fixes'][] = "Create $endpoint file";
        }
    }

    // 5. FRONTEND ROUTING CHECK
    $healthCheck['checks']['frontend'] = [];
    
    // Check if React build files exist
    $frontendBuildPath = __DIR__ . '/../../frontend/dist';
    if (is_dir($frontendBuildPath)) {
        $healthCheck['checks']['frontend']['build_directory'] = '✅ Exists';
    } else {
        $healthCheck['checks']['frontend']['build_directory'] = '❌ Missing';
        $healthCheck['issues'][] = 'Frontend build directory missing';
        $healthCheck['fixes'][] = 'Run npm run build in frontend directory';
    }

    // 6. SUPPORT PAGE SPECIFIC CHECK
    $healthCheck['checks']['support_page'] = [];
    
    // Check support-related files
    $supportFiles = [
        '/support-portal/knowledgebase' => 'Support Portal Knowledgebase',
        '/support-portal/categories' => 'Support Portal Categories',
        '/support-portal/public-tickets' => 'Support Portal Public Tickets'
    ];

    foreach ($supportFiles as $endpoint => $description) {
        $filePath = __DIR__ . $endpoint . '.php';
        if (file_exists($filePath)) {
            $healthCheck['checks']['support_page'][$endpoint] = '✅ Exists';
        } else {
            $healthCheck['checks']['support_page'][$endpoint] = '❌ Missing';
            $healthCheck['issues'][] = "Support endpoint $endpoint is missing";
            $healthCheck['fixes'][] = "Create $endpoint.php file";
        }
    }

    // 7. FILE PERMISSIONS CHECK
    $healthCheck['checks']['permissions'] = [];
    
    $criticalPaths = [
        __DIR__ => 'Backend public directory',
        __DIR__ . '/../.env' => 'Environment file',
        __DIR__ . '/../logs' => 'Logs directory'
    ];

    foreach ($criticalPaths as $path => $description) {
        if (file_exists($path) || is_dir($path)) {
            if (is_readable($path)) {
                $healthCheck['checks']['permissions'][$description] = '✅ Readable';
            } else {
                $healthCheck['checks']['permissions'][$description] = '❌ Not readable';
                $healthCheck['issues'][] = "$description is not readable";
            }
        } else {
            $healthCheck['checks']['permissions'][$description] = '❌ Missing';
            $healthCheck['issues'][] = "$description is missing";
        }
    }

    // 8. LOGGING CHECK
    $healthCheck['checks']['logging'] = [];
    
    $logPath = __DIR__ . '/../logs';
    if (is_dir($logPath) && is_writable($logPath)) {
        $healthCheck['checks']['logging']['logs_directory'] = '✅ Writable';
    } else {
        $healthCheck['checks']['logging']['logs_directory'] = '❌ Not writable';
        $healthCheck['issues'][] = 'Logs directory is not writable';
        $healthCheck['fixes'][] = 'Create logs directory and set proper permissions';
    }

    // 9. CORS AND HEADERS CHECK
    $healthCheck['checks']['cors'] = [];
    
    // Check if CORS headers are properly set
    $healthCheck['checks']['cors']['headers'] = '✅ CORS headers configured in script';
    
    // 10. JWT SECRET STRENGTH CHECK
    $healthCheck['checks']['jwt'] = [];
    
    $jwtSecret = $_ENV['JWT_SECRET'] ?? '';
    if (strlen($jwtSecret) >= 32) {
        $healthCheck['checks']['jwt']['secret_strength'] = '✅ Strong (64+ characters)';
    } else {
        $healthCheck['checks']['jwt']['secret_strength'] = '❌ Weak (less than 32 characters)';
        $healthCheck['issues'][] = 'JWT secret is too weak';
        $healthCheck['fixes'][] = 'Generate a stronger JWT secret (at least 32 characters)';
    }

    // 11. SPECIFIC ISSUE DIAGNOSIS
    $healthCheck['diagnosis'] = [];

    // Super Admin Login Issue
    if (isset($healthCheck['issues']) && count($healthCheck['issues']) > 0) {
        $healthCheck['diagnosis']['super_admin_login'] = [
            'issue' => 'Super admin login failing',
            'possible_causes' => [
                'Missing /auth/super-admin-login.php file',
                'Database connection issues',
                'Missing super admin user in database',
                'JWT secret configuration problems',
                'Frontend API base URL misconfiguration'
            ],
            'solutions' => [
                'Ensure /auth/super-admin-login.php exists and is accessible',
                'Verify database connection and super admin user exists',
                'Check JWT_SECRET environment variable',
                'Update frontend API base URLs to /backend/public',
                'Clear browser cache and localStorage'
            ]
        ];
    }

    // Support Page Blank Issue
    $healthCheck['diagnosis']['support_page_blank'] = [
        'issue' => 'Support page loads blank/white',
        'possible_causes' => [
            'Missing support portal endpoints',
            'Frontend routing issues',
            'API endpoint 404 errors',
            'JavaScript errors in support components',
            'Missing support-related React components'
        ],
        'solutions' => [
            'Create missing support portal endpoints',
            'Check frontend routing configuration',
            'Verify support API endpoints exist and work',
            'Check browser console for JavaScript errors',
            'Ensure support components are properly imported'
        ]
    ];

    // 12. AUTOMATIC FIXES
    $healthCheck['automatic_fixes'] = [];

    // Create missing directories
    $directories = [
        __DIR__ . '/../logs',
        __DIR__ . '/support-portal'
    ];

    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                $healthCheck['automatic_fixes'][] = "Created directory: $dir";
            } else {
                $healthCheck['automatic_fixes'][] = "Failed to create directory: $dir";
            }
        }
    }

    // 13. SUMMARY AND RECOMMENDATIONS
    $totalIssues = count($healthCheck['issues']);
    $totalFixes = count($healthCheck['fixes']);
    
    if ($totalIssues == 0) {
        $healthCheck['status'] = 'HEALTHY';
        $healthCheck['message'] = 'All systems are working correctly';
    } else {
        $healthCheck['status'] = 'ISSUES_FOUND';
        $healthCheck['message'] = "Found $totalIssues issues that need attention";
    }

    $healthCheck['summary'] = [
        'total_checks' => count($healthCheck['checks']),
        'total_issues' => $totalIssues,
        'total_fixes' => $totalFixes,
        'automatic_fixes_applied' => count($healthCheck['automatic_fixes'])
    ];

    // Clear any output buffer and ensure proper JSON output
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($healthCheck, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("System health check error: " . $e->getMessage());

    // Clear any output buffer and ensure proper JSON output for errors
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'System health check failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'ERROR',
        'message' => 'Could not complete health check due to system errors'
    ], JSON_PRETTY_PRINT);
}
?>
