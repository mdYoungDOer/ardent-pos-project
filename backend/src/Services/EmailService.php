<?php

namespace ArdentPOS\Services;

use ArdentPOS\Core\Database;

class EmailService
{
    private string $sendgridApiKey;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->sendgridApiKey = $_ENV['SENDGRID_API_KEY'] ?? '';
        $this->fromEmail = $_ENV['SENDGRID_FROM_EMAIL'] ?? 'notify@ardentpos.com';
        $this->fromName = 'Ardent POS';
    }

    public function sendEmail(string $to, string $subject, string $htmlContent, string $textContent = ''): bool
    {
        if (empty($this->sendgridApiKey)) {
            error_log('SendGrid API key not configured');
            return false;
        }

        $data = [
            'personalizations' => [
                [
                    'to' => [
                        ['email' => $to]
                    ]
                ]
            ],
            'from' => [
                'email' => $this->fromEmail,
                'name' => $this->fromName
            ],
            'subject' => $subject,
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => $htmlContent
                ]
            ]
        ];

        if (!empty($textContent)) {
            $data['content'][] = [
                'type' => 'text/plain',
                'value' => $textContent
            ];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->sendgridApiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 202) {
            $this->logEmailSent($to, $subject, 'success');
            return true;
        } else {
            error_log("SendGrid API error: HTTP $httpCode - $response");
            $this->logEmailSent($to, $subject, 'failed', $response);
            return false;
        }
    }

    public function sendWelcomeEmail(string $email, string $name, array $data = []): bool
    {
        $subject = 'Welcome to Ardent POS!';
        $businessName = $data['business_name'] ?? 'Your Business';
        
        $htmlContent = $this->getWelcomeEmailTemplate($name, $businessName);
        $textContent = "Welcome to Ardent POS, $name! Your account for $businessName has been successfully created.";

        return $this->sendEmail($email, $subject, $htmlContent, $textContent);
    }

    public function sendPasswordResetEmail(string $email, string $name, string $resetToken): bool
    {
        $subject = 'Password Reset Request - Ardent POS';
        $resetUrl = $_ENV['APP_URL'] . '/auth/reset-password?token=' . $resetToken;
        
        $htmlContent = $this->getPasswordResetTemplate($name, $resetUrl);
        $textContent = "Hello $name, you requested a password reset. Click this link: $resetUrl";

        return $this->sendEmail($email, $subject, $htmlContent, $textContent);
    }

    public function sendLowStockAlert(string $email, string $name, array $products): bool
    {
        $subject = 'Low Stock Alert - Ardent POS';
        
        $htmlContent = $this->getLowStockTemplate($name, $products);
        $textContent = "Hello $name, the following products are running low on stock: " . 
                      implode(', ', array_column($products, 'name'));

        return $this->sendEmail($email, $subject, $htmlContent, $textContent);
    }

    public function sendSaleReceipt(string $email, string $name, array $saleData): bool
    {
        $subject = 'Receipt for Sale #' . $saleData['sale_id'] . ' - Ardent POS';
        
        $htmlContent = $this->getSaleReceiptTemplate($name, $saleData);
        $textContent = "Thank you for your purchase! Total: " . $saleData['total'];

        return $this->sendEmail($email, $subject, $htmlContent, $textContent);
    }

    public function sendPaymentConfirmation(string $email, string $name, array $paymentData): bool
    {
        $subject = 'Payment Confirmation - Ardent POS';
        
        $htmlContent = $this->getPaymentConfirmationTemplate($name, $paymentData);
        $textContent = "Payment confirmed! Amount: " . $paymentData['amount'];

        return $this->sendEmail($email, $subject, $htmlContent, $textContent);
    }

    public function sendSubscriptionNotification(string $email, string $name, array $subscriptionData): bool
    {
        $subject = 'Subscription Update - Ardent POS';
        
        $htmlContent = $this->getSubscriptionTemplate($name, $subscriptionData);
        $textContent = "Your subscription has been updated. Status: " . $subscriptionData['status'];

        return $this->sendEmail($email, $subject, $htmlContent, $textContent);
    }

    private function getWelcomeEmailTemplate(string $name, string $businessName): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #E72F7C, #9a0864); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: #E72F7C; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to Ardent POS!</h1>
                </div>
                <div class='content'>
                    <h2>Hello $name,</h2>
                    <p>Welcome to Ardent POS! Your account for <strong>$businessName</strong> has been successfully created.</p>
                    <p>You can now:</p>
                    <ul>
                        <li>Manage your products and inventory</li>
                        <li>Process sales and track revenue</li>
                        <li>Manage customers and their data</li>
                        <li>Generate reports and analytics</li>
                    </ul>
                    <a href='" . $_ENV['APP_URL'] . "/auth/login' class='button'>Login to Your Dashboard</a>
                    <p>If you have any questions, feel free to contact our support team.</p>
                </div>
                <div class='footer'>
                    <p>© 2024 Ardent POS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    private function getPasswordResetTemplate(string $name, string $resetUrl): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #E72F7C, #9a0864); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: #E72F7C; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Password Reset Request</h1>
                </div>
                <div class='content'>
                    <h2>Hello $name,</h2>
                    <p>You requested a password reset for your Ardent POS account.</p>
                    <a href='$resetUrl' class='button'>Reset Your Password</a>
                    <div class='warning'>
                        <strong>Security Notice:</strong> This link will expire in 1 hour. If you didn't request this reset, please ignore this email.
                    </div>
                </div>
                <div class='footer'>
                    <p>© 2024 Ardent POS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    private function getLowStockTemplate(string $name, array $products): string
    {
        $productList = '';
        foreach ($products as $product) {
            $productList .= "<tr><td>{$product['name']}</td><td>{$product['current_stock']}</td><td>{$product['min_stock']}</td></tr>";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #a67c00, #746354); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .button { display: inline-block; background: #E72F7C; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Low Stock Alert</h1>
                </div>
                <div class='content'>
                    <h2>Hello $name,</h2>
                    <p>The following products are running low on stock:</p>
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Current Stock</th>
                                <th>Minimum Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            $productList
                        </tbody>
                    </table>
                    <a href='" . $_ENV['APP_URL'] . "/app/inventory' class='button'>Manage Inventory</a>
                </div>
                <div class='footer'>
                    <p>© 2024 Ardent POS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    private function getSaleReceiptTemplate(string $name, array $saleData): string
    {
        $items = '';
        foreach ($saleData['items'] as $item) {
            $items .= "<tr><td>{$item['name']}</td><td>{$item['quantity']}</td><td>{$item['price']}</td><td>{$item['total']}</td></tr>";
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
                .receipt { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .total { font-size: 18px; font-weight: bold; text-align: right; margin-top: 20px; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Receipt</h1>
                    <p>Sale #{$saleData['sale_id']}</p>
                </div>
                <div class='content'>
                    <div class='receipt'>
                        <h2>Thank you for your purchase!</h2>
                        <p><strong>Date:</strong> {$saleData['date']}</p>
                        <p><strong>Customer:</strong> {$saleData['customer_name']}</p>
                        <table>
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                $items
                            </tbody>
                        </table>
                        <div class='total'>
                            <p><strong>Total: {$saleData['total']}</strong></p>
                        </div>
                    </div>
                </div>
                <div class='footer'>
                    <p>© 2024 Ardent POS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    private function getPaymentConfirmationTemplate(string $name, array $paymentData): string
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
                .payment-details { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Payment Confirmation</h1>
                </div>
                <div class='content'>
                    <h2>Hello $name,</h2>
                    <p>Your payment has been successfully processed!</p>
                    <div class='payment-details'>
                        <p><strong>Transaction ID:</strong> {$paymentData['transaction_id']}</p>
                        <p><strong>Amount:</strong> {$paymentData['amount']}</p>
                        <p><strong>Date:</strong> {$paymentData['date']}</p>
                        <p><strong>Status:</strong> {$paymentData['status']}</p>
                    </div>
                </div>
                <div class='footer'>
                    <p>© 2024 Ardent POS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    private function getSubscriptionTemplate(string $name, array $subscriptionData): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #E72F7C, #9a0864); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .subscription-details { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Subscription Update</h1>
                </div>
                <div class='content'>
                    <h2>Hello $name,</h2>
                    <p>Your subscription has been updated.</p>
                    <div class='subscription-details'>
                        <p><strong>Plan:</strong> {$subscriptionData['plan']}</p>
                        <p><strong>Status:</strong> {$subscriptionData['status']}</p>
                        <p><strong>Next Billing:</strong> {$subscriptionData['next_billing']}</p>
                        <p><strong>Amount:</strong> {$subscriptionData['amount']}</p>
                    </div>
                </div>
                <div class='footer'>
                    <p>© 2024 Ardent POS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    private function logEmailSent(string $to, string $subject, string $status, string $error = ''): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO email_logs (to_email, subject, status, error_message, sent_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$to, $subject, $status, $error]);
        } catch (\Exception $e) {
            error_log('Failed to log email: ' . $e->getMessage());
        }
    }
}
