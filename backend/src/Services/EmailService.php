<?php

namespace ArdentPOS\Services;

use ArdentPOS\Core\Config;
use SendGrid;
use SendGrid\Mail\Mail;

class EmailService
{
    private SendGrid $sendGrid;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $apiKey = Config::get('sendgrid.api_key');
        $this->sendGrid = new SendGrid($apiKey);
        $this->fromEmail = Config::get('sendgrid.from_email');
        $this->fromName = Config::get('sendgrid.from_name');
    }

    public function sendWelcomeEmail(string $toEmail, string $toName, array $data = []): bool
    {
        $subject = 'Welcome to Ardent POS!';
        $templateData = array_merge([
            'name' => $toName,
            'business_name' => $data['business_name'] ?? '',
            'login_url' => Config::get('app.url') . '/login'
        ], $data);

        $htmlContent = $this->renderTemplate('welcome', $templateData);
        
        return $this->sendEmail($toEmail, $toName, $subject, $htmlContent);
    }

    public function sendPasswordResetEmail(string $toEmail, string $toName, string $resetToken): bool
    {
        $subject = 'Reset Your Password - Ardent POS';
        $resetUrl = Config::get('app.url') . '/reset-password?token=' . $resetToken;
        
        $templateData = [
            'name' => $toName,
            'reset_url' => $resetUrl,
            'expires_in' => '1 hour'
        ];

        $htmlContent = $this->renderTemplate('password-reset', $templateData);
        
        return $this->sendEmail($toEmail, $toName, $subject, $htmlContent);
    }

    public function sendLowStockAlert(string $toEmail, string $toName, array $lowStockProducts): bool
    {
        $subject = 'Low Stock Alert - Ardent POS';
        
        $templateData = [
            'name' => $toName,
            'products' => $lowStockProducts,
            'product_count' => count($lowStockProducts),
            'dashboard_url' => Config::get('app.url') . '/app/inventory'
        ];

        $htmlContent = $this->renderTemplate('low-stock-alert', $templateData);
        
        return $this->sendEmail($toEmail, $toName, $subject, $htmlContent);
    }

    public function sendSaleReceiptEmail(string $toEmail, string $toName, array $saleData): bool
    {
        $subject = 'Receipt - Order #' . $saleData['id'];
        
        $templateData = [
            'customer_name' => $toName,
            'sale' => $saleData,
            'business_name' => $saleData['business_name'] ?? 'Ardent POS',
            'business_address' => $saleData['business_address'] ?? ''
        ];

        $htmlContent = $this->renderTemplate('sale-receipt', $templateData);
        
        return $this->sendEmail($toEmail, $toName, $subject, $htmlContent);
    }

    public function sendSubscriptionConfirmation(string $toEmail, string $toName, array $subscriptionData): bool
    {
        $subject = 'Subscription Confirmed - Ardent POS';
        
        $templateData = [
            'name' => $toName,
            'subscription' => $subscriptionData,
            'dashboard_url' => Config::get('app.url') . '/app/settings'
        ];

        $htmlContent = $this->renderTemplate('subscription-confirmation', $templateData);
        
        return $this->sendEmail($toEmail, $toName, $subject, $htmlContent);
    }

    public function sendPaymentFailureNotification(string $toEmail, string $toName, array $subscriptionData): bool
    {
        $subject = 'Payment Failed - Ardent POS';
        
        $templateData = [
            'name' => $toName,
            'subscription' => $subscriptionData,
            'update_payment_url' => Config::get('app.url') . '/app/settings'
        ];

        $htmlContent = $this->renderTemplate('payment-failure', $templateData);
        
        return $this->sendEmail($toEmail, $toName, $subject, $htmlContent);
    }

    public function sendMonthlyReport(string $toEmail, string $toName, array $reportData): bool
    {
        $subject = 'Monthly Business Report - Ardent POS';
        
        $templateData = [
            'name' => $toName,
            'report' => $reportData,
            'month' => date('F Y', strtotime($reportData['period_start'])),
            'dashboard_url' => Config::get('app.url') . '/app/reports'
        ];

        $htmlContent = $this->renderTemplate('monthly-report', $templateData);
        
        return $this->sendEmail($toEmail, $toName, $subject, $htmlContent);
    }

    private function sendEmail(string $toEmail, string $toName, string $subject, string $htmlContent): bool
    {
        try {
            $email = new Mail();
            $email->setFrom($this->fromEmail, $this->fromName);
            $email->setSubject($subject);
            $email->addTo($toEmail, $toName);
            $email->addContent('text/html', $htmlContent);

            $response = $this->sendGrid->send($email);
            
            return $response->statusCode() >= 200 && $response->statusCode() < 300;
            
        } catch (\Exception $e) {
            error_log('Email sending failed: ' . $e->getMessage());
            return false;
        }
    }

    private function renderTemplate(string $template, array $data): string
    {
        // Simple template rendering - in production, you might use Twig or similar
        $templatePath = __DIR__ . "/../Templates/emails/{$template}.html";
        
        if (!file_exists($templatePath)) {
            return $this->getDefaultTemplate($template, $data);
        }
        
        $content = file_get_contents($templatePath);
        
        // Replace placeholders
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $content = str_replace("{{$key}}", $value, $content);
            }
        }
        
        return $content;
    }

    private function getDefaultTemplate(string $template, array $data): string
    {
        $baseStyle = '
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #e41e5b; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
                .button { display: inline-block; padding: 12px 24px; background: #e41e5b; color: white; text-decoration: none; border-radius: 4px; }
                .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .table th, .table td { padding: 8px; border: 1px solid #ddd; text-align: left; }
                .table th { background: #f5f5f5; }
            </style>
        ';

        switch ($template) {
            case 'welcome':
                return "
                    <html><head>{$baseStyle}</head><body>
                        <div class='container'>
                            <div class='header'>
                                <h1>Welcome to Ardent POS!</h1>
                            </div>
                            <div class='content'>
                                <h2>Hello {$data['name']}!</h2>
                                <p>Welcome to Ardent POS! Your account has been successfully created.</p>
                                <p>You can now start managing your business with our powerful POS system.</p>
                                <p><a href='{$data['login_url']}' class='button'>Get Started</a></p>
                            </div>
                            <div class='footer'>
                                <p>&copy; 2024 Ardent POS. All rights reserved.</p>
                            </div>
                        </div>
                    </body></html>
                ";

            case 'low-stock-alert':
                $productList = '';
                foreach ($data['products'] as $product) {
                    $productList .= "<tr><td>{$product['name']}</td><td>{$product['stock_quantity']}</td><td>{$product['min_stock_level']}</td></tr>";
                }
                
                return "
                    <html><head>{$baseStyle}</head><body>
                        <div class='container'>
                            <div class='header'>
                                <h1>Low Stock Alert</h1>
                            </div>
                            <div class='content'>
                                <h2>Hello {$data['name']}!</h2>
                                <p>You have {$data['product_count']} products running low on stock:</p>
                                <table class='table'>
                                    <tr><th>Product</th><th>Current Stock</th><th>Minimum Level</th></tr>
                                    {$productList}
                                </table>
                                <p><a href='{$data['dashboard_url']}' class='button'>Manage Inventory</a></p>
                            </div>
                            <div class='footer'>
                                <p>&copy; 2024 Ardent POS. All rights reserved.</p>
                            </div>
                        </div>
                    </body></html>
                ";

            default:
                return "
                    <html><head>{$baseStyle}</head><body>
                        <div class='container'>
                            <div class='header'>
                                <h1>Ardent POS</h1>
                            </div>
                            <div class='content'>
                                <p>This is a notification from Ardent POS.</p>
                            </div>
                            <div class='footer'>
                                <p>&copy; 2024 Ardent POS. All rights reserved.</p>
                            </div>
                        </div>
                    </body></html>
                ";
        }
    }
}
