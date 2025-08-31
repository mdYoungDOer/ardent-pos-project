<?php
// Prevent any output before JSON
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load environment variables
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

try {
    // Database connection
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_PORT'] ?? '5432',
        $_ENV['DB_NAME'] ?? 'defaultdb'
    );

    $pdo = new PDO(
        $dsn,
        $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? 'postgres',
        $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? 'password',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Create public ticket
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['name']) || !isset($input['email']) || !isset($input['subject']) || !isset($input['message'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Name, email, subject, and message are required'
            ]);
            exit();
        }

        // Validate email
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid email format'
            ]);
            exit();
        }

        // Insert ticket
        $stmt = $pdo->prepare("
            INSERT INTO support_tickets (
                id, name, email, subject, message, priority, status, 
                ticket_type, created_at, updated_at
            ) VALUES (
                uuid_generate_v4(), ?, ?, ?, ?, 'medium', 'open', 
                'public', NOW(), NOW()
            ) RETURNING id
        ");

        $stmt->execute([
            $input['name'],
            $input['email'],
            $input['subject'],
            $input['message']
        ]);

        $ticketId = $stmt->fetchColumn();

        $response = [
            'success' => true,
            'message' => 'Support ticket created successfully',
            'data' => [
                'ticket_id' => $ticketId
            ]
        ];

    } else {
        // Get public tickets (limited info for security)
        $stmt = $pdo->query("
            SELECT 
                id,
                subject,
                priority,
                status,
                created_at,
                updated_at
            FROM support_tickets 
            WHERE ticket_type = 'public'
            ORDER BY created_at DESC
            LIMIT 20
        ");
        
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [
            'success' => true,
            'data' => [
                'tickets' => $tickets,
                'total' => count($tickets)
            ]
        ];
    }

    // Clear any output buffer and ensure proper JSON output
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Support portal public tickets error: " . $e->getMessage());

    // Clear any output buffer and ensure proper JSON output for errors
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Failed to process public ticket: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
