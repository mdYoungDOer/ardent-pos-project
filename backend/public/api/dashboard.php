<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Enterprise-grade error handling and logging
function logError($message, $error = null) {
    error_log("Dashboard API Error: " . $message . ($error ? " - " . $error->getMessage() : ""));
}

function sendErrorResponse($message, $code = 500) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

function sendSuccessResponse($data, $message = 'Success') {
    echo json_encode([
        'success' => true,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

try {
    // Load environment variables properly
    $dbHost = $_ENV['DB_HOST'] ?? 'db-postgresql-nyc3-77594-ardent-pos-do-user-24545475-0.g.db.ondigitalocean.com';
    $dbPort = $_ENV['DB_PORT'] ?? '25060';
    $dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
    $dbUser = $_ENV['DB_USER'] ?? 'doadmin';
    $dbPass = $_ENV['DB_PASS'] ?? '';

    // Validate required environment variables
    if (empty($dbPass)) {
        logError("Database password not configured");
        sendErrorResponse("Database configuration error", 500);
    }

    // Connect to database with proper error handling
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);

    $method = $_SERVER['REQUEST_METHOD'];
    
    // Get tenant ID from JWT token or use default
    $tenantId = '00000000-0000-0000-0000-000000000000'; // Default tenant for now
    
    // TODO: Extract tenant ID from JWT token in production
    // $headers = getallheaders();
    // $token = $headers['Authorization'] ?? '';
    // if (strpos($token, 'Bearer ') === 0) {
    //     $token = substr($token, 7);
    //     // Decode JWT and extract tenant_id
    // }

    if ($method === 'GET') {
        // Get dashboard statistics with proper error handling
        $stats = [];
        
        try {
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

            // Calculate growth percentages (mock data for now)
            $stats['salesGrowth'] = 12.5;
            $stats['ordersGrowth'] = 8.2;
            $stats['productsGrowth'] = 5.7;
            $stats['customersGrowth'] = 15.3;

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

            // Low Stock Products
            $stmt = $pdo->prepare("
                SELECT p.id, p.name, p.price, i.quantity as stock
                FROM products p
                LEFT JOIN inventory i ON p.id = i.product_id
                WHERE p.tenant_id = ? AND (i.quantity <= 10 OR i.quantity IS NULL)
                ORDER BY i.quantity ASC
                LIMIT 5
            ");
            $stmt->execute([$tenantId]);
            $stats['lowStockProducts'] = $stmt->fetchAll();

            sendSuccessResponse($stats, 'Dashboard statistics loaded successfully');

        } catch (PDOException $e) {
            logError("Database query error", $e);
            sendErrorResponse("Failed to load dashboard statistics", 500);
        }

    } else {
        sendErrorResponse("Method not allowed", 405);
    }

} catch (PDOException $e) {
    logError("Database connection error", $e);
    sendErrorResponse("Database connection failed", 500);
} catch (Exception $e) {
    logError("Unexpected error", $e);
    sendErrorResponse("Internal server error", 500);
}
?>
