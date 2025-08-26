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
    // Simple dashboard stats - no database dependency for now
    $stats = [
        'totalSales' => 15000.00,
        'totalOrders' => 45,
        'totalProducts' => 120,
        'totalCustomers' => 28,
        'salesGrowth' => 12.5,
        'ordersGrowth' => 8.2,
        'productsGrowth' => 5.7,
        'customersGrowth' => 15.3,
        'recentSales' => [
            [
                'id' => 1,
                'total_amount' => 250.00,
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'first_name' => 'John',
                'last_name' => 'Doe'
            ],
            [
                'id' => 2,
                'total_amount' => 180.50,
                'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours')),
                'first_name' => 'Jane',
                'last_name' => 'Smith'
            ],
            [
                'id' => 3,
                'total_amount' => 320.75,
                'created_at' => date('Y-m-d H:i:s', strtotime('-6 hours')),
                'first_name' => 'Mike',
                'last_name' => 'Johnson'
            ]
        ],
        'lowStockProducts' => [
            [
                'id' => 1,
                'name' => 'Product A',
                'price' => 25.00,
                'stock' => 5
            ],
            [
                'id' => 2,
                'name' => 'Product B',
                'price' => 15.50,
                'stock' => 0
            ]
        ]
    ];

    echo json_encode([
        'success' => true,
        'data' => $stats,
        'message' => 'Dashboard stats loaded successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Failed to load dashboard stats'
    ]);
}
?>
