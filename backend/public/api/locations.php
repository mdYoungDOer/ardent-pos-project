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
    
    // Extract location ID if present
    $locationId = null;
    if (count($pathParts) > 2 && $pathParts[2] !== '') {
        $locationId = $pathParts[2];
    }

    switch ($method) {
        case 'GET':
            if ($locationId) {
                // Get specific location with users
                $location = Database::fetch(
                    'SELECT l.*, u.name as manager_name 
                     FROM locations l 
                     LEFT JOIN users u ON l.manager_id = u.id 
                     WHERE l.id = ? AND l.tenant_id = ?',
                    [$locationId, $tenantId]
                );
                
                if (!$location) {
                    throw new Exception('Location not found');
                }
                
                // Get users assigned to this location
                $users = Database::fetchAll(
                    'SELECT u.id, u.name, u.email, u.role, lu.role as location_role, lu.permissions
                     FROM location_users lu
                     JOIN users u ON lu.user_id = u.id
                     WHERE lu.location_id = ?',
                    [$locationId]
                );
                
                $location['users'] = $users;
                
                echo json_encode([
                    'success' => true,
                    'data' => $location
                ]);
            } else {
                // Get all locations
                $locations = Database::fetchAll(
                    'SELECT l.*, u.name as manager_name,
                            (SELECT COUNT(*) FROM location_users lu WHERE lu.location_id = l.id) as user_count,
                            (SELECT COUNT(*) FROM sales s WHERE s.location_id = l.id) as sales_count
                     FROM locations l 
                     LEFT JOIN users u ON l.manager_id = u.id 
                     WHERE l.tenant_id = ? 
                     ORDER BY l.name ASC',
                    [$tenantId]
                );
                
                echo json_encode([
                    'success' => true,
                    'data' => $locations
                ]);
            }
            break;

        case 'POST':
            // Create new location
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data) {
                throw new Exception('Invalid JSON data');
            }

            // Validate required fields
            if (empty($data['name'])) {
                throw new Exception('Location name is required');
            }

            // Check for duplicate name
            $existing = Database::fetch(
                'SELECT id FROM locations WHERE tenant_id = ? AND name = ?',
                [$tenantId, trim($data['name'])]
            );
            
            if ($existing) {
                throw new Exception('Location name already exists');
            }

            // Create location
            $locationId = uniqid('loc_', true);
            $stmt = Database::getConnection()->prepare("
                INSERT INTO locations (
                    id, tenant_id, name, type, address, city, state, postal_code, 
                    country, phone, email, manager_id, timezone, currency, tax_rate, 
                    status, settings, created_at, updated_at
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $locationId,
                $tenantId,
                trim($data['name']),
                $data['type'] ?? 'store',
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['postal_code'] ?? null,
                $data['country'] ?? 'Ghana',
                $data['phone'] ?? null,
                $data['email'] ?? null,
                $data['manager_id'] ?? null,
                $data['timezone'] ?? 'Africa/Accra',
                $data['currency'] ?? 'GHS',
                $data['tax_rate'] ?? 15.00,
                $data['status'] ?? 'active',
                json_encode($data['settings'] ?? [])
            ]);

            // Assign users if provided
            if (!empty($data['users'])) {
                foreach ($data['users'] as $userData) {
                    $stmt = Database::getConnection()->prepare("
                        INSERT INTO location_users (id, location_id, user_id, role, permissions, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                        ON CONFLICT (location_id, user_id) DO UPDATE SET
                        role = EXCLUDED.role,
                        permissions = EXCLUDED.permissions
                    ");
                    $stmt->execute([
                        uniqid('lu_', true),
                        $locationId,
                        $userData['user_id'],
                        $userData['role'] ?? 'staff',
                        json_encode($userData['permissions'] ?? [])
                    ]);
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Location created successfully',
                'data' => [
                    'id' => $locationId,
                    'name' => trim($data['name'])
                ]
            ]);
            break;

        case 'PUT':
            if (!$locationId) {
                throw new Exception('Location ID required for update');
            }

            // Update location
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data) {
                throw new Exception('Invalid JSON data');
            }

            // Validate required fields
            if (empty($data['name'])) {
                throw new Exception('Location name is required');
            }

            // Check if location exists
            $existing = Database::fetch(
                'SELECT id FROM locations WHERE id = ? AND tenant_id = ?',
                [$locationId, $tenantId]
            );
            
            if (!$existing) {
                throw new Exception('Location not found');
            }

            // Check for duplicate name (excluding current location)
            $duplicate = Database::fetch(
                'SELECT id FROM locations WHERE tenant_id = ? AND name = ? AND id != ?',
                [$tenantId, trim($data['name']), $locationId]
            );
            
            if ($duplicate) {
                throw new Exception('Location name already exists');
            }

            // Update location
            $stmt = Database::getConnection()->prepare("
                UPDATE locations 
                SET name = ?, type = ?, address = ?, city = ?, state = ?, postal_code = ?,
                    country = ?, phone = ?, email = ?, manager_id = ?, timezone = ?,
                    currency = ?, tax_rate = ?, status = ?, settings = ?, updated_at = NOW()
                WHERE id = ? AND tenant_id = ?
            ");
            $stmt->execute([
                trim($data['name']),
                $data['type'] ?? 'store',
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['postal_code'] ?? null,
                $data['country'] ?? 'Ghana',
                $data['phone'] ?? null,
                $data['email'] ?? null,
                $data['manager_id'] ?? null,
                $data['timezone'] ?? 'Africa/Accra',
                $data['currency'] ?? 'GHS',
                $data['tax_rate'] ?? 15.00,
                $data['status'] ?? 'active',
                json_encode($data['settings'] ?? []),
                $locationId,
                $tenantId
            ]);

            // Update user assignments if provided
            if (isset($data['users'])) {
                // Remove existing assignments
                $stmt = Database::getConnection()->prepare("
                    DELETE FROM location_users WHERE location_id = ?
                ");
                $stmt->execute([$locationId]);

                // Add new assignments
                foreach ($data['users'] as $userData) {
                    $stmt = Database::getConnection()->prepare("
                        INSERT INTO location_users (id, location_id, user_id, role, permissions, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        uniqid('lu_', true),
                        $locationId,
                        $userData['user_id'],
                        $userData['role'] ?? 'staff',
                        json_encode($userData['permissions'] ?? [])
                    ]);
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Location updated successfully'
            ]);
            break;

        case 'DELETE':
            if (!$locationId) {
                throw new Exception('Location ID required for deletion');
            }

            // Check if location exists
            $existing = Database::fetch(
                'SELECT id FROM locations WHERE id = ? AND tenant_id = ?',
                [$locationId, $tenantId]
            );
            
            if (!$existing) {
                throw new Exception('Location not found');
            }

            // Check if location has sales
            $hasSales = Database::fetch(
                'SELECT COUNT(*) as count FROM sales WHERE location_id = ?',
                [$locationId]
            );
            
            if ($hasSales['count'] > 0) {
                throw new Exception('Cannot delete location with sales history. Consider deactivating instead.');
            }

            // Delete location (cascade will handle related records)
            $stmt = Database::getConnection()->prepare("
                DELETE FROM locations WHERE id = ? AND tenant_id = ?
            ");
            $stmt->execute([$locationId, $tenantId]);

            echo json_encode([
                'success' => true,
                'message' => 'Location deleted successfully'
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
