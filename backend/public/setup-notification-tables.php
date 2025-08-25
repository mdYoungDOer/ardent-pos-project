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

    // SQL statements to create notification tables
    $sqlStatements = [
        // Email logs table
        "CREATE TABLE IF NOT EXISTS email_logs (
            id SERIAL PRIMARY KEY,
            to_email VARCHAR(255) NOT NULL,
            subject VARCHAR(500) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            error_message TEXT,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )" => "Email logs table",

        // Payment logs table
        "CREATE TABLE IF NOT EXISTS payment_logs (
            id SERIAL PRIMARY KEY,
            reference VARCHAR(255) NOT NULL UNIQUE,
            status VARCHAR(50) NOT NULL,
            request_data JSONB,
            response_data JSONB,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )" => "Payment logs table",

        // Notification logs table
        "CREATE TABLE IF NOT EXISTS notification_logs (
            id SERIAL PRIMARY KEY,
            type VARCHAR(100) NOT NULL,
            recipient VARCHAR(255) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            error_message TEXT,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )" => "Notification logs table",

        // Create indexes for better performance
        "CREATE INDEX IF NOT EXISTS idx_email_logs_status ON email_logs(status)" => "Email logs status index",
        "CREATE INDEX IF NOT EXISTS idx_email_logs_sent_at ON email_logs(sent_at)" => "Email logs sent_at index",
        "CREATE INDEX IF NOT EXISTS idx_payment_logs_reference ON payment_logs(reference)" => "Payment logs reference index",
        "CREATE INDEX IF NOT EXISTS idx_payment_logs_status ON payment_logs(status)" => "Payment logs status index",
        "CREATE INDEX IF NOT EXISTS idx_notification_logs_type ON notification_logs(type)" => "Notification logs type index",
        "CREATE INDEX IF NOT EXISTS idx_notification_logs_status ON notification_logs(status)" => "Notification logs status index",
        "CREATE INDEX IF NOT EXISTS idx_notification_logs_sent_at ON notification_logs(sent_at)" => "Notification logs sent_at index"
    ];

    // Execute each SQL statement
    foreach ($sqlStatements as $sql => $description) {
        try {
            $pdo->exec($sql);
            $results[] = [
                'status' => 'success',
                'description' => $description,
                'message' => 'Created successfully'
            ];
        } catch (PDOException $e) {
            $errors[] = [
                'status' => 'error',
                'description' => $description,
                'message' => $e->getMessage()
            ];
        }
    }

    // Check if tables exist
    $tableChecks = [
        'email_logs' => 'Email logs table',
        'payment_logs' => 'Payment logs table',
        'notification_logs' => 'Notification logs table'
    ];

    $tableStatus = [];
    foreach ($tableChecks as $tableName => $description) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_name = '$tableName'");
            $exists = $stmt->fetch()['count'] > 0;
            $tableStatus[] = [
                'table' => $tableName,
                'description' => $description,
                'exists' => $exists,
                'status' => $exists ? 'success' : 'error'
            ];
        } catch (PDOException $e) {
            $tableStatus[] = [
                'table' => $tableName,
                'description' => $description,
                'exists' => false,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    // Test insert into email_logs
    $testInsert = null;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO email_logs (to_email, subject, status, sent_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute(['test@example.com', 'Test Email', 'success']);
        $testInsert = [
            'status' => 'success',
            'message' => 'Test insert successful'
        ];
    } catch (PDOException $e) {
        $testInsert = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }

    // Summary
    $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));
    $errorCount = count($errors);
    $totalCount = count($sqlStatements);

    echo json_encode([
        'success' => true,
        'message' => 'Notification tables setup completed',
        'summary' => [
            'total_statements' => $totalCount,
            'successful' => $successCount,
            'errors' => $errorCount,
            'success_rate' => round(($successCount / $totalCount) * 100, 2) . '%'
        ],
        'results' => $results,
        'errors' => $errors,
        'table_status' => $tableStatus,
        'test_insert' => $testInsert,
        'database_info' => [
            'host' => $dbHost,
            'port' => $dbPort,
            'database' => $dbName,
            'user' => $dbUser,
            'connection_status' => 'connected'
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'database_info' => [
            'host' => $dbHost,
            'port' => $dbPort,
            'database' => $dbName,
            'user' => $dbUser,
            'connection_status' => 'failed'
        ]
    ], JSON_PRETTY_PRINT);
}
?>
