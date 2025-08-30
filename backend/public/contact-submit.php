<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

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
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($input['first_name']) || empty($input['last_name']) || empty($input['email']) || empty($input['subject']) || empty($input['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }
    
    // Get client IP
    $clientIP = $_SERVER['HTTP_CLIENT_IP'] ?? 
                $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
                $_SERVER['HTTP_X_FORWARDED'] ?? 
                $_SERVER['HTTP_FORWARDED_FOR'] ?? 
                $_SERVER['HTTP_FORWARDED'] ?? 
                $_SERVER['REMOTE_ADDR'] ?? 
                '0.0.0.0';
    
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
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
    
    // Ensure contact_submissions table exists
    $sql = "CREATE TABLE IF NOT EXISTS contact_submissions (
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
    
    // Insert the contact submission
    $sql = "INSERT INTO contact_submissions (first_name, last_name, email, company, subject, message, ip_address, user_agent, status, created_at) 
            VALUES (:first_name, :last_name, :email, :company, :subject, :message, :ip_address, :user_agent, 'new', NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'first_name' => $input['first_name'],
        'last_name' => $input['last_name'],
        'email' => $input['email'],
        'company' => $input['company'] ?? null,
        'subject' => $input['subject'],
        'message' => $input['message'],
        'ip_address' => $clientIP,
        'user_agent' => $userAgent
    ]);
    
    // Send success response
    echo json_encode([
        'success' => true, 
        'message' => 'Contact form submitted successfully',
        'submission_id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to submit contact form',
        'debug' => $e->getMessage()
    ]);
}
