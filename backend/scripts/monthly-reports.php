<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ArdentPOS\Core\Database;
use ArdentPOS\Core\Config;
use ArdentPOS\Services\NotificationService;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

try {
    // Initialize configuration and database
    Config::init();
    Database::init();
    
    $notificationService = new NotificationService();
    
    echo "Starting monthly reports job...\n";
    
    // Send monthly reports
    $reportsSent = $notificationService->sendMonthlyReports();
    echo "Sent {$reportsSent} monthly reports\n";
    
    echo "Monthly reports job completed successfully\n";
    
} catch (Exception $e) {
    echo "Error in monthly reports job: " . $e->getMessage() . "\n";
    exit(1);
}
