<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Database;
use ArdentPOS\Middleware\TenantMiddleware;

class DashboardController
{
    public function index(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        $stats = $this->getDashboardStats($tenantId);
        
        echo json_encode([
            'stats' => $stats,
            'recent_sales' => $this->getRecentSales($tenantId),
            'low_stock_products' => $this->getLowStockProducts($tenantId),
            'top_products' => $this->getTopProducts($tenantId)
        ]);
    }

    public function stats(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        $stats = $this->getDashboardStats($tenantId);
        
        echo json_encode($stats);
    }

    private function getDashboardStats(string $tenantId): array
    {
        // Today's sales
        $todaySales = Database::fetch(
            'SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total 
             FROM sales WHERE tenant_id = ? AND DATE(created_at) = CURRENT_DATE',
            [$tenantId]
        );

        // This month's sales
        $monthSales = Database::fetch(
            'SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total 
             FROM sales WHERE tenant_id = ? AND DATE_TRUNC(\'month\', created_at) = DATE_TRUNC(\'month\', CURRENT_DATE)',
            [$tenantId]
        );

        // Total products
        $totalProducts = Database::fetch(
            'SELECT COUNT(*) as count FROM products WHERE tenant_id = ? AND status = ?',
            [$tenantId, 'active']
        );

        // Total customers
        $totalCustomers = Database::fetch(
            'SELECT COUNT(*) as count FROM customers WHERE tenant_id = ?',
            [$tenantId]
        );

        // Low stock count
        $lowStockCount = Database::fetch(
            'SELECT COUNT(*) as count FROM inventory i 
             JOIN products p ON i.product_id = p.id 
             WHERE i.tenant_id = ? AND i.quantity <= i.min_stock AND p.track_inventory = true',
            [$tenantId]
        );

        return [
            'today_sales' => [
                'count' => (int)$todaySales['count'],
                'total' => (float)$todaySales['total']
            ],
            'month_sales' => [
                'count' => (int)$monthSales['count'],
                'total' => (float)$monthSales['total']
            ],
            'total_products' => (int)$totalProducts['count'],
            'total_customers' => (int)$totalCustomers['count'],
            'low_stock_count' => (int)$lowStockCount['count']
        ];
    }

    private function getRecentSales(string $tenantId): array
    {
        return Database::fetchAll(
            'SELECT s.id, s.sale_number, s.total_amount, s.created_at,
                    CONCAT(c.first_name, \' \', c.last_name) as customer_name
             FROM sales s
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE s.tenant_id = ?
             ORDER BY s.created_at DESC
             LIMIT 10',
            [$tenantId]
        );
    }

    private function getLowStockProducts(string $tenantId): array
    {
        return Database::fetchAll(
            'SELECT p.id, p.name, p.sku, i.quantity, i.min_stock
             FROM products p
             JOIN inventory i ON p.id = i.product_id
             WHERE p.tenant_id = ? AND i.quantity <= i.min_stock AND p.track_inventory = true
             ORDER BY i.quantity ASC
             LIMIT 10',
            [$tenantId]
        );
    }

    private function getTopProducts(string $tenantId): array
    {
        return Database::fetchAll(
            'SELECT p.id, p.name, SUM(si.quantity) as total_sold, SUM(si.total_amount) as total_revenue
             FROM products p
             JOIN sale_items si ON p.id = si.product_id
             JOIN sales s ON si.sale_id = s.id
             WHERE p.tenant_id = ? AND s.created_at >= CURRENT_DATE - INTERVAL \'30 days\'
             GROUP BY p.id, p.name
             ORDER BY total_sold DESC
             LIMIT 10',
            [$tenantId]
        );
    }
}
