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
    
    $inspection = [];
    $inspection['timestamp'] = date('Y-m-d H:i:s');
    $inspection['database_info'] = [];
    $inspection['tables'] = [];
    $inspection['users'] = [];
    $inspection['issues'] = [];
    $inspection['recommendations'] = [];
    
    // Get database information
    $inspection['database_info']['connection'] = 'Connected successfully';
    $inspection['database_info']['host'] = $_ENV['DB_HOST'] ?? 'localhost';
    $inspection['database_info']['database'] = $_ENV['DB_NAME'] ?? 'defaultdb';
    $inspection['database_info']['port'] = $_ENV['DB_PORT'] ?? '5432';
    
    // Check if UUID extension is enabled
    try {
        $stmt = $pdo->query("SELECT extname FROM pg_extension WHERE extname = 'uuid-ossp'");
        $uuidExtension = $stmt->fetch();
        $inspection['database_info']['uuid_extension'] = $uuidExtension ? 'Enabled' : 'Missing';
        
        if (!$uuidExtension) {
            $inspection['issues'][] = 'UUID extension not enabled - this will cause issues with UUID generation';
            $inspection['recommendations'][] = 'Enable UUID extension: CREATE EXTENSION IF NOT EXISTS "uuid-ossp"';
        }
    } catch (Exception $e) {
        $inspection['issues'][] = 'Could not check UUID extension: ' . $e->getMessage();
    }
    
    // Get all tables
    $stmt = $pdo->query("
        SELECT 
            table_name,
            table_type
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        ORDER BY table_name
    ");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $expectedTables = [
        'tenants', 'users', 'categories', 'products', 'inventory', 
        'customers', 'sales', 'sale_items', 'subscriptions', 'invoices', 
        'payments', 'contact_submissions', 'knowledgebase_categories', 
        'knowledgebase', 'support_tickets', 'support_replies', 'audit_logs'
    ];
    
    $foundTables = [];
    foreach ($tables as $table) {
        $foundTables[] = $table['table_name'];
        
        // Get table structure
        $stmt = $pdo->prepare("
            SELECT 
                column_name,
                data_type,
                is_nullable,
                column_default,
                character_maximum_length
            FROM information_schema.columns 
            WHERE table_name = ? 
            ORDER BY ordinal_position
        ");
        $stmt->execute([$table['table_name']]);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get row count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . $table['table_name']);
        $stmt->execute();
        $rowCount = $stmt->fetchColumn();
        
        $inspection['tables'][$table['table_name']] = [
            'type' => $table['table_type'],
            'columns' => $columns,
            'row_count' => $rowCount
        ];
    }
    
    // Check for missing tables
    $missingTables = array_diff($expectedTables, $foundTables);
    if (!empty($missingTables)) {
        $inspection['issues'][] = 'Missing tables: ' . implode(', ', $missingTables);
        $inspection['recommendations'][] = 'Run setup-unified-database.php to create missing tables';
    }
    
    // Check for unexpected tables
    $unexpectedTables = array_diff($foundTables, $expectedTables);
    if (!empty($unexpectedTables)) {
        $inspection['issues'][] = 'Unexpected tables found: ' . implode(', ', $unexpectedTables);
    }
    
    // Get all users with detailed information
    if (in_array('users', $foundTables)) {
        $stmt = $pdo->query("
            SELECT 
                id,
                first_name,
                last_name,
                email,
                role,
                status,
                tenant_id,
                created_at,
                updated_at
            FROM users 
            ORDER BY created_at DESC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $user) {
            // Get tenant information if available
            $tenantInfo = null;
            if ($user['tenant_id'] && in_array('tenants', $foundTables)) {
                $stmt = $pdo->prepare("SELECT name, subdomain FROM tenants WHERE id = ?");
                $stmt->execute([$user['tenant_id']]);
                $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($tenant) {
                    $tenantInfo = $tenant;
                }
            }
            
            $inspection['users'][] = [
                'id' => $user['id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'status' => $user['status'],
                'tenant' => $tenantInfo,
                'created_at' => $user['created_at'],
                'updated_at' => $user['updated_at']
            ];
        }
        
        // Check for super admin users
        $superAdmins = array_filter($users, function($user) {
            return $user['role'] === 'super_admin';
        });
        
        if (empty($superAdmins)) {
            $inspection['issues'][] = 'No super admin users found in the database';
            $inspection['recommendations'][] = 'Create a super admin user or run setup-unified-database.php';
        } else {
            $inspection['super_admin_count'] = count($superAdmins);
            $inspection['super_admins'] = array_map(function($user) {
                return [
                    'email' => $user['email'],
                    'name' => $user['first_name'] . ' ' . $user['last_name'],
                    'status' => $user['status'],
                    'created_at' => $user['created_at']
                ];
            }, $superAdmins);
        }
        
        // Check for users with null tenant_id (should be super admins)
        $usersWithoutTenant = array_filter($users, function($user) {
            return $user['role'] !== 'super_admin' && $user['tenant_id'] === null;
        });
        
        if (!empty($usersWithoutTenant)) {
            $inspection['issues'][] = 'Found users without tenant_id that are not super admins: ' . 
                implode(', ', array_map(function($user) { return $user['email']; }, $usersWithoutTenant));
        }
        
    } else {
        $inspection['issues'][] = 'Users table not found - database may not be properly initialized';
        $inspection['recommendations'][] = 'Run setup-unified-database.php to create the users table';
    }
    
    // Check foreign key constraints
    $stmt = $pdo->query("
        SELECT 
            tc.table_name, 
            kcu.column_name, 
            ccu.table_name AS foreign_table_name,
            ccu.column_name AS foreign_column_name 
        FROM information_schema.table_constraints AS tc 
        JOIN information_schema.key_column_usage AS kcu
            ON tc.constraint_name = kcu.constraint_name
            AND tc.table_schema = kcu.table_schema
        JOIN information_schema.constraint_column_usage AS ccu
            ON ccu.constraint_name = tc.constraint_name
            AND ccu.table_schema = tc.table_schema
        WHERE tc.constraint_type = 'FOREIGN KEY'
        ORDER BY tc.table_name, kcu.column_name
    ");
    $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $inspection['foreign_keys'] = $foreignKeys;
    
    // Check for orphaned records
    if (in_array('users', $foundTables) && in_array('tenants', $foundTables)) {
        $stmt = $pdo->query("
            SELECT COUNT(*) as orphaned_users
            FROM users u
            LEFT JOIN tenants t ON u.tenant_id = t.id
            WHERE u.tenant_id IS NOT NULL AND t.id IS NULL
        ");
        $orphanedUsers = $stmt->fetchColumn();
        
        if ($orphanedUsers > 0) {
            $inspection['issues'][] = "Found $orphanedUsers users with invalid tenant_id references";
            $inspection['recommendations'][] = 'Clean up orphaned user records or fix tenant references';
        }
    }
    
    // Check database size and performance
    $stmt = $pdo->query("
        SELECT 
            schemaname,
            tablename,
            attname,
            n_distinct,
            correlation
        FROM pg_stats 
        WHERE schemaname = 'public'
        ORDER BY tablename, attname
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $inspection['database_stats'] = $stats;
    
    // Check for any recent errors or issues
    if (in_array('audit_logs', $foundTables)) {
        $stmt = $pdo->query("
            SELECT 
                action,
                table_name,
                created_at,
                COUNT(*) as count
            FROM audit_logs 
            WHERE created_at >= NOW() - INTERVAL '24 hours'
            GROUP BY action, table_name, created_at
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $inspection['recent_activity'] = $recentActivity;
    }
    
    // Generate summary
    $inspection['summary'] = [
        'total_tables' => count($foundTables),
        'expected_tables' => count($expectedTables),
        'missing_tables' => count($missingTables),
        'total_users' => count($inspection['users']),
        'super_admin_count' => $inspection['super_admin_count'] ?? 0,
        'issues_found' => count($inspection['issues']),
        'recommendations' => count($inspection['recommendations'])
    ];
    
    // Set overall status
    if (empty($inspection['issues'])) {
        $inspection['status'] = 'HEALTHY';
        $inspection['message'] = 'Database appears to be in good condition';
    } else {
        $inspection['status'] = 'ISSUES_FOUND';
        $inspection['message'] = 'Database has some issues that should be addressed';
    }
    
    // Clear any output buffer and ensure proper JSON output
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($inspection, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Database inspection error: " . $e->getMessage());
    
    // Clear any output buffer and ensure proper JSON output for errors
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Database inspection failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => 'ERROR',
        'message' => 'Could not inspect database due to connection or query errors'
    ], JSON_PRETTY_PRINT);
}
?>
