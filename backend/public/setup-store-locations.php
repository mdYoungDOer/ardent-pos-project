<?php
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

// Set database credentials directly since environment variables are not loading properly
$_ENV['DB_HOST'] = 'db-postgresql-nyc3-77594-ardent-pos-do-user-24545475-0.g.db.ondigitalocean.com';
$_ENV['DB_PORT'] = '25060';
$_ENV['DB_NAME'] = 'defaultdb';
$_ENV['DB_USER'] = 'doadmin';
// Note: DB_PASS should be set as a secret in Digital Ocean App Platform
// If it's still not working, you'll need to set the correct password here temporarily

require_once __DIR__ . '/../vendor/autoload.php';

use ArdentPOS\Core\Config;
use ArdentPOS\Core\Database;

// Initialize configuration and database
Config::init();
Database::init();

header('Content-Type: application/json');

try {
    // Read the SQL file
    $sqlFile = __DIR__ . '/../../db/store_locations.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception('SQL file not found: ' . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);
    
    if (!$sql) {
        throw new Exception('Failed to read SQL file');
    }
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) { return !empty($stmt) && !preg_match('/^--/', $stmt); }
    );
    
    $pdo = Database::getConnection();
    $pdo->beginTransaction();
    
    $executed = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (Exception $e) {
            $errors[] = [
                'statement' => substr($statement, 0, 100) . '...',
                'error' => $e->getMessage()
            ];
        }
    }
    
    if (empty($errors)) {
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Store locations setup completed successfully',
            'executed_statements' => $executed
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Store locations setup failed',
            'executed_statements' => $executed,
            'errors' => $errors
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
