<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Database;
use ArdentPOS\Middleware\TenantMiddleware;
use ArdentPOS\Middleware\AuthMiddleware;

class ReportsController
{
    public function salesReport(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $groupBy = $_GET['group_by'] ?? 'day'; // day, week, month
        
        // Sales summary
        $summary = Database::fetch("
            SELECT 
                COUNT(*) as total_sales,
                COALESCE(SUM(total_amount), 0) as total_revenue,
                COALESCE(SUM(tax_amount), 0) as total_tax,
                COALESCE(SUM(discount_amount), 0) as total_discount,
                COALESCE(AVG(total_amount), 0) as average_sale
            FROM sales 
            WHERE tenant_id = ? 
            AND DATE(created_at) BETWEEN ? AND ?
            AND payment_status = 'completed'
        ", [$tenantId, $dateFrom, $dateTo]);
        
        // Sales by period
        $dateFormat = match($groupBy) {
            'week' => "DATE_TRUNC('week', created_at)",
            'month' => "DATE_TRUNC('month', created_at)",
            default => "DATE(created_at)"
        };
        
        $salesByPeriod = Database::fetchAll("
            SELECT 
                $dateFormat as period,
                COUNT(*) as sales_count,
                SUM(total_amount) as revenue
            FROM sales 
            WHERE tenant_id = ? 
            AND DATE(created_at) BETWEEN ? AND ?
            AND payment_status = 'completed'
            GROUP BY $dateFormat
            ORDER BY period ASC
        ", [$tenantId, $dateFrom, $dateTo]);
        
        // Top products
        $topProducts = Database::fetchAll("
            SELECT 
                p.name,
                p.sku,
                SUM(si.quantity) as quantity_sold,
                SUM(si.total_price) as revenue
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            JOIN sales s ON si.sale_id = s.id
            WHERE s.tenant_id = ? 
            AND DATE(s.created_at) BETWEEN ? AND ?
            AND s.payment_status = 'completed'
            GROUP BY p.id, p.name, p.sku
            ORDER BY quantity_sold DESC
            LIMIT 10
        ", [$tenantId, $dateFrom, $dateTo]);
        
        // Payment methods
        $paymentMethods = Database::fetchAll("
            SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(total_amount) as total
            FROM sales 
            WHERE tenant_id = ? 
            AND DATE(created_at) BETWEEN ? AND ?
            AND payment_status = 'completed'
            GROUP BY payment_method
            ORDER BY total DESC
        ", [$tenantId, $dateFrom, $dateTo]);
        
        echo json_encode([
            'summary' => $summary,
            'sales_by_period' => $salesByPeriod,
            'top_products' => $topProducts,
            'payment_methods' => $paymentMethods,
            'period' => ['from' => $dateFrom, 'to' => $dateTo, 'group_by' => $groupBy]
        ]);
    }

    public function inventoryReport(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager', 'inventory_staff']);
        
        // Stock levels
        $stockLevels = Database::fetchAll("
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.stock_quantity,
                p.min_stock_level,
                p.max_stock_level,
                p.cost,
                p.price,
                c.name as category_name,
                (p.stock_quantity * p.cost) as stock_value,
                CASE 
                    WHEN p.stock_quantity <= p.min_stock_level THEN 'low'
                    WHEN p.stock_quantity >= p.max_stock_level THEN 'high'
                    ELSE 'normal'
                END as stock_status
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.tenant_id = ?
            ORDER BY p.name ASC
        ", [$tenantId]);
        
        // Stock valuation summary
        $valuation = Database::fetch("
            SELECT 
                COUNT(*) as total_products,
                SUM(stock_quantity) as total_units,
                SUM(stock_quantity * cost) as total_cost_value,
                SUM(stock_quantity * price) as total_retail_value,
                SUM(CASE WHEN stock_quantity <= min_stock_level THEN 1 ELSE 0 END) as low_stock_count,
                SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock_count
            FROM products 
            WHERE tenant_id = ?
        ", [$tenantId]);
        
        // Recent adjustments
        $recentAdjustments = Database::fetchAll("
            SELECT 
                ia.*,
                p.name as product_name,
                p.sku as product_sku,
                u.name as adjusted_by_name
            FROM inventory_adjustments ia
            JOIN products p ON ia.product_id = p.id
            LEFT JOIN users u ON ia.adjusted_by = u.id
            WHERE ia.tenant_id = ?
            ORDER BY ia.created_at DESC
            LIMIT 20
        ", [$tenantId]);
        
        echo json_encode([
            'stock_levels' => $stockLevels,
            'valuation' => $valuation,
            'recent_adjustments' => $recentAdjustments
        ]);
    }

    public function customerReport(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        // Top customers
        $topCustomers = Database::fetchAll("
            SELECT 
                c.id,
                c.name,
                c.email,
                COUNT(s.id) as total_orders,
                SUM(s.total_amount) as total_spent,
                AVG(s.total_amount) as average_order_value,
                MAX(s.created_at) as last_order_date
            FROM customers c
            JOIN sales s ON c.id = s.customer_id
            WHERE c.tenant_id = ? 
            AND DATE(s.created_at) BETWEEN ? AND ?
            AND s.payment_status = 'completed'
            GROUP BY c.id, c.name, c.email
            ORDER BY total_spent DESC
            LIMIT 20
        ", [$tenantId, $dateFrom, $dateTo]);
        
        // Customer acquisition
        $newCustomers = Database::fetchAll("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as new_customers
            FROM customers 
            WHERE tenant_id = ? 
            AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ", [$tenantId, $dateFrom, $dateTo]);
        
        // Customer summary
        $customerSummary = Database::fetch("
            SELECT 
                COUNT(*) as total_customers,
                COUNT(CASE WHEN DATE(created_at) BETWEEN ? AND ? THEN 1 END) as new_customers_period,
                AVG(customer_stats.total_spent) as avg_customer_value,
                AVG(customer_stats.order_count) as avg_orders_per_customer
            FROM customers c
            LEFT JOIN (
                SELECT 
                    customer_id,
                    COUNT(*) as order_count,
                    SUM(total_amount) as total_spent
                FROM sales 
                WHERE payment_status = 'completed'
                GROUP BY customer_id
            ) customer_stats ON c.id = customer_stats.customer_id
            WHERE c.tenant_id = ?
        ", [$dateFrom, $dateTo, $tenantId]);
        
        echo json_encode([
            'top_customers' => $topCustomers,
            'new_customers' => $newCustomers,
            'summary' => $customerSummary,
            'period' => ['from' => $dateFrom, 'to' => $dateTo]
        ]);
    }

    public function profitReport(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireRole('admin');
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        // Profit by product
        $profitByProduct = Database::fetchAll("
            SELECT 
                p.name,
                p.sku,
                SUM(si.quantity) as quantity_sold,
                SUM(si.total_price) as revenue,
                SUM(si.quantity * p.cost) as cost_of_goods,
                SUM(si.total_price - (si.quantity * p.cost)) as gross_profit,
                CASE 
                    WHEN SUM(si.total_price) > 0 
                    THEN ((SUM(si.total_price - (si.quantity * p.cost)) / SUM(si.total_price)) * 100)
                    ELSE 0 
                END as profit_margin
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            JOIN sales s ON si.sale_id = s.id
            WHERE s.tenant_id = ? 
            AND DATE(s.created_at) BETWEEN ? AND ?
            AND s.payment_status = 'completed'
            GROUP BY p.id, p.name, p.sku
            ORDER BY gross_profit DESC
        ", [$tenantId, $dateFrom, $dateTo]);
        
        // Overall profit summary
        $profitSummary = Database::fetch("
            SELECT 
                SUM(si.total_price) as total_revenue,
                SUM(si.quantity * p.cost) as total_cogs,
                SUM(si.total_price - (si.quantity * p.cost)) as gross_profit,
                SUM(s.tax_amount) as total_tax,
                SUM(s.discount_amount) as total_discounts,
                CASE 
                    WHEN SUM(si.total_price) > 0 
                    THEN ((SUM(si.total_price - (si.quantity * p.cost)) / SUM(si.total_price)) * 100)
                    ELSE 0 
                END as gross_margin
            FROM sale_items si
            JOIN products p ON si.product_id = p.id
            JOIN sales s ON si.sale_id = s.id
            WHERE s.tenant_id = ? 
            AND DATE(s.created_at) BETWEEN ? AND ?
            AND s.payment_status = 'completed'
        ", [$tenantId, $dateFrom, $dateTo]);
        
        echo json_encode([
            'profit_by_product' => $profitByProduct,
            'summary' => $profitSummary,
            'period' => ['from' => $dateFrom, 'to' => $dateTo]
        ]);
    }

    public function exportReport(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        $reportType = $_GET['type'] ?? 'sales';
        $format = $_GET['format'] ?? 'csv';
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        $data = [];
        $filename = '';
        
        switch ($reportType) {
            case 'sales':
                $data = Database::fetchAll("
                    SELECT 
                        s.id,
                        s.created_at,
                        s.total_amount,
                        s.payment_method,
                        u.name as cashier,
                        c.name as customer
                    FROM sales s
                    LEFT JOIN users u ON s.cashier_id = u.id
                    LEFT JOIN customers c ON s.customer_id = c.id
                    WHERE s.tenant_id = ? 
                    AND DATE(s.created_at) BETWEEN ? AND ?
                    AND s.payment_status = 'completed'
                    ORDER BY s.created_at DESC
                ", [$tenantId, $dateFrom, $dateTo]);
                $filename = "sales_report_{$dateFrom}_to_{$dateTo}";
                break;
                
            case 'inventory':
                $data = Database::fetchAll("
                    SELECT 
                        p.name,
                        p.sku,
                        p.stock_quantity,
                        p.min_stock_level,
                        p.cost,
                        p.price,
                        c.name as category
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.tenant_id = ?
                    ORDER BY p.name ASC
                ", [$tenantId]);
                $filename = "inventory_report_" . date('Y-m-d');
                break;
        }
        
        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
            
            if (!empty($data)) {
                $output = fopen('php://output', 'w');
                fputcsv($output, array_keys($data[0]));
                
                foreach ($data as $row) {
                    fputcsv($output, $row);
                }
                
                fclose($output);
            }
        } else {
            echo json_encode($data);
        }
    }
}
