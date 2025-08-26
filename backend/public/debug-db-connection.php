<?php
// Debug database connection and environment variables

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $envContent = file_get_contents(__DIR__ . '/../.env');
    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && strpos($line, '=') !== false && !strpos($line, '#') === 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

header('Content-Type: application/json');

try {
    // Check environment variables
    $envVars = [
        'DB_HOST' => $_ENV['DB_HOST'] ?? 'NOT_SET',
        'DB_PORT' => $_ENV['DB_PORT'] ?? 'NOT_SET',
        'DB_NAME' => $_ENV['DB_NAME'] ?? 'NOT_SET',
        'DB_USER' => $_ENV['DB_USER'] ?? 'NOT_SET',
        'DB_PASS' => $_ENV['DB_PASS'] ?? 'NOT_SET',
    ];
    
    echo json_encode([
        'success' => true,
        'environment_variables' => $envVars,
        'message' => 'Environment variables loaded'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'environment_variables' => $envVars ?? []
    ], JSON_PRETTY_PRINT);
}
?>
