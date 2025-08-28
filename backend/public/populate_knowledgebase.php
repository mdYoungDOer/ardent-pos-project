<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // Check if knowledge base already has data
    $stmt = $pdo->query("SELECT COUNT(*) FROM knowledgebase");
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Knowledge base already has data. Skipping population.'
        ]);
        exit;
    }

    // First, insert the knowledge base categories
    $categories = [
        [
            'id' => 1,
            'name' => 'Getting Started',
            'slug' => 'getting-started',
            'description' => 'Essential guides for new users to get up and running quickly',
            'icon' => 'help-circle',
            'sort_order' => 1
        ],
        [
            'id' => 2,
            'name' => 'Sales & Transactions',
            'slug' => 'sales-transactions',
            'description' => 'Everything you need to know about processing sales and managing transactions',
            'icon' => 'shopping-cart',
            'sort_order' => 2
        ],
        [
            'id' => 3,
            'name' => 'Inventory Management',
            'slug' => 'inventory-management',
            'description' => 'Complete guide to managing your product catalog and stock levels',
            'icon' => 'truck',
            'sort_order' => 3
        ],
        [
            'id' => 4,
            'name' => 'Customer Management',
            'slug' => 'customer-management',
            'description' => 'Tools and techniques for managing your customer database',
            'icon' => 'users',
            'sort_order' => 4
        ],
        [
            'id' => 5,
            'name' => 'Reports & Analytics',
            'slug' => 'reports-analytics',
            'description' => 'Understanding your business data and generating insights',
            'icon' => 'bar-chart-2',
            'sort_order' => 5
        ],
        [
            'id' => 6,
            'name' => 'Hardware & Setup',
            'slug' => 'hardware-setup',
            'description' => 'Setting up and configuring POS hardware and devices',
            'icon' => 'monitor',
            'sort_order' => 6
        ],
        [
            'id' => 7,
            'name' => 'Integrations',
            'slug' => 'integrations',
            'description' => 'Connecting your POS with payment gateways and e-commerce platforms',
            'icon' => 'settings',
            'sort_order' => 7
        ],
        [
            'id' => 8,
            'name' => 'Security & Permissions',
            'slug' => 'security-permissions',
            'description' => 'Managing user access, roles, and system security',
            'icon' => 'shield',
            'sort_order' => 8
        ],
        [
            'id' => 9,
            'name' => 'Troubleshooting',
            'slug' => 'troubleshooting',
            'description' => 'Solutions for common issues and system maintenance',
            'icon' => 'tool',
            'sort_order' => 9
        ]
    ];

    // Insert categories
    $categoryStmt = $pdo->prepare("
        INSERT INTO knowledgebase_categories (id, name, slug, description, icon, sort_order, created_at, updated_at)
        VALUES (:id, :name, :slug, :description, :icon, :sort_order, NOW(), NOW())
        ON CONFLICT (id) DO NOTHING
    ");

    foreach ($categories as $category) {
        $categoryStmt->execute($category);
    }

    // Sample knowledge base articles
    $articles = [
        [
            'category_id' => 1,
            'title' => 'Getting Started with Ardent POS',
            'content' => "# Welcome to Ardent POS!\n\n## Quick Setup Guide\n\n### 1. Account Registration\n- Visit our website and click \"Get Started\"\n- Choose your business package (Starter, Professional, or Enterprise)\n- Fill in your business details and create your account\n- Verify your email address\n\n### 2. Initial Configuration\n- Set up your business profile with name, address, and contact information\n- Configure your tax settings and currency preferences\n- Add your first products or services\n- Set up payment methods\n\n### 3. Staff Management\n- Invite team members to your account\n- Assign roles and permissions\n- Set up employee PINs for POS access\n\n### 4. First Sale\n- Open the POS interface\n- Add items to cart\n- Process payment\n- Print or email receipt\n\n## Need Help?\nIf you encounter any issues during setup, our support team is available 24/7. You can also use our live chat widget for instant assistance.",
            'slug' => 'getting-started-with-ardent-pos',
            'excerpt' => 'Complete guide to setting up your Ardent POS account and making your first sale',
            'tags' => 'getting started,setup,first sale,registration',
            'published' => true,
            'helpful_count' => 45,
            'not_helpful_count' => 2,
            'view_count' => 234
        ],
        [
            'category_id' => 1,
            'title' => 'Understanding Your Dashboard',
            'content' => "# Dashboard Overview\n\nYour Ardent POS dashboard provides real-time insights into your business performance.\n\n## Key Metrics\n- **Total Sales**: Daily, weekly, and monthly revenue\n- **Transaction Count**: Number of sales completed\n- **Average Order Value**: Mean transaction amount\n- **Top Products**: Best-selling items\n- **Customer Growth**: New customer registrations\n\n## Navigation\n- **Sales**: View and manage transactions\n- **Inventory**: Track stock levels and products\n- **Customers**: Manage customer database\n- **Reports**: Generate business insights\n- **Settings**: Configure system preferences\n\n## Quick Actions\n- Process new sale\n- Add new product\n- View recent transactions\n- Generate reports\n- Manage staff\n\n## Customization\nYou can customize your dashboard layout and choose which metrics to display prominently.",
            'slug' => 'understanding-your-dashboard',
            'excerpt' => 'Learn how to navigate and customize your Ardent POS dashboard for optimal business insights',
            'tags' => 'dashboard,metrics,navigation,overview',
            'published' => true,
            'helpful_count' => 32,
            'not_helpful_count' => 1,
            'view_count' => 156
        ],
        [
            'category_id' => 2,
            'title' => 'Processing Sales Transactions',
            'content' => "# How to Process Sales\n\n## Step-by-Step Guide\n\n### 1. Open POS Interface\n- Click \"New Sale\" from your dashboard\n- Or use the POS terminal directly\n\n### 2. Add Items\n- Search for products by name, SKU, or barcode\n- Click items to add to cart\n- Adjust quantities as needed\n- Apply discounts if applicable\n\n### 3. Customer Information\n- Select existing customer or create new one\n- Add customer notes if needed\n- Apply customer-specific discounts\n\n### 4. Payment Processing\n- Choose payment method (cash, card, mobile payment)\n- Enter payment amount\n- Process transaction\n- Handle change if paying with cash\n\n### 5. Receipt Options\n- Print receipt\n- Email receipt to customer\n- Send SMS receipt\n- Save to cloud\n\n## Tips\n- Use keyboard shortcuts for faster processing\n- Set up favorite items for quick access\n- Enable barcode scanning for efficiency\n- Train staff on proper procedures",
            'slug' => 'processing-sales-transactions',
            'excerpt' => 'Complete guide to processing sales transactions in Ardent POS with step-by-step instructions',
            'tags' => 'sales,transactions,payment,receipt',
            'published' => true,
            'helpful_count' => 67,
            'not_helpful_count' => 3,
            'view_count' => 445
        ],
        [
            'category_id' => 3,
            'title' => 'Adding and Managing Products',
            'content' => "# Product Management\n\n## Adding New Products\n\n### 1. Basic Information\n- Product name and description\n- SKU or barcode\n- Category and subcategory\n- Brand and model\n\n### 2. Pricing\n- Cost price\n- Selling price\n- Tax settings\n- Discount options\n\n### 3. Inventory Settings\n- Initial stock quantity\n- Low stock alerts\n- Reorder points\n- Supplier information\n\n### 4. Additional Details\n- Product images\n- Weight and dimensions\n- Shipping settings\n- SEO keywords\n\n## Bulk Operations\n- Import products via CSV\n- Bulk price updates\n- Mass category changes\n- Inventory adjustments\n\n## Product Variants\n- Size variations\n- Color options\n- Material differences\n- Custom attributes",
            'slug' => 'adding-and-managing-products',
            'excerpt' => 'Complete guide to adding and managing products in your Ardent POS inventory system',
            'tags' => 'products,inventory,SKU,pricing',
            'published' => true,
            'helpful_count' => 89,
            'not_helpful_count' => 4,
            'view_count' => 567
        ],
        [
            'category_id' => 4,
            'title' => 'Customer Database Management',
            'content' => "# Customer Management\n\n## Adding Customers\n\n### 1. Manual Entry\n- Name and contact information\n- Address details\n- Birthday and preferences\n- Notes and tags\n\n### 2. Import Options\n- CSV file import\n- Bulk customer creation\n- Data validation\n- Duplicate checking\n\n## Customer Profiles\n- Purchase history\n- Preferences and notes\n- Communication history\n- Loyalty points\n\n## Customer Segmentation\n- By purchase value\n- By frequency\n- By location\n- By preferences\n\n## Communication Tools\n- Email marketing\n- SMS notifications\n- Birthday greetings\n- Promotional campaigns",
            'slug' => 'customer-database-management',
            'excerpt' => 'Learn how to manage your customer database, add new customers, and segment your customer base',
            'tags' => 'customers,database,segmentation,communication',
            'published' => true,
            'helpful_count' => 43,
            'not_helpful_count' => 1,
            'view_count' => 234
        ],
        [
            'category_id' => 5,
            'title' => 'Understanding Sales Reports',
            'content' => "# Sales Analytics\n\n## Key Reports\n\n### 1. Sales Summary\n- Total revenue by period\n- Transaction counts\n- Average order value\n- Growth trends\n\n### 2. Product Performance\n- Top-selling items\n- Revenue by product\n- Inventory turnover\n- Profit margins\n\n### 3. Customer Insights\n- Customer acquisition\n- Retention rates\n- Lifetime value\n- Purchase patterns\n\n### 4. Staff Performance\n- Sales by employee\n- Transaction counts\n- Commission tracking\n- Productivity metrics\n\n## Report Customization\n- Date range selection\n- Filter options\n- Export formats\n- Scheduled reports\n\n## Data Visualization\n- Charts and graphs\n- Trend analysis\n- Comparative views\n- Interactive dashboards",
            'slug' => 'understanding-sales-reports',
            'excerpt' => 'Learn how to interpret sales reports and use analytics to improve your business performance',
            'tags' => 'reports,analytics,sales,performance',
            'published' => true,
            'helpful_count' => 78,
            'not_helpful_count' => 3,
            'view_count' => 389
        ],
        [
            'category_id' => 6,
            'title' => 'POS Hardware Setup',
            'content' => "# Hardware Configuration\n\n## Essential Equipment\n- Cash drawer\n- Receipt printer\n- Barcode scanner\n- Payment terminal\n\n## Connection Setup\n- USB connections\n- Network configuration\n- Driver installation\n- Device testing\n\n## Printer Configuration\n- Paper size settings\n- Print quality\n- Header/footer customization\n- Logo printing\n\n## Scanner Setup\n- Barcode format support\n- Scan speed settings\n- Sound feedback\n- Error handling\n\n## Troubleshooting\n- Connection issues\n- Driver problems\n- Hardware conflicts\n- Performance optimization",
            'slug' => 'pos-hardware-setup',
            'excerpt' => 'Complete guide to setting up and configuring POS hardware including printers, scanners, and payment terminals',
            'tags' => 'hardware,setup,printer,scanner',
            'published' => true,
            'helpful_count' => 67,
            'not_helpful_count' => 4,
            'view_count' => 456
        ],
        [
            'category_id' => 7,
            'title' => 'Payment Gateway Integration',
            'content' => "# Payment Processing\n\n## Supported Gateways\n- Stripe\n- PayPal\n- Square\n- Authorize.net\n- Local payment providers\n\n## Setup Process\n- Account creation\n- API key configuration\n- Webhook setup\n- Test transactions\n\n## Security Compliance\n- PCI DSS requirements\n- Data encryption\n- Tokenization\n- Fraud protection\n\n## Transaction Types\n- Credit/debit cards\n- Digital wallets\n- ACH transfers\n- International payments\n\n## Troubleshooting\n- Declined transactions\n- Processing errors\n- Refund issues\n- Settlement delays",
            'slug' => 'payment-gateway-integration',
            'excerpt' => 'Integrate payment gateways to accept various payment methods securely',
            'tags' => 'payment,gateway,stripe,security',
            'published' => true,
            'helpful_count' => 92,
            'not_helpful_count' => 5,
            'view_count' => 678
        ],
        [
            'category_id' => 8,
            'title' => 'User Roles and Permissions',
            'content' => "# Access Control\n\n## Role Types\n- Super Admin: Full system access\n- Manager: Sales and reporting access\n- Cashier: Basic POS operations\n- Inventory: Stock management only\n- Reports: Read-only access\n\n## Permission Settings\n- Sales operations\n- Inventory management\n- Customer data access\n- Financial reports\n- System settings\n\n## Security Features\n- Password requirements\n- Two-factor authentication\n- Session management\n- Activity logging\n\n## Best Practices\n- Principle of least privilege\n- Regular access reviews\n- Strong passwords\n- Staff training\n\n## Emergency Access\n- Account recovery\n- Emergency procedures\n- Backup access\n- Contact protocols",
            'slug' => 'user-roles-and-permissions',
            'excerpt' => 'Set up user roles and permissions to control access to different parts of your POS system',
            'tags' => 'security,roles,permissions,access control',
            'published' => true,
            'helpful_count' => 54,
            'not_helpful_count' => 2,
            'view_count' => 234
        ],
        [
            'category_id' => 9,
            'title' => 'Common POS Issues and Solutions',
            'content' => "# Troubleshooting Guide\n\n## Connection Problems\n- Check internet connection\n- Verify server status\n- Test network connectivity\n- Restart devices\n\n## Printer Issues\n- Paper jams\n- Print quality problems\n- Connection errors\n- Driver issues\n\n## Payment Processing\n- Declined transactions\n- Gateway errors\n- Settlement issues\n- Refund problems\n\n## Performance Issues\n- Slow system response\n- Memory problems\n- Cache clearing\n- System optimization\n\n## Data Issues\n- Sync problems\n- Missing transactions\n- Duplicate entries\n- Data corruption\n\n## Getting Help\n- Check knowledge base\n- Contact support\n- Submit ticket\n- Live chat assistance",
            'slug' => 'common-pos-issues-and-solutions',
            'excerpt' => 'Quick solutions for common POS system issues and problems',
            'tags' => 'troubleshooting,issues,solutions,help',
            'published' => true,
            'helpful_count' => 156,
            'not_helpful_count' => 8,
            'view_count' => 789
        ]
    ];

    // Insert articles
    $stmt = $pdo->prepare("
        INSERT INTO knowledgebase (
            category_id, title, content, slug, excerpt, tags, 
            published, helpful_count, not_helpful_count, view_count, created_at, updated_at
        ) VALUES (
            :category_id, :title, :content, :slug, :excerpt, :tags,
            :published, :helpful_count, :not_helpful_count, :view_count, NOW(), NOW()
        )
    ");

    $insertedCount = 0;
    foreach ($articles as $article) {
        $stmt->execute($article);
        $insertedCount++;
    }

    echo json_encode([
        'success' => true,
        'message' => "Successfully populated knowledge base with $insertedCount articles and 9 categories.",
        'articles_added' => $insertedCount,
        'categories_added' => 9
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error populating knowledge base: ' . $e->getMessage()
    ]);
}
?>
