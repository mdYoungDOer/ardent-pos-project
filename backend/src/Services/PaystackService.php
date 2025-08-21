<?php

namespace ArdentPOS\Services;

use ArdentPOS\Core\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class PaystackService
{
    private Client $client;
    private string $secretKey;
    private string $publicKey;

    public function __construct()
    {
        $this->secretKey = Config::get('paystack.secret_key');
        $this->publicKey = Config::get('paystack.public_key');
        
        $this->client = new Client([
            'base_uri' => 'https://api.paystack.co/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
    }

    public function initializeTransaction(array $data): array
    {
        try {
            $response = $this->client->post('transaction/initialize', [
                'json' => $data
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
            
        } catch (RequestException $e) {
            throw new \Exception('Failed to initialize Paystack transaction: ' . $e->getMessage());
        }
    }

    public function verifyTransaction(string $reference): array
    {
        try {
            $response = $this->client->get("transaction/verify/{$reference}");
            
            return json_decode($response->getBody()->getContents(), true);
            
        } catch (RequestException $e) {
            throw new \Exception('Failed to verify Paystack transaction: ' . $e->getMessage());
        }
    }

    public function createCustomer(array $data): array
    {
        try {
            $response = $this->client->post('customer', [
                'json' => $data
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
            
        } catch (RequestException $e) {
            throw new \Exception('Failed to create Paystack customer: ' . $e->getMessage());
        }
    }

    public function createPlan(array $data): array
    {
        try {
            $response = $this->client->post('plan', [
                'json' => $data
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
            
        } catch (RequestException $e) {
            throw new \Exception('Failed to create Paystack plan: ' . $e->getMessage());
        }
    }

    public function createSubscription(array $data): array
    {
        try {
            $response = $this->client->post('subscription', [
                'json' => $data
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
            
        } catch (RequestException $e) {
            throw new \Exception('Failed to create Paystack subscription: ' . $e->getMessage());
        }
    }

    public function disableSubscription(string $code, string $token): array
    {
        try {
            $response = $this->client->post('subscription/disable', [
                'json' => [
                    'code' => $code,
                    'token' => $token
                ]
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
            
        } catch (RequestException $e) {
            throw new \Exception('Failed to disable Paystack subscription: ' . $e->getMessage());
        }
    }

    public function enableSubscription(string $code, string $token): array
    {
        try {
            $response = $this->client->post('subscription/enable', [
                'json' => [
                    'code' => $code,
                    'token' => $token
                ]
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
            
        } catch (RequestException $e) {
            throw new \Exception('Failed to enable Paystack subscription: ' . $e->getMessage());
        }
    }

    public function getSubscription(string $idOrCode): array
    {
        try {
            $response = $this->client->get("subscription/{$idOrCode}");
            
            return json_decode($response->getBody()->getContents(), true);
            
        } catch (RequestException $e) {
            throw new \Exception('Failed to get Paystack subscription: ' . $e->getMessage());
        }
    }

    public function listTransactions(array $params = []): array
    {
        try {
            $queryString = http_build_query($params);
            $response = $this->client->get("transaction?" . $queryString);
            
            return json_decode($response->getBody()->getContents(), true);
            
        } catch (RequestException $e) {
            throw new \Exception('Failed to list Paystack transactions: ' . $e->getMessage());
        }
    }

    public function refundTransaction(string $reference, array $data = []): array
    {
        try {
            $response = $this->client->post("refund", [
                'json' => array_merge(['transaction' => $reference], $data)
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
            
        } catch (RequestException $e) {
            throw new \Exception('Failed to refund Paystack transaction: ' . $e->getMessage());
        }
    }

    public function createTransferRecipient(array $data): array
    {
        try {
            $response = $this->client->post('transferrecipient', [
                'json' => $data
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
            
        } catch (RequestException $e) {
            throw new \Exception('Failed to create transfer recipient: ' . $e->getMessage());
        }
    }

    public function initiateTransfer(array $data): array
    {
        try {
            $response = $this->client->post('transfer', [
                'json' => $data
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
            
        } catch (RequestException $e) {
            throw new \Exception('Failed to initiate transfer: ' . $e->getMessage());
        }
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function generateReference(string $prefix = 'txn'): string
    {
        return $prefix . '_' . time() . '_' . bin2hex(random_bytes(8));
    }

    public function formatAmount(float $amount): int
    {
        // Convert to kobo (multiply by 100)
        return (int) ($amount * 100);
    }

    public function parseAmount(int $amount): float
    {
        // Convert from kobo (divide by 100)
        return $amount / 100;
    }
}
