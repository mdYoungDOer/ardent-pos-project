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
            // List products with inventory
            $stmt = $pdo->prepare("
                SELECT p.*, COALESCE(i.quantity, 0) as stock
                FROM products p
                LEFT JOIN inventory i ON p.id = i.product_id AND i.tenant_id = p.tenant_id
                WHERE p.tenant_id = ? 
                ORDER BY p.created_at DESC
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

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Create product
                $productId = uniqid('product_', true);
                $stmt = $pdo->prepare("
                    INSERT INTO products (id, tenant_id, name, description, price, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$productId, $tenantId, $name, $description, $price]);

                // Create inventory record
                $inventoryId = uniqid('inventory_', true);
                $stmt = $pdo->prepare("
                    INSERT INTO inventory (id, tenant_id, product_id, quantity, created_at, updated_at)
                    VALUES (?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$inventoryId, $tenantId, $productId, $stock]);

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

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

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Update product
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET name = ?, description = ?, price = ?, updated_at = NOW()
                    WHERE id = ? AND tenant_id = ?
                ");
                $stmt->execute([$name, $description, $price, $data['id'], $tenantId]);

                // Update inventory
                $stmt = $pdo->prepare("
                    UPDATE inventory 
                    SET quantity = ?, updated_at = NOW()
                    WHERE product_id = ? AND tenant_id = ?
                ");
                $stmt->execute([$stock, $data['id'], $tenantId]);

                // If no inventory record exists, create one
                if ($stmt->rowCount() === 0) {
                    $inventoryId = uniqid('inventory_', true);
                    $stmt = $pdo->prepare("
                        INSERT INTO inventory (id, tenant_id, product_id, quantity, created_at, updated_at)
                        VALUES (?, ?, ?, ?, NOW(), NOW())
                    ");
                    $stmt->execute([$inventoryId, $tenantId, $data['id'], $stock]);
                }

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

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

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Delete inventory first (due to foreign key constraint)
                $stmt = $pdo->prepare("DELETE FROM inventory WHERE product_id = ? AND tenant_id = ?");
                $stmt->execute([$productId, $tenantId]);

                // Delete product
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$productId, $tenantId]);

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

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
