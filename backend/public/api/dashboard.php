<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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

    $method = $_SERVER['REQUEST_METHOD'];
    $tenantId = '00000000-0000-0000-0000-000000000000'; // Default tenant for now

    if ($method === 'GET') {
        // Get dashboard statistics
        $stats = [];
        
        // Total Sales (sum of all sales amounts)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(total_amount), 0) as total_sales 
            FROM sales 
            WHERE tenant_id = ?
        ");
        $stmt->execute([$tenantId]);
        $stats['totalSales'] = floatval($stmt->fetch()['total_sales']);

        // Total Orders (count of sales)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_orders 
            FROM sales 
            WHERE tenant_id = ?
        ");
        $stmt->execute([$tenantId]);
        $stats['totalOrders'] = intval($stmt->fetch()['total_orders']);

        // Total Customers (count of unique customers)
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT customer_id) as total_customers 
            FROM sales 
            WHERE tenant_id = ? AND customer_id IS NOT NULL
        ");
        $stmt->execute([$tenantId]);
        $stats['totalCustomers'] = intval($stmt->fetch()['total_customers']);

        // Total Products (count of products)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_products 
            FROM products 
            WHERE tenant_id = ?
        ");
        $stmt->execute([$tenantId]);
        $stats['totalProducts'] = intval($stmt->fetch()['total_products']);

        // Recent Sales (last 5 sales)
        $stmt = $pdo->prepare("
            SELECT s.id, s.total_amount, s.created_at, c.first_name, c.last_name
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE s.tenant_id = ?
            ORDER BY s.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$tenantId]);
        $stats['recentSales'] = $stmt->fetchAll();

        // Top Products (by sales quantity)
        $stmt = $pdo->prepare("
            SELECT p.name, 
                   COALESCE(SUM(si.quantity), 0) as total_sold,
                   COALESCE(SUM(si.quantity * si.unit_price), 0) as total_revenue
            FROM products p
            LEFT JOIN sale_items si ON p.id = si.product_id
            LEFT JOIN sales s ON si.sale_id = s.id
            WHERE p.tenant_id = ? AND (s.tenant_id = ? OR s.tenant_id IS NULL)
            GROUP BY p.id, p.name
            ORDER BY total_sold DESC
            LIMIT 5
        ");
        $stmt->execute([$tenantId, $tenantId]);
        $stats['topProducts'] = $stmt->fetchAll();

        // Monthly Sales Trend (last 6 months)
        $stmt = $pdo->prepare("
            SELECT 
                DATE_TRUNC('month', created_at) as month,
                COALESCE(SUM(total_amount), 0) as monthly_sales,
                COUNT(*) as monthly_orders
            FROM sales 
            WHERE tenant_id = ? 
                AND created_at >= DATE_TRUNC('month', NOW() - INTERVAL '6 months')
            GROUP BY DATE_TRUNC('month', created_at)
            ORDER BY month DESC
        ");
        $stmt->execute([$tenantId]);
        $stats['monthlyTrend'] = $stmt->fetchAll();

        // Low Stock Products (products with stock < 10)
        $stmt = $pdo->prepare("
            SELECT id, name, stock, price
            FROM products 
            WHERE tenant_id = ? AND stock < 10
            ORDER BY stock ASC
            LIMIT 5
        ");
        $stmt->execute([$tenantId]);
        $stats['lowStockProducts'] = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
