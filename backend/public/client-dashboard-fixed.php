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

// Include unified authentication
require_once __DIR__ . '/auth/unified-auth.php';

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
    
    // Initialize unified authentication
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
    $auth = new UnifiedAuth($pdo, $jwtSecret);
    
    // Check authentication
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
    
    // Only regular users (not super admins) can access client dashboard
    if ($currentUser['role'] === 'super_admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Super admins cannot access client dashboard']);
        exit;
    }
    
    // Regular users must have a tenant
    if (!$currentTenant) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Tenant required for client dashboard access']);
        exit;
    }
    
    // Handle requests
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    switch ($method) {
        case 'GET':
            if ($endpoint === 'dashboard') {
                getDashboardStats($pdo, $currentTenant);
            } elseif ($endpoint === 'products') {
                getProducts($pdo, $currentTenant);
            } elseif ($endpoint === 'categories') {
                getCategories($pdo, $currentTenant);
            } elseif ($endpoint === 'sales') {
                getSales($pdo, $currentTenant);
            } elseif ($endpoint === 'customers') {
                getCustomers($pdo, $currentTenant);
            } elseif ($endpoint === 'inventory') {
                getInventory($pdo, $currentTenant);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
            }
            break;
            
        case 'POST':
            if ($endpoint === 'products') {
                createProduct($pdo, $currentTenant);
            } elseif ($endpoint === 'categories') {
                createCategory($pdo, $currentTenant);
            } elseif ($endpoint === 'sales') {
                createSale($pdo, $currentTenant);
            } elseif ($endpoint === 'customers') {
                createCustomer($pdo, $currentTenant);
            } elseif ($endpoint === 'inventory') {
                updateInventory($pdo, $currentTenant);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
            }
            break;
            
        case 'PUT':
            if ($endpoint === 'products') {
                updateProduct($pdo, $currentTenant);
            } elseif ($endpoint === 'categories') {
                updateCategory($pdo, $currentTenant);
            } elseif ($endpoint === 'sales') {
                updateSale($pdo, $currentTenant);
            } elseif ($endpoint === 'customers') {
                updateCustomer($pdo, $currentTenant);
            } elseif ($endpoint === 'inventory') {
                updateInventory($pdo, $currentTenant);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
            }
            break;
            
        case 'DELETE':
            if ($endpoint === 'products') {
                deleteProduct($pdo, $currentTenant);
            } elseif ($endpoint === 'categories') {
                deleteCategory($pdo, $currentTenant);
            } elseif ($endpoint === 'sales') {
                deleteSale($pdo, $currentTenant);
            } elseif ($endpoint === 'customers') {
                deleteCustomer($pdo, $currentTenant);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Client Dashboard Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}

function getDashboardStats($pdo, $currentTenant) {
    try {
        // Get total products
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_products FROM products WHERE tenant_id = ?");
        $stmt->execute([$currentTenant['id']]);
        $totalProducts = $stmt->fetch()['total_products'];
        
        // Get total sales
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_sales FROM sales WHERE tenant_id = ?");
        $stmt->execute([$currentTenant['id']]);
        $totalSales = $stmt->fetch()['total_sales'];
        
        // Get total customers
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_customers FROM customers WHERE tenant_id = ?");
        $stmt->execute([$currentTenant['id']]);
        $totalCustomers = $stmt->fetch()['total_customers'];
        
        // Get total revenue
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total_revenue FROM sales WHERE tenant_id = ?");
        $stmt->execute([$currentTenant['id']]);
        $totalRevenue = $stmt->fetch()['total_revenue'];
        
        // Get low stock products
        $stmt = $pdo->prepare("SELECT COUNT(*) as low_stock_count FROM products WHERE tenant_id = ? AND stock_quantity <= 10");
        $stmt->execute([$currentTenant['id']]);
        $lowStockCount = $stmt->fetch()['low_stock_count'];
        
        // Get recent sales
        $stmt = $pdo->prepare("
            SELECT s.*, c.first_name, c.last_name 
            FROM sales s 
            LEFT JOIN customers c ON s.customer_id = c.id 
            WHERE s.tenant_id = ? 
            ORDER BY s.created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$currentTenant['id']]);
        $recentSales = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_products' => (int)$totalProducts,
                'total_sales' => (int)$totalSales,
                'total_customers' => (int)$totalCustomers,
                'total_revenue' => (float)$totalRevenue,
                'low_stock_count' => (int)$lowStockCount,
                'recent_sales' => $recentSales
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get dashboard stats error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch dashboard stats']);
    }
}

