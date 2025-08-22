<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$debug = [];

try {
    // Step 1: Check autoloader
    $debug['step1_autoloader'] = 'checking';
    
    // Try different possible autoloader paths
    $autoloaderPaths = [
        __DIR__ . '/../../vendor/autoload.php',
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/vendor/autoload.php',
        '/var/www/html/vendor/autoload.php',
        '/var/www/html/backend/vendor/autoload.php'
    ];
    
    $autoloaderFound = false;
    foreach ($autoloaderPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $debug['step1_autoloader'] = 'success - found at: ' . $path;
            $autoloaderFound = true;
            break;
        }
    }
    
    if (!$autoloaderFound) {
        $debug['step1_autoloader'] = 'failed - tried paths: ' . implode(', ', $autoloaderPaths);
        throw new Exception('Autoloader not found in any expected location');
    }
    
    // Step 2: Check environment variables
    $debug['step2_env'] = 'checking';
    $envFile = __DIR__ . '/../../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
        $debug['step2_env'] = 'loaded from .env file';
    } else {
        $debug['step2_env'] = 'no .env file, using system env';
    }
    
    // Step 3: Check database configuration
    $debug['step3_db_config'] = 'checking';
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
    
    $debug['step3_db_config'] = [
        'status' => 'loaded',
        'host' => $dbHost,
        'port' => $dbPort,
        'database' => $dbName,
        'user' => $dbUser ? '***set***' : '***missing***',
        'password' => $dbPass ? '***set***' : '***missing***',
        'jwt_secret' => $jwtSecret ? '***set***' : '***missing***'
    ];
    
    // Step 4: Test database connection
    $debug['step4_db_connection'] = 'checking';
    if (empty($dbUser) || empty($dbPass)) {
        $debug['step4_db_connection'] = [
            'status' => 'failed',
            'error' => 'Database credentials not configured',
            'solution' => 'Set DB_USERNAME and DB_PASSWORD in Digital Ocean environment variables'
        ];
    } else {
        $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $debug['step4_db_connection'] = 'success';
    }
    
    // Step 5: Check JWT library
    $debug['step5_jwt_library'] = 'checking';
    if (class_exists('Firebase\JWT\JWT')) {
        $debug['step5_jwt_library'] = 'available';
    } else {
        $debug['step5_jwt_library'] = 'missing';
    }
    
    // Step 6: Test with sample login data
    $debug['step6_sample_login'] = 'checking';
    if (isset($debug['step4_db_connection']) && $debug['step4_db_connection'] === 'success') {
        $email = 'deyoungdoer@gmail.com';
        $stmt = $pdo->prepare("
            SELECT u.*, t.name as tenant_name, t.status as tenant_status
            FROM users u 
            JOIN tenants t ON u.tenant_id = t.id 
            WHERE u.email = ? AND u.status = 'active'
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $debug['step6_sample_login'] = [
                'status' => 'user_found',
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'tenant_name' => $user['tenant_name'],
                'tenant_status' => $user['tenant_status']
            ];
        } else {
            $debug['step6_sample_login'] = 'user_not_found';
        }
    } else {
        $debug['step6_sample_login'] = 'skipped - no db connection';
    }
    
    // Step 7: Test JWT generation
    $debug['step7_jwt_test'] = 'checking';
    if (isset($debug['step5_jwt_library']) && $debug['step5_jwt_library'] === 'available' && 
        isset($debug['step6_sample_login']['status']) && $debug['step6_sample_login']['status'] === 'user_found') {
        
        $payload = [
            'user_id' => $debug['step6_sample_login']['user_id'],
            'tenant_id' => '00000000-0000-0000-0000-000000000000',
            'email' => $debug['step6_sample_login']['email'],
            'role' => $debug['step6_sample_login']['role'],
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60)
        ];
        
        $token = Firebase\JWT\JWT::encode($payload, $jwtSecret, 'HS256');
        $debug['step7_jwt_test'] = [
            'status' => 'success',
            'token_length' => strlen($token)
        ];
    } else {
        $debug['step7_jwt_test'] = 'skipped - missing dependencies';
    }
    
    echo json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'debug' => $debug,
        'overall_status' => 'completed'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'debug' => $debug ?? []
    ]);
}
?>
