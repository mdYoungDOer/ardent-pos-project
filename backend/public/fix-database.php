<?php
header('Content-Type: application/json');

try {
    // Load environment variables
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USERNAME'] ?? '';
    $dbPass = $_ENV['DB_PASSWORD'] ?? '';

    // Connect to database
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $tenantId = '00000000-0000-0000-0000-000000000000';
    $changes = [];

    // Check if inventory table exists
    $stmt = $pdo->prepare("
        SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'inventory'
        )
    ");
    $stmt->execute();
    $inventoryExists = $stmt->fetch()['exists'];

    if (!$inventoryExists) {
        // Create inventory table
        $pdo->exec("
            CREATE TABLE inventory (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                tenant_id UUID NOT NULL,
                product_id UUID NOT NULL,
                quantity INTEGER NOT NULL DEFAULT 0,
                min_stock INTEGER DEFAULT 0,
                max_stock INTEGER,
                location VARCHAR(100),
                last_counted TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(tenant_id, product_id)
            )
        ");
        $changes[] = "Created inventory table";
    }

    // Check if products table has stock column
    $stmt = $pdo->prepare("
        SELECT EXISTS (
            SELECT FROM information_schema.columns 
            WHERE table_name = 'products' 
            AND column_name = 'stock'
        )
    ");
    $stmt->execute();
    $stockColumnExists = $stmt->fetch()['exists'];

    if ($stockColumnExists) {
        // Remove stock column from products table
        $pdo->exec("ALTER TABLE products DROP COLUMN IF EXISTS stock");
        $changes[] = "Removed stock column from products table";
    }

    // Check if sales table has total_amount column
    $stmt = $pdo->prepare("
        SELECT EXISTS (
            SELECT FROM information_schema.columns 
            WHERE table_name = 'sales' 
            AND column_name = 'total_amount'
        )
    ");
    $stmt->execute();
    $totalAmountExists = $stmt->fetch()['exists'];

    if (!$totalAmountExists) {
        // Add total_amount column to sales table
        $pdo->exec("ALTER TABLE sales ADD COLUMN total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00");
        $changes[] = "Added total_amount column to sales table";
    }

    // Check if sale_items table has unit_price column
    $stmt = $pdo->prepare("
        SELECT EXISTS (
            SELECT FROM information_schema.columns 
            WHERE table_name = 'sale_items' 
            AND column_name = 'unit_price'
        )
    ");
    $stmt->execute();
    $unitPriceExists = $stmt->fetch()['exists'];

    if (!$unitPriceExists) {
        // Add unit_price column to sale_items table
        $pdo->exec("ALTER TABLE sale_items ADD COLUMN unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        $changes[] = "Added unit_price column to sale_items table";
    }

    // Check if sale_items table has tenant_id column
    $stmt = $pdo->prepare("
        SELECT EXISTS (
            SELECT FROM information_schema.columns 
            WHERE table_name = 'sale_items' 
            AND column_name = 'tenant_id'
        )
    ");
    $stmt->execute();
    $saleItemsTenantExists = $stmt->fetch()['exists'];

    if (!$saleItemsTenantExists) {
        // Add tenant_id column to sale_items table
        $pdo->exec("ALTER TABLE sale_items ADD COLUMN tenant_id UUID NOT NULL DEFAULT '00000000-0000-0000-0000-000000000000'");
        $changes[] = "Added tenant_id column to sale_items table";
    }

    // Create indexes if they don't exist
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_inventory_tenant_product ON inventory(tenant_id, product_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sale_items_tenant ON sale_items(tenant_id)");

    echo json_encode([
        'success' => true,
        'message' => 'Database schema updated successfully',
        'changes' => $changes
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
