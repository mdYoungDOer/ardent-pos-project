<?php
// Test database connection with correct credentials

header('Content-Type: application/json');

try {
    // Database credentials from Digital Ocean App Platform
    $host = 'db-postgresql-nyc3-77594-ardent-pos-do-user-24545475-0.g.db.ondigitalocean.com';
    $port = '25060';
    $database = 'defaultdb';
    $username = 'doadmin';
    $password = $_ENV['DB_PASS'] ?? 'password'; // This should be set as a secret in DO App Platform
    
    // Build PostgreSQL connection string
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        $host,
        $port,
        $database
    );

    echo json_encode([
        'success' => true,
        'connection_info' => [
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password_set' => !empty($password) && $password !== 'password'
        ],
        'message' => 'Database connection parameters loaded'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
