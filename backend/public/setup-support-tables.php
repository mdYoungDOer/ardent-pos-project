<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../vendor/autoload.php';

use ArdentPOS\Core\Database;

try {
    Database::init();
    
    // Create knowledgebase_categories table
    $sql = "CREATE TABLE IF NOT EXISTS knowledgebase_categories (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        description TEXT,
        sort_order INTEGER DEFAULT 0,
        is_active BOOLEAN DEFAULT true,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    Database::query($sql);
    
    // Create knowledgebase table
    $sql = "CREATE TABLE IF NOT EXISTS knowledgebase (
        id SERIAL PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        content TEXT NOT NULL,
        excerpt TEXT,
        category_id INTEGER REFERENCES knowledgebase_categories(id),
        tags TEXT,
        author_id INTEGER,
        published BOOLEAN DEFAULT true,
        view_count INTEGER DEFAULT 0,
        helpful_count INTEGER DEFAULT 0,
        not_helpful_count INTEGER DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    Database::query($sql);
    
    // Create contact_submissions table
    $sql = "CREATE TABLE IF NOT EXISTS contact_submissions (
        id SERIAL PRIMARY KEY,
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
    Database::query($sql);
    
    // Create support_tickets table
    $sql = "CREATE TABLE IF NOT EXISTS support_tickets (
        id SERIAL PRIMARY KEY,
        ticket_number VARCHAR(50) UNIQUE NOT NULL,
        user_id INTEGER,
        tenant_id INTEGER,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        category VARCHAR(100),
        priority VARCHAR(20) DEFAULT 'medium',
        status VARCHAR(20) DEFAULT 'open',
        assigned_to INTEGER,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    Database::query($sql);
    
    // Create indexes
    Database::query("CREATE INDEX IF NOT EXISTS idx_knowledgebase_category ON knowledgebase(category_id)");
    Database::query("CREATE INDEX IF NOT EXISTS idx_knowledgebase_published ON knowledgebase(published)");
    Database::query("CREATE INDEX IF NOT EXISTS idx_knowledgebase_slug ON knowledgebase(slug)");
    Database::query("CREATE INDEX IF NOT EXISTS idx_contact_submissions_email ON contact_submissions(email)");
    Database::query("CREATE INDEX IF NOT EXISTS idx_contact_submissions_status ON contact_submissions(status)");
    Database::query("CREATE INDEX IF NOT EXISTS idx_contact_submissions_created_at ON contact_submissions(created_at)");
    Database::query("CREATE INDEX IF NOT EXISTS idx_support_tickets_user ON support_tickets(user_id)");
    Database::query("CREATE INDEX IF NOT EXISTS idx_support_tickets_tenant ON support_tickets(tenant_id)");
    Database::query("CREATE INDEX IF NOT EXISTS idx_support_tickets_status ON support_tickets(status)");
    
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
    
    foreach ($categories as $category) {
        $sql = "INSERT INTO knowledgebase_categories (name, slug, description, sort_order) 
                VALUES (:name, :slug, :description, :sort_order) 
                ON CONFLICT (slug) DO NOTHING";
        Database::query($sql, $category);
    }
    
    // Insert sample knowledgebase articles
    $articles = [
        [
            'title' => 'Getting Started with Ardent POS',
            'slug' => 'getting-started-with-ardent-pos',
            'content' => '<h2>Welcome to Ardent POS!</h2><p>This guide will help you get started with your new Ardent POS system. Follow these steps to set up your account and start processing sales.</p><h3>Step 1: Account Setup</h3><p>After creating your account, you\'ll need to complete your business profile and configure your settings.</p><h3>Step 2: Add Your Products</h3><p>Start by adding your products to the system. You can import them in bulk or add them one by one.</p><h3>Step 3: Configure Your POS</h3><p>Set up your payment methods, tax rates, and other business settings.</p>',
            'excerpt' => 'Learn how to set up your Ardent POS account and start processing sales.',
            'category_id' => 1,
            'tags' => 'setup,getting-started,first-time'
        ],
        [
            'title' => 'How to Process a Sale',
            'slug' => 'how-to-process-a-sale',
            'content' => '<h2>Processing Sales in Ardent POS</h2><p>Learn how to quickly and efficiently process sales transactions in your Ardent POS system.</p><h3>Adding Items</h3><p>Use the product search or scan barcodes to add items to the sale.</p><h3>Applying Discounts</h3><p>You can apply percentage or fixed amount discounts to individual items or the entire sale.</p><h3>Payment Processing</h3><p>Accept cash, card, or mobile payments. The system will automatically calculate change.</p>',
            'excerpt' => 'Step-by-step guide to processing sales transactions in Ardent POS.',
            'category_id' => 2,
            'tags' => 'sales,transactions,payment'
        ],
        [
            'title' => 'Managing Your Inventory',
            'slug' => 'managing-your-inventory',
            'content' => '<h2>Inventory Management</h2><p>Keep track of your stock levels and manage your inventory effectively.</p><h3>Stock Levels</h3><p>Monitor your current stock levels and set up low stock alerts.</p><h3>Stock Adjustments</h3><p>Make manual adjustments for damaged goods, theft, or other inventory changes.</p><h3>Stock Transfers</h3><p>Transfer stock between different locations if you have multiple stores.</p>',
            'excerpt' => 'Complete guide to managing your inventory and stock levels.',
            'category_id' => 3,
            'tags' => 'inventory,stock,management'
        ]
    ];
    
    foreach ($articles as $article) {
        $sql = "INSERT INTO knowledgebase (title, slug, content, excerpt, category_id, tags) 
                VALUES (:title, :slug, :content, :excerpt, :category_id, :tags) 
                ON CONFLICT (slug) DO NOTHING";
        Database::query($sql, $article);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Support portal tables created successfully with sample data'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
