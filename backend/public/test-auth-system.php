<?php
// Comprehensive authentication system test
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$testResults = [
    'timestamp' => date('Y-m-d H:i:s'),
    'system' => 'Ardent POS Authentication System Test',
    'tests' => []
];

try {
    // Test 1: Check if Composer autoloader exists
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        $testResults['tests']['autoloader'] = ['status' => 'PASS', 'message' => 'Composer autoloader loaded successfully'];
    } else {
        $testResults['tests']['autoloader'] = ['status' => 'FAIL', 'message' => 'Composer autoloader not found'];
        throw new Exception('Autoloader not found');
    }

    // Test 2: Check environment variables
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
        $testResults['tests']['env_file'] = ['status' => 'PASS', 'message' => '.env file loaded'];
    } else {
        $testResults['tests']['env_file'] = ['status' => 'INFO', 'message' => 'No .env file, using system environment variables'];
    }

    // Test 3: Check database configuration
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_DATABASE'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';

    $testResults['tests']['db_config'] = [
        'status' => 'INFO',
        'message' => 'Database configuration loaded',
        'details' => [
            'host' => $dbHost,
            'port' => $dbPort,
            'database' => $dbName,
            'user' => $dbUser ? '***set***' : '***missing***',
            'password' => $dbPass ? '***set***' : '***missing***',
            'jwt_secret' => $jwtSecret ? '***set***' : '***missing***'
        ]
    ];

    // Test 4: Check database connection
    if (empty($dbUser) || empty($dbPass)) {
        $testResults['tests']['db_connection'] = [
            'status' => 'FAIL',
            'message' => 'Database credentials not configured',
            'solution' => 'Set DB_USERNAME and DB_PASSWORD in Digital Ocean environment variables'
        ];
    } else {
        try {
            $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            $testResults['tests']['db_connection'] = ['status' => 'PASS', 'message' => 'Database connection successful'];
        } catch (Exception $e) {
            $testResults['tests']['db_connection'] = [
                'status' => 'FAIL',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    // Test 5: Check if JWT library is available
    if (class_exists('Firebase\JWT\JWT')) {
        $testResults['tests']['jwt_library'] = ['status' => 'PASS', 'message' => 'JWT library available'];
    } else {
        $testResults['tests']['jwt_library'] = ['status' => 'FAIL', 'message' => 'JWT library not found'];
    }

    // Test 6: Check if super admin user exists
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE email = ? AND role = 'super_admin'");
            $stmt->execute(['deyoungdoer@gmail.com']);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                $testResults['tests']['super_admin'] = ['status' => 'PASS', 'message' => 'Super admin user exists'];
            } else {
                $testResults['tests']['super_admin'] = ['status' => 'WARN', 'message' => 'Super admin user not found'];
            }
        } catch (Exception $e) {
            $testResults['tests']['super_admin'] = [
                'status' => 'FAIL',
                'message' => 'Error checking super admin: ' . $e->getMessage()
            ];
        }
    }

    // Test 7: Check if auth endpoints exist
    $authEndpoints = [
        'login' => __DIR__ . '/auth/login.php',
        'register' => __DIR__ . '/auth/register.php',
        'verify' => __DIR__ . '/auth/verify.php'
    ];

    foreach ($authEndpoints as $name => $path) {
        if (file_exists($path)) {
            $testResults['tests']["endpoint_$name"] = ['status' => 'PASS', 'message' => "$name endpoint exists"];
        } else {
            $testResults['tests']["endpoint_$name"] = ['status' => 'FAIL', 'message' => "$name endpoint missing"];
        }
    }

    // Test 8: Test JWT generation (if possible)
    if (class_exists('Firebase\JWT\JWT') && $jwtSecret !== 'your-secret-key') {
        try {
            $payload = [
                'user_id' => 'test-user',
                'tenant_id' => 'test-tenant',
                'email' => 'test@example.com',
                'role' => 'test',
                'iat' => time(),
                'exp' => time() + 3600
            ];
            
            $token = Firebase\JWT\JWT::encode($payload, $jwtSecret, 'HS256');
            $decoded = Firebase\JWT\JWT::decode($token, new Firebase\JWT\Key($jwtSecret, 'HS256'));
            
            $testResults['tests']['jwt_generation'] = ['status' => 'PASS', 'message' => 'JWT generation and verification successful'];
        } catch (Exception $e) {
            $testResults['tests']['jwt_generation'] = [
                'status' => 'FAIL',
                'message' => 'JWT generation failed: ' . $e->getMessage()
            ];
        }
    } else {
        $testResults['tests']['jwt_generation'] = ['status' => 'SKIP', 'message' => 'JWT library or secret not available'];
    }

    // Summary
    $passCount = 0;
    $failCount = 0;
    $warnCount = 0;
    $skipCount = 0;

    foreach ($testResults['tests'] as $test) {
        switch ($test['status']) {
            case 'PASS':
                $passCount++;
                break;
            case 'FAIL':
                $failCount++;
                break;
            case 'WARN':
                $warnCount++;
                break;
            case 'SKIP':
                $skipCount++;
                break;
        }
    }

    $testResults['summary'] = [
        'total_tests' => count($testResults['tests']),
        'passed' => $passCount,
        'failed' => $failCount,
        'warnings' => $warnCount,
        'skipped' => $skipCount,
        'overall_status' => $failCount > 0 ? 'FAILED' : ($warnCount > 0 ? 'WARNING' : 'PASSED')
    ];

    if ($failCount > 0) {
        $testResults['recommendations'] = [
            'Fix database credentials if missing',
            'Ensure all auth endpoints are deployed',
            'Check JWT secret configuration',
            'Verify super admin user exists'
        ];
    }

} catch (Exception $e) {
    $testResults['error'] = $e->getMessage();
    $testResults['summary'] = [
        'overall_status' => 'ERROR',
        'message' => 'Test execution failed'
    ];
}

echo json_encode($testResults, JSON_PRETTY_PRINT);
?>
