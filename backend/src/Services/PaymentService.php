<?php

namespace ArdentPOS\Services;

use ArdentPOS\Core\Database;

class PaymentService
{
    private string $paystackSecretKey;
    private string $paystackPublicKey;
    private string $baseUrl = 'https://api.paystack.co';

    public function __construct()
    {
        $this->paystackSecretKey = $_ENV['PAYSTACK_SECRET_KEY'] ?? '';
        $this->paystackPublicKey = $_ENV['PAYSTACK_PUBLIC_KEY'] ?? '';
    }

    public function initializeTransaction(array $data): array
    {
        $payload = [
            'email' => $data['email'],
            'amount' => $data['amount'] * 100, // Convert to kobo
            'currency' => $data['currency'] ?? 'GHS',
            'reference' => $data['reference'] ?? $this->generateReference(),
            'callback_url' => $data['callback_url'] ?? $_ENV['APP_URL'] . '/api/payment/verify',
            'metadata' => [
                'tenant_id' => $data['tenant_id'] ?? '',
                'user_id' => $data['user_id'] ?? '',
                'sale_id' => $data['sale_id'] ?? '',
                'custom_fields' => $data['metadata'] ?? []
            ]
        ];

        $response = $this->makeRequest('POST', '/transaction/initialize', $payload);
        
        if ($response['status']) {
            $this->logPaymentAttempt($data['reference'], 'initialized', $payload, $response);
            return [
                'success' => true,
                'authorization_url' => $response['data']['authorization_url'],
                'reference' => $response['data']['reference'],
                'access_code' => $response['data']['access_code']
            ];
        }

        $this->logPaymentAttempt($data['reference'], 'failed', $payload, $response);
        return [
            'success' => false,
            'error' => $response['message'] ?? 'Payment initialization failed'
        ];
    }

    public function verifyTransaction(string $reference): array
    {
        $response = $this->makeRequest('GET', "/transaction/verify/{$reference}");
        
        if ($response['status']) {
            $transaction = $response['data'];
            
            if ($transaction['status'] === 'success') {
                $this->logPaymentAttempt($reference, 'success', [], $response);
                return [
                    'success' => true,
                    'transaction_id' => $transaction['id'],
                    'reference' => $transaction['reference'],
                    'amount' => $transaction['amount'] / 100, // Convert from kobo
                    'currency' => $transaction['currency'],
                    'status' => $transaction['status'],
                    'gateway_response' => $transaction['gateway_response'],
                    'paid_at' => $transaction['paid_at'],
                    'metadata' => $transaction['metadata']
                ];
            } else {
                $this->logPaymentAttempt($reference, 'failed', [], $response);
                return [
                    'success' => false,
                    'error' => $transaction['gateway_response'] ?? 'Payment failed'
                ];
            }
        }

        $this->logPaymentAttempt($reference, 'verification_failed', [], $response);
        return [
            'success' => false,
            'error' => $response['message'] ?? 'Transaction verification failed'
        ];
    }

    public function createCustomer(array $data): array
    {
        $payload = [
            'email' => $data['email'],
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'phone' => $data['phone'] ?? '',
            'metadata' => [
                'tenant_id' => $data['tenant_id'] ?? '',
                'user_id' => $data['user_id'] ?? ''
            ]
        ];

        $response = $this->makeRequest('POST', '/customer', $payload);
        
        if ($response['status']) {
            return [
                'success' => true,
                'customer_code' => $response['data']['customer_code'],
                'customer_id' => $response['data']['id']
            ];
        }

        return [
            'success' => false,
            'error' => $response['message'] ?? 'Customer creation failed'
        ];
    }

    public function createSubscription(array $data): array
    {
        $payload = [
            'customer' => $data['customer_code'],
            'plan' => $data['plan_code'],
            'start_date' => $data['start_date'] ?? date('Y-m-d'),
            'metadata' => [
                'tenant_id' => $data['tenant_id'] ?? '',
                'user_id' => $data['user_id'] ?? ''
            ]
        ];

        $response = $this->makeRequest('POST', '/subscription', $payload);
        
        if ($response['status']) {
            return [
                'success' => true,
                'subscription_code' => $response['data']['subscription_code'],
                'subscription_id' => $response['data']['id'],
                'status' => $response['data']['status']
            ];
        }

        return [
            'success' => false,
            'error' => $response['message'] ?? 'Subscription creation failed'
        ];
    }

