<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Load environment variables
    $dbHost = $_ENV['DB_HOST'] ?? 'db-postgresql-nyc3-77594-ardent-pos-do-user-24545475-0.g.db.ondigitalocean.com';
    $dbPort = $_ENV['DB_PORT'] ?? '25060';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USER'] ?? $_ENV['DB_USERNAME'] ?? 'doadmin';
    $dbPass = $_ENV['DB_PASS'] ?? $_ENV['DB_PASSWORD'] ?? '';

    if (empty($dbPass)) {
        throw new Exception('Database password not configured');
    }

    // Connect to database
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Create tables with proper structure
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

    // Create subscriptions table with flexible plan_id
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

    // Create payments table
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

    // Create indexes for performance
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_tenant_email ON users(tenant_id, email)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_subscriptions_tenant ON subscriptions(tenant_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_invoices_tenant ON invoices(tenant_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_payments_reference ON payments(reference)");

    echo json_encode([
        'success' => true,
        'message' => 'Database setup completed successfully',
        'tables_created' => [
            'tenants',
            'users', 
            'subscriptions',
            'invoices',
            'payments'
        ]
    ]);

} catch (Exception $e) {
    error_log("Database setup error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
