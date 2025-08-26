<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test all CRUD operations
function testCRUDOperations() {
    $results = [];
    $baseUrl = 'http://localhost/api'; // Adjust for your environment
    
    // Test Products CRUD
    $results['products'] = testProductsCRUD($baseUrl);
    
    // Test Customers CRUD
    $results['customers'] = testCustomersCRUD($baseUrl);
    
    // Test Sales CRUD
    $results['sales'] = testSalesCRUD($baseUrl);
    
    // Test Categories CRUD
    $results['categories'] = testCategoriesCRUD($baseUrl);
    
    // Test Locations CRUD
    $results['locations'] = testLocationsCRUD($baseUrl);
    
    return $results;
}

function testProductsCRUD($baseUrl) {
    $results = [];
    
    // Test GET (Read)
    $response = file_get_contents($baseUrl . '/products.php');
    $data = json_decode($response, true);
    $results['GET'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
    
    // Test POST (Create)
    $postData = json_encode([
        'name' => 'Test Product',
        'description' => 'Test Description',
        'price' => 25.00,
        'stock' => 10
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $postData
        ]
    ]);
    
    $response = file_get_contents($baseUrl . '/products.php', false, $context);
    $data = json_decode($response, true);
    $results['POST'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
    
    // Test PUT (Update)
    if ($data['success']) {
        $productId = $data['data']['id'];
        $putData = json_encode([
            'id' => $productId,
            'name' => 'Updated Test Product',
            'description' => 'Updated Description',
            'price' => 30.00,
            'stock' => 15
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'PUT',
                'header' => 'Content-Type: application/json',
                'content' => $putData
            ]
        ]);
        
        $response = file_get_contents($baseUrl . '/products.php', false, $context);
        $data = json_decode($response, true);
        $results['PUT'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
        
        // Test DELETE
        $context = stream_context_create([
            'http' => [
                'method' => 'DELETE'
            ]
        ]);
        
        $response = file_get_contents($baseUrl . '/products.php?id=' . $productId, false, $context);
        $data = json_decode($response, true);
        $results['DELETE'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
    } else {
        $results['PUT'] = 'SKIP: Create failed';
        $results['DELETE'] = 'SKIP: Create failed';
    }
    
    return $results;
}

function testCustomersCRUD($baseUrl) {
    $results = [];
    
    // Test GET (Read)
    $response = file_get_contents($baseUrl . '/customers.php');
    $data = json_decode($response, true);
    $results['GET'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
    
    // Test POST (Create)
    $postData = json_encode([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@test.com',
        'phone' => '+233 20 123 4567',
        'address' => '123 Test Street'
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $postData
        ]
    ]);
    
    $response = file_get_contents($baseUrl . '/customers.php', false, $context);
    $data = json_decode($response, true);
    $results['POST'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
    
    // Test PUT (Update)
    if ($data['success']) {
        $customerId = $data['data']['id'];
        $putData = json_encode([
            'id' => $customerId,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@test.com',
            'phone' => '+233 24 987 6543',
            'address' => '456 Updated Street'
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'PUT',
                'header' => 'Content-Type: application/json',
                'content' => $putData
            ]
        ]);
        
        $response = file_get_contents($baseUrl . '/customers.php', false, $context);
        $data = json_decode($response, true);
        $results['PUT'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
        
        // Test DELETE
        $context = stream_context_create([
            'http' => [
                'method' => 'DELETE'
            ]
        ]);
        
        $response = file_get_contents($baseUrl . '/customers.php?id=' . $customerId, false, $context);
        $data = json_decode($response, true);
        $results['DELETE'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
    } else {
        $results['PUT'] = 'SKIP: Create failed';
        $results['DELETE'] = 'SKIP: Create failed';
    }
    
    return $results;
}

function testSalesCRUD($baseUrl) {
    $results = [];
    
    // Test GET (Read)
    $response = file_get_contents($baseUrl . '/sales.php');
    $data = json_decode($response, true);
    $results['GET'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
    
    // Test POST (Create)
    $postData = json_encode([
        'customer_id' => null,
        'total_amount' => 100.00,
        'payment_method' => 'cash',
        'status' => 'completed',
        'items' => []
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $postData
        ]
    ]);
    
    $response = file_get_contents($baseUrl . '/sales.php', false, $context);
    $data = json_decode($response, true);
    $results['POST'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
    
    // Test PUT (Update)
    if ($data['success']) {
        $saleId = $data['data']['id'];
        $putData = json_encode([
            'id' => $saleId,
            'customer_id' => null,
            'total_amount' => 150.00,
            'payment_method' => 'card',
            'status' => 'completed'
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'PUT',
                'header' => 'Content-Type: application/json',
                'content' => $putData
            ]
        ]);
        
        $response = file_get_contents($baseUrl . '/sales.php', false, $context);
        $data = json_decode($response, true);
        $results['PUT'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
        
        // Test DELETE
        $context = stream_context_create([
            'http' => [
                'method' => 'DELETE'
            ]
        ]);
        
        $response = file_get_contents($baseUrl . '/sales.php?id=' . $saleId, false, $context);
        $data = json_decode($response, true);
        $results['DELETE'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
    } else {
        $results['PUT'] = 'SKIP: Create failed';
        $results['DELETE'] = 'SKIP: Create failed';
    }
    
    return $results;
}

function testCategoriesCRUD($baseUrl) {
    $results = [];
    
    // Test GET (Read)
    $response = file_get_contents($baseUrl . '/categories.php');
    $data = json_decode($response, true);
    $results['GET'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
    
    // Test POST (Create)
    $postData = json_encode([
        'name' => 'Test Category',
        'description' => 'Test Category Description',
        'color' => '#e41e5b'
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $postData
        ]
    ]);
    
    $response = file_get_contents($baseUrl . '/categories.php', false, $context);
    $data = json_decode($response, true);
    $results['POST'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
    
    // Test PUT (Update)
    if ($data['success']) {
        $categoryId = $data['data']['id'];
        $putData = json_encode([
            'id' => $categoryId,
            'name' => 'Updated Test Category',
            'description' => 'Updated Category Description',
            'color' => '#9a0864'
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'PUT',
                'header' => 'Content-Type: application/json',
                'content' => $putData
            ]
        ]);
        
        $response = file_get_contents($baseUrl . '/categories.php', false, $context);
        $data = json_decode($response, true);
        $results['PUT'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
        
        // Test DELETE
        $context = stream_context_create([
            'http' => [
                'method' => 'DELETE'
            ]
        ]);
        
        $response = file_get_contents($baseUrl . '/categories.php?id=' . $categoryId, false, $context);
        $data = json_decode($response, true);
        $results['DELETE'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
    } else {
        $results['PUT'] = 'SKIP: Create failed';
        $results['DELETE'] = 'SKIP: Create failed';
    }
    
    return $results;
}

function testLocationsCRUD($baseUrl) {
    $results = [];
    
    // Test GET (Read)
    $response = file_get_contents($baseUrl . '/locations.php');
    $data = json_decode($response, true);
    $results['GET'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
    
    // Test POST (Create)
    $postData = json_encode([
        'name' => 'Test Location',
        'address' => '123 Test Street',
        'city' => 'Test City',
        'state' => 'Test State',
        'country' => 'Ghana',
        'phone' => '+233 20 123 4567',
        'email' => 'test@location.com',
        'manager' => 'Test Manager',
        'status' => 'active'
    ]);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $postData
        ]
    ]);
    
    $response = file_get_contents($baseUrl . '/locations.php', false, $context);
    $data = json_decode($response, true);
    $results['POST'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
    
    // Test PUT (Update)
    if ($data['success']) {
        $locationId = $data['data']['id'];
        $putData = json_encode([
            'id' => $locationId,
            'name' => 'Updated Test Location',
            'address' => '456 Updated Street',
            'city' => 'Updated City',
            'state' => 'Updated State',
            'country' => 'Ghana',
            'phone' => '+233 24 987 6543',
            'email' => 'updated@location.com',
            'manager' => 'Updated Manager',
            'status' => 'active'
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'PUT',
                'header' => 'Content-Type: application/json',
                'content' => $putData
            ]
        ]);
        
        $response = file_get_contents($baseUrl . '/locations.php', false, $context);
        $data = json_decode($response, true);
        $results['PUT'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
        
        // Test DELETE
        $context = stream_context_create([
            'http' => [
                'method' => 'DELETE'
            ]
        ]);
        
        $response = file_get_contents($baseUrl . '/locations.php?id=' . $locationId, false, $context);
        $data = json_decode($response, true);
        $results['DELETE'] = $data['success'] ? 'PASS' : 'FAIL: ' . ($data['error'] ?? 'Unknown error');
    } else {
        $results['PUT'] = 'SKIP: Create failed';
        $results['DELETE'] = 'SKIP: Create failed';
    }
    
    return $results;
}

// Run tests
$testResults = testCRUDOperations();

echo json_encode([
    'success' => true,
    'message' => 'CRUD Operations Test Results',
    'timestamp' => date('Y-m-d H:i:s'),
    'results' => $testResults
], JSON_PRETTY_PRINT);
?>
