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
}
