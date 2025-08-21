<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Database;
use ArdentPOS\Core\Config;

class PaystackController
{
    public function webhook(): void
    {
        // Verify webhook signature
        $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
        $body = file_get_contents('php://input');
        
        if (!$this->verifySignature($body, $signature)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid signature']);
            return;
        }
        
        $event = json_decode($body, true);
        
        if (!$event) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            return;
        }
        
        try {
            switch ($event['event']) {
                case 'charge.success':
                    $this->handleChargeSuccess($event['data']);
                    break;
                    
                case 'subscription.create':
                    $this->handleSubscriptionCreate($event['data']);
                    break;
                    
                case 'subscription.disable':
                    $this->handleSubscriptionDisable($event['data']);
                    break;
                    
                case 'invoice.create':
                    $this->handleInvoiceCreate($event['data']);
                    break;
                    
                case 'invoice.payment_failed':
                    $this->handleInvoicePaymentFailed($event['data']);
                    break;
                    
                default:
                    // Log unknown event
                    error_log("Unknown Paystack event: " . $event['event']);
            }
            
            http_response_code(200);
            echo json_encode(['status' => 'success']);
            
        } catch (\Exception $e) {
            error_log("Paystack webhook error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Webhook processing failed']);
        }
    }

    private function handleChargeSuccess(array $data): void
    {
        $reference = $data['reference'];
        $amount = $data['amount'] / 100; // Convert from kobo
        $metadata = $data['metadata'] ?? [];
        
        // Check if this is a subscription payment
        if (isset($metadata['subscription_id'])) {
            $subscriptionId = $metadata['subscription_id'];
            
            // Update subscription status
            Database::update('subscriptions', [
                'status' => 'active',
                'paystack_subscription_code' => $data['subscription']['subscription_code'] ?? null,
                'last_payment_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$subscriptionId]);
            
            // Send confirmation email
            $this->sendSubscriptionConfirmationEmail($subscriptionId);
        }
        
        // Log the payment
        Database::insert('payments', [
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $data['currency'],
            'status' => 'success',
            'gateway' => 'paystack',
            'gateway_response' => json_encode($data),
            'metadata' => json_encode($metadata)
        ]);
    }

    private function handleSubscriptionCreate(array $data): void
    {
        $subscriptionCode = $data['subscription_code'];
        $customerCode = $data['customer']['customer_code'];
        
        // Find subscription by customer email or metadata
        $subscription = Database::fetch(
            'SELECT * FROM subscriptions WHERE paystack_subscription_code = ? OR (tenant_id IN (SELECT id FROM tenants WHERE business_email = ?))',
            [$subscriptionCode, $data['customer']['email']]
        );
        
        if ($subscription) {
            Database::update('subscriptions', [
                'paystack_subscription_code' => $subscriptionCode,
                'paystack_customer_code' => $customerCode,
                'status' => 'active'
            ], 'id = ?', [$subscription['id']]);
        }
    }

    private function handleSubscriptionDisable(array $data): void
    {
        $subscriptionCode = $data['subscription_code'];
        
        Database::update('subscriptions', [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s')
        ], 'paystack_subscription_code = ?', [$subscriptionCode]);
    }

    private function handleInvoiceCreate(array $data): void
    {
        // Log invoice creation for record keeping
        Database::insert('invoices', [
            'paystack_invoice_code' => $data['invoice_code'],
            'subscription_code' => $data['subscription']['subscription_code'] ?? null,
            'amount' => $data['amount'] / 100,
            'currency' => $data['currency'],
            'status' => $data['status'],
            'due_date' => $data['due_date'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function handleInvoicePaymentFailed(array $data): void
    {
        $subscriptionCode = $data['subscription']['subscription_code'] ?? null;
        
        if ($subscriptionCode) {
            // Update subscription status to indicate payment failure
            Database::update('subscriptions', [
                'status' => 'past_due',
                'last_payment_failed_at' => date('Y-m-d H:i:s')
            ], 'paystack_subscription_code = ?', [$subscriptionCode]);
            
            // Send payment failure notification
            $this->sendPaymentFailureEmail($subscriptionCode);
        }
    }

    private function verifySignature(string $body, string $signature): bool
    {
        $secret = Config::get('paystack.webhook_secret');
        $computedSignature = hash_hmac('sha512', $body, $secret);
        
        return hash_equals($signature, $computedSignature);
    }

    private function sendSubscriptionConfirmationEmail(string $subscriptionId): void
    {
        // Get subscription and tenant details
        $subscription = Database::fetch("
            SELECT s.*, t.business_name, t.business_email
            FROM subscriptions s
            JOIN tenants t ON s.tenant_id = t.id
            WHERE s.id = ?
        ", [$subscriptionId]);
        
        if ($subscription) {
            // This would integrate with SendGrid
            // For now, just log the action
            error_log("Subscription confirmation email should be sent to: " . $subscription['business_email']);
        }
    }

    private function sendPaymentFailureEmail(string $subscriptionCode): void
    {
        // Get subscription and tenant details
        $subscription = Database::fetch("
            SELECT s.*, t.business_name, t.business_email
            FROM subscriptions s
            JOIN tenants t ON s.tenant_id = t.id
            WHERE s.paystack_subscription_code = ?
        ", [$subscriptionCode]);
        
        if ($subscription) {
            // This would integrate with SendGrid
            // For now, just log the action
            error_log("Payment failure email should be sent to: " . $subscription['business_email']);
        }
    }
}
