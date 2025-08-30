<?php
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

// Database configuration
$dbConfig = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '5432',
    'database' => $_ENV['DB_NAME'] ?? 'defaultdb',
    'username' => $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? 'postgres',
    'password' => $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? 'password',
];

try {
    // Create database connection
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['database']
    );
    
    $pdo = new PDO(
        $dsn,
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Check if table exists
    $stmt = $pdo->query("SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'contact_submissions'
    )");
    $tableExists = $stmt->fetchColumn();
    
    if ($tableExists) {
        // Get current table structure
        $stmt = $pdo->query("SELECT column_name, data_type, is_nullable 
                            FROM information_schema.columns 
                            WHERE table_name = 'contact_submissions' 
                            ORDER BY ordinal_position");
        $columns = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'table_exists' => true,
            'columns' => $columns
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'table_exists' => false,
            'message' => 'Table does not exist'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
