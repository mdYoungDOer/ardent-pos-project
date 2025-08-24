<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
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

    // Get request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    // Check if user exists
    $stmt = $pdo->prepare("
        SELECT u.*, t.name as tenant_name, t.status as tenant_status
        FROM users u 
        JOIN tenants t ON u.tenant_id = t.id 
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode([
            'success' => false,
            'error' => 'User not found',
            'email' => $email,
            'debug' => [
                'user_exists' => false,
                'email_provided' => $email
            ]
        ]);
        exit;
    }

    // Check password
    $passwordValid = password_verify($password, $user['password_hash']);

    echo json_encode([
        'success' => true,
        'message' => 'User credentials check',
        'user_found' => true,
        'password_valid' => $passwordValid,
        'user_info' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role' => $user['role'],
            'status' => $user['status'],
            'tenant_name' => $user['tenant_name'],
            'tenant_status' => $user['tenant_status']
        ],
        'debug' => [
            'password_hash_exists' => !empty($user['password_hash']),
            'password_hash_length' => strlen($user['password_hash']),
            'tenant_active' => $user['tenant_status'] === 'active',
            'user_active' => $user['status'] === 'active'
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
