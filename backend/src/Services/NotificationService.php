<?php

namespace ArdentPOS\Services;

use ArdentPOS\Core\Database;
use ArdentPOS\Services\EmailService;

class NotificationService
{
    private EmailService $emailService;

    public function __construct()
    {
        $this->emailService = new EmailService();
    }

    public function sendWelcomeNotification(string $userId, string $tenantId): bool
    {
        $user = Database::fetch(
            'SELECT u.*, t.business_name FROM users u JOIN tenants t ON u.tenant_id = t.id WHERE u.id = ?',
            [$userId]
        );

        if (!$user) {
            return false;
        }

        return $this->emailService->sendWelcomeEmail(
            $user['email'],
            $user['name'],
            ['business_name' => $user['business_name']]
        );
    }

    public function sendPasswordResetNotification(string $email, string $resetToken): bool
    {
        $user = Database::fetch('SELECT name FROM users WHERE email = ?', [$email]);
        
        if (!$user) {
            return false;
        }

        return $this->emailService->sendPasswordResetEmail(
            $email,
            $user['name'],
            $resetToken
        );
    }

    public function checkAndSendLowStockAlerts(): int
    {
        $alertsSent = 0;
        
        // Get all tenants with email notifications enabled
        $tenants = Database::fetchAll(
            'SELECT id, business_name, business_email FROM tenants WHERE email_notifications = 1'
        );

        foreach ($tenants as $tenant) {
            $lowStockProducts = Database::fetchAll(
                'SELECT name, sku, stock_quantity, min_stock_level FROM products WHERE tenant_id = ? AND stock_quantity <= min_stock_level',
                [$tenant['id']]
            );

            if (!empty($lowStockProducts)) {
                // Get admin users for this tenant
                $admins = Database::fetchAll(
                    'SELECT name, email FROM users WHERE tenant_id = ? AND role IN (?, ?)',
                    [$tenant['id'], 'admin', 'manager']
                );

                foreach ($admins as $admin) {
                    if ($this->emailService->sendLowStockAlert(
                        $admin['email'],
                        $admin['name'],
                        $lowStockProducts
                    )) {
                        $alertsSent++;
                    }
                }
            }
        }

        return $alertsSent;
    }

    public function sendSaleReceiptNotification(string $saleId): bool
    {
        $sale = Database::fetch("
            SELECT 
                s.*,
                c.name as customer_name,
                c.email as customer_email,
                t.business_name,
                t.business_address
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            JOIN tenants t ON s.tenant_id = t.id
            WHERE s.id = ?
        ", [$saleId]);

        if (!$sale || !$sale['customer_email']) {
            return false;
        }

        // Get sale items
        $items = Database::fetchAll("
            SELECT 
                si.*,
                p.name as product_name
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            WHERE si.sale_id = ?
        ", [$saleId]);

        $sale['items'] = $items;

        return $this->emailService->sendSaleReceiptEmail(
            $sale['customer_email'],
            $sale['customer_name'],
            $sale
        );
    }

    public function sendSubscriptionConfirmationNotification(string $subscriptionId): bool
    {
        $subscription = Database::fetch("
            SELECT 
                s.*,
                t.business_name,
                t.business_email,
                u.name as admin_name
            FROM subscriptions s
            JOIN tenants t ON s.tenant_id = t.id
            LEFT JOIN users u ON t.id = u.tenant_id AND u.role = 'admin'
            WHERE s.id = ?
            LIMIT 1
        ", [$subscriptionId]);

        if (!$subscription || !$subscription['business_email']) {
            return false;
        }

        return $this->emailService->sendSubscriptionConfirmation(
            $subscription['business_email'],
            $subscription['admin_name'] ?: 'Admin',
            $subscription
        );
    }

    public function sendPaymentFailureNotification(string $subscriptionCode): bool
    {
        $subscription = Database::fetch("
            SELECT 
                s.*,
                t.business_name,
                t.business_email,
                u.name as admin_name
            FROM subscriptions s
            JOIN tenants t ON s.tenant_id = t.id
            LEFT JOIN users u ON t.id = u.tenant_id AND u.role = 'admin'
            WHERE s.paystack_subscription_code = ?
            LIMIT 1
        ", [$subscriptionCode]);

        if (!$subscription || !$subscription['business_email']) {
            return false;
        }

        return $this->emailService->sendPaymentFailureNotification(
            $subscription['business_email'],
            $subscription['admin_name'] ?: 'Admin',
            $subscription
        );
    }

    public function sendMonthlyReports(): int
    {
        $reportsSent = 0;
        $lastMonth = date('Y-m-01', strtotime('-1 month'));
        $thisMonth = date('Y-m-01');

        // Get all active tenants
        $tenants = Database::fetchAll(
            'SELECT id, business_name, business_email FROM tenants WHERE status = ?',
            ['active']
        );

        foreach ($tenants as $tenant) {
            // Generate monthly report data
            $reportData = $this->generateMonthlyReportData($tenant['id'], $lastMonth, $thisMonth);
            
            if ($reportData) {
                // Get admin users
                $admins = Database::fetchAll(
                    'SELECT name, email FROM users WHERE tenant_id = ? AND role = ?',
                    [$tenant['id'], 'admin']
                );

                foreach ($admins as $admin) {
                    if ($this->emailService->sendMonthlyReport(
                        $admin['email'],
                        $admin['name'],
                        $reportData
                    )) {
                        $reportsSent++;
                    }
                }
            }
        }

        return $reportsSent;
    }

    private function generateMonthlyReportData(string $tenantId, string $periodStart, string $periodEnd): ?array
    {
        // Sales summary
        $salesSummary = Database::fetch("
            SELECT 
                COUNT(*) as total_sales,
                COALESCE(SUM(total_amount), 0) as total_revenue,
                COALESCE(AVG(total_amount), 0) as average_sale
            FROM sales 
            WHERE tenant_id = ? 
            AND created_at >= ? 
            AND created_at < ?
            AND payment_status = 'completed'
        ", [$tenantId, $periodStart, $periodEnd]);

        // Top products
        $topProducts = Database::fetchAll("
            SELECT 
                p.name,
                SUM(si.quantity) as quantity_sold,
                SUM(si.total_price) as revenue
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            JOIN sales s ON si.sale_id = s.id
            WHERE s.tenant_id = ? 
            AND s.created_at >= ? 
            AND s.created_at < ?
            AND s.payment_status = 'completed'
            GROUP BY p.id, p.name
            ORDER BY quantity_sold DESC
            LIMIT 5
        ", [$tenantId, $periodStart, $periodEnd]);

        // New customers
        $newCustomers = Database::fetch("
            SELECT COUNT(*) as count
            FROM customers 
            WHERE tenant_id = ? 
            AND created_at >= ? 
            AND created_at < ?
        ", [$tenantId, $periodStart, $periodEnd])['count'];

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'sales_summary' => $salesSummary,
            'top_products' => $topProducts,
            'new_customers' => $newCustomers
        ];
    }

    public function logNotification(string $type, string $recipient, bool $success, array $data = []): void
    {
        Database::insert('notification_logs', [
            'type' => $type,
            'recipient' => $recipient,
            'status' => $success ? 'sent' : 'failed',
            'data' => json_encode($data),
            'sent_at' => date('Y-m-d H:i:s')
        ]);
    }
}
