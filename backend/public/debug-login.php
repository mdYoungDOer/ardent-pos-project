<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$debug = [
    'debug' => 'Starting comprehensive login debug...',
    'timestamp' => date('Y-m-d H:i:s'),
    'steps' => []
];

try {
    // Check 1: Autoload
    $debug['steps'][] = ['step' => 'autoload', 'status' => 'checking'];
    require_once __DIR__ . '/../vendor/autoload.php';
    $debug['steps'][] = ['step' => 'autoload', 'status' => 'success'];
    
    // Check 2: Environment variables
    $debug['steps'][] = ['step' => 'env', 'status' => 'checking'];
    if (class_exists('Dotenv\Dotenv')) {
        $envPath = __DIR__ . '/../';
        if (file_exists($envPath . '.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable($envPath);
            $dotenv->load();
            $debug['steps'][] = ['step' => 'env', 'status' => 'loaded from .env'];
        } else {
            $debug['steps'][] = ['step' => 'env', 'status' => 'no .env file, using system env'];
        }
    } else {
        $debug['steps'][] = ['step' => 'env', 'status' => 'dotenv not available, using system env'];
    }
    
    // Check 3: Database configuration
    $debug['steps'][] = ['step' => 'db_config', 'status' => 'checking'];
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_DATABASE'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
    
    $debug['steps'][] = [
        'step' => 'db_config', 
        'status' => 'success',
        'config' => [
            'host' => $dbHost,
            'port' => $dbPort,
            'database' => $dbName,
            'user' => $dbUser ? '***set***' : '***missing***',
            'password' => $dbPass ? '***set***' : '***missing***',
            'jwt_secret' => $jwtSecret ? '***set***' : '***missing***'
        ]
    ];
    
    // Check 4: Database connection
    $debug['steps'][] = ['step' => 'db_connection', 'status' => 'checking'];
    
    if (empty($dbUser) || empty($dbPass)) {
        $debug['steps'][] = [
            'step' => 'db_connection', 
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
        $debug['steps'][] = ['step' => 'db_connection', 'status' => 'success'];
        
        // Check 5: Test user lookup
        $debug['steps'][] = ['step' => 'user_lookup', 'status' => 'checking'];
        $stmt = $pdo->prepare("
            SELECT u.*, t.name as tenant_name 
            FROM users u 
            JOIN tenants t ON u.tenant_id = t.id 
            WHERE u.email = ? AND u.status = 'active' AND t.status = 'active'
        ");
        $stmt->execute(['deyoungdoer@gmail.com']);
        $user = $stmt->fetch();
        
        if ($user) {
            $debug['steps'][] = [
                'step' => 'user_lookup', 
                'status' => 'success',
                'user_found' => true,
                'user_id' => $user['id'],
                'has_password_hash' => !empty($user['password_hash'])
            ];
        } else {
            $debug['steps'][] = ['step' => 'user_lookup', 'status' => 'user_not_found'];
        }
        
        // Check 6: JWT library
        $debug['steps'][] = ['step' => 'jwt_library', 'status' => 'checking'];
        if (class_exists('Firebase\JWT\JWT')) {
            $debug['steps'][] = ['step' => 'jwt_library', 'status' => 'available'];
        } else {
            $debug['steps'][] = ['step' => 'jwt_library', 'status' => 'not_available'];
        }
        
        // Check 7: Test JWT generation
        if ($user && class_exists('Firebase\JWT\JWT')) {
            $debug['steps'][] = ['step' => 'jwt_generation', 'status' => 'checking'];
            $payload = [
                'user_id' => $user['id'],
                'tenant_id' => $user['tenant_id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'iat' => time(),
                'exp' => time() + (24 * 60 * 60)
            ];
            
            $token = Firebase\JWT\JWT::encode($payload, $jwtSecret, 'HS256');
            $debug['steps'][] = ['step' => 'jwt_generation', 'status' => 'success', 'token_length' => strlen($token)];
        }
    }
    
    $debug['steps'][] = ['step' => 'complete', 'status' => 'debug_completed'];
    
} catch (Exception $e) {
    $debug['steps'][] = [
        'step' => 'error',
        'status' => 'failed',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
}

// Output single JSON object
echo json_encode($debug);
