<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Load environment variables
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';

    // Connect to database
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Get all users with their tenant info
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.email,
            u.first_name,
            u.last_name,
            u.role,
            u.status,
            u.created_at,
            t.name as tenant_name,
            t.status as tenant_status
        FROM users u 
        JOIN tenants t ON u.tenant_id = t.id 
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'message' => 'Users list',
        'total_users' => count($users),
        'users' => $users
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
