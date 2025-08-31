<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load environment variables
$dbHost = $_ENV['DB_HOST'] ?? 'db-postgresql-nyc3-77594-ardent-pos-do-user-24545475-0.g.db.ondigitalocean.com';
$dbPort = $_ENV['DB_PORT'] ?? '25060';
$dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
$dbUser = $_ENV['DB_USER'] ?? $_ENV['DB_USERNAME'] ?? 'doadmin';
$dbPass = $_ENV['DB_PASS'] ?? $_ENV['DB_PASSWORD'] ?? '';

// Simple authentication check
function checkAuth() {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return false;
    }
    
    $token = $matches[1];
    return !empty($token);
}

try {
    // Connect to database
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Ensure required tables exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            tenant_id UUID,
            subscription_id UUID,
            reference VARCHAR(255) UNIQUE NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            email VARCHAR(255) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            paystack_data JSONB,
            paystack_reference VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    // Remove .php extension if present
    $endpoint = str_replace('.php', '', $endpoint);
    
    switch ($method) {
        case 'GET':
            if ($endpoint === 'subscriptions') {
                handleGetSubscriptions($pdo);
            } elseif ($endpoint === 'invoices') {
                handleGetInvoices($pdo);
            } elseif ($endpoint === 'billing-overview') {
                handleGetBillingOverview($pdo);
            } elseif ($endpoint === 'subscription-plans') {
                handleGetSubscriptionPlans($pdo);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            }
            break;
            
        case 'POST':
            if ($endpoint === 'upgrade-subscription') {
                handleUpgradeSubscription($pdo);
            } elseif ($endpoint === 'cancel-subscription') {
                handleCancelSubscription($pdo);
            } elseif ($endpoint === 'generate-invoice') {
                handleGenerateInvoice($pdo);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Billing management error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}

function handleGetSubscriptions($pdo) {
    if (!checkAuth()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 10;
    $offset = ($page - 1) * $limit;
    
    // Get subscriptions with tenant information
    $sql = "
        SELECT 
            s.*,
            t.name as tenant_name,
            t.subdomain
        FROM subscriptions s
        JOIN tenants t ON s.tenant_id = t.id
        ORDER BY s.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit, $offset]);
    $subscriptions = $stmt->fetchAll();
    
    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM subscriptions");
    $stmt->execute();
    $total = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'subscriptions' => $subscriptions,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]
    ]);
}

function handleGetInvoices($pdo) {
    if (!checkAuth()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 10;
    $offset = ($page - 1) * $limit;
    
    // Get invoices with tenant information
    $sql = "
        SELECT 
            i.*,
            t.name as tenant_name,
            s.plan as subscription_plan
        FROM invoices i
        JOIN tenants t ON i.tenant_id = t.id
        LEFT JOIN subscriptions s ON i.subscription_id = s.id
        ORDER BY i.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit, $offset]);
    $invoices = $stmt->fetchAll();
    
    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM invoices");
    $stmt->execute();
    $total = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'invoices' => $invoices,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]
    ]);
}

function handleGetBillingOverview($pdo) {
    if (!checkAuth()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    // Get total revenue
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(amount), 0) as total_revenue,
            COUNT(*) as total_invoices,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_invoices,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_invoices
        FROM invoices 
        WHERE status = 'paid'
    ");
    $stmt->execute();
    $revenue = $stmt->fetch();
    
    // Get active subscriptions
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as active_subscriptions,
            COUNT(CASE WHEN plan = 'free' THEN 1 END) as free_subscriptions,
            COUNT(CASE WHEN plan != 'free' THEN 1 END) as paid_subscriptions
        FROM subscriptions 
        WHERE status = 'active'
    ");
    $stmt->execute();
    $subscriptions = $stmt->fetch();
    
    // Get monthly revenue
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as monthly_revenue
        FROM invoices 
        WHERE status = 'paid' 
        AND created_at >= DATE_TRUNC('month', CURRENT_DATE)
    ");
    $stmt->execute();
    $monthly = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_revenue' => (float)$revenue['total_revenue'],
            'monthly_revenue' => (float)$monthly['monthly_revenue'],
            'active_subscriptions' => (int)$subscriptions['active_subscriptions'],
            'free_subscriptions' => (int)$subscriptions['free_subscriptions'],
            'paid_subscriptions' => (int)$subscriptions['paid_subscriptions'],
            'total_invoices' => (int)$revenue['total_invoices'],
            'paid_invoices' => (int)$revenue['paid_invoices'],
            'pending_invoices' => (int)$revenue['pending_invoices']
        ]
    ]);
}

function handleGetSubscriptionPlans($pdo) {
    $plans = [
        [
            'id' => 'free',
            'name' => 'Free',
            'description' => 'Perfect for getting started',
            'price' => 0,
            'currency' => 'GHS',
            'billing_cycle' => 'monthly',
            'features' => [
                'Up to 100 products',
                'Basic sales tracking',
                '1 user account',
                'Email support',
                'Mobile app access',
                'Basic reporting'
            ],
            'limitations' => [
                'Limited to 50 transactions/month',
                'No inventory alerts',
                'No customer management',
                'No advanced reporting'
            ]
        ],
        [
            'id' => 'starter',
            'name' => 'Starter',
            'description' => 'Great for small businesses',
            'price' => 120,
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
            ],
            'limitations' => [
                'No multi-location support',
                'Limited integrations'
            ]
        ],
        [
            'id' => 'professional',
            'name' => 'Professional',
            'description' => 'Perfect for growing businesses',
            'price' => 240,
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
            ],
            'limitations' => []
        ],
        [
            'id' => 'enterprise',
            'name' => 'Enterprise',
            'description' => 'For large businesses requiring maximum flexibility',
            'price' => 480,
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
            ],
            'limitations' => []
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $plans
    ]);
}

