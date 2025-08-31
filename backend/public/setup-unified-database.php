<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database configuration
$dbConfig = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '5432',
    'database' => $_ENV['DB_NAME'] ?? 'defaultdb',
    'username' => $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? 'postgres',
    'password' => $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? 'password',
];

try {
    // Create PDO connection
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['database']
    );
    
    $pdo = new PDO(
        $dsn,
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Test connection
    $pdo->query('SELECT 1');
    
    // Enable UUID extension
    $pdo->exec('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
    
    // Create tenants table
    $sql = "CREATE TABLE IF NOT EXISTS tenants (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        name VARCHAR(255) NOT NULL,
        subdomain VARCHAR(100) UNIQUE NOT NULL,
        plan VARCHAR(50) DEFAULT 'free',
        status VARCHAR(20) DEFAULT 'active',
        settings JSONB DEFAULT '{}',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
        email VARCHAR(255) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        role VARCHAR(50) NOT NULL DEFAULT 'cashier',
        status VARCHAR(20) DEFAULT 'active',
        last_login TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(tenant_id, email)
    )";
    $pdo->exec($sql);
    
    // Create categories table
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        color VARCHAR(7) DEFAULT '#e41e5b',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(tenant_id, name)
    )";
    $pdo->exec($sql);
    
    // Create products table
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
        category_id UUID REFERENCES categories(id) ON DELETE SET NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        sku VARCHAR(100),
        barcode VARCHAR(100),
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        cost DECIMAL(10,2) DEFAULT 0.00,
        tax_rate DECIMAL(5,2) DEFAULT 0.00,
        track_inventory BOOLEAN DEFAULT true,
        status VARCHAR(20) DEFAULT 'active',
        image_url VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(tenant_id, sku),
        UNIQUE(tenant_id, barcode)
    )";
    $pdo->exec($sql);
    
    // Create inventory table
    $sql = "CREATE TABLE IF NOT EXISTS inventory (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
        product_id UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
        quantity INTEGER NOT NULL DEFAULT 0,
        min_stock INTEGER DEFAULT 0,
        max_stock INTEGER,
        location VARCHAR(100),
        last_counted TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(tenant_id, product_id)
    )";
    $pdo->exec($sql);
    
    // Create customers table
    $sql = "CREATE TABLE IF NOT EXISTS customers (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255),
        phone VARCHAR(20),
        address TEXT,
        city VARCHAR(100),
        state VARCHAR(100),
        postal_code VARCHAR(20),
        country VARCHAR(100) DEFAULT 'Ghana',
        loyalty_points INTEGER DEFAULT 0,
        total_spent DECIMAL(12,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(tenant_id, email)
    )";
    $pdo->exec($sql);
    
    // Create sales table
    $sql = "CREATE TABLE IF NOT EXISTS sales (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
        user_id UUID NOT NULL REFERENCES users(id),
        customer_id UUID REFERENCES customers(id),
        sale_number VARCHAR(50) NOT NULL,
        subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        discount_amount DECIMAL(12,2) DEFAULT 0.00,
        total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        payment_method VARCHAR(50) NOT NULL,
        payment_status VARCHAR(20) DEFAULT 'pending',
        payment_reference VARCHAR(255),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(tenant_id, sale_number)
    )";
    $pdo->exec($sql);
    
    // Create sale_items table
    $sql = "CREATE TABLE IF NOT EXISTS sale_items (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
        sale_id UUID NOT NULL REFERENCES sales(id) ON DELETE CASCADE,
        product_id UUID NOT NULL REFERENCES products(id),
        quantity INTEGER NOT NULL DEFAULT 1,
        unit_price DECIMAL(10,2) NOT NULL,
        discount_amount DECIMAL(10,2) DEFAULT 0.00,
        tax_amount DECIMAL(10,2) DEFAULT 0.00,
        total_amount DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    // Create subscriptions table
    $sql = "CREATE TABLE IF NOT EXISTS subscriptions (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
        plan VARCHAR(50) NOT NULL,
        status VARCHAR(20) DEFAULT 'active',
        paystack_subscription_code VARCHAR(255),
        paystack_customer_code VARCHAR(255),
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'GHS',
        billing_cycle VARCHAR(20) DEFAULT 'monthly',
        next_payment_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    // Create invoices table
    $sql = "CREATE TABLE IF NOT EXISTS invoices (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
        subscription_id UUID REFERENCES subscriptions(id),
        invoice_number VARCHAR(50) UNIQUE NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'GHS',
        status VARCHAR(20) DEFAULT 'pending',
        paystack_reference VARCHAR(255),
        due_date DATE,
        paid_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    // Create contact_submissions table (public, no tenant_id)
    $sql = "CREATE TABLE IF NOT EXISTS contact_submissions (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        company VARCHAR(255),
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        status VARCHAR(20) DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    // Create knowledgebase_categories table (public, no tenant_id)
    $sql = "CREATE TABLE IF NOT EXISTS knowledgebase_categories (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        description TEXT,
        sort_order INTEGER DEFAULT 0,
        is_active BOOLEAN DEFAULT true,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    // Create knowledgebase table (public, no tenant_id)
    $sql = "CREATE TABLE IF NOT EXISTS knowledgebase (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        content TEXT NOT NULL,
        excerpt TEXT,
        category_id UUID REFERENCES knowledgebase_categories(id),
        tags TEXT,
        author_id UUID,
        published BOOLEAN DEFAULT true,
        view_count INTEGER DEFAULT 0,
        helpful_count INTEGER DEFAULT 0,
        not_helpful_count INTEGER DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    // Create support_tickets table
    $sql = "CREATE TABLE IF NOT EXISTS support_tickets (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        ticket_number VARCHAR(50) UNIQUE NOT NULL,
        user_id UUID REFERENCES users(id),
        tenant_id UUID REFERENCES tenants(id),
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        category VARCHAR(100),
        priority VARCHAR(20) DEFAULT 'medium',
        status VARCHAR(20) DEFAULT 'open',
        assigned_to UUID REFERENCES users(id),
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    // Create support_replies table
    $sql = "CREATE TABLE IF NOT EXISTS support_replies (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        ticket_id UUID NOT NULL REFERENCES support_tickets(id) ON DELETE CASCADE,
        user_id UUID REFERENCES users(id),
        message TEXT NOT NULL,
        is_internal BOOLEAN DEFAULT false,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    // Create audit_logs table
    $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID REFERENCES tenants(id) ON DELETE CASCADE,
        user_id UUID REFERENCES users(id),
        table_name VARCHAR(100) NOT NULL,
        record_id UUID NOT NULL,
        action VARCHAR(20) NOT NULL,
        old_values JSONB,
        new_values JSONB,
        ip_address INET,
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    // Create indexes for performance
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_users_tenant_email ON users(tenant_id, email)",
        "CREATE INDEX IF NOT EXISTS idx_products_tenant_sku ON products(tenant_id, sku)",
        "CREATE INDEX IF NOT EXISTS idx_products_tenant_barcode ON products(tenant_id, barcode)",
        "CREATE INDEX IF NOT EXISTS idx_inventory_tenant_product ON inventory(tenant_id, product_id)",
        "CREATE INDEX IF NOT EXISTS idx_sales_tenant_date ON sales(tenant_id, created_at)",
        "CREATE INDEX IF NOT EXISTS idx_sale_items_sale ON sale_items(sale_id)",
        "CREATE INDEX IF NOT EXISTS idx_customers_tenant_email ON customers(tenant_id, email)",
        "CREATE INDEX IF NOT EXISTS idx_audit_logs_tenant_table ON audit_logs(tenant_id, table_name)",
        "CREATE INDEX IF NOT EXISTS idx_contact_submissions_email ON contact_submissions(email)",
        "CREATE INDEX IF NOT EXISTS idx_contact_submissions_status ON contact_submissions(status)",
        "CREATE INDEX IF NOT EXISTS idx_knowledgebase_category ON knowledgebase(category_id)",
        "CREATE INDEX IF NOT EXISTS idx_knowledgebase_published ON knowledgebase(published)",
        "CREATE INDEX IF NOT EXISTS idx_knowledgebase_slug ON knowledgebase(slug)",
        "CREATE INDEX IF NOT EXISTS idx_support_tickets_user ON support_tickets(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_support_tickets_tenant ON support_tickets(tenant_id)",
        "CREATE INDEX IF NOT EXISTS idx_support_tickets_status ON support_tickets(status)"
    ];
    
    foreach ($indexes as $index) {
        $pdo->exec($index);
    }
    
    // Create triggers for updated_at timestamps
    $pdo->exec("
        CREATE OR REPLACE FUNCTION update_updated_at_column()
        RETURNS TRIGGER AS $$
        BEGIN
            NEW.updated_at = CURRENT_TIMESTAMP;
            RETURN NEW;
        END;
        $$ language 'plpgsql'
    ");
    
    $tables = [
        'tenants', 'users', 'categories', 'products', 'inventory', 
        'customers', 'sales', 'sale_items', 'subscriptions', 'invoices',
        'contact_submissions', 'knowledgebase_categories', 'knowledgebase',
        'support_tickets', 'support_replies', 'audit_logs'
    ];
    
    foreach ($tables as $table) {
        $pdo->exec("DROP TRIGGER IF EXISTS update_{$table}_updated_at ON {$table}");
        $pdo->exec("CREATE TRIGGER update_{$table}_updated_at BEFORE UPDATE ON {$table} FOR EACH ROW EXECUTE FUNCTION update_updated_at_column()");
    }
    
    // Insert default super admin tenant if not exists
    $stmt = $pdo->prepare("SELECT id FROM tenants WHERE id = '00000000-0000-0000-0000-000000000000'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("
            INSERT INTO tenants (id, name, subdomain, plan, status) 
            VALUES ('00000000-0000-0000-0000-000000000000', 'Super Admin', 'admin', 'enterprise', 'active')
        ");
        
        // Insert super admin user
        $pdo->exec("
            INSERT INTO users (tenant_id, email, password_hash, first_name, last_name, role, status)
            VALUES (
                '00000000-0000-0000-0000-000000000000',
                'admin@ardentpos.com',
                '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'Super',
                'Admin',
                'super_admin',
                'active'
            )
        ");
    }
    
    // Insert default knowledgebase categories
    $categories = [
        ['name' => 'Getting Started', 'slug' => 'getting-started', 'description' => 'Basic setup and getting started guides', 'sort_order' => 1],
        ['name' => 'Sales & Transactions', 'slug' => 'sales-transactions', 'description' => 'How to process sales and manage transactions', 'sort_order' => 2],
        ['name' => 'Inventory Management', 'slug' => 'inventory-management', 'description' => 'Managing your product inventory', 'sort_order' => 3],
        ['name' => 'Customer Management', 'slug' => 'customer-management', 'description' => 'Managing customer information and relationships', 'sort_order' => 4],
        ['name' => 'Reports & Analytics', 'slug' => 'reports-analytics', 'description' => 'Understanding your business reports', 'sort_order' => 5],
        ['name' => 'Hardware & Setup', 'slug' => 'hardware-setup', 'description' => 'Setting up hardware and devices', 'sort_order' => 6],
        ['name' => 'Integrations', 'slug' => 'integrations', 'description' => 'Third-party integrations and APIs', 'sort_order' => 7],
        ['name' => 'Security & Permissions', 'slug' => 'security-permissions', 'description' => 'Security settings and user permissions', 'sort_order' => 8],
        ['name' => 'Troubleshooting', 'slug' => 'troubleshooting', 'description' => 'Common issues and solutions', 'sort_order' => 9]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO knowledgebase_categories (name, slug, description, sort_order) VALUES (:name, :slug, :description, :sort_order) ON CONFLICT (slug) DO NOTHING");
    foreach ($categories as $category) {
        $stmt->execute($category);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Unified database setup completed successfully',
        'tables_created' => $tables,
        'super_admin' => 'Default super admin account created (admin@ardentpos.com / password)',
        'knowledgebase' => 'Default categories created'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'db_config' => [
            'host' => $dbConfig['host'],
            'port' => $dbConfig['port'],
            'database' => $dbConfig['database'],
            'username' => $dbConfig['username']
        ]
    ]);
}
?>
