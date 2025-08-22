<?php
header('Content-Type: application/json');

$secretKey = 'ardent-pos-2024';
$providedKey = $_GET['key'] ?? '';

if ($providedKey !== $secretKey) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid secret key']);
    exit;
}

try {
    // Load environment variables
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';

    // Connect to database
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Check if super admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['deyoungdoer@gmail.com']);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        echo json_encode([
            'success' => true,
            'message' => 'Super admin already exists',
            'user' => [
                'id' => $existingUser['id'],
                'email' => 'deyoungdoer@gmail.com',
                'role' => 'super_admin',
                'status' => 'active'
            ]
        ]);
        exit;
    }

    // Create super admin tenant if it doesn't exist
    $stmt = $pdo->prepare("SELECT id FROM tenants WHERE id = ?");
    $stmt->execute(['00000000-0000-0000-0000-000000000000']);
    $existingTenant = $stmt->fetch();

    if (!$existingTenant) {
        $stmt = $pdo->prepare("
            INSERT INTO tenants (id, name, status, created_at, updated_at)
            VALUES (?, 'Super Admin', 'active', NOW(), NOW())
        ");
        $stmt->execute(['00000000-0000-0000-0000-000000000000']);
    }

    // Create super admin user
    $userId = 'f88870bf-b087-4574-8f5d-5889244c4f4e';
    $passwordHash = password_hash('@am171293GH!!', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users (id, tenant_id, email, password_hash, first_name, last_name, role, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 'super_admin', 'active', NOW(), NOW())
    ");
    $stmt->execute([$userId, '00000000-0000-0000-0000-000000000000', 'deyoungdoer@gmail.com', $passwordHash, 'DeYoung', 'Doer']);

    echo json_encode([
        'success' => true,
        'message' => 'Super admin user created successfully!',
        'user' => [
            'id' => $userId,
            'email' => 'deyoungdoer@gmail.com',
            'role' => 'super_admin',
            'status' => 'active'
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
