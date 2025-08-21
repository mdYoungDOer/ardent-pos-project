<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Database;
use ArdentPOS\Middleware\TenantMiddleware;
use ArdentPOS\Middleware\AuthMiddleware;
use Ramsey\Uuid\Uuid;

class ProductController
{
    public function index(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        // Check permissions
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $sql = "SELECT p.*, c.name as category_name, i.quantity, i.min_stock 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN inventory i ON p.id = i.product_id 
                WHERE p.tenant_id = ?";
        
        $params = [$tenantId];
        
        if ($search) {
            $sql .= " AND (p.name ILIKE ? OR p.sku ILIKE ? OR p.barcode ILIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if ($category) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category;
        }
        
        if ($status) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        $products = Database::fetchAll($sql, $params);
        
        echo json_encode($products);
    }

    public function show(string $id): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        $product = Database::fetch(
            "SELECT p.*, c.name as category_name, i.quantity, i.min_stock, i.max_stock 
             FROM products p 
             LEFT JOIN categories c ON p.category_id = c.id 
             LEFT JOIN inventory i ON p.id = i.product_id 
             WHERE p.id = ? AND p.tenant_id = ?",
            [$id, $tenantId]
        );
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            return;
        }
        
        echo json_encode($product);
    }

    public function store(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $errors = $this->validateProduct($input);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }
        
        try {
            Database::beginTransaction();
            
            // Check for duplicate SKU/barcode
            if ($input['sku']) {
                $existing = Database::fetch(
                    'SELECT id FROM products WHERE tenant_id = ? AND sku = ?',
                    [$tenantId, $input['sku']]
                );
                if ($existing) {
                    throw new \Exception('SKU already exists');
                }
            }
            
            if ($input['barcode']) {
                $existing = Database::fetch(
                    'SELECT id FROM products WHERE tenant_id = ? AND barcode = ?',
                    [$tenantId, $input['barcode']]
                );
                if ($existing) {
                    throw new \Exception('Barcode already exists');
                }
            }
            
            // Create product
            $productId = Database::insert('products', [
                'tenant_id' => $tenantId,
                'category_id' => $input['category_id'] ?: null,
                'name' => $input['name'],
                'description' => $input['description'] ?? null,
                'sku' => $input['sku'] ?? null,
                'barcode' => $input['barcode'] ?? null,
                'price' => $input['price'],
                'cost' => $input['cost'] ?? 0,
                'tax_rate' => $input['tax_rate'] ?? 0,
                'track_inventory' => $input['track_inventory'] ?? true,
                'status' => $input['status'] ?? 'active',
                'image_url' => $input['image_url'] ?? null
            ]);
            
            // Create inventory record if tracking inventory
            if ($input['track_inventory'] ?? true) {
                Database::insert('inventory', [
                    'tenant_id' => $tenantId,
                    'product_id' => $productId,
                    'quantity' => $input['initial_quantity'] ?? 0,
                    'min_stock' => $input['min_stock'] ?? 0,
                    'max_stock' => $input['max_stock'] ?? null
                ]);
            }
            
            Database::commit();
            
            echo json_encode([
                'message' => 'Product created successfully',
                'id' => $productId
            ]);
            
        } catch (\Exception $e) {
            Database::rollback();
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function update(string $id): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $errors = $this->validateProduct($input, $id);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }
        
        try {
            Database::beginTransaction();
            
            // Update product
            $updated = Database::update('products', [
                'category_id' => $input['category_id'] ?: null,
                'name' => $input['name'],
                'description' => $input['description'] ?? null,
                'sku' => $input['sku'] ?? null,
                'barcode' => $input['barcode'] ?? null,
                'price' => $input['price'],
                'cost' => $input['cost'] ?? 0,
                'tax_rate' => $input['tax_rate'] ?? 0,
                'track_inventory' => $input['track_inventory'] ?? true,
                'status' => $input['status'] ?? 'active',
                'image_url' => $input['image_url'] ?? null
            ], 'id = ? AND tenant_id = ?', [$id, $tenantId]);
            
            if ($updated === 0) {
                throw new \Exception('Product not found');
            }
            
            // Update inventory if exists
            if (isset($input['min_stock']) || isset($input['max_stock'])) {
                Database::update('inventory', [
                    'min_stock' => $input['min_stock'] ?? 0,
                    'max_stock' => $input['max_stock'] ?? null
                ], 'product_id = ? AND tenant_id = ?', [$id, $tenantId]);
            }
            
            Database::commit();
            
            echo json_encode(['message' => 'Product updated successfully']);
            
        } catch (\Exception $e) {
            Database::rollback();
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function destroy(string $id): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireRole('admin');
        
        try {
            Database::beginTransaction();
            
            // Check if product has sales
            $hasSales = Database::fetch(
                'SELECT COUNT(*) as count FROM sale_items WHERE product_id = ? AND tenant_id = ?',
                [$id, $tenantId]
            );
            
            if ($hasSales['count'] > 0) {
                // Soft delete - just mark as inactive
                Database::update('products', [
                    'status' => 'inactive'
                ], 'id = ? AND tenant_id = ?', [$id, $tenantId]);
            } else {
                // Hard delete
                Database::delete('inventory', 'product_id = ? AND tenant_id = ?', [$id, $tenantId]);
                Database::delete('products', 'id = ? AND tenant_id = ?', [$id, $tenantId]);
            }
            
            Database::commit();
            
            echo json_encode(['message' => 'Product deleted successfully']);
            
        } catch (\Exception $e) {
            Database::rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete product']);
        }
    }

    public function import(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        // TODO: Implement CSV import functionality
        echo json_encode(['message' => 'Import functionality coming soon']);
    }

    private function validateProduct(array $input, string $excludeId = null): array
    {
        $errors = [];
        
        if (empty($input['name'])) {
            $errors['name'] = 'Product name is required';
        }
        
        if (empty($input['price']) || !is_numeric($input['price']) || $input['price'] < 0) {
            $errors['price'] = 'Valid price is required';
        }
        
        if (isset($input['cost']) && (!is_numeric($input['cost']) || $input['cost'] < 0)) {
            $errors['cost'] = 'Cost must be a valid number';
        }
        
        if (isset($input['tax_rate']) && (!is_numeric($input['tax_rate']) || $input['tax_rate'] < 0 || $input['tax_rate'] > 100)) {
            $errors['tax_rate'] = 'Tax rate must be between 0 and 100';
        }
        
        return $errors;
    }
}
