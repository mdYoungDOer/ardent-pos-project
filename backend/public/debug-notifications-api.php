<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'debug' => 'Starting notifications API debug...',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
]);

// Check 1: Environment variables
$checks = [];
$checks[] = [
    'step' => 'env_check',
    'status' => 'checking'
];

$apiKey = $_ENV['SENDGRID_API_KEY'] ?? '';
$fromEmail = $_ENV['SENDGRID_FROM_EMAIL'] ?? 'notify@ardentwebservices.com';

$checks[] = [
    'step' => 'env_check',
    'status' => 'success',
    'data' => [
        'api_key_exists' => !empty($apiKey),
        'api_key_length' => strlen($apiKey),
        'from_email' => $fromEmail,
        'env_vars' => [
            'SENDGRID_API_KEY' => !empty($apiKey) ? 'SET' : 'MISSING',
            'SENDGRID_FROM_EMAIL' => !empty($fromEmail) ? 'SET' : 'MISSING'
        ]
    ]
];

// Check 2: Database connection
$checks[] = [
    'step' => 'db_connection',
    'status' => 'checking'
];

try {
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';

    if (empty($dbUser) || empty($dbPass)) {
        throw new Exception('Database credentials not configured');
    }

    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $checks[] = [
        'step' => 'db_connection',
        'status' => 'success',
        'data' => [
            'host' => $dbHost,
            'port' => $dbPort,
            'database' => $dbName,
            'user' => $dbUser,
            'connection' => 'successful'
        ]
    ];
} catch (Exception $e) {
    $checks[] = [
        'step' => 'db_connection',
        'status' => 'failed',
        'error' => $e->getMessage()
    ];
}

// Check 3: Request parsing
$checks[] = [
    'step' => 'request_parsing',
    'status' => 'checking'
];

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$endpoint = end($pathParts);

$checks[] = [
    'step' => 'request_parsing',
    'status' => 'success',
    'data' => [
        'method' => $method,
        'path' => $path,
        'pathParts' => $pathParts,
        'endpoint' => $endpoint,
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set'
    ]
];

// Check 4: Email function test
$checks[] = [
    'step' => 'email_function',
    'status' => 'checking'
];

function testSendEmail($to, $subject, $htmlContent, $textContent = '') {
    $apiKey = $_ENV['SENDGRID_API_KEY'] ?? '';
    $fromEmail = $_ENV['SENDGRID_FROM_EMAIL'] ?? 'notify@ardentwebservices.com';
    
    // Ensure from email is a valid email address
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $fromEmail = 'notify@ardentwebservices.com';
    }
    
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'SendGrid API key not configured'];
    }
    
    $data = [
        'personalizations' => [
            [
                'to' => [['email' => $to]]
            ]
        ],
        'from' => ['email' => $fromEmail, 'name' => 'Ardent POS'],
        'subject' => $subject,
        'content' => [
            [
                'type' => 'text/html',
                'value' => $htmlContent
            ]
        ]
    ];
    
    // Add text content if provided
    if (!empty($textContent)) {
        $data['content'][] = [
            'type' => 'text/plain',
            'value' => $textContent
        ];
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'message' => 'Email sent successfully'];
    } else {
        return [
            'success' => false, 
            'error' => 'Failed to send email: HTTP ' . $httpCode,
            'response' => $response,
            'curl_error' => $curlError
        ];
    }
}

try {
    $testResult = testSendEmail('test@example.com', 'Test', '<p>Test</p>', 'Test');
    $checks[] = [
        'step' => 'email_function',
        'status' => 'success',
        'data' => $testResult
    ];
} catch (Exception $e) {
    $checks[] = [
        'step' => 'email_function',
        'status' => 'failed',
        'error' => $e->getMessage()
    ];
}

echo json_encode([
    'debug' => 'Notifications API debug completed',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => $checks
], JSON_PRETTY_PRINT);
?>