function getProducts($pdo, $currentTenant) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $search = $_GET['search'] ?? '';
        $category_id = $_GET['category_id'] ?? '';
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['p.tenant_id = ?'];
        $params = [$currentTenant['id']];
        
        if (!empty($search)) {
            $whereConditions[] = '(p.name ILIKE ? OR p.description ILIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($category_id)) {
            $whereConditions[] = 'p.category_id = ?';
            $params[] = $category_id;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM products p WHERE $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get products with category info
        $sql = "
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE $whereClause 
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'products' => $products,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
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

function createProduct($pdo, $currentTenant) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['name']) || empty($data['price'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Name and price are required']);
            return;
        }
        
        $sql = "
            INSERT INTO products (id, tenant_id, name, description, price, cost_price, stock_quantity, category_id, sku, barcode, created_at, updated_at)
            VALUES (uuid_generate_v4(), ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $currentTenant['id'],
            $data['name'],
            $data['description'] ?? '',
            $data['price'],
            $data['cost_price'] ?? 0,
            $data['stock_quantity'] ?? 0,
            $data['category_id'] ?? null,
            $data['sku'] ?? '',
            $data['barcode'] ?? ''
        ]);
        
        $product = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $product
        ]);
    } catch (Exception $e) {
        error_log("Create product error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create product']);
    }
}

function updateProduct($pdo, $currentTenant) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Product ID is required']);
            return;
        }
        
        $sql = "
            UPDATE products 
            SET name = ?, description = ?, price = ?, cost_price = ?, stock_quantity = ?, category_id = ?, sku = ?, barcode = ?, updated_at = NOW()
            WHERE id = ? AND tenant_id = ?
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['name'] ?? '',
            $data['description'] ?? '',
            $data['price'] ?? 0,
            $data['cost_price'] ?? 0,
            $data['stock_quantity'] ?? 0,
            $data['category_id'] ?? null,
            $data['sku'] ?? '',
            $data['barcode'] ?? '',
            $data['id'],
            $currentTenant['id']
        ]);
        
        $product = $stmt->fetch();
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Product not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $product
        ]);
    } catch (Exception $e) {
        error_log("Update product error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update product']);
    }
}

function deleteProduct($pdo, $currentTenant) {
    try {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Product ID is required']);
            return;
        }
        
        $sql = "DELETE FROM products WHERE id = ? AND tenant_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $currentTenant['id']]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Product not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    } catch (Exception $e) {
        error_log("Delete product error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete product']);
    }
}

function getCategories($pdo, $currentTenant) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $search = $_GET['search'] ?? '';
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['tenant_id = ?'];
        $params = [$currentTenant['id']];
        
        if (!empty($search)) {
            $whereConditions[] = 'name ILIKE ?';
            $params[] = "%$search%";
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM categories WHERE $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get categories
        $sql = "
            SELECT * FROM categories 
            WHERE $whereClause 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $categories = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get categories error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch categories']);
    }
}

function createCategory($pdo, $currentTenant) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Category name is required']);
            return;
        }
        
        $sql = "
            INSERT INTO categories (id, tenant_id, name, description, created_at, updated_at)
            VALUES (uuid_generate_v4(), ?, ?, ?, NOW(), NOW())
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $currentTenant['id'],
            $data['name'],
            $data['description'] ?? ''
        ]);
        
        $category = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $category
        ]);
    } catch (Exception $e) {
        error_log("Create category error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create category']);
    }
}

function updateCategory($pdo, $currentTenant) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Category ID is required']);
            return;
        }
        
        $sql = "
            UPDATE categories 
            SET name = ?, description = ?, updated_at = NOW()
            WHERE id = ? AND tenant_id = ?
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['name'] ?? '',
            $data['description'] ?? '',
            $data['id'],
            $currentTenant['id']
        ]);
        
        $category = $stmt->fetch();
        
        if (!$category) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Category not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $category
        ]);
    } catch (Exception $e) {
        error_log("Update category error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update category']);
    }
}

function deleteCategory($pdo, $currentTenant) {
    try {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Category ID is required']);
            return;
        }
        
        $sql = "DELETE FROM categories WHERE id = ? AND tenant_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $currentTenant['id']]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Category not found']);
            return;
        }
        
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

