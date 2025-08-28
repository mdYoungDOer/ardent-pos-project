<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Load environment variables
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '5432';
$dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
$dbUser = $_ENV['DB_USERNAME'] ?? '';
$dbPass = $_ENV['DB_PASSWORD'] ?? '';

try {
    // Validate database credentials
    if (empty($dbUser) || empty($dbPass)) {
        throw new Exception('Database credentials not configured');
    }

    // Connect to database
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $results = [];
    $errors = [];

    // Read and execute the support tables SQL
    $sqlFile = __DIR__ . '/../../db/support_tables.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception('Support tables SQL file not found: ' . $sqlFile);
    }

    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) { return !empty($stmt) && !preg_match('/^--/', $stmt); }
    );

    foreach ($statements as $statement) {
        try {
            if (!empty(trim($statement))) {
                $pdo->exec($statement);
                $results[] = "Executed: " . substr($statement, 0, 50) . "...";
            }
        } catch (PDOException $e) {
            $errors[] = "Error executing statement: " . $e->getMessage();
        }
    }

    // Verify tables were created
    $tables = [
        'knowledgebase_categories',
        'knowledgebase', 
        'support_tickets',
        'support_chat_sessions',
        'support_chat_messages',
        'contact_submissions'
    ];

    $verification = [];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            $verification[$table] = "exists with $count records";
        } catch (PDOException $e) {
            $verification[$table] = "error: " . $e->getMessage();
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Support portal tables setup completed',
        'results' => $results,
        'errors' => $errors,
        'verification' => $verification,
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
