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

    // Ensure required tables exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tenants (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            name VARCHAR(255) NOT NULL,
            subdomain VARCHAR(100) UNIQUE,
            plan VARCHAR(50) DEFAULT 'free',
            status VARCHAR(20) DEFAULT 'active',
            settings JSONB DEFAULT '{}',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            tenant_id UUID NOT NULL,
            email VARCHAR(255) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'admin',
            status VARCHAR(20) DEFAULT 'active',
            last_login TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subscriptions (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            tenant_id UUID NOT NULL,
            plan VARCHAR(50) NOT NULL DEFAULT 'free',
            plan_id UUID,
            status VARCHAR(20) DEFAULT 'active',
            paystack_subscription_code VARCHAR(255),
            paystack_customer_code VARCHAR(255),
            amount DECIMAL(10,2) DEFAULT 0.00,
            currency VARCHAR(3) DEFAULT 'GHS',
            billing_cycle VARCHAR(20) DEFAULT 'monthly',
            next_payment_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS invoices (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            tenant_id UUID NOT NULL,
            subscription_id UUID,
            invoice_number VARCHAR(50) UNIQUE NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'GHS',
            status VARCHAR(20) DEFAULT 'pending',
            paystack_reference VARCHAR(255),
            due_date DATE,
            paid_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

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
    $selectedPlan = trim($data['selected_plan'] ?? 'free');

    if (empty($email) || empty($password) || empty($firstName) || empty($lastName) || empty($businessName)) {
        throw new Exception('All fields are required');
    }

    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters');
    }

    // Validate email format
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

        // Create tenant
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

        // Create initial subscription
        $planDetails = getPlanDetails($selectedPlan);
        $stmt = $pdo->prepare("
            INSERT INTO subscriptions (tenant_id, plan, plan_id, status, amount, currency, billing_cycle, created_at, updated_at)
            VALUES (?, ?, ?, 'active', ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$tenantId, $selectedPlan, null, $planDetails['amount'], $planDetails['currency'], $planDetails['billing_cycle']]);

        // Create initial invoice if not free plan
        if ($selectedPlan !== 'free') {
            $invoiceNumber = generateInvoiceNumber();
            $stmt = $pdo->prepare("
                INSERT INTO invoices (tenant_id, invoice_number, amount, currency, status, due_date, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'pending', ?, NOW(), NOW())
            ");
            $stmt->execute([$tenantId, $invoiceNumber, $planDetails['amount'], $planDetails['currency'], date('Y-m-d', strtotime('+7 days'))]);
        }

        $pdo->commit();

        // Generate simple token (we'll implement proper JWT later)
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
            'billing_cycle' => 'monthly',
            'features' => [
                'Up to 100 products',
                'Basic sales tracking',
                '1 user account',
                'Email support',
                'Mobile app access',
                'Basic reporting'
            ]
        ],
        'starter' => [
            'amount' => 120.00,
            'currency' => 'GHS',
            'billing_cycle' => 'monthly',
            'features' => [
                'Up to 1,000 products',
                'Unlimited transactions',
                'Up to 3 user accounts',
                'Inventory management',
                'Customer database',
                'Low stock alerts',
                'Email notifications',
                'Priority support',
                'Advanced reporting'
            ]
        ],
        'professional' => [
            'amount' => 240.00,
            'currency' => 'GHS',
            'billing_cycle' => 'monthly',
            'features' => [
                'Unlimited products',
                'Unlimited transactions',
                'Up to 10 user accounts',
                'Multi-location support',
                'Advanced inventory',
                'Loyalty programs',
                'Custom reports',
                'API access',
                'Phone support',
                'All integrations'
            ]
        ],
        'enterprise' => [
            'amount' => 480.00,
            'currency' => 'GHS',
            'billing_cycle' => 'monthly',
            'features' => [
                'Unlimited everything',
                'Unlimited users',
                'White-label options',
                'Custom integrations',
                'Dedicated support',
                'Advanced analytics',
                'Custom development',
                'SLA guarantee'
            ]
        ]
    ];
    
    return $plans[$plan] ?? $plans['free'];
}

function generateInvoiceNumber() {
    return 'INV-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
}
?>
