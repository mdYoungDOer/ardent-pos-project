<?php

namespace ArdentPOS\Middleware;

use ArdentPOS\Core\Database;

class RoleMiddleware
{
    private static array $roleHierarchy = [
        'super_admin' => 100,
        'admin' => 80,
        'manager' => 60,
        'cashier' => 40,
        'inventory_staff' => 30,
        'viewer' => 10
    ];

    private static array $permissions = [
        // User management
        'users.view' => ['super_admin', 'admin', 'manager'],
        'users.create' => ['super_admin', 'admin'],
        'users.update' => ['super_admin', 'admin'],
        'users.delete' => ['super_admin', 'admin'],
        
        // Product management
        'products.view' => ['super_admin', 'admin', 'manager', 'cashier', 'inventory_staff', 'viewer'],
        'products.create' => ['super_admin', 'admin', 'manager'],
        'products.update' => ['super_admin', 'admin', 'manager'],
        'products.delete' => ['super_admin', 'admin'],
        'products.import' => ['super_admin', 'admin', 'manager'],
        
        // Category management
        'categories.view' => ['super_admin', 'admin', 'manager', 'cashier', 'inventory_staff', 'viewer'],
        'categories.create' => ['super_admin', 'admin', 'manager'],
        'categories.update' => ['super_admin', 'admin', 'manager'],
        'categories.delete' => ['super_admin', 'admin'],
        
        // Inventory management
        'inventory.view' => ['super_admin', 'admin', 'manager', 'inventory_staff', 'viewer'],
        'inventory.adjust' => ['super_admin', 'admin', 'manager', 'inventory_staff'],
        'inventory.reports' => ['super_admin', 'admin', 'manager', 'inventory_staff'],
        
        // Sales management
        'sales.view' => ['super_admin', 'admin', 'manager', 'cashier', 'viewer'],
        'sales.create' => ['super_admin', 'admin', 'manager', 'cashier'],
        'sales.update' => ['super_admin', 'admin', 'manager'],
        'sales.delete' => ['super_admin', 'admin'],
        'sales.refund' => ['super_admin', 'admin', 'manager'],
        
        // Customer management
        'customers.view' => ['super_admin', 'admin', 'manager', 'cashier', 'viewer'],
        'customers.create' => ['super_admin', 'admin', 'manager', 'cashier'],
        'customers.update' => ['super_admin', 'admin', 'manager', 'cashier'],
        'customers.delete' => ['super_admin', 'admin'],
        
        // Reports
        'reports.sales' => ['super_admin', 'admin', 'manager', 'viewer'],
        'reports.inventory' => ['super_admin', 'admin', 'manager', 'inventory_staff', 'viewer'],
        'reports.customers' => ['super_admin', 'admin', 'manager', 'viewer'],
        'reports.profit' => ['super_admin', 'admin'],
        'reports.export' => ['super_admin', 'admin', 'manager'],
        
        // Settings
        'settings.view' => ['super_admin', 'admin', 'manager'],
        'settings.update' => ['super_admin', 'admin'],
        
        // Subscriptions
        'subscription.view' => ['super_admin', 'admin'],
        'subscription.manage' => ['super_admin', 'admin'],
        
        // Notifications
        'notifications.view' => ['super_admin', 'admin', 'manager'],
        'notifications.send' => ['super_admin', 'admin', 'manager'],
        'notifications.settings' => ['super_admin', 'admin'],
        
        // Dashboard
        'dashboard.view' => ['super_admin', 'admin', 'manager', 'cashier', 'inventory_staff', 'viewer']
    ];

    public static function checkPermission(string $permission, string $userRole): bool
    {
        if (!isset(self::$permissions[$permission])) {
            return false;
        }

        return in_array($userRole, self::$permissions[$permission]);
    }

    public static function requirePermission(string $permission): void
    {
        $user = AuthMiddleware::getCurrentUser();
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }

        if (!self::checkPermission($permission, $user['role'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Insufficient permissions']);
            exit;
        }
    }

    public static function hasHigherRole(string $userRole, string $targetRole): bool
    {
        $userLevel = self::$roleHierarchy[$userRole] ?? 0;
        $targetLevel = self::$roleHierarchy[$targetRole] ?? 0;
        
        return $userLevel > $targetLevel;
    }

    public static function canManageUser(string $managerRole, string $targetRole): bool
    {
        // Super admin can manage anyone
        if ($managerRole === 'super_admin') {
            return true;
        }
        
        // Admin can manage everyone except super admin
        if ($managerRole === 'admin' && $targetRole !== 'super_admin') {
            return true;
        }
        
        // Manager can manage cashier, inventory_staff, and viewer
        if ($managerRole === 'manager' && in_array($targetRole, ['cashier', 'inventory_staff', 'viewer'])) {
            return true;
        }
        
        return false;
    }

    public static function getAccessibleRoles(string $userRole): array
    {
        switch ($userRole) {
            case 'super_admin':
                return ['super_admin', 'admin', 'manager', 'cashier', 'inventory_staff', 'viewer'];
            case 'admin':
                return ['admin', 'manager', 'cashier', 'inventory_staff', 'viewer'];
            case 'manager':
                return ['cashier', 'inventory_staff', 'viewer'];
            default:
                return [];
        }
    }

    public static function filterDataByRole(array $data, string $userRole, string $context = ''): array
    {
        switch ($context) {
            case 'sales':
                if (in_array($userRole, ['cashier'])) {
                    // Cashiers can only see their own sales
                    $userId = AuthMiddleware::getCurrentUserId();
                    return array_filter($data, function($sale) use ($userId) {
                        return $sale['cashier_id'] === $userId;
                    });
                }
                break;
                
            case 'reports':
                if (in_array($userRole, ['viewer'])) {
                    // Viewers get limited report data
                    foreach ($data as &$item) {
                        if (isset($item['profit'])) {
                            unset($item['profit']);
                        }
                        if (isset($item['cost'])) {
                            unset($item['cost']);
                        }
                    }
                }
                break;
        }
        
        return $data;
    }

    public static function getPermissions(string $role): array
    {
        $permissions = [];
        
        foreach (self::$permissions as $permission => $allowedRoles) {
            if (in_array($role, $allowedRoles)) {
                $permissions[] = $permission;
            }
        }
        
        return $permissions;
    }

    public static function validateRoleTransition(string $currentRole, string $newRole, string $managerRole): bool
    {
        // Can't change to a role you can't manage
        if (!self::canManageUser($managerRole, $newRole)) {
            return false;
        }
        
        // Can't demote someone with higher privileges than you
        if (!self::canManageUser($managerRole, $currentRole)) {
            return false;
        }
        
        return true;
    }
}
