<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test if we can reach the support portal endpoint
$url = 'https://ardentpos.com/api/support-portal/knowledgebase';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer test-token'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo json_encode([
    'success' => true,
    'test_url' => $url,
    'http_code' => $httpCode,
    'response' => $response,
    'curl_error' => $error,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
