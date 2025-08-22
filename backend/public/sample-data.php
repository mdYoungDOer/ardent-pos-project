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

    // Sample products
    $products = [
        ['name' => 'iPhone 15 Pro', 'description' => 'Latest iPhone with advanced features', 'price' => 4500.00, 'stock' => 25],
        ['name' => 'Samsung Galaxy S24', 'description' => 'Premium Android smartphone', 'price' => 3800.00, 'stock' => 18],
        ['name' => 'MacBook Air M3', 'description' => 'Lightweight laptop for professionals', 'price' => 8500.00, 'stock' => 12],
        ['name' => 'AirPods Pro', 'description' => 'Wireless earbuds with noise cancellation', 'price' => 1200.00, 'stock' => 45],
        ['name' => 'iPad Air', 'description' => 'Versatile tablet for work and play', 'price' => 3200.00, 'stock' => 30],
        ['name' => 'Apple Watch Series 9', 'description' => 'Smartwatch with health features', 'price' => 1800.00, 'stock' => 22],
        ['name' => 'Sony WH-1000XM5', 'description' => 'Premium noise-canceling headphones', 'price' => 2100.00, 'stock' => 15],
        ['name' => 'Dell XPS 13', 'description' => 'Ultrabook for business users', 'price' => 7200.00, 'stock' => 8],
        ['name' => 'Google Pixel 8', 'description' => 'Android phone with great camera', 'price' => 3600.00, 'stock' => 20],
        ['name' => 'Microsoft Surface Pro', 'description' => '2-in-1 laptop and tablet', 'price' => 6800.00, 'stock' => 5]
    ];

    // Sample customers
    $customers = [
        ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john.doe@email.com', 'phone' => '+233 20 123 4567', 'address' => 'Accra, Ghana'],
        ['first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane.smith@email.com', 'phone' => '+233 24 234 5678', 'address' => 'Kumasi, Ghana'],
        ['first_name' => 'Michael', 'last_name' => 'Johnson', 'email' => 'michael.j@email.com', 'phone' => '+233 26 345 6789', 'address' => 'Tema, Ghana'],
        ['first_name' => 'Sarah', 'last_name' => 'Williams', 'email' => 'sarah.w@email.com', 'phone' => '+233 27 456 7890', 'address' => 'Cape Coast, Ghana'],
        ['first_name' => 'David', 'last_name' => 'Brown', 'email' => 'david.brown@email.com', 'phone' => '+233 28 567 8901', 'address' => 'Takoradi, Ghana'],
        ['first_name' => 'Emily', 'last_name' => 'Davis', 'email' => 'emily.davis@email.com', 'phone' => '+233 29 678 9012', 'address' => 'Tamale, Ghana'],
        ['first_name' => 'Robert', 'last_name' => 'Wilson', 'email' => 'robert.w@email.com', 'phone' => '+233 30 789 0123', 'address' => 'Ho, Ghana'],
        ['first_name' => 'Lisa', 'last_name' => 'Anderson', 'email' => 'lisa.anderson@email.com', 'phone' => '+233 31 890 1234', 'address' => 'Sunyani, Ghana']
    ];

    // Insert products
    $insertedProducts = [];
    foreach ($products as $product) {
        $productId = uniqid('product_', true);
        $stmt = $pdo->prepare("
            INSERT INTO products (id, tenant_id, name, description, price, stock, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$productId, $tenantId, $product['name'], $product['description'], $product['price'], $product['stock']]);
        $insertedProducts[] = $productId;
    }

    // Insert customers
    $insertedCustomers = [];
    foreach ($customers as $customer) {
        $customerId = uniqid('customer_', true);
        $stmt = $pdo->prepare("
            INSERT INTO customers (id, tenant_id, first_name, last_name, email, phone, address, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$customerId, $tenantId, $customer['first_name'], $customer['last_name'], $customer['email'], $customer['phone'], $customer['address']]);
        $insertedCustomers[] = $customerId;
    }

    // Create sample sales (last 30 days)
    $sales = [];
    for ($i = 0; $i < 25; $i++) {
        $saleId = uniqid('sale_', true);
        $customerId = $insertedCustomers[array_rand($insertedCustomers)];
        $numItems = rand(1, 4);
        $totalAmount = 0;
        $items = [];

        // Generate sale items
        for ($j = 0; $j < $numItems; $j++) {
            $productId = $insertedProducts[array_rand($insertedProducts)];
            $quantity = rand(1, 3);
            
            // Get product price
            $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            $unitPrice = $product['price'];
            
            $itemTotal = $quantity * $unitPrice;
            $totalAmount += $itemTotal;
            $items[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice
            ];
        }

        // Random date within last 30 days
        $daysAgo = rand(0, 30);
        $saleDate = date('Y-m-d H:i:s', strtotime("-$daysAgo days"));

        // Insert sale
        $stmt = $pdo->prepare("
            INSERT INTO sales (id, tenant_id, customer_id, total_amount, payment_method, notes, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$saleId, $tenantId, $customerId, $totalAmount, 'cash', 'Sample sale', $saleDate, $saleDate]);

        // Insert sale items
        foreach ($items as $item) {
            $itemId = uniqid('item_', true);
            $stmt = $pdo->prepare("
                INSERT INTO sale_items (id, sale_id, product_id, quantity, unit_price, created_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$itemId, $saleId, $item['product_id'], $item['quantity'], $item['unit_price'], $saleDate]);
        }

        $sales[] = $saleId;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Sample data created successfully',
        'data' => [
            'products_created' => count($insertedProducts),
            'customers_created' => count($insertedCustomers),
            'sales_created' => count($sales)
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
