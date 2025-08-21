<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ArdentPOS\Middleware\RoleMiddleware;

class RoleMiddlewareTest extends TestCase
{
    public function testCheckPermissionWithValidRole()
    {
        $this->assertTrue(RoleMiddleware::checkPermission('products.view', 'admin'));
        $this->assertTrue(RoleMiddleware::checkPermission('products.create', 'manager'));
        $this->assertTrue(RoleMiddleware::checkPermission('sales.create', 'cashier'));
    }

    public function testCheckPermissionWithInvalidRole()
    {
        $this->assertFalse(RoleMiddleware::checkPermission('products.delete', 'cashier'));
        $this->assertFalse(RoleMiddleware::checkPermission('users.create', 'viewer'));
        $this->assertFalse(RoleMiddleware::checkPermission('reports.profit', 'manager'));
    }

    public function testCheckPermissionWithNonExistentPermission()
    {
        $this->assertFalse(RoleMiddleware::checkPermission('nonexistent.permission', 'admin'));
    }

    public function testHasHigherRole()
    {
        $this->assertTrue(RoleMiddleware::hasHigherRole('admin', 'manager'));
        $this->assertTrue(RoleMiddleware::hasHigherRole('manager', 'cashier'));
        $this->assertTrue(RoleMiddleware::hasHigherRole('super_admin', 'admin'));
        
        $this->assertFalse(RoleMiddleware::hasHigherRole('cashier', 'manager'));
        $this->assertFalse(RoleMiddleware::hasHigherRole('viewer', 'admin'));
    }

    public function testCanManageUser()
    {
        // Super admin can manage anyone
        $this->assertTrue(RoleMiddleware::canManageUser('super_admin', 'admin'));
        $this->assertTrue(RoleMiddleware::canManageUser('super_admin', 'viewer'));
        
        // Admin can manage everyone except super admin
        $this->assertTrue(RoleMiddleware::canManageUser('admin', 'manager'));
        $this->assertTrue(RoleMiddleware::canManageUser('admin', 'cashier'));
        $this->assertFalse(RoleMiddleware::canManageUser('admin', 'super_admin'));
        
        // Manager can manage lower roles
        $this->assertTrue(RoleMiddleware::canManageUser('manager', 'cashier'));
        $this->assertTrue(RoleMiddleware::canManageUser('manager', 'viewer'));
        $this->assertFalse(RoleMiddleware::canManageUser('manager', 'admin'));
        
        // Cashier cannot manage anyone
        $this->assertFalse(RoleMiddleware::canManageUser('cashier', 'viewer'));
    }

    public function testGetAccessibleRoles()
    {
        $superAdminRoles = RoleMiddleware::getAccessibleRoles('super_admin');
        $this->assertContains('admin', $superAdminRoles);
        $this->assertContains('viewer', $superAdminRoles);
        
        $adminRoles = RoleMiddleware::getAccessibleRoles('admin');
        $this->assertContains('manager', $adminRoles);
        $this->assertNotContains('super_admin', $adminRoles);
        
        $managerRoles = RoleMiddleware::getAccessibleRoles('manager');
        $this->assertContains('cashier', $managerRoles);
        $this->assertNotContains('admin', $managerRoles);
        
        $cashierRoles = RoleMiddleware::getAccessibleRoles('cashier');
        $this->assertEmpty($cashierRoles);
    }

    public function testGetPermissions()
    {
        $adminPermissions = RoleMiddleware::getPermissions('admin');
        $this->assertContains('users.create', $adminPermissions);
        $this->assertContains('products.delete', $adminPermissions);
        $this->assertContains('reports.profit', $adminPermissions);
        
        $cashierPermissions = RoleMiddleware::getPermissions('cashier');
        $this->assertContains('sales.create', $cashierPermissions);
        $this->assertNotContains('users.create', $cashierPermissions);
        $this->assertNotContains('products.delete', $cashierPermissions);
        
        $viewerPermissions = RoleMiddleware::getPermissions('viewer');
        $this->assertContains('products.view', $viewerPermissions);
        $this->assertNotContains('products.create', $viewerPermissions);
    }

    public function testValidateRoleTransition()
    {
        // Admin promoting cashier to manager
        $this->assertTrue(RoleMiddleware::validateRoleTransition('cashier', 'manager', 'admin'));
        
        // Manager trying to promote cashier to admin (should fail)
        $this->assertFalse(RoleMiddleware::validateRoleTransition('cashier', 'admin', 'manager'));
        
        // Admin trying to demote super admin (should fail)
        $this->assertFalse(RoleMiddleware::validateRoleTransition('super_admin', 'admin', 'admin'));
        
        // Super admin can do anything
        $this->assertTrue(RoleMiddleware::validateRoleTransition('admin', 'manager', 'super_admin'));
    }

    public function testFilterDataByRole()
    {
        $salesData = [
            ['id' => 1, 'cashier_id' => 'user1', 'total' => 100],
            ['id' => 2, 'cashier_id' => 'user2', 'total' => 200],
            ['id' => 3, 'cashier_id' => 'user1', 'total' => 150]
        ];
        
        // Mock current user ID
        $reflection = new \ReflectionClass('ArdentPOS\Middleware\AuthMiddleware');
        if ($reflection->hasProperty('currentUser')) {
            $property = $reflection->getProperty('currentUser');
            $property->setAccessible(true);
            $property->setValue(null, ['id' => 'user1']);
        }
        
        $filteredData = RoleMiddleware::filterDataByRole($salesData, 'cashier', 'sales');
        
        // Should only return sales for user1
        $this->assertCount(2, $filteredData);
        foreach ($filteredData as $sale) {
            $this->assertEquals('user1', $sale['cashier_id']);
        }
        
        // Admin should see all data
        $adminData = RoleMiddleware::filterDataByRole($salesData, 'admin', 'sales');
        $this->assertCount(3, $adminData);
    }
}