    public function createPlan(array $data): array
    {
        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'amount' => $data['amount'] * 100, // Convert to kobo
            'currency' => $data['currency'] ?? 'GHS',
            'interval' => $data['interval'] ?? 'monthly',
            'send_invoices' => $data['send_invoices'] ?? true,
            'send_sms' => $data['send_sms'] ?? false,
            'metadata' => [
                'tenant_id' => $data['tenant_id'] ?? ''
            ]
        ];

        $response = $this->makeRequest('POST', '/plan', $payload);
        
        if ($response['status']) {
            return [
                'success' => true,
                'plan_code' => $response['data']['plan_code'],
                'plan_id' => $response['data']['id']
            ];
        }

        return [
            'success' => false,
            'error' => $response['message'] ?? 'Plan creation failed'
        ];
    }

    public function refundTransaction(string $reference, int $amount = null): array
    {
        $payload = ['transaction' => $reference];
        if ($amount) {
            $payload['amount'] = $amount * 100; // Convert to kobo
        }

        $response = $this->makeRequest('POST', '/refund', $payload);
        
        if ($response['status']) {
            $this->logPaymentAttempt($reference, 'refunded', $payload, $response);
            return [
                'success' => true,
                'refund_id' => $response['data']['id'],
                'amount' => $response['data']['amount'] / 100,
                'status' => $response['data']['status']
            ];
        }

        $this->logPaymentAttempt($reference, 'refund_failed', $payload, $response);
        return [
            'success' => false,
            'error' => $response['message'] ?? 'Refund failed'
        ];
    }

    public function getTransactionHistory(string $customerCode = null, int $page = 1): array
    {
        $endpoint = '/transaction';
        $params = ['page' => $page, 'perPage' => 50];
        
        if ($customerCode) {
            $params['customer'] = $customerCode;
        }

        $response = $this->makeRequest('GET', $endpoint, [], $params);
        
        if ($response['status']) {
            return [
                'success' => true,
                'transactions' => $response['data'],
                'meta' => $response['meta']
            ];
        }

        return [
            'success' => false,
            'error' => $response['message'] ?? 'Failed to fetch transactions'
        ];
    }

    public function getBankList(): array
    {
        $response = $this->makeRequest('GET', '/bank');
        
        if ($response['status']) {
            return [
                'success' => true,
                'banks' => $response['data']
            ];
        }

        return [
            'success' => false,
            'error' => $response['message'] ?? 'Failed to fetch banks'
        ];
    }

    public function resolveAccountNumber(string $accountNumber, string $bankCode): array
    {
        $params = [
            'account_number' => $accountNumber,
            'bank_code' => $bankCode
        ];

        $response = $this->makeRequest('GET', '/bank/resolve', [], $params);
        
        if ($response['status']) {
            return [
                'success' => true,
                'account_name' => $response['data']['account_name'],
                'account_number' => $response['data']['account_number'],
                'bank_id' => $response['data']['bank_id']
            ];
        }

        return [
            'success' => false,
            'error' => $response['message'] ?? 'Account resolution failed'
        ];
    }

    private function makeRequest(string $method, string $endpoint, array $data = [], array $params = []): array
    {
        if (empty($this->paystackSecretKey)) {
            return ['status' => false, 'message' => 'Paystack secret key not configured'];
        }

        $url = $this->baseUrl . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->paystackSecretKey,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return ['status' => false, 'message' => 'Network error'];
        }

        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['status' => false, 'message' => 'Invalid JSON response'];
        }

        return $result;
    }

    private function generateReference(): string
    {
        return 'ARDENT_' . time() . '_' . rand(1000, 9999);
    }

    private function logPaymentAttempt(string $reference, string $status, array $request, array $response): void
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO payment_logs (reference, status, request_data, response_data, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $reference,
                $status,
                json_encode($request),
                json_encode($response)
            ]);
        } catch (\Exception $e) {
            error_log('Failed to log payment: ' . $e->getMessage());
        }
    }

    public function getPublicKey(): string
    {
        return $this->paystackPublicKey;
    }

    public function isConfigured(): bool
    {
        return !empty($this->paystackSecretKey) && !empty($this->paystackPublicKey);
    }
}
