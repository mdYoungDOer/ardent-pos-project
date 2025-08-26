<?php
// Simple setup script for store locations
// Run this with: ?password=YOUR_DATABASE_PASSWORD

header('Content-Type: application/json');

$password = $_GET['password'] ?? null;

if (!$password) {
    echo json_encode([
        'success' => false,
        'error' => 'Password required',
        'message' => 'Add ?password=YOUR_PASSWORD to the URL'
    ]);
    exit;
}

try {
    $host = 'db-postgresql-nyc3-77594-ardent-pos-do-user-24545475-0.g.db.ondigitalocean.com';
    $port = '25060';
    $database = 'defaultdb';
    $username = 'doadmin';
    
    $dsn = "pgsql:host=$host;port=$port;dbname=$database;sslmode=require";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $sqlFile = __DIR__ . '/../../db/store_locations.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception('SQL file not found');
    }
    
    $sql = file_get_contents($sqlFile);
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) { return !empty($stmt) && !preg_match('/^--/', $stmt); }
    );
    
    $pdo->beginTransaction();
    $executed = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
    
    if (empty($errors)) {
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Migration completed successfully',
            'executed_statements' => $executed
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Migration failed',
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
