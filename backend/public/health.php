<?php
header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'version' => '1.0.0',
    'environment' => $_ENV['APP_ENV'] ?? 'production',
    'checks' => []
];

// Basic system check
$health['checks']['system'] = 'ok';

// Database check (if environment variables are available)
if (isset($_ENV['DB_HOST'])) {
    try {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'] ?? '5432',
            $_ENV['DB_NAME'] ?? 'defaultdb'
        );
        
        $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
        $pdo->query('SELECT 1');
        $health['checks']['database'] = 'ok';
    } catch (Exception $e) {
        $health['checks']['database'] = 'error';
        $health['status'] = 'error';
        $health['database_error'] = $e->getMessage();
    }
} else {
    $health['checks']['database'] = 'skipped';
}

// Configuration check
$health['checks']['config'] = isset($_ENV['APP_URL']) ? 'ok' : 'error';

echo json_encode($health, JSON_PRETTY_PRINT);
