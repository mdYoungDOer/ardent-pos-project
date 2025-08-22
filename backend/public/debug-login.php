<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'debug' => 'Starting comprehensive login debug...',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
]);

try {
    // Check 1: Autoload
    echo json_encode(['step' => 'autoload', 'status' => 'checking']);
    require_once __DIR__ . '/../vendor/autoload.php';
    echo json_encode(['step' => 'autoload', 'status' => 'success']);
    
    // Check 2: Environment variables
    echo json_encode(['step' => 'env', 'status' => 'checking']);
    if (class_exists('Dotenv\Dotenv')) {
        $envPath = __DIR__ . '/../';
        if (file_exists($envPath . '.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable($envPath);
            $dotenv->load();
            echo json_encode(['step' => 'env', 'status' => 'loaded from .env']);
        } else {
            echo json_encode(['step' => 'env', 'status' => 'no .env file, using system env']);
        }
    } else {
        echo json_encode(['step' => 'env', 'status' => 'dotenv not available, using system env']);
    }
    
    // Check 3: Database configuration
    echo json_encode(['step' => 'db_config', 'status' => 'checking']);
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_DATABASE'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
    
    echo json_encode([
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
    ]);
    
    // Check 4: Database connection
    echo json_encode(['step' => 'db_connection', 'status' => 'checking']);
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo json_encode(['step' => 'db_connection', 'status' => 'success']);
    
    // Check 5: Test user lookup
    echo json_encode(['step' => 'user_lookup', 'status' => 'checking']);
    $stmt = $pdo->prepare("
        SELECT u.*, t.name as tenant_name 
        FROM users u 
        JOIN tenants t ON u.tenant_id = t.id 
        WHERE u.email = ? AND u.status = 'active' AND t.status = 'active'
    ");
    $stmt->execute(['deyoungdoer@gmail.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo json_encode([
            'step' => 'user_lookup', 
            'status' => 'success',
            'user_found' => true,
            'user_id' => $user['id'],
            'has_password_hash' => !empty($user['password_hash'])
        ]);
    } else {
        echo json_encode(['step' => 'user_lookup', 'status' => 'user_not_found']);
    }
    
    // Check 6: JWT library
    echo json_encode(['step' => 'jwt_library', 'status' => 'checking']);
    if (class_exists('Firebase\JWT\JWT')) {
        echo json_encode(['step' => 'jwt_library', 'status' => 'available']);
    } else {
        echo json_encode(['step' => 'jwt_library', 'status' => 'not_available']);
    }
    
    // Check 7: Test JWT generation
    if ($user && class_exists('Firebase\JWT\JWT')) {
        echo json_encode(['step' => 'jwt_generation', 'status' => 'checking']);
        $payload = [
            'user_id' => $user['id'],
            'tenant_id' => $user['tenant_id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60)
        ];
        
        $token = Firebase\JWT\JWT::encode($payload, $jwtSecret, 'HS256');
        echo json_encode(['step' => 'jwt_generation', 'status' => 'success', 'token_length' => strlen($token)]);
    }
    
    echo json_encode(['step' => 'complete', 'status' => 'all_checks_passed']);
    
} catch (Exception $e) {
    echo json_encode([
        'step' => 'error',
        'status' => 'failed',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
