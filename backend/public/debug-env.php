<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Check all possible environment variable names for database credentials
$envVars = [
    'DB_HOST' => getenv('DB_HOST'),
    'DATABASE_HOST' => getenv('DATABASE_HOST'),
    'DB_PORT' => getenv('DB_PORT'),
    'DATABASE_PORT' => getenv('DATABASE_PORT'),
    'DB_NAME' => getenv('DB_NAME'),
    'DATABASE_NAME' => getenv('DATABASE_NAME'),
    'DB_USER' => getenv('DB_USER'),
    'DATABASE_USER' => getenv('DATABASE_USER'),
    'DB_USERNAME' => getenv('DB_USERNAME'),
    'DATABASE_USERNAME' => getenv('DATABASE_USERNAME'),
    'DB_PASS' => getenv('DB_PASS') ? 'SET' : 'EMPTY',
    'DATABASE_PASS' => getenv('DATABASE_PASS') ? 'SET' : 'EMPTY',
    'DB_PASSWORD' => getenv('DB_PASSWORD') ? 'SET' : 'EMPTY',
    'DATABASE_PASSWORD' => getenv('DATABASE_PASSWORD') ? 'SET' : 'EMPTY',
    'JWT_SECRET' => getenv('JWT_SECRET') ? 'SET' : 'EMPTY'
];

// Check $_ENV array
$envArray = [];
foreach ($_ENV as $key => $value) {
    if (strpos($key, 'DB') !== false || strpos($key, 'DATABASE') !== false || strpos($key, 'JWT') !== false) {
        $envArray[$key] = strpos($key, 'PASS') !== false || strpos($key, 'PASSWORD') !== false ? 'SET' : $value;
    }
}

// Check $_SERVER array for database-related variables
$serverVars = [];
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'DB') !== false || strpos($key, 'DATABASE') !== false || strpos($key, 'JWT') !== false) {
        $serverVars[$key] = strpos($key, 'PASS') !== false || strpos($key, 'PASSWORD') !== false ? 'SET' : $value;
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Environment variables debug info',
    'getenv' => $envVars,
    '$_ENV' => $envArray,
    '$_SERVER' => $serverVars,
    'all_env_keys' => array_keys($_ENV),
    'all_server_keys' => array_keys($_SERVER)
], JSON_PRETTY_PRINT);
?>
