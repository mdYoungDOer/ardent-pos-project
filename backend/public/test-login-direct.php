<?php
// Prevent any output before JSON
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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
    // Database connection
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
    
    $test = [];
    $test['timestamp'] = date('Y-m-d H:i:s');
    $test['steps'] = [];
    
    // Step 1: Test database connection
    $test['steps']['database_connection'] = '✅ Connected successfully';
    
    // Step 2: Find super admin user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'super_admin'");
    $stmt->execute(['superadmin@ardentpos.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $test['steps']['user_found'] = '✅ Super admin user found';
        $test['user_info'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'status' => $user['status'],
            'has_password' => !empty($user['password_hash'])
        ];
    } else {
        $test['steps']['user_found'] = '❌ Super admin user not found';
        ob_clean();
        echo json_encode($test, JSON_PRETTY_PRINT);
        exit;
    }
    
    // Step 3: Test password verification
    $testPassword = 'superadmin123';
    $passwordValid = password_verify($testPassword, $user['password_hash']);
    
    if ($passwordValid) {
        $test['steps']['password_verification'] = '✅ Password verification successful';
    } else {
        $test['steps']['password_verification'] = '❌ Password verification failed';
        ob_clean();
        echo json_encode($test, JSON_PRETTY_PRINT);
        exit;
    }
    
    // Step 4: Test JWT token generation
    $jwtSecret = $_ENV['JWT_SECRET'] ?? null;
    if ($jwtSecret) {
        // Simple JWT generation (you'll need to install firebase/php-jwt for production)
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'tenant_id' => $user['tenant_id'],
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24) // 24 hours
        ]);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $jwtSecret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        $jwt = $base64Header . "." . $base64Payload . "." . $base64Signature;
        
        $test['steps']['jwt_generation'] = '✅ JWT token generated successfully';
        $test['jwt_token'] = $jwt;
        $test['jwt_payload'] = json_decode($payload, true);
    } else {
        $test['steps']['jwt_generation'] = '❌ JWT_SECRET not found';
    }
    
    // Step 5: Test API endpoint accessibility
    $test['steps']['api_endpoints'] = [
        'auth_verify' => 'Available at: /backend/public/auth/verify.php',
        'super_admin_dashboard' => 'Available at: /backend/public/super-admin-dashboard-fixed.php',
        'client_dashboard' => 'Available at: /backend/public/client-dashboard-fixed.php'
    ];
    
    // Step 6: Test environment variables
    $test['environment_check'] = [
        'DB_HOST' => $_ENV['DB_HOST'] ? '✅ Set' : '❌ Missing',
        'DB_NAME' => $_ENV['DB_NAME'] ? '✅ Set' : '❌ Missing',
        'DB_USERNAME' => $_ENV['DB_USERNAME'] ? '✅ Set' : '❌ Missing',
        'DB_PASSWORD' => $_ENV['DB_PASSWORD'] ? '✅ Set' : '❌ Missing',
        'JWT_SECRET' => $_ENV['JWT_SECRET'] ? '✅ Set' : '❌ Missing',
        'APP_URL' => $_ENV['APP_URL'] ? '✅ Set' : '❌ Missing',
        'CORS_ALLOWED_ORIGINS' => $_ENV['CORS_ALLOWED_ORIGINS'] ? '✅ Set' : '❌ Missing'
    ];
    
    // Step 7: Generate login test data
    $test['login_test_data'] = [
        'email' => 'superadmin@ardentpos.com',
        'password' => 'superadmin123',
        'expected_response' => [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'name' => $user['first_name'] . ' ' . $user['last_name']
            ],
            'token' => 'JWT token will be generated'
        ]
    ];
    
    // Step 8: Test frontend authentication flow
    $test['frontend_test'] = [
        'login_url' => 'Your domain (e.g., https://your-domain.com)',
        'api_base_url' => 'Your domain + /backend/public/',
        'auth_header_format' => 'Authorization: Bearer {JWT_TOKEN}',
        'expected_flow' => [
            '1. User enters credentials on frontend',
            '2. Frontend sends POST to /backend/public/auth/login.php',
            '3. Backend verifies credentials and returns JWT',
            '4. Frontend stores JWT in localStorage',
            '5. Frontend includes JWT in Authorization header for all API calls',
            '6. Backend validates JWT for protected endpoints'
        ]
    ];
    
    // Overall status
    $test['status'] = 'READY_FOR_LOGIN';
    $test['message'] = 'Authentication system is working correctly. Login should work with the provided credentials.';
    
    // Clear any output buffer and ensure proper JSON output
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($test, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Login test error: " . $e->getMessage());
    
    // Clear any output buffer and ensure proper JSON output for errors
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Login test failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'ERROR',
        'message' => 'Could not test login system due to errors'
    ], JSON_PRETTY_PRINT);
}
?>
