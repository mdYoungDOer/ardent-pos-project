<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Load environment variables
    $envFile = __DIR__ . '/../../../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
    }

    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
    $host = $_ENV['DB_HOST'] ?? '';
    $port = $_ENV['DB_PORT'] ?? '25060';
    $dbname = $_ENV['DB_NAME'] ?? '';
    $user = $_ENV['DB_USER'] ?? $_ENV['DB_USERNAME'] ?? '';
    $password = $_ENV['DB_PASS'] ?? $_ENV['DB_PASSWORD'] ?? '';

    if (empty($host) || empty($dbname) || empty($user) || empty($password)) {
        throw new Exception('Database configuration incomplete');
    }

    // Connect to database
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Include unified authentication
    require_once __DIR__ . '/unified-auth.php';
    $auth = new UnifiedAuth($pdo, $jwtSecret);

    // Get request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || empty($data['token'])) {
        throw new Exception('Token is required');
    }

    // Verify token using unified authentication
    $result = $auth->verifyToken($data['token']);

    if (!$result['success']) {
        http_response_code(401);
        echo json_encode($result);
        exit;
    }

    // Return user info
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid token: ' . $e->getMessage()
    ]);
}
?>