function handleUpgradeSubscription($pdo) {
    if (!checkAuth()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || empty($data['plan']) || empty($data['tenant_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Plan and tenant_id are required']);
        return;
    }
    
    $plan = $data['plan'];
    $tenantId = $data['tenant_id'];
    $billingCycle = $data['billing_cycle'] ?? 'monthly';
    
    $planDetails = getPlanDetails($plan, $billingCycle);
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Update existing subscription or create new one
        $stmt = $pdo->prepare("
            UPDATE subscriptions 
            SET plan = ?, amount = ?, currency = ?, billing_cycle = ?, status = 'active', updated_at = NOW()
            WHERE tenant_id = ? AND status = 'active'
        ");
        $stmt->execute([$plan, $planDetails['amount'], $planDetails['currency'], $billingCycle, $tenantId]);
        
        if ($stmt->rowCount() === 0) {
            // Create new subscription
            $stmt = $pdo->prepare("
                INSERT INTO subscriptions (tenant_id, plan, status, amount, currency, billing_cycle, created_at, updated_at)
                VALUES (?, ?, 'active', ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$tenantId, $plan, $planDetails['amount'], $planDetails['currency'], $billingCycle]);
        }
        
        // Update tenant plan
        $stmt = $pdo->prepare("
            UPDATE tenants 
            SET plan = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$plan, $tenantId]);
        
        // Generate invoice if not free plan
        if ($plan !== 'free') {
            $invoiceNumber = generateInvoiceNumber();
            $stmt = $pdo->prepare("
                INSERT INTO invoices (tenant_id, invoice_number, amount, currency, status, due_date, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'pending', ?, NOW(), NOW())
            ");
            $stmt->execute([$tenantId, $invoiceNumber, $planDetails['amount'], $planDetails['currency'], date('Y-m-d', strtotime('+7 days'))]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Subscription upgraded successfully',
            'data' => [
                'plan' => $plan,
                'amount' => $planDetails['amount'],
                'currency' => $planDetails['currency']
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleCancelSubscription($pdo) {
    if (!checkAuth()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || empty($data['tenant_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tenant ID is required']);
        return;
    }
    
    $tenantId = $data['tenant_id'];
    
    // Update subscription status
    $stmt = $pdo->prepare("
        UPDATE subscriptions 
        SET status = 'cancelled', updated_at = NOW()
        WHERE tenant_id = ? AND status = 'active'
    ");
    $stmt->execute([$tenantId]);
    
    // Update tenant plan to free
    $stmt = $pdo->prepare("
        UPDATE tenants 
        SET plan = 'free', updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$tenantId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Subscription cancelled successfully'
    ]);
}

function handleGenerateInvoice($pdo) {
    if (!checkAuth()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || empty($data['tenant_id']) || empty($data['amount'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tenant ID and amount are required']);
        return;
    }
    
    $tenantId = $data['tenant_id'];
    $amount = $data['amount'];
    $currency = $data['currency'] ?? 'GHS';
    $description = $data['description'] ?? 'Subscription payment';
    
    $invoiceNumber = generateInvoiceNumber();
    
    $stmt = $pdo->prepare("
        INSERT INTO invoices (tenant_id, invoice_number, amount, currency, status, due_date, created_at, updated_at)
        VALUES (?, ?, ?, ?, 'pending', ?, NOW(), NOW())
    ");
    $stmt->execute([$tenantId, $invoiceNumber, $amount, $currency, date('Y-m-d', strtotime('+7 days'))]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Invoice generated successfully',
        'data' => [
            'invoice_number' => $invoiceNumber,
            'amount' => $amount,
            'currency' => $currency
        ]
    ]);
}

function getPlanDetails($plan, $billingCycle) {
    $plans = [
        'free' => [
            'amount' => 0.00,
            'currency' => 'GHS',
            'billing_cycle' => 'monthly'
        ],
        'starter' => [
            'monthly' => ['amount' => 120.00, 'currency' => 'GHS'],
            'yearly' => ['amount' => 1200.00, 'currency' => 'GHS']
        ],
        'professional' => [
            'monthly' => ['amount' => 240.00, 'currency' => 'GHS'],
            'yearly' => ['amount' => 2400.00, 'currency' => 'GHS']
        ],
        'enterprise' => [
            'monthly' => ['amount' => 480.00, 'currency' => 'GHS'],
            'yearly' => ['amount' => 4800.00, 'currency' => 'GHS']
        ]
    ];
    
    if ($plan === 'free') {
        return $plans['free'];
    }
    
    return $plans[$plan][$billingCycle] ?? $plans[$plan]['monthly'];
}

function generateInvoiceNumber() {
    return 'INV-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
}
?>
