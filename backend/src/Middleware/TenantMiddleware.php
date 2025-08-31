<?php

namespace ArdentPOS\Middleware;

use ArdentPOS\Core\Database;

class TenantMiddleware
{
    public static function handle(): void
    {
        $currentUser = $GLOBALS['current_user'] ?? null;
        
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized', 'message' => 'User not authenticated']);
            exit;
        }

        // Super Admins bypass tenant checks - they are not tenants
        if ($currentUser['role'] === 'super_admin') {
            $GLOBALS['current_tenant'] = null;
            $GLOBALS['current_tenant_id'] = null;
            $GLOBALS['is_super_admin'] = true;
            $GLOBALS['tenant_scoped'] = false;
            return;
        }

        // Regular users must have a valid tenant
        if (empty($currentUser['tenant_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden', 'message' => 'User must belong to a tenant']);
            exit;
        }

        // Verify tenant exists and is active
        $tenant = Database::fetch(
            'SELECT * FROM tenants WHERE id = ? AND status = ?',
            [$currentUser['tenant_id'], 'active']
        );

        if (!$tenant) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden', 'message' => 'Tenant not found or inactive']);
            exit;
        }

        // Store tenant data in globals
        $GLOBALS['current_tenant'] = $tenant;
        $GLOBALS['current_tenant_id'] = $currentUser['tenant_id'];
        $GLOBALS['is_super_admin'] = false;
        
        // Add tenant_id to all database queries automatically
        self::addTenantScope();
    }

    private static function addTenantScope(): void
    {
        // This would be implemented with a query builder or ORM
        // For now, we'll rely on controllers to add tenant_id to queries
        $GLOBALS['tenant_scoped'] = true;
    }

    public static function getCurrentTenantId(): ?string
    {
        return $GLOBALS['current_tenant_id'] ?? null;
    }

    public static function getCurrentTenant(): ?array
    {
        return $GLOBALS['current_tenant'] ?? null;
    }

    public static function isSuperAdmin(): bool
    {
        return $GLOBALS['is_super_admin'] ?? false;
    }

    public static function requireTenant(): void
    {
        if (self::isSuperAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden', 'message' => 'This endpoint requires a tenant context']);
            exit;
        }
    }

    public static function requireSuperAdmin(): void
    {
        if (!self::isSuperAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden', 'message' => 'Super admin access required']);
            exit;
        }
    }
}
