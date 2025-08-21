<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Database;
use ArdentPOS\Middleware\TenantMiddleware;
use ArdentPOS\Middleware\AuthMiddleware;

class InventoryController
{
    public function index(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager', 'inventory_staff']);
        
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $lowStock = $_GET['low_stock'] ?? false;
        
        $query = "
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.price,
                p.cost,
                p.stock_quantity,
                p.min_stock_level,
                p.max_stock_level,
                c.name as category_name,
                CASE 
                    WHEN p.stock_quantity <= p.min_stock_level THEN 'low'
                    WHEN p.stock_quantity >= p.max_stock_level THEN 'high'
                    ELSE 'normal'
                END as stock_status
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.tenant_id = ?
        ";
        
        $params = [$tenantId];
        
        if (!empty($search)) {
            $query .= " AND (p.name ILIKE ? OR p.sku ILIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($category)) {
            $query .= " AND p.category_id = ?";
            $params[] = $category;
        }
        
        if ($lowStock) {
            $query .= " AND p.stock_quantity <= p.min_stock_level";
        }
        
        $query .= " ORDER BY p.name ASC";
        
        $inventory = Database::fetchAll($query, $params);
        
        echo json_encode($inventory);
    }

    public function adjustStock(string $id): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager', 'inventory_staff']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $errors = $this->validateStockAdjustment($input);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }
        
        try {
            Database::beginTransaction();
            
            // Get current product
            $product = Database::fetch(
                'SELECT * FROM products WHERE id = ? AND tenant_id = ?',
                [$id, $tenantId]
            );
            
            if (!$product) {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
                return;
            }
            
            $adjustmentType = $input['type']; // 'add', 'subtract', 'set'
            $quantity = (int)$input['quantity'];
            $reason = $input['reason'] ?? '';
            $notes = $input['notes'] ?? '';
            
            $oldQuantity = (int)$product['stock_quantity'];
            $newQuantity = $oldQuantity;
            
            switch ($adjustmentType) {
                case 'add':
                    $newQuantity = $oldQuantity + $quantity;
                    break;
                case 'subtract':
                    $newQuantity = max(0, $oldQuantity - $quantity);
                    break;
                case 'set':
                    $newQuantity = $quantity;
                    break;
            }
            
            // Update product stock
            Database::update('products', [
                'stock_quantity' => $newQuantity
            ], 'id = ? AND tenant_id = ?', [$id, $tenantId]);
            
            // Record adjustment
            Database::insert('inventory_adjustments', [
                'tenant_id' => $tenantId,
                'product_id' => $id,
                'adjustment_type' => $adjustmentType,
                'quantity_before' => $oldQuantity,
                'quantity_after' => $newQuantity,
                'quantity_changed' => abs($newQuantity - $oldQuantity),
                'reason' => $reason,
                'notes' => $notes,
                'adjusted_by' => AuthMiddleware::getCurrentUserId()
            ]);
            
            Database::commit();
            
            echo json_encode([
                'message' => 'Stock adjusted successfully',
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity
            ]);
            
        } catch (\Exception $e) {
            Database::rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to adjust stock']);
        }
    }

    public function adjustmentHistory(string $id): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager', 'inventory_staff']);
        
        $adjustments = Database::fetchAll("
            SELECT 
                ia.*,
                u.name as adjusted_by_name,
                p.name as product_name
            FROM inventory_adjustments ia
            LEFT JOIN users u ON ia.adjusted_by = u.id
            LEFT JOIN products p ON ia.product_id = p.id
            WHERE ia.product_id = ? AND ia.tenant_id = ?
            ORDER BY ia.created_at DESC
        ", [$id, $tenantId]);
        
        echo json_encode($adjustments);
    }

    public function lowStockReport(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager', 'inventory_staff']);
        
        $lowStockProducts = Database::fetchAll("
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.stock_quantity,
                p.min_stock_level,
                c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.tenant_id = ? AND p.stock_quantity <= p.min_stock_level
            ORDER BY (p.stock_quantity::float / NULLIF(p.min_stock_level, 0)) ASC
        ", [$tenantId]);
        
        echo json_encode($lowStockProducts);
    }

    public function stockValuation(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        $valuation = Database::fetch("
            SELECT 
                COUNT(*) as total_products,
                SUM(stock_quantity) as total_units,
                SUM(stock_quantity * cost) as total_cost_value,
                SUM(stock_quantity * price) as total_retail_value,
                SUM(CASE WHEN stock_quantity <= min_stock_level THEN 1 ELSE 0 END) as low_stock_count
            FROM products 
            WHERE tenant_id = ?
        ", [$tenantId]);
        
        echo json_encode($valuation);
    }

    private function validateStockAdjustment(array $input): array
    {
        $errors = [];
        
        if (empty($input['type']) || !in_array($input['type'], ['add', 'subtract', 'set'])) {
            $errors['type'] = 'Valid adjustment type is required (add, subtract, set)';
        }
        
        if (!isset($input['quantity']) || !is_numeric($input['quantity']) || $input['quantity'] < 0) {
            $errors['quantity'] = 'Valid quantity is required';
        }
        
        if (empty($input['reason'])) {
            $errors['reason'] = 'Reason for adjustment is required';
        }
        
        return $errors;
    }
}
