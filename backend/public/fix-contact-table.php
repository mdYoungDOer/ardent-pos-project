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
    
    // Drop the existing table and recreate it with the correct structure
    $pdo->exec("DROP TABLE IF EXISTS contact_submissions CASCADE");
    
    // Create the table with the correct structure
    $sql = "CREATE TABLE contact_submissions (
        id SERIAL PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        company VARCHAR(255),
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        status VARCHAR(20) DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    // Create indexes
    $indexes = [
        "CREATE INDEX idx_contact_submissions_email ON contact_submissions(email)",
        "CREATE INDEX idx_contact_submissions_status ON contact_submissions(status)",
        "CREATE INDEX idx_contact_submissions_created_at ON contact_submissions(created_at)"
    ];
    
    foreach ($indexes as $index) {
        $pdo->exec($index);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Contact submissions table fixed successfully',
        'action' => 'Table dropped and recreated with correct structure'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
