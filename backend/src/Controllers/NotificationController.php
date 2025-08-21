<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Database;
use ArdentPOS\Middleware\TenantMiddleware;
use ArdentPOS\Middleware\AuthMiddleware;
use ArdentPOS\Services\NotificationService;

class NotificationController
{
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    public function sendLowStockAlerts(): void
    {
        AuthMiddleware::requireRole('admin');
        
        try {
            $alertsSent = $this->notificationService->checkAndSendLowStockAlerts();
            
            echo json_encode([
                'message' => 'Low stock alerts processed',
                'alerts_sent' => $alertsSent
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send low stock alerts']);
        }
    }

    public function sendSaleReceipt(string $saleId): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager', 'cashier']);
        
        try {
            // Verify sale belongs to tenant
            $sale = Database::fetch(
                'SELECT id FROM sales WHERE id = ? AND tenant_id = ?',
                [$saleId, $tenantId]
            );
            
            if (!$sale) {
                http_response_code(404);
                echo json_encode(['error' => 'Sale not found']);
                return;
            }
            
            $success = $this->notificationService->sendSaleReceiptNotification($saleId);
            
            if ($success) {
                echo json_encode(['message' => 'Receipt sent successfully']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Failed to send receipt']);
            }
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send receipt']);
        }
    }

    public function getNotificationSettings(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        $settings = Database::fetch(
            'SELECT email_notifications, sms_notifications, low_stock_threshold FROM tenants WHERE id = ?',
            [$tenantId]
        );
        
        echo json_encode($settings ?: [
            'email_notifications' => false,
            'sms_notifications' => false,
            'low_stock_threshold' => 10
        ]);
    }

    public function updateNotificationSettings(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireRole('admin');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            $updateData = [];
            
            if (isset($input['email_notifications'])) {
                $updateData['email_notifications'] = $input['email_notifications'] ? 1 : 0;
            }
            
            if (isset($input['sms_notifications'])) {
                $updateData['sms_notifications'] = $input['sms_notifications'] ? 1 : 0;
            }
            
            if (isset($input['low_stock_threshold']) && is_numeric($input['low_stock_threshold'])) {
                $updateData['low_stock_threshold'] = (int)$input['low_stock_threshold'];
            }
            
            if (!empty($updateData)) {
                Database::update('tenants', $updateData, 'id = ?', [$tenantId]);
            }
            
            echo json_encode(['message' => 'Notification settings updated successfully']);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update notification settings']);
        }
    }

    public function getNotificationHistory(): void
    {
        AuthMiddleware::requireRole('admin');
        
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        $type = $_GET['type'] ?? '';
        
        $query = "SELECT * FROM notification_logs WHERE 1=1";
        $params = [];
        
        if (!empty($type)) {
            $query .= " AND type = ?";
            $params[] = $type;
        }
        
        $query .= " ORDER BY sent_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $notifications = Database::fetchAll($query, $params);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM notification_logs WHERE 1=1";
        $countParams = [];
        
        if (!empty($type)) {
            $countQuery .= " AND type = ?";
            $countParams[] = $type;
        }
        
        $total = Database::fetch($countQuery, $countParams)['total'];
        
        echo json_encode([
            'notifications' => $notifications,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
}
