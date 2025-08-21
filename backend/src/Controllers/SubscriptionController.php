<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Database;
use ArdentPOS\Middleware\TenantMiddleware;
use ArdentPOS\Middleware\AuthMiddleware;

class SubscriptionController
{
    public function show(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireRole('admin');
        
        $subscription = Database::fetch("
            SELECT 
                s.*,
                t.business_name
            FROM subscriptions s
            JOIN tenants t ON s.tenant_id = t.id
            WHERE s.tenant_id = ?
            ORDER BY s.created_at DESC
            LIMIT 1
        ", [$tenantId]);
        
        if (!$subscription) {
            // Create default subscription if none exists
            $subscriptionId = Database::insert('subscriptions', [
                'tenant_id' => $tenantId,
                'plan_name' => 'trial',
                'status' => 'active',
                'starts_at' => date('Y-m-d H:i:s'),
                'ends_at' => date('Y-m-d H:i:s', strtotime('+14 days'))
            ]);
            
            $subscription = Database::fetch(
                'SELECT * FROM subscriptions WHERE id = ?',
                [$subscriptionId]
            );
        }
        
        // Calculate usage statistics
        $usage = $this->calculateUsage($tenantId);
        $subscription['usage'] = $usage;
        
        echo json_encode($subscription);
    }

    public function upgrade(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireRole('admin');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $errors = $this->validateUpgrade($input);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }
        
        try {
            $planName = $input['plan'];
            $billingCycle = $input['billing_cycle'] ?? 'monthly';
            
            // Get plan details
            $planDetails = $this->getPlanDetails($planName, $billingCycle);
            
            if (!$planDetails) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid plan selected']);
                return;
            }
            
            // Create new subscription record
            $subscriptionId = Database::insert('subscriptions', [
                'tenant_id' => $tenantId,
                'plan_name' => $planName,
                'billing_cycle' => $billingCycle,
                'amount' => $planDetails['amount'],
                'currency' => $planDetails['currency'],
                'status' => 'pending',
                'starts_at' => date('Y-m-d H:i:s'),
                'ends_at' => date('Y-m-d H:i:s', strtotime('+1 ' . $billingCycle))
            ]);
            
            // Generate Paystack payment link
            $paymentData = [
                'email' => AuthMiddleware::getCurrentUser()['email'],
                'amount' => $planDetails['amount'] * 100, // Convert to kobo
                'currency' => $planDetails['currency'],
                'reference' => 'sub_' . $subscriptionId . '_' . time(),
                'callback_url' => $_ENV['APP_URL'] . '/subscription/success',
                'metadata' => [
                    'subscription_id' => $subscriptionId,
                    'tenant_id' => $tenantId,
                    'plan' => $planName
                ]
            ];
            
            // This would integrate with Paystack API
            // For now, return the payment data
            echo json_encode([
                'message' => 'Subscription upgrade initiated',
                'subscription_id' => $subscriptionId,
                'payment_data' => $paymentData
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to upgrade subscription']);
        }
    }

    public function cancel(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireRole('admin');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            // Get current subscription
            $subscription = Database::fetch(
                'SELECT * FROM subscriptions WHERE tenant_id = ? AND status = ? ORDER BY created_at DESC LIMIT 1',
                [$tenantId, 'active']
            );
            
            if (!$subscription) {
                http_response_code(404);
                echo json_encode(['error' => 'No active subscription found']);
                return;
            }
            
            // Update subscription status
            Database::update('subscriptions', [
                'status' => 'cancelled',
                'cancelled_at' => date('Y-m-d H:i:s'),
                'cancellation_reason' => $input['reason'] ?? null
            ], 'id = ?', [$subscription['id']]);
            
            echo json_encode(['message' => 'Subscription cancelled successfully']);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to cancel subscription']);
        }
    }

    private function calculateUsage(string $tenantId): array
    {
        $currentMonth = date('Y-m-01');
        
        // Calculate monthly usage
        $salesCount = Database::fetch(
            'SELECT COUNT(*) as count FROM sales WHERE tenant_id = ? AND created_at >= ?',
            [$tenantId, $currentMonth]
        )['count'];
        
        $productsCount = Database::fetch(
            'SELECT COUNT(*) as count FROM products WHERE tenant_id = ?',
            [$tenantId]
        )['count'];
        
        $usersCount = Database::fetch(
            'SELECT COUNT(*) as count FROM users WHERE tenant_id = ?',
            [$tenantId]
        )['count'];
        
        return [
            'sales_this_month' => (int)$salesCount,
            'total_products' => (int)$productsCount,
            'total_users' => (int)$usersCount
        ];
    }

    private function getPlanDetails(string $planName, string $billingCycle): ?array
    {
        $plans = [
            'starter' => [
                'monthly' => ['amount' => 120, 'currency' => 'GHS'],
                'yearly' => ['amount' => 1200, 'currency' => 'GHS']
            ],
            'professional' => [
                'monthly' => ['amount' => 240, 'currency' => 'GHS'],
                'yearly' => ['amount' => 2400, 'currency' => 'GHS']
            ],
            'enterprise' => [
                'monthly' => ['amount' => 480, 'currency' => 'GHS'],
                'yearly' => ['amount' => 4800, 'currency' => 'GHS']
            ]
        ];
        
        return $plans[$planName][$billingCycle] ?? null;
    }

    private function validateUpgrade(array $input): array
    {
        $errors = [];
        
        $validPlans = ['starter', 'professional', 'enterprise'];
        if (empty($input['plan']) || !in_array($input['plan'], $validPlans)) {
            $errors['plan'] = 'Valid plan is required';
        }
        
        $validCycles = ['monthly', 'yearly'];
        if (!empty($input['billing_cycle']) && !in_array($input['billing_cycle'], $validCycles)) {
            $errors['billing_cycle'] = 'Valid billing cycle is required';
        }
        
        return $errors;
    }
}
