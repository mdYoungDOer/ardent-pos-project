<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';

use ArdentPOS\Controllers\AuthController;
use ArdentPOS\Core\Database;
use ArdentPOS\Core\Config;

try {
    // Load environment variables
    if (class_exists('Dotenv\Dotenv')) {
        $envPath = __DIR__ . '/../';
        if (file_exists($envPath . '.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable($envPath);
            $dotenv->load();
        }
    }

    // Initialize configuration and database
    Config::init();
    Database::init();

    // Set up the request data
    $input = [
        'email' => 'deyoungdoer@gmail.com',
        'password' => '@am171293GH!!'
    ];

    // Create a temporary file to simulate php://input
    $tempFile = tmpfile();
    fwrite($tempFile, json_encode($input));
    rewind($tempFile);

    // Override the php://input stream
    $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode($input);

    echo json_encode([
        'test' => 'Testing AuthController directly',
        'input' => $input,
        'raw_input' => $GLOBALS['HTTP_RAW_POST_DATA']
    ]);

    // Create AuthController instance
    $authController = new AuthController();
    
    // Call the login method directly
    $authController->login();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Test failed: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
