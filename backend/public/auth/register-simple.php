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
    $dbHost = $_ENV['DB_HOST'] ?? 'db-postgresql-nyc3-77594-ardent-pos-do-user-24545475-0.g.db.ondigitalocean.com';
    $dbPort = $_ENV['DB_PORT'] ?? '25060';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USER'] ?? $_ENV['DB_USERNAME'] ?? 'doadmin';
    $dbPass = $_ENV['DB_PASS'] ?? $_ENV['DB_PASSWORD'] ?? '';

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

    // Extract data
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $firstName = trim($data['first_name'] ?? '');
    $lastName = trim($data['last_name'] ?? '');
    $businessName = trim($data['business_name'] ?? '');
    $selectedPlan = trim($data['selected_plan'] ?? 'free');

    // Validate required fields
    if (empty($email) || empty($password) || empty($firstName) || empty($lastName) || empty($businessName)) {
        throw new Exception('All fields are required');
    }

    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check if email already exists
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
        
        // Ensure subdomain is unique
        $stmt = $pdo->prepare("SELECT id FROM tenants WHERE subdomain = ?");
        $stmt->execute([$subdomain]);
        $counter = 1;
        $originalSubdomain = $subdomain;
        while ($stmt->fetch()) {
            $subdomain = $originalSubdomain . $counter;
            $stmt->execute([$subdomain]);
            $counter++;
        }

        // Create tenant with simple structure
        $stmt = $pdo->prepare("
            INSERT INTO tenants (name, subdomain, plan, status, created_at, updated_at)
            VALUES (?, ?, ?, 'active', NOW(), NOW())
            RETURNING id
        ");
        $stmt->execute([$businessName, $subdomain, $selectedPlan]);
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

        // Create subscription with minimal required fields
        $planDetails = getPlanDetails($selectedPlan);
        
        // Try to insert subscription with minimal fields first
        try {
            $stmt = $pdo->prepare("
                INSERT INTO subscriptions (tenant_id, plan, status, amount, currency, billing_cycle, created_at, updated_at)
                VALUES (?, ?, 'active', ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$tenantId, $selectedPlan, $planDetails['amount'], $planDetails['currency'], $planDetails['billing_cycle']]);
        } catch (Exception $e) {
            // If that fails, try with plan_id as null
            $stmt = $pdo->prepare("
                INSERT INTO subscriptions (tenant_id, plan, plan_id, status, amount, currency, billing_cycle, created_at, updated_at)
                VALUES (?, ?, NULL, 'active', ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$tenantId, $selectedPlan, $planDetails['amount'], $planDetails['currency'], $planDetails['billing_cycle']]);
        }

        // Create invoice if not free plan
        if ($selectedPlan !== 'free') {
            $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO invoices (tenant_id, invoice_number, amount, currency, status, due_date, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 'pending', ?, NOW(), NOW())
                ");
                $stmt->execute([$tenantId, $invoiceNumber, $planDetails['amount'], $planDetails['currency'], date('Y-m-d', strtotime('+7 days'))]);
            } catch (Exception $e) {
                // If invoice creation fails, continue anyway
                error_log("Invoice creation failed: " . $e->getMessage());
            }
        }

        $pdo->commit();

        // Generate simple token
        $token = base64_encode(json_encode([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'email' => $email,
            'role' => 'admin',
            'exp' => time() + (24 * 60 * 60)
        ]));

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
                'name' => $businessName,
                'subdomain' => $subdomain,
                'plan' => $selectedPlan
            ],
            'subscription' => [
                'plan' => $selectedPlan,
                'status' => 'active',
                'amount' => $planDetails['amount'],
                'currency' => $planDetails['currency']
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

function getPlanDetails($plan) {
    $plans = [
        'free' => [
            'amount' => 0.00,
            'currency' => 'GHS',
            'billing_cycle' => 'monthly'
        ],
        'starter' => [
            'amount' => 120.00,
            'currency' => 'GHS',
            'billing_cycle' => 'monthly'
        ],
        'professional' => [
            'amount' => 240.00,
            'currency' => 'GHS',
            'billing_cycle' => 'monthly'
        ],
        'enterprise' => [
            'amount' => 480.00,
            'currency' => 'GHS',
            'billing_cycle' => 'monthly'
        ]
    ];
    
    return $plans[$plan] ?? $plans['free'];
}
?>
