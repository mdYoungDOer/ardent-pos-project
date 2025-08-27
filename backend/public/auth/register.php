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
    // Load environment variables with fallbacks
    $dbHost = $_ENV['DB_HOST'] ?? 'db-postgresql-nyc3-77594-ardent-pos-do-user-24545475-0.g.db.ondigitalocean.com';
    $dbPort = $_ENV['DB_PORT'] ?? '25060';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USER'] ?? $_ENV['DB_USERNAME'] ?? 'doadmin';
    $dbPass = $_ENV['DB_PASS'] ?? $_ENV['DB_PASSWORD'] ?? '';
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';

    // Validate database credentials
    if (empty($dbPass)) {
        throw new Exception('Database password not configured');
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

    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $firstName = trim($data['first_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');
    $businessName = trim($data['business_name'] ?? '');

    if (empty($email) || empty($password) || empty($firstName) || empty($lastName) || empty($businessName)) {
        throw new Exception('All fields are required');
    }

    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters');
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Create tenant
        $tenantId = uniqid('tenant_', true);
        $stmt = $pdo->prepare("
            INSERT INTO tenants (id, name, status, created_at, updated_at)
            VALUES (?, ?, 'active', NOW(), NOW())
        ");
        $stmt->execute([$tenantId, $businessName]);

        // Create user
        $userId = uniqid('user_', true);
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (id, tenant_id, email, password_hash, first_name, last_name, role, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 'admin', 'active', NOW(), NOW())
        ");
        $stmt->execute([$userId, $tenantId, $email, $passwordHash, $firstName, $lastName]);

        $pdo->commit();

        // Load JWT library - try multiple paths
        $autoloaderPaths = [
            __DIR__ . '/../../vendor/autoload.php',
            __DIR__ . '/../vendor/autoload.php',
            '/var/www/html/vendor/autoload.php',
            '/var/www/html/backend/vendor/autoload.php',
            dirname(__DIR__, 2) . '/vendor/autoload.php'
        ];
        
        $autoloaderFound = false;
        foreach ($autoloaderPaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $autoloaderFound = true;
                break;
            }
        }
        
        if (!$autoloaderFound) {
            // Try to find vendor directory
            $vendorPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
            if (file_exists($vendorPath)) {
                require_once $vendorPath;
                $autoloaderFound = true;
            } else {
                throw new Exception('JWT library not found. Vendor directory not accessible.');
            }
        }

        // Generate token
        $payload = [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'email' => $email,
            'role' => 'admin',
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60)
        ];

        $token = Firebase\JWT\JWT::encode($payload, $jwtSecret, 'HS256');

        // Return success
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'token' => $token,
            'user' => [
                'id' => $userId,
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'role' => 'admin'
            ],
            'tenant' => [
                'id' => $tenantId,
                'name' => $businessName
            ]
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
