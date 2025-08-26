<?php
require_once __DIR__ . '/../vendor/autoload.php';

use ArdentPOS\Core\Config;
use ArdentPOS\Core\Database;
use ArdentPOS\Middleware\AuthMiddleware;
use ArdentPOS\Middleware\TenantMiddleware;

// Initialize configuration and database
Config::init();
Database::init();

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Get tenant ID from JWT token
    $tenantId = null;
    
    // Check for Authorization header
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
        
        try {
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key(Config::get('jwt.secret'), 'HS256'));
            
            // Verify user exists and is active
            $user = Database::fetch(
                'SELECT * FROM users WHERE id = ? AND status = ?',
                [$decoded->user_id, 'active']
            );

            if ($user) {
                $tenantId = $user['tenant_id'];
            }
        } catch (Exception $e) {
            // Token is invalid, but we'll continue for public endpoints
        }
    }
    
    if (!$tenantId) {
        throw new Exception('Unauthorized - Valid tenant required');
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    
    // Extract category ID if present
    $categoryId = null;
    if (count($pathParts) > 2 && $pathParts[2] !== '') {
        $categoryId = $pathParts[2];
    }

    switch ($method) {
        case 'GET':
            if ($categoryId) {
                // Get specific category
                $category = Database::fetch(
                    'SELECT * FROM categories WHERE id = ? AND tenant_id = ?',
                    [$categoryId, $tenantId]
                );
                
                if (!$category) {
                    throw new Exception('Category not found');
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $category
                ]);
            } else {
                // Get all categories
                $categories = Database::fetchAll(
                    'SELECT * FROM categories WHERE tenant_id = ? ORDER BY name ASC',
                    [$tenantId]
                );
                
                echo json_encode([
                    'success' => true,
                    'data' => $categories
                ]);
            }
            break;

        case 'POST':
            // Create new category
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data) {
                throw new Exception('Invalid JSON data');
            }

            // Validate required fields
            if (empty($data['name'])) {
                throw new Exception('Category name is required');
            }

            // Check for duplicate name
            $existing = Database::fetch(
                'SELECT id FROM categories WHERE tenant_id = ? AND name = ?',
                [$tenantId, trim($data['name'])]
            );
            
            if ($existing) {
                throw new Exception('Category name already exists');
            }

            // Create category
            $categoryId = uniqid('cat_', true);
            $stmt = Database::getConnection()->prepare("
                INSERT INTO categories (id, tenant_id, name, description, color, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $categoryId,
                $tenantId,
                trim($data['name']),
                $data['description'] ?? null,
                $data['color'] ?? '#e41e5b'
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => [
                    'id' => $categoryId,
                    'name' => trim($data['name']),
                    'description' => $data['description'] ?? null,
                    'color' => $data['color'] ?? '#e41e5b'
                ]
            ]);
            break;

        case 'PUT':
            if (!$categoryId) {
                throw new Exception('Category ID required for update');
            }

            // Update category
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data) {
                throw new Exception('Invalid JSON data');
            }

            // Validate required fields
            if (empty($data['name'])) {
                throw new Exception('Category name is required');
            }

            // Check if category exists
            $existing = Database::fetch(
                'SELECT id FROM categories WHERE id = ? AND tenant_id = ?',
                [$categoryId, $tenantId]
            );
            
            if (!$existing) {
                throw new Exception('Category not found');
            }

            // Check for duplicate name (excluding current category)
            $duplicate = Database::fetch(
                'SELECT id FROM categories WHERE tenant_id = ? AND name = ? AND id != ?',
                [$tenantId, trim($data['name']), $categoryId]
            );
            
            if ($duplicate) {
                throw new Exception('Category name already exists');
            }

            // Update category
            $stmt = Database::getConnection()->prepare("
                UPDATE categories 
                SET name = ?, description = ?, color = ?, updated_at = NOW()
                WHERE id = ? AND tenant_id = ?
            ");
            $stmt->execute([
                trim($data['name']),
                $data['description'] ?? null,
                $data['color'] ?? '#e41e5b',
                $categoryId,
                $tenantId
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Category updated successfully'
            ]);
            break;

        case 'DELETE':
            if (!$categoryId) {
                throw new Exception('Category ID required for deletion');
            }

            // Check if category exists
            $existing = Database::fetch(
                'SELECT id FROM categories WHERE id = ? AND tenant_id = ?',
                [$categoryId, $tenantId]
            );
            
            if (!$existing) {
                throw new Exception('Category not found');
            }

            // Check if category has products
            $hasProducts = Database::fetch(
                'SELECT COUNT(*) as count FROM products WHERE category_id = ? AND tenant_id = ?',
                [$categoryId, $tenantId]
            );
            
            if ($hasProducts['count'] > 0) {
                throw new Exception('Cannot delete category with products. Move products to another category first.');
            }

            // Delete category
            $stmt = Database::getConnection()->prepare("
                DELETE FROM categories WHERE id = ? AND tenant_id = ?
            ");
            $stmt->execute([$categoryId, $tenantId]);

            echo json_encode([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
            break;

        default:
            throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
