<?php
require_once __DIR__ . '/vendor/autoload.php';

use ArdentPOS\Core\Database;

try {
    Database::init();
    
    // Read and execute the migration
    $sql = file_get_contents(__DIR__ . '/database/migrations/create_contact_submissions_table.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            Database::query($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