function getSales($pdo, $currentTenant) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $search = $_GET['search'] ?? '';
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['s.tenant_id = ?'];
        $params = [$currentTenant['id']];
        
        if (!empty($search)) {
            $whereConditions[] = '(s.invoice_number ILIKE ? OR c.first_name ILIKE ? OR c.last_name ILIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($start_date)) {
            $whereConditions[] = 's.created_at >= ?';
            $params[] = $start_date;
        }
        
        if (!empty($end_date)) {
            $whereConditions[] = 's.created_at <= ?';
            $params[] = $end_date;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "
            SELECT COUNT(*) FROM sales s 
            LEFT JOIN customers c ON s.customer_id = c.id 
            WHERE $whereClause
        ";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get sales with customer info
        $sql = "
            SELECT s.*, c.first_name, c.last_name, c.email 
            FROM sales s 
            LEFT JOIN customers c ON s.customer_id = c.id 
            WHERE $whereClause 
            ORDER BY s.created_at DESC 
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sales = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'sales' => $sales,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
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

function createSale($pdo, $currentTenant) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['items']) || !is_array($data['items'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Sale items are required']);
            return;
        }
        
        $pdo->beginTransaction();
        
        try {
            // Create sale record
            $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            
            $sql = "
                INSERT INTO sales (id, tenant_id, invoice_number, customer_id, total_amount, tax_amount, discount_amount, payment_method, status, created_at, updated_at)
                VALUES (uuid_generate_v4(), ?, ?, ?, ?, ?, ?, ?, 'completed', NOW(), NOW())
                RETURNING *
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $currentTenant['id'],
                $invoiceNumber,
                $data['customer_id'] ?? null,
                $data['total_amount'] ?? 0,
                $data['tax_amount'] ?? 0,
                $data['discount_amount'] ?? 0,
                $data['payment_method'] ?? 'cash'
            ]);
            
            $sale = $stmt->fetch();
            $saleId = $sale['id'];
            
            // Create sale items and update inventory
            foreach ($data['items'] as $item) {
                // Create sale item
                $itemSql = "
                    INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, total_price, created_at)
                    VALUES (uuid_generate_v4(), ?, ?, ?, ?, ?, NOW())
                ";
                $itemStmt = $pdo->prepare($itemSql);
                $itemStmt->execute([
                    $saleId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['quantity'] * $item['unit_price']
                ]);
                
                // Update product stock
                $updateStockSql = "
                    UPDATE products 
                    SET stock_quantity = stock_quantity - ?, updated_at = NOW()
                    WHERE id = ? AND tenant_id = ?
                ";
                $updateStockStmt = $pdo->prepare($updateStockSql);
                $updateStockStmt->execute([
                    $item['quantity'],
                    $item['product_id'],
                    $currentTenant['id']
                ]);
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'data' => $sale
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        error_log("Create sale error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create sale']);
    }
}

function updateSale($pdo, $currentTenant) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Sale ID is required']);
            return;
        }
        
        $sql = "
            UPDATE sales 
            SET customer_id = ?, total_amount = ?, tax_amount = ?, discount_amount = ?, payment_method = ?, status = ?, updated_at = NOW()
            WHERE id = ? AND tenant_id = ?
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['customer_id'] ?? null,
            $data['total_amount'] ?? 0,
            $data['tax_amount'] ?? 0,
            $data['discount_amount'] ?? 0,
            $data['payment_method'] ?? 'cash',
            $data['status'] ?? 'completed',
            $data['id'],
            $currentTenant['id']
        ]);
        
        $sale = $stmt->fetch();
        
        if (!$sale) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Sale not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $sale
        ]);
    } catch (Exception $e) {
        error_log("Update sale error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update sale']);
    }
}

