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

// Include unified authentication
require_once __DIR__ . '/auth/unified-auth.php';

try {
    // Create database connection
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
    
    // Initialize unified authentication
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
    $auth = new UnifiedAuth($pdo, $jwtSecret);
    
    // Get request data
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    // Check authentication for all operations
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Token not provided']);
        exit;
    }
    
    $token = substr($authHeader, 7);
    $authResult = $auth->verifyToken($token);
    
    if (!$authResult['success']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Token not provided or invalid']);
        exit;
    }
    
    $currentUser = $authResult['user'];
    $currentTenant = $authResult['tenant'];
    
    // Handle different endpoints
    switch ($method) {
        case 'GET':
            handleGetRequest($pdo, $endpoint, $_GET, $currentUser, $currentTenant);
            break;
        case 'POST':
            handlePostRequest($pdo, $endpoint, file_get_contents('php://input'), $currentUser, $currentTenant);
            break;
        case 'PUT':
            handlePutRequest($pdo, $endpoint, file_get_contents('php://input'), $currentUser, $currentTenant);
            break;
        case 'DELETE':
            handleDeleteRequest($pdo, $endpoint, $_GET, $currentUser, $currentTenant);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Client Dashboard Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'debug' => $e->getMessage()
    ]);
}

function handleGetRequest($pdo, $endpoint, $params, $currentUser, $currentTenant) {
    switch ($endpoint) {
        case 'dashboard':
            getDashboardStats($pdo, $currentUser, $currentTenant);
            break;
        case 'products':
            getProducts($pdo, $params, $currentUser, $currentTenant);
            break;
        case 'categories':
            getCategories($pdo, $params, $currentUser, $currentTenant);
            break;
        case 'sales':
            getSales($pdo, $params, $currentUser, $currentTenant);
            break;
        case 'customers':
            getCustomers($pdo, $params, $currentUser, $currentTenant);
            break;
        case 'inventory':
            getInventory($pdo, $params, $currentUser, $currentTenant);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handlePostRequest($pdo, $endpoint, $rawData, $currentUser, $currentTenant) {
    $data = json_decode($rawData, true);
    
    switch ($endpoint) {
        case 'products':
            createProduct($pdo, $data, $currentUser, $currentTenant);
            break;
        case 'categories':
            createCategory($pdo, $data, $currentUser, $currentTenant);
            break;
        case 'sales':
            createSale($pdo, $data, $currentUser, $currentTenant);
            break;
        case 'customers':
            createCustomer($pdo, $data, $currentUser, $currentTenant);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handlePutRequest($pdo, $endpoint, $rawData, $currentUser, $currentTenant) {
    $data = json_decode($rawData, true);
    
    switch ($endpoint) {
        case 'products':
            updateProduct($pdo, $data, $currentUser, $currentTenant);
            break;
        case 'categories':
            updateCategory($pdo, $data, $currentUser, $currentTenant);
            break;
        case 'customers':
            updateCustomer($pdo, $data, $currentUser, $currentTenant);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handleDeleteRequest($pdo, $endpoint, $params, $currentUser, $currentTenant) {
    switch ($endpoint) {
        case 'products':
            deleteProduct($pdo, $params, $currentUser, $currentTenant);
            break;
        case 'categories':
            deleteCategory($pdo, $params, $currentUser, $currentTenant);
            break;
        case 'customers':
            deleteCustomer($pdo, $params, $currentUser, $currentTenant);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

// Dashboard Functions
function getDashboardStats($pdo, $currentUser, $currentTenant) {
    try {
        $tenantId = $currentTenant['id'];
        
        // Today's sales
        $todaySales = $pdo->prepare("
            SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total 
            FROM sales WHERE tenant_id = ? AND DATE(created_at) = CURRENT_DATE
        ");
        $todaySales->execute([$tenantId]);
        $todayData = $todaySales->fetch();
        
        // This month's sales
        $monthSales = $pdo->prepare("
            SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total 
            FROM sales WHERE tenant_id = ? AND DATE_TRUNC('month', created_at) = DATE_TRUNC('month', CURRENT_DATE)
        ");
        $monthSales->execute([$tenantId]);
        $monthData = $monthSales->fetch();
        
        // Total products
        $totalProducts = $pdo->prepare("
            SELECT COUNT(*) as count FROM products WHERE tenant_id = ? AND status = 'active'
        ");
        $totalProducts->execute([$tenantId]);
        $productsData = $totalProducts->fetch();
        
        // Total customers
        $totalCustomers = $pdo->prepare("
            SELECT COUNT(*) as count FROM customers WHERE tenant_id = ?
        ");
        $totalCustomers->execute([$tenantId]);
        $customersData = $totalCustomers->fetch();
        
        // Low stock count
        $lowStockCount = $pdo->prepare("
            SELECT COUNT(*) as count FROM inventory i 
            JOIN products p ON i.product_id = p.id 
            WHERE i.tenant_id = ? AND i.quantity <= i.min_stock AND p.track_inventory = true
        ");
        $lowStockCount->execute([$tenantId]);
        $lowStockData = $lowStockCount->fetch();
        
        // Recent sales
        $recentSales = $pdo->prepare("
            SELECT s.id, s.total_amount, s.created_at,
                   CONCAT(c.first_name, ' ', c.last_name) as customer_name
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE s.tenant_id = ?
            ORDER BY s.created_at DESC
            LIMIT 10
        ");
        $recentSales->execute([$tenantId]);
        $recentSalesData = $recentSales->fetchAll();
        
        // Low stock products
        $lowStockProducts = $pdo->prepare("
            SELECT p.id, p.name, p.sku, i.quantity, i.min_stock
            FROM products p
            JOIN inventory i ON p.id = i.product_id
            WHERE p.tenant_id = ? AND i.quantity <= i.min_stock AND p.track_inventory = true
            ORDER BY i.quantity ASC
            LIMIT 10
        ");
        $lowStockProducts->execute([$tenantId]);
        $lowStockProductsData = $lowStockProducts->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'stats' => [
                    'today_sales' => [
                        'count' => (int)$todayData['count'],
                        'total' => (float)$todayData['total']
                    ],
                    'month_sales' => [
                        'count' => (int)$monthData['count'],
                        'total' => (float)$monthData['total']
                    ],
                    'total_products' => (int)$productsData['count'],
                    'total_customers' => (int)$customersData['count'],
                    'low_stock_count' => (int)$lowStockData['count']
                ],
                'recent_sales' => $recentSalesData,
                'low_stock_products' => $lowStockProductsData
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get dashboard stats error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch dashboard stats']);
    }
}

// Products Functions
function getProducts($pdo, $params, $currentUser, $currentTenant) {
    try {
        $tenantId = $currentUser['tenant_id'];
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        $offset = ($page - 1) * $limit;
        $search = $params['search'] ?? '';
        $category = $params['category'] ?? '';
        $status = $params['status'] ?? '';
        
        $whereConditions = ['p.tenant_id = ?'];
        $bindParams = [$tenantId];
        
        if ($search) {
            $whereConditions[] = "(p.name ILIKE ? OR p.sku ILIKE ? OR p.barcode ILIKE ?)";
            $searchParam = "%$search%";
            $bindParams[] = $searchParam;
            $bindParams[] = $searchParam;
            $bindParams[] = $searchParam;
        }
        
        if ($category) {
            $whereConditions[] = "p.category_id = ?";
            $bindParams[] = $category;
        }
        
        if ($status) {
            $whereConditions[] = "p.status = ?";
            $bindParams[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT p.*, c.name as category_name, i.quantity, i.min_stock 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN inventory i ON p.id = i.product_id 
                WHERE $whereClause
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $bindParams[] = $limit;
        $bindParams[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindParams);
        $products = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM products p WHERE $whereClause";
        $countStmt = $pdo->prepare($countSql);
        array_pop($bindParams); // Remove limit
        array_pop($bindParams); // Remove offset
        $countStmt->execute($bindParams);
        $total = $countStmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'products' => $products,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get products error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch products']);
    }
}

function createProduct($pdo, $data, $currentUser, $currentTenant) {
    try {
        if (empty($data['name']) || empty($data['price'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Name and price are required']);
            return;
        }
        
        $tenantId = $currentUser['tenant_id'];
        
        // Check for duplicate SKU/barcode
        if (!empty($data['sku'])) {
            $checkStmt = $pdo->prepare("SELECT id FROM products WHERE tenant_id = ? AND sku = ?");
            $checkStmt->execute([$tenantId, $data['sku']]);
            if ($checkStmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'SKU already exists']);
                return;
            }
        }
        
        if (!empty($data['barcode'])) {
            $checkStmt = $pdo->prepare("SELECT id FROM products WHERE tenant_id = ? AND barcode = ?");
            $checkStmt->execute([$tenantId, $data['barcode']]);
            if ($checkStmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Barcode already exists']);
                return;
            }
        }
        
        $pdo->beginTransaction();
        
        // Create product
        $productSql = "INSERT INTO products (id, tenant_id, category_id, name, description, sku, barcode, price, cost, tax_rate, track_inventory, status, image_url, created_at, updated_at) 
                      VALUES (uuid_generate_v4(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) RETURNING id";
        $productStmt = $pdo->prepare($productSql);
        $productStmt->execute([
            $tenantId,
            $data['category_id'] ?? null,
            $data['name'],
            $data['description'] ?? null,
            $data['sku'] ?? null,
            $data['barcode'] ?? null,
            $data['price'],
            $data['cost'] ?? 0,
            $data['tax_rate'] ?? 0,
            $data['track_inventory'] ?? true,
            $data['status'] ?? 'active',
            $data['image_url'] ?? null
        ]);
        
        $productId = $productStmt->fetch()['id'];
        
        // Create inventory record if tracking inventory
        if ($data['track_inventory'] ?? true) {
            $inventorySql = "INSERT INTO inventory (id, tenant_id, product_id, quantity, min_stock, max_stock, created_at, updated_at) 
                            VALUES (uuid_generate_v4(), ?, ?, ?, ?, ?, NOW(), NOW())";
            $inventoryStmt = $pdo->prepare($inventorySql);
            $inventoryStmt->execute([
                $tenantId,
                $productId,
                $data['stock'] ?? 0,
                $data['min_stock'] ?? 0,
                $data['max_stock'] ?? null
            ]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => ['id' => $productId]
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Create product error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create product']);
    }
}

function updateProduct($pdo, $data, $currentUser, $currentTenant) {
    try {
        if (empty($data['id']) || empty($data['name']) || empty($data['price'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID, name and price are required']);
            return;
        }
        
        $tenantId = $currentUser['tenant_id'];
        
        // Check if product exists and belongs to tenant
        $checkStmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND tenant_id = ?");
        $checkStmt->execute([$data['id'], $tenantId]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Product not found']);
            return;
        }
        
        // Check for duplicate SKU/barcode (excluding current product)
        if (!empty($data['sku'])) {
            $checkStmt = $pdo->prepare("SELECT id FROM products WHERE tenant_id = ? AND sku = ? AND id != ?");
            $checkStmt->execute([$tenantId, $data['sku'], $data['id']]);
            if ($checkStmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'SKU already exists']);
                return;
            }
        }
        
        if (!empty($data['barcode'])) {
            $checkStmt = $pdo->prepare("SELECT id FROM products WHERE tenant_id = ? AND barcode = ? AND id != ?");
            $checkStmt->execute([$tenantId, $data['barcode'], $data['id']]);
            if ($checkStmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Barcode already exists']);
                return;
            }
        }
        
        $pdo->beginTransaction();
        
        // Update product
        $productSql = "UPDATE products SET 
                      category_id = ?, name = ?, description = ?, sku = ?, barcode = ?, 
                      price = ?, cost = ?, tax_rate = ?, track_inventory = ?, status = ?, 
                      image_url = ?, updated_at = NOW() 
                      WHERE id = ? AND tenant_id = ?";
        $productStmt = $pdo->prepare($productSql);
        $productStmt->execute([
            $data['category_id'] ?? null,
            $data['name'],
            $data['description'] ?? null,
            $data['sku'] ?? null,
            $data['barcode'] ?? null,
            $data['price'],
            $data['cost'] ?? 0,
            $data['tax_rate'] ?? 0,
            $data['track_inventory'] ?? true,
            $data['status'] ?? 'active',
            $data['image_url'] ?? null,
            $data['id'],
            $tenantId
        ]);
        
        // Update inventory if exists
        if (isset($data['stock']) || isset($data['min_stock']) || isset($data['max_stock'])) {
            $inventorySql = "UPDATE inventory SET 
                            quantity = ?, min_stock = ?, max_stock = ?, updated_at = NOW() 
                            WHERE product_id = ? AND tenant_id = ?";
            $inventoryStmt = $pdo->prepare($inventorySql);
            $inventoryStmt->execute([
                $data['stock'] ?? 0,
                $data['min_stock'] ?? 0,
                $data['max_stock'] ?? null,
                $data['id'],
                $tenantId
            ]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Product updated successfully'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Update product error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update product']);
    }
}

function deleteProduct($pdo, $params, $currentUser, $currentTenant) {
    try {
        $productId = $params['id'] ?? null;
        if (!$productId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Product ID required']);
            return;
        }
        
        $tenantId = $currentUser['tenant_id'];
        
        // Check if product exists and belongs to tenant
        $checkStmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND tenant_id = ?");
        $checkStmt->execute([$productId, $tenantId]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Product not found']);
            return;
        }
        
        $pdo->beginTransaction();
        
        // Delete inventory records
        $inventorySql = "DELETE FROM inventory WHERE product_id = ? AND tenant_id = ?";
        $inventoryStmt = $pdo->prepare($inventorySql);
        $inventoryStmt->execute([$productId, $tenantId]);
        
        // Delete product
        $productSql = "DELETE FROM products WHERE id = ? AND tenant_id = ?";
        $productStmt = $pdo->prepare($productSql);
        $productStmt->execute([$productId, $tenantId]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Delete product error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete product']);
    }
}

// Categories Functions
function getCategories($pdo, $params, $currentUser, $currentTenant) {
    try {
        $tenantId = $currentUser['tenant_id'];
        
        $sql = "SELECT * FROM categories WHERE tenant_id = ? ORDER BY name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tenantId]);
        $categories = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $categories
        ]);
    } catch (Exception $e) {
        error_log("Get categories error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch categories']);
    }
}

function createCategory($pdo, $data, $currentUser, $currentTenant) {
    try {
        if (empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Name is required']);
            return;
        }
        
        $tenantId = $currentUser['tenant_id'];
        
        // Check for duplicate name
        $checkStmt = $pdo->prepare("SELECT id FROM categories WHERE tenant_id = ? AND name = ?");
        $checkStmt->execute([$tenantId, $data['name']]);
        if ($checkStmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Category name already exists']);
            return;
        }
        
        $sql = "INSERT INTO categories (id, tenant_id, name, description, color, created_at, updated_at) 
                VALUES (uuid_generate_v4(), ?, ?, ?, ?, NOW(), NOW()) RETURNING id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $tenantId,
            $data['name'],
            $data['description'] ?? null,
            $data['color'] ?? '#e41e5b'
        ]);
        
        $categoryId = $stmt->fetch()['id'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => ['id' => $categoryId]
        ]);
    } catch (Exception $e) {
        error_log("Create category error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create category']);
    }
}

function updateCategory($pdo, $data, $currentUser, $currentTenant) {
    try {
        if (empty($data['id']) || empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID and name are required']);
            return;
        }
        
        $tenantId = $currentUser['tenant_id'];
        
        // Check if category exists and belongs to tenant
        $checkStmt = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND tenant_id = ?");
        $checkStmt->execute([$data['id'], $tenantId]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Category not found']);
            return;
        }
        
        // Check for duplicate name (excluding current category)
        $checkStmt = $pdo->prepare("SELECT id FROM categories WHERE tenant_id = ? AND name = ? AND id != ?");
        $checkStmt->execute([$tenantId, $data['name'], $data['id']]);
        if ($checkStmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Category name already exists']);
            return;
        }
        
        $sql = "UPDATE categories SET name = ?, description = ?, color = ?, updated_at = NOW() 
                WHERE id = ? AND tenant_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['color'] ?? '#e41e5b',
            $data['id'],
            $tenantId
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Category updated successfully'
        ]);
    } catch (Exception $e) {
        error_log("Update category error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update category']);
    }
}

function deleteCategory($pdo, $params, $currentUser, $currentTenant) {
    try {
        $categoryId = $params['id'] ?? null;
        if (!$categoryId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Category ID required']);
            return;
        }
        
        $tenantId = $currentUser['tenant_id'];
        
        // Check if category exists and belongs to tenant
        $checkStmt = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND tenant_id = ?");
        $checkStmt->execute([$categoryId, $tenantId]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Category not found']);
            return;
        }
        
        // Check if category has products
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ? AND tenant_id = ?");
        $checkStmt->execute([$categoryId, $tenantId]);
        $productCount = $checkStmt->fetch()['count'];
        
        if ($productCount > 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'error' => "Cannot delete category with $productCount products. Please move or delete the products first."
            ]);
            return;
        }
        
        $sql = "DELETE FROM categories WHERE id = ? AND tenant_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$categoryId, $tenantId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    } catch (Exception $e) {
        error_log("Delete category error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete category']);
    }
}

// Additional functions for sales, customers, and inventory will be added in the next part
function getSales($pdo, $params, $currentUser, $currentTenant) {
    try {
        $tenantId = $currentUser['tenant_id'];
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name
                FROM sales s
                LEFT JOIN customers c ON s.customer_id = c.id
                WHERE s.tenant_id = ?
                ORDER BY s.created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tenantId, $limit, $offset]);
        $sales = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM sales WHERE tenant_id = ?";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([$tenantId]);
        $total = $countStmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'sales' => $sales,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get sales error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch sales']);
    }
}

function getCustomers($pdo, $params, $currentUser, $currentTenant) {
    try {
        $tenantId = $currentUser['tenant_id'];
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        $offset = ($page - 1) * $limit;
        $search = $params['search'] ?? '';
        
        $whereConditions = ['tenant_id = ?'];
        $bindParams = [$tenantId];
        
        if ($search) {
            $whereConditions[] = "(first_name ILIKE ? OR last_name ILIKE ? OR email ILIKE ? OR phone ILIKE ?)";
            $searchParam = "%$search%";
            $bindParams[] = $searchParam;
            $bindParams[] = $searchParam;
            $bindParams[] = $searchParam;
            $bindParams[] = $searchParam;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT * FROM customers WHERE $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $bindParams[] = $limit;
        $bindParams[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindParams);
        $customers = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM customers WHERE $whereClause";
        $countStmt = $pdo->prepare($countSql);
        array_pop($bindParams); // Remove limit
        array_pop($bindParams); // Remove offset
        $countStmt->execute($bindParams);
        $total = $countStmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'customers' => $customers,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get customers error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch customers']);
    }
}

function getInventory($pdo, $params, $currentUser, $currentTenant) {
    try {
        $tenantId = $currentUser['tenant_id'];
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        $offset = ($page - 1) * $limit;
        $search = $params['search'] ?? '';
        $lowStock = $params['low_stock'] ?? false;
        
        $whereConditions = ['i.tenant_id = ?'];
        $bindParams = [$tenantId];
        
        if ($search) {
            $whereConditions[] = "(p.name ILIKE ? OR p.sku ILIKE ?)";
            $searchParam = "%$search%";
            $bindParams[] = $searchParam;
            $bindParams[] = $searchParam;
        }
        
        if ($lowStock) {
            $whereConditions[] = "i.quantity <= i.min_stock";
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT p.id, p.name, p.sku, p.price, i.quantity, i.min_stock, i.max_stock,
                       CASE 
                           WHEN i.quantity <= i.min_stock THEN 'low'
                           WHEN i.quantity >= i.max_stock THEN 'high'
                           ELSE 'normal'
                       END as stock_status
                FROM products p
                JOIN inventory i ON p.id = i.product_id
                WHERE $whereClause
                ORDER BY p.name ASC
                LIMIT ? OFFSET ?";
        
        $bindParams[] = $limit;
        $bindParams[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindParams);
        $inventory = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM products p JOIN inventory i ON p.id = i.product_id WHERE $whereClause";
        $countStmt = $pdo->prepare($countSql);
        array_pop($bindParams); // Remove limit
        array_pop($bindParams); // Remove offset
        $countStmt->execute($bindParams);
        $total = $countStmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'inventory' => $inventory,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get inventory error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch inventory']);
    }
}
