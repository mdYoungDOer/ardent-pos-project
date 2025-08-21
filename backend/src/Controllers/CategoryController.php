<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Database;
use ArdentPOS\Middleware\TenantMiddleware;
use ArdentPOS\Middleware\AuthMiddleware;

class CategoryController
{
    public function index(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        $categories = Database::fetchAll(
            'SELECT * FROM categories WHERE tenant_id = ? ORDER BY name ASC',
            [$tenantId]
        );
        
        echo json_encode($categories);
    }

    public function store(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $errors = $this->validateCategory($input);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }
        
        try {
            // Check for duplicate name
            $existing = Database::fetch(
                'SELECT id FROM categories WHERE tenant_id = ? AND name = ?',
                [$tenantId, $input['name']]
            );
            
            if ($existing) {
                http_response_code(400);
                echo json_encode(['error' => 'Category name already exists']);
                return;
            }
            
            $categoryId = Database::insert('categories', [
                'tenant_id' => $tenantId,
                'name' => $input['name'],
                'description' => $input['description'] ?? null,
                'color' => $input['color'] ?? '#e41e5b'
            ]);
            
            echo json_encode([
                'message' => 'Category created successfully',
                'id' => $categoryId
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create category']);
        }
    }

    public function update(string $id): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $errors = $this->validateCategory($input);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }
        
        try {
            $updated = Database::update('categories', [
                'name' => $input['name'],
                'description' => $input['description'] ?? null,
                'color' => $input['color'] ?? '#e41e5b'
            ], 'id = ? AND tenant_id = ?', [$id, $tenantId]);
            
            if ($updated === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Category not found']);
                return;
            }
            
            echo json_encode(['message' => 'Category updated successfully']);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update category']);
        }
    }

    public function destroy(string $id): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireRole('admin');
        
        try {
            // Check if category has products
            $hasProducts = Database::fetch(
                'SELECT COUNT(*) as count FROM products WHERE category_id = ? AND tenant_id = ?',
                [$id, $tenantId]
            );
            
            if ($hasProducts['count'] > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot delete category with products. Move products to another category first.']);
                return;
            }
            
            $deleted = Database::delete('categories', 'id = ? AND tenant_id = ?', [$id, $tenantId]);
            
            if ($deleted === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Category not found']);
                return;
            }
            
            echo json_encode(['message' => 'Category deleted successfully']);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete category']);
        }
    }

    private function validateCategory(array $input): array
    {
        $errors = [];
        
        if (empty($input['name'])) {
            $errors['name'] = 'Category name is required';
        }
        
        if (isset($input['color']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $input['color'])) {
            $errors['color'] = 'Color must be a valid hex color code';
        }
        
        return $errors;
    }
}
