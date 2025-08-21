<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Database;
use ArdentPOS\Middleware\TenantMiddleware;
use ArdentPOS\Middleware\AuthMiddleware;

class UserController
{
    public function index(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $query = "
            SELECT 
                id, name, email, role, status, 
                last_login_at, created_at
            FROM users 
            WHERE tenant_id = ?
        ";
        
        $params = [$tenantId];
        
        if (!empty($search)) {
            $query .= " AND (name ILIKE ? OR email ILIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($role)) {
            $query .= " AND role = ?";
            $params[] = $role;
        }
        
        if (!empty($status)) {
            $query .= " AND status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY name ASC";
        
        $users = Database::fetchAll($query, $params);
        
        echo json_encode($users);
    }

    public function store(): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireRole('admin');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $errors = $this->validateUser($input);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }
        
        try {
            // Check for duplicate email
            $existing = Database::fetch(
                'SELECT id FROM users WHERE email = ?',
                [$input['email']]
            );
            
            if ($existing) {
                http_response_code(400);
                echo json_encode(['error' => 'User with this email already exists']);
                return;
            }
            
            $userId = Database::insert('users', [
                'tenant_id' => $tenantId,
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => password_hash($input['password'], PASSWORD_DEFAULT),
                'role' => $input['role'],
                'status' => $input['status'] ?? 'active'
            ]);
            
            echo json_encode([
                'message' => 'User created successfully',
                'id' => $userId
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create user']);
        }
    }

    public function show(string $id): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        $user = Database::fetch(
            'SELECT id, name, email, role, status, last_login_at, created_at FROM users WHERE id = ? AND tenant_id = ?',
            [$id, $tenantId]
        );
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        echo json_encode($user);
    }

    public function update(string $id): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        $currentUserId = AuthMiddleware::getCurrentUserId();
        
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Users can update their own profile with limited fields
        $isOwnProfile = ($id === $currentUserId);
        
        if (!$isOwnProfile) {
            AuthMiddleware::requireRole('admin');
        }
        
        $errors = $this->validateUserUpdate($input, $isOwnProfile);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }
        
        try {
            // Check for duplicate email (excluding current user)
            if (!empty($input['email'])) {
                $existing = Database::fetch(
                    'SELECT id FROM users WHERE email = ? AND id != ?',
                    [$input['email'], $id]
                );
                
                if ($existing) {
                    http_response_code(400);
                    echo json_encode(['error' => 'User with this email already exists']);
                    return;
                }
            }
            
            $updateData = [
                'name' => $input['name']
            ];
            
            if (!empty($input['email'])) {
                $updateData['email'] = $input['email'];
            }
            
            if (!empty($input['password'])) {
                $updateData['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
            }
            
            // Only admins can update role and status
            if (!$isOwnProfile) {
                if (isset($input['role'])) {
                    $updateData['role'] = $input['role'];
                }
                if (isset($input['status'])) {
                    $updateData['status'] = $input['status'];
                }
            }
            
            $updated = Database::update('users', $updateData, 'id = ? AND tenant_id = ?', [$id, $tenantId]);
            
            if ($updated === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                return;
            }
            
            echo json_encode(['message' => 'User updated successfully']);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update user']);
        }
    }

    public function destroy(string $id): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        $currentUserId = AuthMiddleware::getCurrentUserId();
        
        AuthMiddleware::requireRole('admin');
        
        if ($id === $currentUserId) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete your own account']);
            return;
        }
        
        try {
            // Check if user has sales records
            $hasSales = Database::fetch(
                'SELECT COUNT(*) as count FROM sales WHERE cashier_id = ?',
                [$id]
            );
            
            if ($hasSales['count'] > 0) {
                // Deactivate instead of delete
                Database::update('users', [
                    'status' => 'inactive'
                ], 'id = ? AND tenant_id = ?', [$id, $tenantId]);
                
                echo json_encode(['message' => 'User deactivated successfully (has sales history)']);
                return;
            }
            
            $deleted = Database::delete('users', 'id = ? AND tenant_id = ?', [$id, $tenantId]);
            
            if ($deleted === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                return;
            }
            
            echo json_encode(['message' => 'User deleted successfully']);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete user']);
        }
    }

    public function changePassword(string $id): void
    {
        $tenantId = TenantMiddleware::getCurrentTenantId();
        $currentUserId = AuthMiddleware::getCurrentUserId();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Users can change their own password, admins can change any password
        if ($id !== $currentUserId) {
            AuthMiddleware::requireRole('admin');
        }
        
        $errors = [];
        
        if (empty($input['new_password']) || strlen($input['new_password']) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters';
        }
        
        if ($input['new_password'] !== $input['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
        
        // If changing own password, require current password
        if ($id === $currentUserId) {
            if (empty($input['current_password'])) {
                $errors['current_password'] = 'Current password is required';
            } else {
                $user = Database::fetch(
                    'SELECT password FROM users WHERE id = ? AND tenant_id = ?',
                    [$id, $tenantId]
                );
                
                if (!$user || !password_verify($input['current_password'], $user['password'])) {
                    $errors['current_password'] = 'Current password is incorrect';
                }
            }
        }
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }
        
        try {
            $updated = Database::update('users', [
                'password' => password_hash($input['new_password'], PASSWORD_DEFAULT)
            ], 'id = ? AND tenant_id = ?', [$id, $tenantId]);
            
            if ($updated === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                return;
            }
            
            echo json_encode(['message' => 'Password changed successfully']);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to change password']);
        }
    }

    private function validateUser(array $input): array
    {
        $errors = [];
        
        if (empty($input['name'])) {
            $errors['name'] = 'Name is required';
        }
        
        if (empty($input['email']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }
        
        if (empty($input['password']) || strlen($input['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }
        
        $validRoles = ['admin', 'manager', 'cashier', 'inventory_staff', 'viewer'];
        if (empty($input['role']) || !in_array($input['role'], $validRoles)) {
            $errors['role'] = 'Valid role is required';
        }
        
        return $errors;
    }

    private function validateUserUpdate(array $input, bool $isOwnProfile): array
    {
        $errors = [];
        
        if (empty($input['name'])) {
            $errors['name'] = 'Name is required';
        }
        
        if (!empty($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }
        
        if (!empty($input['password']) && strlen($input['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }
        
        if (!$isOwnProfile && !empty($input['role'])) {
            $validRoles = ['admin', 'manager', 'cashier', 'inventory_staff', 'viewer'];
            if (!in_array($input['role'], $validRoles)) {
                $errors['role'] = 'Valid role is required';
            }
        }
        
        return $errors;
    }
}
