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
            // List customers
            $stmt = $pdo->prepare("
                SELECT * FROM customers 
                WHERE tenant_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$tenantId]);
            $customers = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $customers
            ]);
            break;

        case 'POST':
            // Create customer
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data) {
                throw new Exception('Invalid JSON data');
            }

            $firstName = trim($data['first_name'] ?? '');
            $lastName = trim($data['last_name'] ?? '');
            $email = trim($data['email'] ?? '');
            $phone = trim($data['phone'] ?? '');
            $address = trim($data['address'] ?? '');

            if (empty($firstName) || empty($lastName)) {
                throw new Exception('First name and last name are required');
            }

            $customerId = uniqid('customer_', true);
            $stmt = $pdo->prepare("
                INSERT INTO customers (id, tenant_id, first_name, last_name, email, phone, address, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$customerId, $tenantId, $firstName, $lastName, $email, $phone, $address]);

            echo json_encode([
                'success' => true,
                'message' => 'Customer created successfully',
                'data' => ['id' => $customerId]
            ]);
            break;

        case 'PUT':
            // Update customer
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data || empty($data['id'])) {
                throw new Exception('Customer ID is required');
            }

            $firstName = trim($data['first_name'] ?? '');
            $lastName = trim($data['last_name'] ?? '');
            $email = trim($data['email'] ?? '');
            $phone = trim($data['phone'] ?? '');
            $address = trim($data['address'] ?? '');

            if (empty($firstName) || empty($lastName)) {
                throw new Exception('First name and last name are required');
            }

            $stmt = $pdo->prepare("
                UPDATE customers 
                SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW()
                WHERE id = ? AND tenant_id = ?
            ");
            $stmt->execute([$firstName, $lastName, $email, $phone, $address, $data['id'], $tenantId]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Customer not found');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Customer updated successfully'
            ]);
            break;

        case 'DELETE':
            // Delete customer
            $customerId = $_GET['id'] ?? '';

            if (empty($customerId)) {
                throw new Exception('Customer ID is required');
            }

            $stmt = $pdo->prepare("
                DELETE FROM customers 
                WHERE id = ? AND tenant_id = ?
            ");
            $stmt->execute([$customerId, $tenantId]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Customer not found');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Customer deleted successfully'
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
