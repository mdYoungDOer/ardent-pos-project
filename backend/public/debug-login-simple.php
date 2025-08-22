<?php
header('Content-Type: application/json');

try {
    // Step 1: Check environment variables
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';

    $envCheck = [
        'db_host' => $dbHost,
        'db_port' => $dbPort,
        'db_name' => $dbName,
        'db_user' => $dbUser ? '***set***' : '***missing***',
        'db_pass' => $dbPass ? '***set***' : '***missing***',
        'jwt_secret' => $jwtSecret ? '***set***' : '***missing***'
    ];

    // Step 2: Test database connection
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Step 3: Test user lookup
    $stmt = $pdo->prepare("
        SELECT u.*, t.name as tenant_name, t.status as tenant_status
        FROM users u 
        JOIN tenants t ON u.tenant_id = t.id 
        WHERE u.email = ? AND u.status = 'active'
    ");
    $stmt->execute(['deyoungdoer@gmail.com']);
    $user = $stmt->fetch();

    // Step 4: Test password verification
    $passwordValid = false;
    if ($user) {
        $passwordValid = password_verify('@am171293GH!!', $user['password_hash']);
    }

    // Step 5: Test JWT library
    $jwtAvailable = false;
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        $jwtAvailable = class_exists('Firebase\JWT\JWT');
    } catch (Exception $e) {
        $jwtAvailable = false;
    }

    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => $envCheck,
        'database_connection' => 'success',
        'user_found' => $user ? true : false,
        'user_id' => $user ? $user['id'] : null,
        'password_valid' => $passwordValid,
        'jwt_available' => $jwtAvailable,
        'tenant_status' => $user ? $user['tenant_status'] : null
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
