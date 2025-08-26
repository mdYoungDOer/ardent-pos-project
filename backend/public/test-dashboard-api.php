<?php
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Dashboard API test endpoint working',
    'timestamp' => date('Y-m-d H:i:s'),
    'test_data' => [
        'totalSales' => 15000,
        'totalOrders' => 45,
        'totalProducts' => 120,
        'totalCustomers' => 28
    ]
]);
?>
