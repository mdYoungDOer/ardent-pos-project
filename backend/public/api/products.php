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

    switch ($method) {
        case 'GET':
            // List products
            $stmt = $pdo->prepare("
                SELECT * FROM products 
                WHERE tenant_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$tenantId]);
            $products = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $products
            ]);
            break;

        case 'POST':
            // Create product
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data) {
                throw new Exception('Invalid JSON data');
            }

            $name = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');
            $price = floatval($data['price'] ?? 0);
            $stock = intval($data['stock'] ?? 0);

            if (empty($name) || $price <= 0) {
                throw new Exception('Name and price are required');
            }

            $productId = uniqid('product_', true);
            $stmt = $pdo->prepare("
                INSERT INTO products (id, tenant_id, name, description, price, stock, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$productId, $tenantId, $name, $description, $price, $stock]);

            echo json_encode([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => ['id' => $productId]
            ]);
            break;

        case 'PUT':
            // Update product
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data || empty($data['id'])) {
                throw new Exception('Product ID is required');
            }

            $name = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');
            $price = floatval($data['price'] ?? 0);
            $stock = intval($data['stock'] ?? 0);

            if (empty($name) || $price <= 0) {
                throw new Exception('Name and price are required');
            }

            $stmt = $pdo->prepare("
                UPDATE products 
                SET name = ?, description = ?, price = ?, stock = ?, updated_at = NOW()
                WHERE id = ? AND tenant_id = ?
            ");
            $stmt->execute([$name, $description, $price, $stock, $data['id'], $tenantId]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Product not found');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Product updated successfully'
            ]);
            break;

        case 'DELETE':
            // Delete product
            $productId = $_GET['id'] ?? '';

            if (empty($productId)) {
                throw new Exception('Product ID is required');
            }

            $stmt = $pdo->prepare("
                DELETE FROM products 
                WHERE id = ? AND tenant_id = ?
            ");
            $stmt->execute([$productId, $tenantId]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Product not found');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
