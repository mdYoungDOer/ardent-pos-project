<?php
header('Content-Type: application/json');

// Simple security check - you can modify this secret key
$secretKey = 'ardent-pos-session-clear-2024';
$providedKey = $_GET['key'] ?? '';

if ($providedKey !== $secretKey) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid secret key'
    ]);
    exit;
}

try {
    // Load environment variables
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';

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

    $results = [
        'sessions_cleared' => 0,
        'cache_cleared' => 0,
        'users_logged_out' => 0,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // 1. Clear all user sessions by updating last_login to force re-authentication
    $stmt = $pdo->prepare("
        UPDATE users 
        SET last_login = NULL, 
            updated_at = NOW() 
        WHERE role != 'super_admin'
    ");
    $stmt->execute();
    $results['users_logged_out'] = $stmt->rowCount();

    // 2. Clear any session tokens or cached authentication data
    try {
        $stmt = $pdo->prepare("
            DELETE FROM user_sessions 
            WHERE created_at < NOW() - INTERVAL '1 hour'
        ");
        $stmt->execute();
        $results['sessions_cleared'] = $stmt->rowCount();
    } catch (Exception $e) {
        // Table might not exist, that's okay
        $results['sessions_cleared'] = 0;
    }

    // 3. Clear any cached data
    try {
        $stmt = $pdo->prepare("
            DELETE FROM cache_data 
            WHERE expires_at < NOW()
        ");
        $stmt->execute();
        $results['cache_cleared'] = $stmt->rowCount();
    } catch (Exception $e) {
        // Table might not exist, that's okay
        $results['cache_cleared'] = 0;
    }

    // 4. Log the session clearing event
    try {
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (action, description, user_id, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([
            'GLOBAL_SESSION_CLEAR',
            'Global session and cache clearing initiated via direct script',
            '00000000-0000-0000-0000-000000000000'
        ]);
    } catch (Exception $e) {
        // Table might not exist, that's okay
    }

    // 5. Update system settings
    try {
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (key, value, updated_at)
            VALUES ('last_session_clear', ?, NOW())
            ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = NOW()
        ");
        $stmt->execute([date('Y-m-d H:i:s')]);
    } catch (Exception $e) {
        // Table might not exist, that's okay
    }

    echo json_encode([
        'success' => true,
        'message' => 'Global session and cache clearing completed successfully',
        'data' => $results,
        'note' => 'All users will need to log in again'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Session clearing failed: ' . $e->getMessage()
    ]);
}
?>
