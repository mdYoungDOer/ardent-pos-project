<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'status' => 'success',
    'message' => 'New authentication system is ready!',
    'endpoints' => [
        'login' => '/auth/login.php',
        'register' => '/auth/register.php', 
        'verify' => '/auth/verify.php'
    ],
    'timestamp' => date('Y-m-d H:i:s'),
    'system' => 'Ardent POS - New Auth Architecture'
]);
