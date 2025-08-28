<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../vendor/autoload.php';

use ArdentPOS\Core\Database;

try {
    Database::init();
    
    // Create the contact_submissions table
    $sql = "CREATE TABLE IF NOT EXISTS contact_submissions (
        id SERIAL PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        company VARCHAR(255),
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        status VARCHAR(20) DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    Database::query($sql);
    
    // Create indexes
    Database::query("CREATE INDEX IF NOT EXISTS idx_contact_submissions_email ON contact_submissions(email)");
    Database::query("CREATE INDEX IF NOT EXISTS idx_contact_submissions_status ON contact_submissions(status)");
    Database::query("CREATE INDEX IF NOT EXISTS idx_contact_submissions_created_at ON contact_submissions(created_at)");
    
    echo json_encode([
        'success' => true,
        'message' => 'Contact submissions table created successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
