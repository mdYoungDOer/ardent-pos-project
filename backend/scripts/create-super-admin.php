<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ArdentPOS\Core\Database;
use ArdentPOS\Core\Config;

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

try {
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
        echo "✅ Super admin user updated successfully!\n";
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
        echo "✅ Super admin user created successfully with ID: $userId\n";
    }
    
    echo "\n📋 Login Details:\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
    echo "Role: Super Admin\n";
    echo "\n🔗 Login URL: https://ardent-pos-app-sdq3t.ondigitalocean.app/auth/login\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
