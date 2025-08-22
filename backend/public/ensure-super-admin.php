<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Database configuration
$dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
$dbPort = $_ENV['DB_PORT'] ?? getenv('DB_PORT');
$dbName = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
$dbUser = $_ENV['DB_USER'] ?? getenv('DB_USER');
$dbPass = $_ENV['DB_PASS'] ?? getenv('DB_PASS');

try {
    // Database connection
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Super admin credentials
    $superAdminEmail = 'deyoungdoer@gmail.com';
    $superAdminPassword = '@am171293GH!!';
    $superAdminTenantId = '00000000-0000-0000-0000-000000000000';
    
    // Check if super admin tenant exists
    $stmt = $pdo->prepare("SELECT id FROM tenants WHERE id = ?");
    $stmt->execute([$superAdminTenantId]);
    $tenant = $stmt->fetch();
    
    if (!$tenant) {
        // Create super admin tenant
        $stmt = $pdo->prepare("
            INSERT INTO tenants (id, name, subdomain, plan, status, created_at, updated_at)
            VALUES (?, 'Super Admin', 'admin', 'enterprise', 'active', NOW(), NOW())
        ");
        $stmt->execute([$superAdminTenantId]);
        echo "✅ Super admin tenant created\n";
    } else {
        echo "✅ Super admin tenant already exists\n";
    }
    
    // Check if super admin user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND tenant_id = ?");
    $stmt->execute([$superAdminEmail, $superAdminTenantId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Create super admin user
        $passwordHash = password_hash($superAdminPassword, PASSWORD_DEFAULT);
        $userId = 'f88870bf-b087-4574-8f5d-5889244c4f4e';
        
        $stmt = $pdo->prepare("
            INSERT INTO users (id, tenant_id, email, password_hash, first_name, last_name, role, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, 'DeYoung', 'Doer', 'super_admin', 'active', NOW(), NOW())
        ");
        $stmt->execute([$userId, $superAdminTenantId, $superAdminEmail, $passwordHash]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Super admin user created successfully!',
            'user' => [
                'id' => $userId,
                'email' => $superAdminEmail,
                'role' => 'super_admin',
                'status' => 'active'
            ]
        ]);
    } else {
        // Update existing super admin user password
        $passwordHash = password_hash($superAdminPassword, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password_hash = ?, first_name = 'DeYoung', last_name = 'Doer', role = 'super_admin', status = 'active', updated_at = NOW()
            WHERE email = ? AND tenant_id = ?
        ");
        $stmt->execute([$passwordHash, $superAdminEmail, $superAdminTenantId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Super admin user updated successfully!',
            'user' => [
                'id' => $user['id'],
                'email' => $superAdminEmail,
                'role' => 'super_admin',
                'status' => 'active'
            ]
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
