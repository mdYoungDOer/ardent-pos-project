<?php

namespace ArdentPOS\Services;

use ArdentPOS\Core\Database;

class NotificationService
{
    private EmailService $emailService;
    private PaymentService $paymentService;

    public function __construct()
    {
        $this->emailService = new EmailService();
        $this->paymentService = new PaymentService();
    }

    // User Registration & Welcome Notifications
    public function sendWelcomeNotification(string $userId, string $tenantId): bool
    {
        try {
        $user = Database::fetch(
                'SELECT u.*, t.name as business_name FROM users u JOIN tenants t ON u.tenant_id = t.id WHERE u.id = ?',
            [$userId]
        );

        if (!$user) {
            return false;
        }

        return $this->emailService->sendWelcomeEmail(
            $user['email'],
                $user['first_name'] . ' ' . $user['last_name'],
            ['business_name' => $user['business_name']]
        );
        } catch (\Exception $e) {
            error_log('Welcome notification failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendPasswordResetNotification(string $email, string $resetToken): bool
    {
        try {
            $user = Database::fetch('SELECT first_name, last_name FROM users WHERE email = ?', [$email]);
        
        if (!$user) {
            return false;
        }

            $name = $user['first_name'] . ' ' . $user['last_name'];
            return $this->emailService->sendPasswordResetEmail($email, $name, $resetToken);
        } catch (\Exception $e) {
            error_log('Password reset notification failed: ' . $e->getMessage());
            return false;
        }
    }

    // Inventory & Stock Notifications
    public function checkAndSendLowStockAlerts(): int
    {
        $alertsSent = 0;
        
        try {
        // Get all tenants with email notifications enabled
        $tenants = Database::fetchAll(
                'SELECT id, name, email_notifications FROM tenants WHERE status = ? AND email_notifications = ?',
                ['active', true]
        );

        foreach ($tenants as $tenant) {
                // Get low stock products for this tenant
            $lowStockProducts = Database::fetchAll(
                    'SELECT p.name, i.quantity as current_stock, p.min_stock 
                     FROM products p 
                     JOIN inventory i ON p.id = i.product_id 
                     WHERE p.tenant_id = ? AND i.quantity <= p.min_stock',
                [$tenant['id']]
            );

            if (!empty($lowStockProducts)) {
                // Get admin users for this tenant
                $admins = Database::fetchAll(
                        'SELECT email, first_name, last_name FROM users 
                         WHERE tenant_id = ? AND role IN (?, ?) AND status = ?',
                        [$tenant['id'], 'admin', 'manager', 'active']
                );

                foreach ($admins as $admin) {
                        $name = $admin['first_name'] . ' ' . $admin['last_name'];
                        if ($this->emailService->sendLowStockAlert($admin['email'], $name, $lowStockProducts)) {
                        $alertsSent++;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            error_log('Low stock alerts failed: ' . $e->getMessage());
        }

        return $alertsSent;
    }

    // Sales & Receipt Notifications
    public function sendSaleReceiptNotification(string $saleId): bool
    {
        try {
            $sale = Database::fetch(
                'SELECT s.*, c.email as customer_email, c.first_name, c.last_name, t.name as business_name
            FROM sales s
                 JOIN customers c ON s.customer_id = c.id 
            JOIN tenants t ON s.tenant_id = t.id
                 WHERE s.id = ?',
                [$saleId]
            );

            if (!$sale || empty($sale['customer_email'])) {
            return false;
        }

        // Get sale items
            $items = Database::fetchAll(
                'SELECT p.name, si.quantity, si.unit_price, si.total_price 
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
                 WHERE si.sale_id = ?',
                [$saleId]
            );

            $saleData = [
                'sale_id' => $sale['id'],
                'date' => $sale['created_at'],
                'customer_name' => $sale['first_name'] . ' ' . $sale['last_name'],
                'total' => $sale['total_amount'],
                'items' => $items
            ];

            $customerName = $sale['first_name'] . ' ' . $sale['last_name'];
            return $this->emailService->sendSaleReceipt($sale['customer_email'], $customerName, $saleData);
        } catch (\Exception $e) {
            error_log('Sale receipt notification failed: ' . $e->getMessage());
            return false;
        }
    }

    // Payment Notifications
    public function sendPaymentConfirmationNotification(string $paymentId): bool
    {
        try {
            $payment = Database::fetch(
                'SELECT p.*, u.email, u.first_name, u.last_name 
                 FROM payments p 
                 JOIN users u ON p.user_id = u.id 
                 WHERE p.id = ?',
                [$paymentId]
            );

            if (!$payment) {
                return false;
            }

            $paymentData = [
                'transaction_id' => $payment['transaction_id'],
                'amount' => $payment['amount'],
                'date' => $payment['created_at'],
                'status' => $payment['status']
            ];

            $userName = $payment['first_name'] . ' ' . $payment['last_name'];
            return $this->emailService->sendPaymentConfirmation($payment['email'], $userName, $paymentData);
        } catch (\Exception $e) {
            error_log('Payment confirmation notification failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendPaymentFailureNotification(string $paymentId): bool
    {
        try {
            $payment = Database::fetch(
                'SELECT p.*, u.email, u.first_name, u.last_name 
                 FROM payments p 
                 JOIN users u ON p.user_id = u.id 
                 WHERE p.id = ?',
                [$paymentId]
            );

            if (!$payment) {
                return false;
            }

            $subject = 'Payment Failed - Ardent POS';
            $userName = $payment['first_name'] . ' ' . $payment['last_name'];
            $htmlContent = $this->getPaymentFailureTemplate($userName, $payment);
            $textContent = "Payment failed for amount: " . $payment['amount'];

            return $this->emailService->sendEmail($payment['email'], $subject, $htmlContent, $textContent);
        } catch (\Exception $e) {
            error_log('Payment failure notification failed: ' . $e->getMessage());
            return false;
        }
    }

    // Subscription Notifications
    public function sendSubscriptionNotification(string $subscriptionId): bool
    {
        try {
            $subscription = Database::fetch(
                'SELECT s.*, u.email, u.first_name, u.last_name 
                 FROM subscriptions s 
                 JOIN users u ON s.user_id = u.id 
                 WHERE s.id = ?',
                [$subscriptionId]
            );

            if (!$subscription) {
                return false;
            }

            $subscriptionData = [
                'plan' => $subscription['plan_name'],
                'status' => $subscription['status'],
                'next_billing' => $subscription['next_billing_date'],
                'amount' => $subscription['amount']
            ];

            $userName = $subscription['first_name'] . ' ' . $subscription['last_name'];
            return $this->emailService->sendSubscriptionNotification($subscription['email'], $userName, $subscriptionData);
        } catch (\Exception $e) {
            error_log('Subscription notification failed: ' . $e->getMessage());
            return false;
        }
    }

    // System Notifications
    public function sendSystemAlert(string $type, string $message, array $recipients = []): bool
    {
        try {
            if (empty($recipients)) {
                // Get all super admin users
                $recipients = Database::fetchAll(
                    'SELECT email, first_name, last_name FROM users WHERE role = ? AND status = ?',
                    ['super_admin', 'active']
                );
            }

            $subject = "System Alert - $type - Ardent POS";
            $htmlContent = $this->getSystemAlertTemplate($type, $message);
            $textContent = "System Alert: $message";

            $successCount = 0;
            foreach ($recipients as $recipient) {
                $name = $recipient['first_name'] . ' ' . $recipient['last_name'];
                if ($this->emailService->sendEmail($recipient['email'], $subject, $htmlContent, $textContent)) {
                    $successCount++;
                }
            }

            return $successCount > 0;
        } catch (\Exception $e) {
            error_log('System alert notification failed: ' . $e->getMessage());
            return false;
        }
    }

    // Monthly Reports
    public function sendMonthlyReport(string $tenantId, string $month = null): bool
    {
        try {
            if (!$month) {
                $month = date('Y-m', strtotime('-1 month'));
            }

            $tenant = Database::fetch(
                'SELECT t.*, u.email, u.first_name, u.last_name 
                 FROM tenants t 
                 JOIN users u ON t.owner_id = u.id 
                 WHERE t.id = ?',
                [$tenantId]
            );

            if (!$tenant) {
                return false;
            }

            // Get monthly statistics
            $stats = $this->getMonthlyStats($tenantId, $month);
            
            $subject = "Monthly Report - $month - Ardent POS";
            $htmlContent = $this->getMonthlyReportTemplate($tenant['name'], $month, $stats);
            $textContent = "Monthly report for $month: " . json_encode($stats);

            $userName = $tenant['first_name'] . ' ' . $tenant['last_name'];
            return $this->emailService->sendEmail($tenant['email'], $subject, $htmlContent, $textContent);
        } catch (\Exception $e) {
            error_log('Monthly report notification failed: ' . $e->getMessage());
            return false;
        }
    }

    // Utility Methods
    private function getMonthlyStats(string $tenantId, string $month): array
    {
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $stats = [];

        // Total sales
        $salesResult = Database::fetch(
            'SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total 
            FROM sales 
             WHERE tenant_id = ? AND DATE(created_at) BETWEEN ? AND ?',
            [$tenantId, $startDate, $endDate]
        );
        $stats['total_sales'] = $salesResult['count'];
        $stats['total_revenue'] = $salesResult['total'];

        // Top products
        $topProducts = Database::fetchAll(
            'SELECT p.name, SUM(si.quantity) as total_sold 
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            JOIN sales s ON si.sale_id = s.id
             WHERE s.tenant_id = ? AND DATE(s.created_at) BETWEEN ? AND ? 
            GROUP BY p.id, p.name
             ORDER BY total_sold DESC 
             LIMIT 5',
            [$tenantId, $startDate, $endDate]
        );
        $stats['top_products'] = $topProducts;

        // Low stock products
        $lowStockProducts = Database::fetchAll(
            'SELECT p.name, i.quantity as current_stock 
             FROM products p 
             JOIN inventory i ON p.id = i.product_id 
             WHERE p.tenant_id = ? AND i.quantity <= p.min_stock',
            [$tenantId]
        );
        $stats['low_stock_products'] = $lowStockProducts;

        return $stats;
    }

    private function getPaymentFailureTemplate(string $name, array $payment): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #e41e5b, #9a0864); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .alert { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .button { display: inline-block; background: #E72F7C; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Payment Failed</h1>
                </div>
                <div class='content'>
                    <h2>Hello $name,</h2>
                    <div class='alert'>
                        <strong>Payment Failed:</strong> Your payment of {$payment['amount']} has failed to process.
                    </div>
                    <p>Please check your payment method and try again. If the problem persists, contact our support team.</p>
                    <a href='" . $_ENV['APP_URL'] . "/app/settings' class='button'>Update Payment Method</a>
                </div>
                <div class='footer'>
                    <p>© 2024 Ardent POS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    private function getSystemAlertTemplate(string $type, string $message): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #a67c00, #746354); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .alert { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>System Alert</h1>
                </div>
                <div class='content'>
                    <h2>System Alert: $type</h2>
                    <div class='alert'>
                        <strong>Message:</strong> $message
                    </div>
                    <p>This is an automated system alert. Please review and take appropriate action if necessary.</p>
                </div>
                <div class='footer'>
                    <p>© 2024 Ardent POS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    private function getMonthlyReportTemplate(string $businessName, string $month, array $stats): string
    {
        $topProductsList = '';
        foreach ($stats['top_products'] as $product) {
            $topProductsList .= "<tr><td>{$product['name']}</td><td>{$product['total_sold']}</td></tr>";
        }

        $lowStockList = '';
        foreach ($stats['low_stock_products'] as $product) {
            $lowStockList .= "<tr><td>{$product['name']}</td><td>{$product['current_stock']}</td></tr>";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #E72F7C, #9a0864); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .stats { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Monthly Report</h1>
                    <p>$businessName - $month</p>
                </div>
                <div class='content'>
                    <div class='stats'>
                        <h3>Summary</h3>
                        <p><strong>Total Sales:</strong> {$stats['total_sales']}</p>
                        <p><strong>Total Revenue:</strong> {$stats['total_revenue']}</p>
                    </div>
                    
                    <div class='stats'>
                        <h3>Top Products</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Units Sold</th>
                                </tr>
                            </thead>
                            <tbody>
                                $topProductsList
                            </tbody>
                        </table>
                    </div>
                    
                    <div class='stats'>
                        <h3>Low Stock Products</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Current Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                $lowStockList
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class='footer'>
                    <p>© 2024 Ardent POS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    // Log notification attempts
    private function logNotification(string $type, string $recipient, string $status, string $error = ''): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO notification_logs (type, recipient, status, error_message, sent_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$type, $recipient, $status, $error]);
        } catch (\Exception $e) {
            error_log('Failed to log notification: ' . $e->getMessage());
        }
    }
}
