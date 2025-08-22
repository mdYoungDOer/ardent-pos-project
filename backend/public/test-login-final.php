<?php
header('Content-Type: application/json');

// Test the login endpoint directly
$url = 'https://ardent-pos-app-sdq3t.ondigitalocean.app/api/auth/login.php';

$testData = [
    'email' => 'deyoungdoer@gmail.com',
    'password' => '@am171293GH!!'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo json_encode([
    'url' => $url,
    'http_code' => $httpCode,
    'response' => $response,
    'curl_error' => $error,
    'test_data' => $testData,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
