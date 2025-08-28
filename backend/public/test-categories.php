<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test categories endpoint
$url = 'https://ardentpos.com/api/support-portal/categories';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo json_encode([
    'success' => true,
    'categories_test' => [
        'url' => $url,
        'http_code' => $httpCode,
        'response' => json_decode($response, true)
    ],
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
