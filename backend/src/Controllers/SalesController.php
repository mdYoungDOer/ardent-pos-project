<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Database;
use ArdentPOS\Middleware\TenantMiddleware;
use ArdentPOS\Middleware\AuthMiddleware;

class SalesController
{
    public function index(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager', 'cashier']);
        
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $cashier = $_GET['cashier'] ?? '';
        
        $query = "
            SELECT 
                s.*,
                u.name as cashier_name,
                c.name as customer_name,
                COUNT(si.id) as item_count
            FROM sales s
            LEFT JOIN users u ON s.cashier_id = u.id
            LEFT JOIN customers c ON s.customer_id = c.id
            LEFT JOIN sale_items si ON s.id = si.sale_id
            WHERE s.tenant_id = ?
        ";
        
        $params = [$tenantId];
        
        if (!empty($dateFrom)) {
            $query .= " AND s.created_at >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }
        
        if (!empty($dateTo)) {
            $query .= " AND s.created_at <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }
        
        if (!empty($cashier)) {
            $query .= " AND s.cashier_id = ?";
            $params[] = $cashier;
        }
        
        $query .= " GROUP BY s.id, u.name, c.name ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $sales = Database::fetchAll($query, $params);
        
        // Get total count
        $countQuery = str_replace('SELECT s.*, u.name as cashier_name, c.name as customer_name, COUNT(si.id) as item_count', 'SELECT COUNT(DISTINCT s.id) as total', $query);
        $countQuery = preg_replace('/GROUP BY.*$/', '', $countQuery);
        $countQuery = preg_replace('/LIMIT.*$/', '', $countQuery);
        
        $total = Database::fetch($countQuery, array_slice($params, 0, -2))['total'];
        
        echo json_encode([
            'sales' => $sales,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    public function store(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager', 'cashier']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $errors = $this->validateSale($input);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }
        
        try {
            Database::beginTransaction();
            
            $items = $input['items'];
            $subtotal = 0;
            
            // Validate items and calculate subtotal
            foreach ($items as $item) {
                $product = Database::fetch(
                    'SELECT * FROM products WHERE id = ? AND tenant_id = ?',
                    [$item['product_id'], $tenantId]
                );
                
                if (!$product) {
                    throw new \Exception("Product not found: {$item['product_id']}");
                }
                
                if ($product['stock_quantity'] < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product: {$product['name']}");
                }
                
                $subtotal += $item['quantity'] * $item['price'];
            }
            
            $tax = $subtotal * ($input['tax_rate'] ?? 0) / 100;
            $discount = $input['discount_amount'] ?? 0;
            $total = $subtotal + $tax - $discount;
            
            // Create sale record
            $saleId = Database::insert('sales', [
                'tenant_id' => $tenantId,
                'customer_id' => $input['customer_id'] ?? null,
                'cashier_id' => AuthMiddleware::getCurrentUserId(),
                'subtotal' => $subtotal,
                'tax_rate' => $input['tax_rate'] ?? 0,
                'tax_amount' => $tax,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'payment_method' => $input['payment_method'],
                'payment_status' => 'completed',
                'notes' => $input['notes'] ?? null
            ]);
            
            // Create sale items and update inventory
            foreach ($items as $item) {
                Database::insert('sale_items', [
                    'sale_id' => $saleId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total_price' => $item['quantity'] * $item['price']
                ]);
                
                // Update product stock
                Database::query(
                    'UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND tenant_id = ?',
                    [$item['quantity'], $item['product_id'], $tenantId]
                );
            }
            
            Database::commit();
            
            echo json_encode([
                'message' => 'Sale completed successfully',
                'sale_id' => $saleId,
                'total' => $total
            ]);
            
        } catch (\Exception $e) {
            Database::rollback();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function show(string $id): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager', 'cashier']);
        
        $sale = Database::fetch("
            SELECT 
                s.*,
                u.name as cashier_name,
                c.name as customer_name,
                c.email as customer_email,
                c.phone as customer_phone
            FROM sales s
            LEFT JOIN users u ON s.cashier_id = u.id
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE s.id = ? AND s.tenant_id = ?
        ", [$id, $tenantId]);
        
        if (!$sale) {
            http_response_code(404);
            echo json_encode(['error' => 'Sale not found']);
            return;
        }
        
        $items = Database::fetchAll("
            SELECT 
                si.*,
                p.name as product_name,
                p.sku as product_sku
            FROM sale_items si
            LEFT JOIN products p ON si.product_id = p.id
            WHERE si.sale_id = ?
        ", [$id]);
        
        $sale['items'] = $items;
        
        echo json_encode($sale);
    }

    public function refund(string $id): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            Database::beginTransaction();
            
            $sale = Database::fetch(
                'SELECT * FROM sales WHERE id = ? AND tenant_id = ?',
                [$id, $tenantId]
            );
            
            if (!$sale) {
                http_response_code(404);
                echo json_encode(['error' => 'Sale not found']);
                return;
            }
            
            if ($sale['payment_status'] === 'refunded') {
                http_response_code(400);
                echo json_encode(['error' => 'Sale already refunded']);
                return;
            }
            
            // Get sale items
            $items = Database::fetchAll(
                'SELECT * FROM sale_items WHERE sale_id = ?',
                [$id]
            );
            
            // Restore inventory
            foreach ($items as $item) {
                Database::query(
                    'UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?',
                    [$item['quantity'], $item['product_id']]
                );
            }
            
            // Update sale status
            Database::update('sales', [
                'payment_status' => 'refunded',
                'refund_reason' => $input['reason'] ?? null,
                'refunded_at' => date('Y-m-d H:i:s'),
                'refunded_by' => AuthMiddleware::getCurrentUserId()
            ], 'id = ?', [$id]);
            
            Database::commit();
            
            echo json_encode(['message' => 'Sale refunded successfully']);
            
        } catch (\Exception $e) {
            Database::rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to process refund']);
        }
    }

    public function dailySummary(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager', 'cashier']);
        
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $summary = Database::fetch("
            SELECT 
                COUNT(*) as total_sales,
                COALESCE(SUM(total_amount), 0) as total_revenue,
                COALESCE(SUM(tax_amount), 0) as total_tax,
                COALESCE(SUM(discount_amount), 0) as total_discount,
                COALESCE(AVG(total_amount), 0) as average_sale
            FROM sales 
            WHERE tenant_id = ? 
            AND DATE(created_at) = ?
            AND payment_status = 'completed'
        ", [$tenantId, $date]);
        
        $paymentMethods = Database::fetchAll("
            SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(total_amount) as total
            FROM sales 
            WHERE tenant_id = ? 
            AND DATE(created_at) = ?
            AND payment_status = 'completed'
            GROUP BY payment_method
        ", [$tenantId, $date]);
        
        echo json_encode([
            'summary' => $summary,
            'payment_methods' => $paymentMethods,
            'date' => $date
        ]);
    }

    private function validateSale(array $input): array
    {
        $errors = [];
        
        if (empty($input['items']) || !is_array($input['items'])) {
            $errors['items'] = 'Sale items are required';
        } else {
            foreach ($input['items'] as $index => $item) {
                if (empty($item['product_id'])) {
                    $errors["items.$index.product_id"] = 'Product ID is required';
                }
                if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                    $errors["items.$index.quantity"] = 'Valid quantity is required';
                }
                if (!isset($item['price']) || $item['price'] < 0) {
                    $errors["items.$index.price"] = 'Valid price is required';
                }
            }
        }
        
        if (empty($input['payment_method'])) {
            $errors['payment_method'] = 'Payment method is required';
        }
        
        return $errors;
    }
}
