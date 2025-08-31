<?php
// Prevent any output before JSON
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

try {
    // Database connection
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_PORT'] ?? '5432',
        $_ENV['DB_NAME'] ?? 'defaultdb'
    );
    
    $pdo = new PDO(
        $dsn,
        $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? 'postgres',
        $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? 'password',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $check = [];
    $check['timestamp'] = date('Y-m-d H:i:s');
    $check['login_issues'] = [];
    $check['super_admin_status'] = [];
    $check['test_results'] = [];
    
    // Check if users table exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_name = 'users'");
    $usersTableExists = $stmt->fetchColumn() > 0;
    
    if (!$usersTableExists) {
        $check['login_issues'][] = 'Users table does not exist - database not properly initialized';
        $check['recommendations'][] = 'Run setup-unified-database.php to create the users table';
        
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($check, JSON_PRETTY_PRINT);
        exit;
    }
    
    // Get all users
    $stmt = $pdo->query("
        SELECT 
            id,
            first_name,
            last_name,
            email,
            password_hash,
            role,
            status,
            tenant_id,
            created_at
        FROM users 
        ORDER BY created_at DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $check['total_users'] = count($users);
    
    // Check for super admin users
    $superAdmins = array_filter($users, function($user) {
        return $user['role'] === 'super_admin';
    });
    
    $check['super_admin_count'] = count($superAdmins);
    
    if (empty($superAdmins)) {
        $check['login_issues'][] = 'No super admin users found - cannot login as super admin';
        $check['recommendations'][] = 'Run setup-unified-database.php to create default super admin';
    } else {
        foreach ($superAdmins as $admin) {
            $check['super_admin_status'][] = [
                'email' => $admin['email'],
                'name' => $admin['first_name'] . ' ' . $admin['last_name'],
                'status' => $admin['status'],
                'has_password' => !empty($admin['password_hash']),
                'password_length' => strlen($admin['password_hash']),
                'created_at' => $admin['created_at']
            ];
            
            // Test password hash format
            if (!empty($admin['password_hash'])) {
                if (strlen($admin['password_hash']) < 20) {
                    $check['login_issues'][] = "Super admin {$admin['email']} has suspiciously short password hash";
                }
                
                // Check if it's a valid bcrypt hash
                if (!preg_match('/^\$2[aby]\$\d{1,2}\$[./A-Za-z0-9]{53}$/', $admin['password_hash'])) {
                    $check['login_issues'][] = "Super admin {$admin['email']} has invalid password hash format";
                }
            } else {
                $check['login_issues'][] = "Super admin {$admin['email']} has no password hash";
            }
        }
    }
    
    // Check for users with invalid status
    $inactiveUsers = array_filter($users, function($user) {
        return $user['status'] !== 'active';
    });
    
    if (!empty($inactiveUsers)) {
        $check['login_issues'][] = 'Found users with non-active status: ' . 
            implode(', ', array_map(function($user) { return $user['email']; }, $inactiveUsers));
    }
    
    // Check for users with missing password hashes
    $usersWithoutPassword = array_filter($users, function($user) {
        return empty($user['password_hash']);
    });
    
    if (!empty($usersWithoutPassword)) {
        $check['login_issues'][] = 'Found users without password hashes: ' . 
            implode(', ', array_map(function($user) { return $user['email']; }, $usersWithoutPassword));
    }
    
    // Test specific super admin login
    $expectedSuperAdmin = 'superadmin@ardentpos.com';
    $superAdminUser = null;
    
    foreach ($users as $user) {
        if ($user['email'] === $expectedSuperAdmin) {
            $superAdminUser = $user;
            break;
        }
    }
    
    if ($superAdminUser) {
        $check['test_results']['expected_super_admin'] = [
            'found' => true,
            'email' => $superAdminUser['email'],
            'role' => $superAdminUser['role'],
            'status' => $superAdminUser['status'],
            'has_password' => !empty($superAdminUser['password_hash']),
            'tenant_id' => $superAdminUser['tenant_id']
        ];
        
        // Test password verification
        if (!empty($superAdminUser['password_hash'])) {
            $testPassword = 'superadmin123';
            $passwordValid = password_verify($testPassword, $superAdminUser['password_hash']);
            
            $check['test_results']['password_test'] = [
                'test_password' => $testPassword,
                'password_valid' => $passwordValid,
                'hash_format' => substr($superAdminUser['password_hash'], 0, 7) . '...'
            ];
            
            if (!$passwordValid) {
                $check['login_issues'][] = "Expected super admin password 'superadmin123' is not valid";
                $check['recommendations'][] = "Reset super admin password or check if different password was used";
            }
        }
    } else {
        $check['test_results']['expected_super_admin'] = [
            'found' => false,
            'expected_email' => $expectedSuperAdmin
        ];
        $check['login_issues'][] = "Expected super admin email '{$expectedSuperAdmin}' not found";
        $check['recommendations'][] = "Create super admin user with email '{$expectedSuperAdmin}'";
    }
    
    // Check for duplicate emails
    $emailCounts = [];
    foreach ($users as $user) {
        $email = $user['email'];
        $emailCounts[$email] = ($emailCounts[$email] ?? 0) + 1;
    }
    
    $duplicateEmails = array_filter($emailCounts, function($count) {
        return $count > 1;
    });
    
    if (!empty($duplicateEmails)) {
        $check['login_issues'][] = 'Found duplicate email addresses: ' . implode(', ', array_keys($duplicateEmails));
        $check['recommendations'][] = 'Remove duplicate user accounts';
    }
    
    // Check JWT secret
    $jwtSecret = $_ENV['JWT_SECRET'] ?? null;
    if (empty($jwtSecret)) {
        $check['login_issues'][] = 'JWT_SECRET environment variable not set';
        $check['recommendations'][] = 'Set JWT_SECRET environment variable';
    } else {
        $check['test_results']['jwt_secret'] = [
            'set' => true,
            'length' => strlen($jwtSecret),
            'strength' => strlen($jwtSecret) >= 32 ? 'strong' : 'weak'
        ];
        
        if (strlen($jwtSecret) < 32) {
            $check['login_issues'][] = 'JWT_SECRET is too short (should be at least 32 characters)';
            $check['recommendations'][] = 'Generate a stronger JWT_SECRET';
        }
    }
    
    // Check database connection for authentication
    $check['test_results']['database_connection'] = [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'database' => $_ENV['DB_NAME'] ?? 'defaultdb',
        'port' => $_ENV['DB_PORT'] ?? '5432',
        'connected' => true
    ];
    
    // Generate summary
    $check['summary'] = [
        'total_users' => count($users),
        'super_admin_count' => count($superAdmins),
        'active_users' => count($users) - count($inactiveUsers),
        'issues_found' => count($check['login_issues']),
        'recommendations' => count($check['recommendations'] ?? [])
    ];
    
    // Set overall status
    if (empty($check['login_issues'])) {
        $check['status'] = 'LOGIN_READY';
        $check['message'] = 'Login system appears to be working correctly';
    } else {
        $check['status'] = 'LOGIN_ISSUES';
        $check['message'] = 'Login issues detected that need to be resolved';
    }
    
    // Clear any output buffer and ensure proper JSON output
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($check, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("User login check error: " . $e->getMessage());
    
    // Clear any output buffer and ensure proper JSON output for errors
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'User login check failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'ERROR',
        'message' => 'Could not check user login system due to database errors'
    ], JSON_PRETTY_PRINT);
}
?>
