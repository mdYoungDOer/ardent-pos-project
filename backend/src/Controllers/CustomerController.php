<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Database;
use ArdentPOS\Middleware\TenantMiddleware;
use ArdentPOS\Middleware\AuthMiddleware;

class CustomerController
{
    public function index(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager', 'cashier']);
        
        $search = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        $query = "
            SELECT 
                c.*,
                COUNT(s.id) as total_orders,
                COALESCE(SUM(s.total_amount), 0) as total_spent,
                MAX(s.created_at) as last_order_date
            FROM customers c
            LEFT JOIN sales s ON c.id = s.customer_id AND s.payment_status = 'completed'
            WHERE c.tenant_id = ?
        ";
        
        $params = [$tenantId];
        
        if (!empty($search)) {
            $query .= " AND (c.name ILIKE ? OR c.email ILIKE ? OR c.phone ILIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $query .= " GROUP BY c.id ORDER BY c.name ASC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $customers = Database::fetchAll($query, $params);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM customers WHERE tenant_id = ?";
        $countParams = [$tenantId];
        
        if (!empty($search)) {
            $countQuery .= " AND (name ILIKE ? OR email ILIKE ? OR phone ILIKE ?)";
            $countParams[] = "%$search%";
            $countParams[] = "%$search%";
            $countParams[] = "%$search%";
        }
        
        $total = Database::fetch($countQuery, $countParams)['total'];
        
        echo json_encode([
            'customers' => $customers,
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
        
        $errors = $this->validateCustomer($input);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }
        
        try {
            // Check for duplicate email
            if (!empty($input['email'])) {
                $existing = Database::fetch(
                    'SELECT id FROM customers WHERE tenant_id = ? AND email = ?',
                    [$tenantId, $input['email']]
                );
                
                if ($existing) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Customer with this email already exists']);
                    return;
                }
            }
            
            $customerId = Database::insert('customers', [
                'tenant_id' => $tenantId,
                'name' => $input['name'],
                'email' => $input['email'] ?? null,
                'phone' => $input['phone'] ?? null,
                'address' => $input['address'] ?? null,
                'city' => $input['city'] ?? null,
                'state' => $input['state'] ?? null,
                'postal_code' => $input['postal_code'] ?? null,
                'country' => $input['country'] ?? null,
                'date_of_birth' => $input['date_of_birth'] ?? null,
                'notes' => $input['notes'] ?? null
            ]);
            
            echo json_encode([
                'message' => 'Customer created successfully',
                'id' => $customerId
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create customer']);
        }
    }

    public function show(string $id): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager', 'cashier']);
        
        $customer = Database::fetch(
            'SELECT * FROM customers WHERE id = ? AND tenant_id = ?',
            [$id, $tenantId]
        );
        
        if (!$customer) {
            http_response_code(404);
            echo json_encode(['error' => 'Customer not found']);
            return;
        }
        
        // Get customer statistics
        $stats = Database::fetch("
            SELECT 
                COUNT(s.id) as total_orders,
                COALESCE(SUM(s.total_amount), 0) as total_spent,
                COALESCE(AVG(s.total_amount), 0) as average_order_value,
                MAX(s.created_at) as last_order_date,
                MIN(s.created_at) as first_order_date
            FROM sales s
            WHERE s.customer_id = ? AND s.payment_status = 'completed'
        ", [$id]);
        
        // Get recent orders
        $recentOrders = Database::fetchAll("
            SELECT 
                s.id,
                s.total_amount,
                s.payment_method,
                s.created_at,
                COUNT(si.id) as item_count
            FROM sales s
            LEFT JOIN sale_items si ON s.id = si.sale_id
            WHERE s.customer_id = ?
            GROUP BY s.id
            ORDER BY s.created_at DESC
            LIMIT 10
        ", [$id]);
        
        $customer['stats'] = $stats;
        $customer['recent_orders'] = $recentOrders;
        
        echo json_encode($customer);
    }

    public function update(string $id): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager', 'cashier']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $errors = $this->validateCustomer($input);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }
        
        try {
            // Check for duplicate email (excluding current customer)
            if (!empty($input['email'])) {
                $existing = Database::fetch(
                    'SELECT id FROM customers WHERE tenant_id = ? AND email = ? AND id != ?',
                    [$tenantId, $input['email'], $id]
                );
                
                if ($existing) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Customer with this email already exists']);
                    return;
                }
            }
            
            $updated = Database::update('customers', [
                'name' => $input['name'],
                'email' => $input['email'] ?? null,
                'phone' => $input['phone'] ?? null,
                'address' => $input['address'] ?? null,
                'city' => $input['city'] ?? null,
                'state' => $input['state'] ?? null,
                'postal_code' => $input['postal_code'] ?? null,
                'country' => $input['country'] ?? null,
                'date_of_birth' => $input['date_of_birth'] ?? null,
                'notes' => $input['notes'] ?? null
            ], 'id = ? AND tenant_id = ?', [$id, $tenantId]);
            
            if ($updated === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Customer not found']);
                return;
            }
            
            echo json_encode(['message' => 'Customer updated successfully']);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update customer']);
        }
    }

    public function destroy(string $id): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireRole('admin');
        
        try {
            // Check if customer has orders
            $hasOrders = Database::fetch(
                'SELECT COUNT(*) as count FROM sales WHERE customer_id = ?',
                [$id]
            );
            
            if ($hasOrders['count'] > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot delete customer with existing orders']);
                return;
            }
            
            $deleted = Database::delete('customers', 'id = ? AND tenant_id = ?', [$id, $tenantId]);
            
            if ($deleted === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Customer not found']);
                return;
            }
            
            echo json_encode(['message' => 'Customer deleted successfully']);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete customer']);
        }
    }

    public function search(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager', 'cashier']);
        
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            echo json_encode([]);
            return;
        }
        
        $customers = Database::fetchAll("
            SELECT id, name, email, phone
            FROM customers 
            WHERE tenant_id = ? 
            AND (name ILIKE ? OR email ILIKE ? OR phone ILIKE ?)
            ORDER BY name ASC
            LIMIT 10
        ", [$tenantId, "%$query%", "%$query%", "%$query%"]);
        
        echo json_encode($customers);
    }

    private function validateCustomer(array $input): array
    {
        $errors = [];
        
        if (empty($input['name'])) {
            $errors['name'] = 'Customer name is required';
        }
        
        if (!empty($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email address is required';
        }
        
        if (!empty($input['date_of_birth'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $input['date_of_birth']);
            if (!$date || $date->format('Y-m-d') !== $input['date_of_birth']) {
                $errors['date_of_birth'] = 'Valid date of birth is required (YYYY-MM-DD)';
            }
        }
        
        return $errors;
    }
}
