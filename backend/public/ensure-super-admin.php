<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load environment variables - try multiple paths
$envPaths = [
    __DIR__ . '/.env',
    __DIR__ . '/../.env',
    __DIR__ . '/../../.env',
    '/var/www/html/.env',
    '/var/www/html/backend/.env'
];

foreach ($envPaths as $envPath) {
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
                putenv(trim($key) . '=' . trim($value));
            }
        }
        break;
    }
}

// Database configuration - try multiple environment variable names
$dbHost = $_ENV['DB_HOST'] ?? $_ENV['DATABASE_HOST'] ?? getenv('DB_HOST') ?? getenv('DATABASE_HOST') ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? $_ENV['DATABASE_PORT'] ?? getenv('DB_PORT') ?? getenv('DATABASE_PORT') ?? '5432';
$dbName = $_ENV['DB_NAME'] ?? $_ENV['DATABASE_NAME'] ?? getenv('DB_NAME') ?? getenv('DATABASE_NAME') ?? 'defaultdb';
$dbUser = $_ENV['DB_USER'] ?? $_ENV['DATABASE_USER'] ?? $_ENV['DB_USERNAME'] ?? $_ENV['DATABASE_USERNAME'] ?? getenv('DB_USER') ?? getenv('DATABASE_USER') ?? getenv('DB_USERNAME') ?? getenv('DATABASE_USERNAME') ?? '';
$dbPass = $_ENV['DB_PASS'] ?? $_ENV['DATABASE_PASS'] ?? $_ENV['DB_PASSWORD'] ?? $_ENV['DATABASE_PASSWORD'] ?? getenv('DB_PASS') ?? getenv('DATABASE_PASS') ?? getenv('DB_PASSWORD') ?? getenv('DATABASE_PASSWORD') ?? '';

// Debug: Log the database configuration (without password)
error_log("DB Host: " . $dbHost);
error_log("DB Port: " . $dbPort);
error_log("DB Name: " . $dbName);
error_log("DB User: " . $dbUser);
error_log("DB Pass: " . (empty($dbPass) ? 'EMPTY' : 'SET'));

// Validate database credentials
if (empty($dbUser) || empty($dbPass)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database credentials not configured. Please check environment variables.',
        'debug' => [
            'dbHost' => $dbHost,
            'dbPort' => $dbPort,
            'dbName' => $dbName,
            'dbUser' => $dbUser,
            'dbPass' => empty($dbPass) ? 'EMPTY' : 'SET',
            'envVars' => array_keys($_ENV)
        ]
    ]);
    exit();
}

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
        'error' => 'Database error: ' . $e->getMessage(),
        'debug' => [
            'dbHost' => $dbHost,
            'dbPort' => $dbPort,
            'dbName' => $dbName,
            'dbUser' => $dbUser,
            'dbPass' => empty($dbPass) ? 'EMPTY' : 'SET'
        ]
    ]);
}
?>
