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

try {
    // Database connection
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_PORT'] ?? '5432',
        $_ENV['DB_NAME'] ?? 'defaultdb'
    );
    
    $pdo = new PDO(
        $dsn,
        $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? 'postgres',
        $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? 'password',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Connected to database successfully.\n";
    
    // Enable UUID extension if not already enabled
    $pdo->exec("CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\"");
    echo "UUID extension enabled.\n";
    
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
    echo "Tenants table created.\n";
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(50) DEFAULT 'user',
        tenant_id UUID REFERENCES tenants(id) ON DELETE CASCADE,
        status VARCHAR(50) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Users table created.\n";
    
    // Create categories table
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID REFERENCES tenants(id) ON DELETE CASCADE,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        parent_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Categories table created.\n";
    
    // Create products table
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID REFERENCES tenants(id) ON DELETE CASCADE,
        category_id UUID REFERENCES categories(id) ON DELETE SET NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        cost_price DECIMAL(10,2) DEFAULT 0,
        stock_quantity INTEGER DEFAULT 0,
        sku VARCHAR(100),
        barcode VARCHAR(100),
        image_url TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Products table created.\n";
    
    // Create inventory table
    $sql = "CREATE TABLE IF NOT EXISTS inventory (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        product_id UUID REFERENCES products(id) ON DELETE CASCADE,
        tenant_id UUID REFERENCES tenants(id) ON DELETE CASCADE,
        quantity INTEGER NOT NULL,
        adjustment_type VARCHAR(20) NOT NULL,
        reason TEXT,
        created_by UUID REFERENCES users(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Inventory table created.\n";
    
    // Create customers table
    $sql = "CREATE TABLE IF NOT EXISTS customers (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID REFERENCES tenants(id) ON DELETE CASCADE,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255),
        phone VARCHAR(20),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Customers table created.\n";
    
    // Create sales table
    $sql = "CREATE TABLE IF NOT EXISTS sales (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID REFERENCES tenants(id) ON DELETE CASCADE,
        customer_id UUID REFERENCES customers(id) ON DELETE SET NULL,
        invoice_number VARCHAR(100) UNIQUE NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        tax_amount DECIMAL(10,2) DEFAULT 0,
        discount_amount DECIMAL(10,2) DEFAULT 0,
        payment_method VARCHAR(50) DEFAULT 'cash',
        status VARCHAR(20) DEFAULT 'completed',
        created_by UUID REFERENCES users(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Sales table created.\n";
    
    // Create sale_items table
    $sql = "CREATE TABLE IF NOT EXISTS sale_items (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        sale_id UUID REFERENCES sales(id) ON DELETE CASCADE,
        product_id UUID REFERENCES products(id) ON DELETE CASCADE,
        quantity INTEGER NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Sale items table created.\n";
    
    // Create subscriptions table
    $sql = "CREATE TABLE IF NOT EXISTS subscriptions (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID REFERENCES tenants(id) ON DELETE CASCADE,
        plan_name VARCHAR(100) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        billing_cycle VARCHAR(20) DEFAULT 'monthly',
        status VARCHAR(20) DEFAULT 'active',
        payment_reference VARCHAR(100),
        start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        end_date TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Subscriptions table created.\n";
    
    // Create invoices table
    $sql = "CREATE TABLE IF NOT EXISTS invoices (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID REFERENCES tenants(id) ON DELETE CASCADE,
        invoice_number VARCHAR(100) UNIQUE NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(10) DEFAULT 'GHS',
        status VARCHAR(20) DEFAULT 'pending',
        description TEXT,
        payment_reference VARCHAR(100),
        due_date TIMESTAMP,
        paid_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Invoices table created.\n";
    
    // Create payments table
    $sql = "CREATE TABLE IF NOT EXISTS payments (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID REFERENCES tenants(id) ON DELETE CASCADE,
        user_id UUID REFERENCES users(id) ON DELETE SET NULL,
        reference VARCHAR(100) UNIQUE NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(10) DEFAULT 'GHS',
        status VARCHAR(20) DEFAULT 'pending',
        payment_method VARCHAR(50) DEFAULT 'paystack',
        gateway_response JSONB,
        metadata JSONB,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Payments table created.\n";
    
    // Create contact_submissions table
    $sql = "CREATE TABLE IF NOT EXISTS contact_submissions (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255),
        message TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Contact submissions table created.\n";
    
    // Create knowledgebase_categories table
    $sql = "CREATE TABLE IF NOT EXISTS knowledgebase_categories (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        name VARCHAR(255) NOT NULL,
        description TEXT,
        slug VARCHAR(255) UNIQUE NOT NULL,
        sort_order INTEGER DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Knowledgebase categories table created.\n";
    
    // Create knowledgebase table
    $sql = "CREATE TABLE IF NOT EXISTS knowledgebase (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        category_id UUID REFERENCES knowledgebase_categories(id) ON DELETE CASCADE,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        published BOOLEAN DEFAULT true,
        view_count INTEGER DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Knowledgebase table created.\n";
    
    // Create support_tickets table
    $sql = "CREATE TABLE IF NOT EXISTS support_tickets (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        tenant_id UUID REFERENCES tenants(id) ON DELETE CASCADE,
        user_id UUID REFERENCES users(id) ON DELETE SET NULL,
        subject VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'open',
        priority VARCHAR(20) DEFAULT 'medium',
        category VARCHAR(50),
        assigned_to UUID REFERENCES users(id) ON DELETE SET NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Support tickets table created.\n";
    
    // Create support_replies table
    $sql = "CREATE TABLE IF NOT EXISTS support_replies (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        ticket_id UUID REFERENCES support_tickets(id) ON DELETE CASCADE,
        user_id UUID REFERENCES users(id) ON DELETE SET NULL,
        message TEXT NOT NULL,
        is_internal BOOLEAN DEFAULT false,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Support replies table created.\n";
    
    // Create audit_logs table
    $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        user_id UUID REFERENCES users(id) ON DELETE SET NULL,
        tenant_id UUID REFERENCES tenants(id) ON DELETE CASCADE,
        action VARCHAR(100) NOT NULL,
        table_name VARCHAR(100),
        record_id UUID,
        old_values JSONB,
        new_values JSONB,
        ip_address INET,
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Audit logs table created.\n";
    
    // Create indexes for better performance
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_users_email ON users(email)",
        "CREATE INDEX IF NOT EXISTS idx_users_tenant_id ON users(tenant_id)",
        "CREATE INDEX IF NOT EXISTS idx_products_tenant_id ON products(tenant_id)",
        "CREATE INDEX IF NOT EXISTS idx_products_category_id ON products(category_id)",
        "CREATE INDEX IF NOT EXISTS idx_sales_tenant_id ON sales(tenant_id)",
        "CREATE INDEX IF NOT EXISTS idx_sales_customer_id ON sales(customer_id)",
        "CREATE INDEX IF NOT EXISTS idx_customers_tenant_id ON customers(tenant_id)",
        "CREATE INDEX IF NOT EXISTS idx_subscriptions_tenant_id ON subscriptions(tenant_id)",
        "CREATE INDEX IF NOT EXISTS idx_invoices_tenant_id ON invoices(tenant_id)",
        "CREATE INDEX IF NOT EXISTS idx_payments_tenant_id ON payments(tenant_id)",
        "CREATE INDEX IF NOT EXISTS idx_support_tickets_tenant_id ON support_tickets(tenant_id)",
        "CREATE INDEX IF NOT EXISTS idx_knowledgebase_category_id ON knowledgebase(category_id)",
        "CREATE INDEX IF NOT EXISTS idx_knowledgebase_slug ON knowledgebase(slug)",
        "CREATE INDEX IF NOT EXISTS idx_knowledgebase_categories_slug ON knowledgebase_categories(slug)"
    ];
    
    foreach ($indexes as $index) {
        $pdo->exec($index);
    }
    echo "Database indexes created.\n";
    
    // Insert default super admin user
    $superAdminEmail = 'superadmin@ardentpos.com';
    $superAdminPassword = password_hash('superadmin123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$superAdminEmail]);
    
    if ($stmt->fetchColumn() == 0) {
        $sql = "INSERT INTO users (id, first_name, last_name, email, password_hash, role, status) 
                VALUES (uuid_generate_v4(), 'Super', 'Admin', ?, ?, 'super_admin', 'active')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$superAdminEmail, $superAdminPassword]);
        echo "Default super admin user created.\n";
        echo "Email: superadmin@ardentpos.com\n";
        echo "Password: superadmin123\n";
    } else {
        echo "Super admin user already exists.\n";
    }
    
    // Insert default knowledgebase categories
    $defaultCategories = [
        ['Getting Started', 'Basic setup and configuration guides', 'getting-started'],
        ['User Management', 'Managing users and permissions', 'user-management'],
        ['Products & Inventory', 'Product and inventory management', 'products-inventory'],
        ['Sales & Reports', 'Sales processing and reporting', 'sales-reports'],
        ['Billing & Subscriptions', 'Billing and subscription management', 'billing-subscriptions'],
        ['Troubleshooting', 'Common issues and solutions', 'troubleshooting']
    ];
    
    foreach ($defaultCategories as $category) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM knowledgebase_categories WHERE slug = ?");
        $stmt->execute([$category[2]]);
        
        if ($stmt->fetchColumn() == 0) {
            $sql = "INSERT INTO knowledgebase_categories (id, name, description, slug) 
                    VALUES (uuid_generate_v4(), ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($category);
        }
    }
    echo "Default knowledgebase categories created.\n";
    
    // Insert sample knowledgebase articles
    $sampleArticles = [
        [
            'Welcome to Ardent POS',
            'Welcome to Ardent POS! This guide will help you get started with your new point of sale system.',
            'welcome-to-ardent-pos',
            'getting-started'
        ],
        [
            'Setting Up Your First Product',
            'Learn how to add your first product to the system and manage your inventory.',
            'setting-up-first-product',
            'products-inventory'
        ],
        [
            'Processing Your First Sale',
            'Step-by-step guide to processing your first sale using Ardent POS.',
            'processing-first-sale',
            'sales-reports'
        ]
    ];
    
    foreach ($sampleArticles as $article) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM knowledgebase WHERE slug = ?");
        $stmt->execute([$article[2]]);
        
        if ($stmt->fetchColumn() == 0) {
            // Get category ID
            $stmt = $pdo->prepare("SELECT id FROM knowledgebase_categories WHERE slug = ?");
            $stmt->execute([$article[3]]);
            $categoryId = $stmt->fetchColumn();
            
            if ($categoryId) {
                $sql = "INSERT INTO knowledgebase (id, category_id, title, content, slug) 
                        VALUES (uuid_generate_v4(), ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$categoryId, $article[0], $article[1], $article[2]]);
            }
        }
    }
    echo "Sample knowledgebase articles created.\n";
    
    echo json_encode([
        'success' => true,
        'message' => 'Database setup completed successfully',
        'details' => [
            'tables_created' => 15,
            'indexes_created' => count($indexes),
            'super_admin_created' => true,
            'knowledgebase_categories_created' => count($defaultCategories),
            'sample_articles_created' => count($sampleArticles)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Database setup error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database setup failed: ' . $e->getMessage()
    ]);
}
?>
