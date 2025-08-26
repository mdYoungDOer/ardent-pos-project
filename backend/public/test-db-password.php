<?php
// Test database password - this will help identify the correct password

header('Content-Type: application/json');

// Database connection parameters
$host = 'db-postgresql-nyc3-77594-ardent-pos-do-user-24545475-0.g.db.ondigitalocean.com';
$port = '25060';
$database = 'defaultdb';
$username = 'doadmin';

// Common passwords to test
$passwords = [
    $_ENV['DB_PASS'] ?? 'password',
    'password',
    'admin',
    'doadmin',
    'postgres',
    'root',
    '123456',
    ''
];

$results = [];

foreach ($passwords as $password) {
    try {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
            $host,
            $port,
            $database
        );

        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // Test the connection
        $pdo->query('SELECT 1');
        
        $results[] = [
            'password' => $password === '' ? '(empty)' : $password,
            'status' => 'SUCCESS',
            'message' => 'Connection successful'
        ];
        
        // If we get here, we found the correct password
        break;
        
    } catch (Exception $e) {
        $results[] = [
            'password' => $password === '' ? '(empty)' : $password,
            'status' => 'FAILED',
            'message' => $e->getMessage()
        ];
    }
}

echo json_encode([
    'success' => true,
    'connection_tests' => $results,
    'note' => 'Check the results above to find the correct password'
], JSON_PRETTY_PRINT);
?>
