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
            // List sales with customer and items details
            $stmt = $pdo->prepare("
                SELECT s.*, 
                       c.first_name, c.last_name, c.email,
                       COUNT(si.id) as item_count
                FROM sales s
                LEFT JOIN customers c ON s.customer_id = c.id
                LEFT JOIN sale_items si ON s.id = si.sale_id
                WHERE s.tenant_id = ?
                GROUP BY s.id, c.first_name, c.last_name, c.email
                ORDER BY s.created_at DESC
            ");
            $stmt->execute([$tenantId]);
            $sales = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $sales
            ]);
            break;

        case 'POST':
            // Create sale with items
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data) {
                throw new Exception('Invalid JSON data');
            }

            $customerId = $data['customer_id'] ?? null;
            $items = $data['items'] ?? [];
            $paymentMethod = $data['payment_method'] ?? 'cash';
            $notes = trim($data['notes'] ?? '');

            if (empty($items)) {
                throw new Exception('Sale must have at least one item');
            }

            // Calculate total
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += ($item['quantity'] * $item['unit_price']);
            }

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Create sale
                $saleId = uniqid('sale_', true);
                $stmt = $pdo->prepare("
                    INSERT INTO sales (id, tenant_id, customer_id, total_amount, payment_method, notes, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$saleId, $tenantId, $customerId, $totalAmount, $paymentMethod, $notes]);

                // Create sale items and update product stock
                foreach ($items as $item) {
                    $itemId = uniqid('item_', true);
                    $stmt = $pdo->prepare("
                        INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$itemId, $saleId, $item['product_id'], $item['quantity'], $item['unit_price']]);

                    // Update product stock
                    $stmt = $pdo->prepare("
                        UPDATE products 
                        SET stock = stock - ?, updated_at = NOW()
                        WHERE id = ? AND tenant_id = ?
                    ");
                    $stmt->execute([$item['quantity'], $item['product_id'], $tenantId]);
                }

                $pdo->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Sale created successfully',
                    'data' => [
                        'id' => $saleId,
                        'total_amount' => $totalAmount
                    ]
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'PUT':
            // Update sale (limited - mainly for status changes)
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data || empty($data['id'])) {
                throw new Exception('Sale ID is required');
            }

            $status = $data['status'] ?? '';
            $notes = trim($data['notes'] ?? '');

            $stmt = $pdo->prepare("
                UPDATE sales 
                SET status = ?, notes = ?, updated_at = NOW()
                WHERE id = ? AND tenant_id = ?
            ");
            $stmt->execute([$status, $notes, $data['id'], $tenantId]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Sale not found');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Sale updated successfully'
            ]);
            break;

        case 'DELETE':
            // Delete sale (with items)
            $saleId = $_GET['id'] ?? '';

            if (empty($saleId)) {
                throw new Exception('Sale ID is required');
            }

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Delete sale items first
                $stmt = $pdo->prepare("DELETE FROM sale_items WHERE sale_id = ?");
                $stmt->execute([$saleId]);

                // Delete sale
                $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$saleId, $tenantId]);

                if ($stmt->rowCount() === 0) {
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
