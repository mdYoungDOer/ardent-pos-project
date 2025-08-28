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

    // Test database connection
    if (empty($dbUser) || empty($dbPass)) {
        throw new Exception('Database credentials not configured');
    }

    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Test a simple query
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM users');
    $result = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'env_vars' => [
            'DB_HOST' => $dbHost,
            'DB_PORT' => $dbPort,
            'DB_NAME' => $dbName,
            'DB_USERNAME' => $dbUser ? 'SET' : 'NOT_SET',
            'DB_PASSWORD' => $dbPass ? 'SET' : 'NOT_SET'
        ],
        'user_count' => $result['count'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
