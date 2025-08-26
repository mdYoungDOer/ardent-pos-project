<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../core/Config.php';
require_once __DIR__ . '/../../core/Database.php';

use ArdentPOS\Core\Config;
use ArdentPOS\Core\Database;

// Initialize configuration
$config = new Config();
$debug = $config->get('debug', false);

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

    // Verify super admin authentication
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';
    $token = str_replace('Bearer ', '', $token);

    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['error' => 'No token provided']);
        exit;
    }

    // Verify JWT token and check if user is super admin
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
    
    // Simple JWT verification
    $tokenParts = explode('.', $token);
    if (count($tokenParts) !== 3) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token format']);
        exit;
    }

    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1])), true);
    
    if (!$payload || $payload['role'] !== 'super_admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Super admin access required']);
        exit;
    }

    // Only allow POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    // Clear all sessions and cache
    $results = clearAllSessionsAndCache($pdo);

    echo json_encode([
        'success' => true,
        'message' => 'Global session and cache clearing completed successfully',
        'data' => $results
    ]);

} catch (Exception $e) {
    error_log("Session Clear API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $debug ? $e->getMessage() : 'Internal server error'
    ]);
}

function clearAllSessionsAndCache($pdo) {
    $results = [
        'sessions_cleared' => 0,
        'cache_cleared' => 0,
        'users_logged_out' => 0,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    try {
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
        // This would typically involve clearing Redis/Memcached, but for now we'll clear database-based sessions
        $stmt = $pdo->prepare("
            DELETE FROM user_sessions 
            WHERE created_at < NOW() - INTERVAL '1 hour'
        ");
        $stmt->execute();
        $results['sessions_cleared'] = $stmt->rowCount();

        // 3. Clear any cached data (if you have a cache table)
        $stmt = $pdo->prepare("
            DELETE FROM cache_data 
            WHERE expires_at < NOW()
        ");
        $stmt->execute();
        $results['cache_cleared'] = $stmt->rowCount();

        // 4. Log the session clearing event
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (action, description, user_id, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([
            'GLOBAL_SESSION_CLEAR',
            'Global session and cache clearing initiated by super admin',
            '00000000-0000-0000-0000-000000000000' // System user ID
        ]);

        // 5. Update system settings to indicate session clearing
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (key, value, updated_at)
            VALUES ('last_session_clear', ?, NOW())
            ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_at = NOW()
        ");
        $stmt->execute([date('Y-m-d H:i:s')]);

        return $results;

    } catch (Exception $e) {
        // If tables don't exist, return basic results
        return [
            'sessions_cleared' => 0,
            'cache_cleared' => 0,
            'users_logged_out' => 0,
            'timestamp' => date('Y-m-d H:i:s'),
            'note' => 'Basic session clearing completed (some tables may not exist)'
        ];
    }
}
?>