function deleteSale($pdo, $currentTenant) {
    try {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Sale ID is required']);
            return;
        }
        
        $pdo->beginTransaction();
        
        try {
            // Get sale items to restore inventory
            $itemsSql = "SELECT product_id, quantity FROM sale_items WHERE sale_id = ?";
            $itemsStmt = $pdo->prepare($itemsSql);
            $itemsStmt->execute([$id]);
            $items = $itemsStmt->fetchAll();
            
            // Restore inventory
            foreach ($items as $item) {
                $updateStockSql = "
                    UPDATE products 
                    SET stock_quantity = stock_quantity + ?, updated_at = NOW()
                    WHERE id = ? AND tenant_id = ?
                ";
                $updateStockStmt = $pdo->prepare($updateStockSql);
                $updateStockStmt->execute([
                    $item['quantity'],
                    $item['product_id'],
                    $currentTenant['id']
                ]);
            }
            
            // Delete sale items
            $deleteItemsSql = "DELETE FROM sale_items WHERE sale_id = ?";
            $deleteItemsStmt = $pdo->prepare($deleteItemsSql);
            $deleteItemsStmt->execute([$id]);
            
            // Delete sale
            $deleteSaleSql = "DELETE FROM sales WHERE id = ? AND tenant_id = ?";
            $deleteSaleStmt = $pdo->prepare($deleteSaleSql);
            $deleteSaleStmt->execute([$id, $currentTenant['id']]);
            
            if ($deleteSaleStmt->rowCount() === 0) {
                throw new Exception('Sale not found');
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Sale deleted successfully'
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        error_log("Delete sale error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete sale']);
    }
}

function getCustomers($pdo, $currentTenant) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $search = $_GET['search'] ?? '';
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['tenant_id = ?'];
        $params = [$currentTenant['id']];
        
        if (!empty($search)) {
            $whereConditions[] = '(first_name ILIKE ? OR last_name ILIKE ? OR email ILIKE ? OR phone ILIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM customers WHERE $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get customers
        $sql = "
            SELECT * FROM customers 
            WHERE $whereClause 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'customers' => $customers,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
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

function createCustomer($pdo, $currentTenant) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['first_name']) || empty($data['last_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'First name and last name are required']);
            return;
        }
        
        $sql = "
            INSERT INTO customers (id, tenant_id, first_name, last_name, email, phone, address, created_at, updated_at)
            VALUES (uuid_generate_v4(), ?, ?, ?, ?, ?, ?, NOW(), NOW())
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $currentTenant['id'],
            $data['first_name'],
            $data['last_name'],
            $data['email'] ?? '',
            $data['phone'] ?? '',
            $data['address'] ?? ''
        ]);
        
        $customer = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'data' => $customer
        ]);
    } catch (Exception $e) {
        error_log("Create customer error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create customer']);
    }
}

function updateCustomer($pdo, $currentTenant) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Customer ID is required']);
            return;
        }
        
        $sql = "
            UPDATE customers 
            SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW()
            WHERE id = ? AND tenant_id = ?
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['first_name'] ?? '',
            $data['last_name'] ?? '',
            $data['email'] ?? '',
            $data['phone'] ?? '',
            $data['address'] ?? '',
            $data['id'],
            $currentTenant['id']
        ]);
        
        $customer = $stmt->fetch();
        
        if (!$customer) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Customer not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $customer
        ]);
    } catch (Exception $e) {
        error_log("Update customer error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update customer']);
    }
}

function deleteCustomer($pdo, $currentTenant) {
    try {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Customer ID is required']);
            return;
        }
        
        $sql = "DELETE FROM customers WHERE id = ? AND tenant_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $currentTenant['id']]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Customer not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Customer deleted successfully'
        ]);
    } catch (Exception $e) {
        error_log("Delete customer error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete customer']);
    }
}

function getInventory($pdo, $currentTenant) {
    try {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $low_stock = $_GET['low_stock'] ?? false;
        $product_id = $_GET['product_id'] ?? '';
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['p.tenant_id = ?'];
        $params = [$currentTenant['id']];
        
        if ($low_stock) {
            $whereConditions[] = 'p.stock_quantity <= 10';
        }
        
        if (!empty($product_id)) {
            $whereConditions[] = 'p.id = ?';
            $params[] = $product_id;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "
            SELECT COUNT(*) FROM products p 
            WHERE $whereClause
        ";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get inventory with category info
        $sql = "
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE $whereClause 
            ORDER BY p.stock_quantity ASC, p.name ASC 
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $inventory = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'inventory' => $inventory,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
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

function updateInventory($pdo, $currentTenant) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || empty($data['product_id']) || !isset($data['quantity'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Product ID and quantity are required']);
            return;
        }
        
        $sql = "
            UPDATE products 
            SET stock_quantity = ?, updated_at = NOW()
            WHERE id = ? AND tenant_id = ?
            RETURNING *
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['quantity'],
            $data['product_id'],
            $currentTenant['id']
        ]);
        
        $product = $stmt->fetch();
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Product not found']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $product
        ]);
    } catch (Exception $e) {
        error_log("Update inventory error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update inventory']);
    }
}
