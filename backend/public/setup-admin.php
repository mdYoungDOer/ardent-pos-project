<?php
header('Content-Type: application/json');

// Only allow this in development or with a secret key
$secretKey = $_GET['key'] ?? '';
$allowedKey = 'ardent-pos-setup-2024';

if ($secretKey !== $allowedKey) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

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

    // Super admin credentials
    $email = 'deyoungdoer@gmail.com';
    $password = '@am171293GH!!';
    $firstName = 'DeYoung';
    $lastName = 'Doer';
    
    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if user already exists
    $existingUser = Database::fetch(
        "SELECT id FROM users WHERE email = ? AND tenant_id = '00000000-0000-0000-0000-000000000000'",
        [$email]
    );
    
    if ($existingUser) {
        // Update existing user
        Database::update(
            'users',
            [
                'password_hash' => $passwordHash,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'role' => 'super_admin',
                'status' => 'active'
            ],
            'id = ?',
            [$existingUser['id']]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Super admin user updated successfully!',
            'user' => [
                'email' => $email,
                'role' => 'super_admin',
                'status' => 'active'
            ]
        ]);
    } else {
        // Create new user
        $userId = Database::insert('users', [
            'tenant_id' => '00000000-0000-0000-0000-000000000000', // Super admin tenant
            'email' => $email,
            'password_hash' => $passwordHash,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => 'super_admin',
            'status' => 'active'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Super admin user created successfully!',
            'user' => [
                'id' => $userId,
                'email' => $email,
                'role' => 'super_admin',
                'status' => 'active'
            ]
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
