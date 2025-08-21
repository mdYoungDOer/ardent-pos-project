<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Database;
use ArdentPOS\Middleware\TenantMiddleware;
use ArdentPOS\Middleware\AuthMiddleware;

class SettingsController
{
    public function index(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        // Get tenant settings
        $tenant = Database::fetch(
            'SELECT * FROM tenants WHERE id = ?',
            [$tenantId]
        );
        
        if (!$tenant) {
            http_response_code(404);
            echo json_encode(['error' => 'Tenant not found']);
            return;
        }
        
        // Remove sensitive data
        unset($tenant['created_at'], $tenant['updated_at']);
        
        echo json_encode($tenant);
    }

    public function update(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireRole('admin');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $errors = $this->validateSettings($input);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }
        
        try {
            $updateData = [];
            
            // Business information
            if (isset($input['business_name'])) {
                $updateData['business_name'] = $input['business_name'];
            }
            
            if (isset($input['business_email'])) {
                $updateData['business_email'] = $input['business_email'];
            }
            
            if (isset($input['business_phone'])) {
                $updateData['business_phone'] = $input['business_phone'];
            }
            
            if (isset($input['business_address'])) {
                $updateData['business_address'] = $input['business_address'];
            }
            
            // Tax settings
            if (isset($input['default_tax_rate'])) {
                $updateData['default_tax_rate'] = $input['default_tax_rate'];
            }
            
            if (isset($input['tax_number'])) {
                $updateData['tax_number'] = $input['tax_number'];
            }
            
            // Currency and locale
            if (isset($input['currency'])) {
                $updateData['currency'] = $input['currency'];
            }
            
            if (isset($input['timezone'])) {
                $updateData['timezone'] = $input['timezone'];
            }
            
            if (isset($input['date_format'])) {
                $updateData['date_format'] = $input['date_format'];
            }
            
            // Receipt settings
            if (isset($input['receipt_header'])) {
                $updateData['receipt_header'] = $input['receipt_header'];
            }
            
            if (isset($input['receipt_footer'])) {
                $updateData['receipt_footer'] = $input['receipt_footer'];
            }
            
            // Notification settings
            if (isset($input['low_stock_threshold'])) {
                $updateData['low_stock_threshold'] = $input['low_stock_threshold'];
            }
            
            if (isset($input['email_notifications'])) {
                $updateData['email_notifications'] = $input['email_notifications'] ? 1 : 0;
            }
            
            if (isset($input['sms_notifications'])) {
                $updateData['sms_notifications'] = $input['sms_notifications'] ? 1 : 0;
            }
            
            if (!empty($updateData)) {
                Database::update('tenants', $updateData, 'id = ?', [$tenantId]);
            }
            
            echo json_encode(['message' => 'Settings updated successfully']);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update settings']);
        }
    }

    private function validateSettings(array $input): array
    {
        $errors = [];
        
        if (isset($input['business_email']) && !empty($input['business_email'])) {
            if (!filter_var($input['business_email'], FILTER_VALIDATE_EMAIL)) {
                $errors['business_email'] = 'Valid business email is required';
            }
        }
        
        if (isset($input['default_tax_rate']) && !is_numeric($input['default_tax_rate'])) {
            $errors['default_tax_rate'] = 'Tax rate must be a valid number';
        }
        
        if (isset($input['low_stock_threshold']) && (!is_numeric($input['low_stock_threshold']) || $input['low_stock_threshold'] < 0)) {
            $errors['low_stock_threshold'] = 'Low stock threshold must be a positive number';
        }
        
        if (isset($input['currency']) && !in_array($input['currency'], ['GHS', 'USD', 'EUR', 'GBP', 'NGN', 'CAD', 'AUD'])) {
            $errors['currency'] = 'Invalid currency code';
        }
        
        return $errors;
    }
}
