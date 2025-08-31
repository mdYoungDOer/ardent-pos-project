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
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
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

    // Database connection using environment variables
    $host = $_ENV['DB_HOST'] ?? '';
    $port = $_ENV['DB_PORT'] ?? '25060';
    $dbname = $_ENV['DB_NAME'] ?? '';
    $user = $_ENV['DB_USER'] ?? $_ENV['DB_USERNAME'] ?? '';
    $password = $_ENV['DB_PASS'] ?? $_ENV['DB_PASSWORD'] ?? '';

    if (empty($host) || empty($dbname) || empty($user) || empty($password)) {
        throw new Exception('Database configuration incomplete');
    }

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    // Extract and validate data
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $firstName = trim($data['first_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');
    $businessName = trim($data['business_name'] ?? '');
    $plan = trim($data['selected_plan'] ?? 'free');

    // Validation
    if (empty($email) || empty($password) || empty($firstName) || empty($lastName) || empty($businessName)) {
        throw new Exception('All fields are required');
    }

    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email already registered']);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Generate unique subdomain
        $subdomain = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $businessName));
        $subdomain = substr($subdomain, 0, 20);
        
        // Ensure subdomain uniqueness
        $stmt = $pdo->prepare("SELECT id FROM tenants WHERE subdomain = ?");
        $stmt->execute([$subdomain]);
        $counter = 1;
        $originalSubdomain = $subdomain;
        while ($stmt->fetch()) {
            $subdomain = $originalSubdomain . $counter;
            $stmt->execute([$subdomain]);
            $counter++;
        }

        // Create tenant
        $stmt = $pdo->prepare("
            INSERT INTO tenants (name, subdomain, plan, status, created_at, updated_at)
            VALUES (?, ?, ?, 'active', NOW(), NOW())
            RETURNING id
        ");
        $stmt->execute([$businessName, $subdomain, $plan]);
        $tenantId = $stmt->fetchColumn();

        // Create user
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (tenant_id, email, password_hash, first_name, last_name, role, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'admin', 'active', NOW(), NOW())
            RETURNING id
        ");
        $stmt->execute([$tenantId, $email, $passwordHash, $firstName, $lastName]);
        $userId = $stmt->fetchColumn();

        // Create subscription
        $amount = $plan === 'free' ? 0 : ($plan === 'starter' ? 120 : ($plan === 'professional' ? 240 : 480));
        
        $stmt = $pdo->prepare("
            INSERT INTO subscriptions (tenant_id, plan, status, amount, currency, billing_cycle, created_at, updated_at)
            VALUES (?, ?, 'active', ?, 'GHS', 'monthly', NOW(), NOW())
        ");
        $stmt->execute([$tenantId, $plan, $amount]);

        $pdo->commit();

        // Generate JWT token for consistency
        $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
        
        // Load JWT library
        $autoloaderPaths = [
            __DIR__ . '/../../vendor/autoload.php',
            __DIR__ . '/../vendor/autoload.php',
            '/var/www/html/vendor/autoload.php',
            '/var/www/html/backend/vendor/autoload.php'
        ];
        
        $autoloaderFound = false;
        foreach ($autoloaderPaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $autoloaderFound = true;
                break;
            }
        }
        
        if ($autoloaderFound) {
            $payload = [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'email' => $email,
                'role' => 'admin',
                'iat' => time(),
                'exp' => time() + (24 * 60 * 60)
            ];
            $token = Firebase\JWT\JWT::encode($payload, $jwtSecret, 'HS256');
        } else {
            // Fallback to base64 token if JWT library not available
            $token = base64_encode(json_encode([
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'email' => $email,
                'role' => 'admin',
                'exp' => time() + (24 * 60 * 60)
            ]));
        }

        // Return success response
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
                'name' => $businessName,
                'subdomain' => $subdomain,
                'plan' => $plan
            ],
            'subscription' => [
                'plan' => $plan,
                'status' => 'active',
                'amount' => $amount,
                'currency' => 'GHS'
            ]
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
