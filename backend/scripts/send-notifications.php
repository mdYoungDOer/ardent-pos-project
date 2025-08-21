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
    
    echo "Starting notification job...\n";
    
    // Send low stock alerts
    $lowStockAlerts = $notificationService->checkAndSendLowStockAlerts();
    echo "Sent {$lowStockAlerts} low stock alerts\n";
    
    echo "Notification job completed successfully\n";
    
} catch (Exception $e) {
    echo "Error in notification job: " . $e->getMessage() . "\n";
    exit(1);
}
