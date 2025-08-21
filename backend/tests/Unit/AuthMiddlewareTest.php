<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ArdentPOS\Middleware\AuthMiddleware;
use ArdentPOS\Core\Database;

class AuthMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock global variables for testing
        $_SERVER['HTTP_AUTHORIZATION'] = '';
        $_ENV['JWT_SECRET'] = 'test-secret';
    }

    public function testRequireAuthenticationWithoutToken()
    {
        $this->expectOutputString('{"error":"Authentication required"}');
        
        ob_start();
        AuthMiddleware::requireAuthentication();
        $output = ob_get_clean();
        
        $this->assertEquals('{"error":"Authentication required"}', $output);
    }

    public function testRequireRoleWithInsufficientPermissions()
    {
        // Mock a user with cashier role
        $mockUser = [
            'id' => '1',
            'role' => 'cashier',
            'tenant_id' => '1'
        ];
        
        // Use reflection to set the current user
        $reflection = new \ReflectionClass(AuthMiddleware::class);
        $property = $reflection->getProperty('currentUser');
        $property->setAccessible(true);
        $property->setValue(null, $mockUser);
        
        $this->expectOutputString('{"error":"Insufficient permissions"}');
        
        ob_start();
        AuthMiddleware::requireRole('admin');
        $output = ob_get_clean();
        
        $this->assertEquals('{"error":"Insufficient permissions"}', $output);
    }

    public function testRequireAnyRoleWithValidRole()
    {
        $mockUser = [
            'id' => '1',
            'role' => 'manager',
            'tenant_id' => '1'
        ];
        
        $reflection = new \ReflectionClass(AuthMiddleware::class);
        $property = $reflection->getProperty('currentUser');
        $property->setAccessible(true);
        $property->setValue(null, $mockUser);
        
        // This should not throw an exception or output error
        $this->expectOutputString('');
        
        ob_start();
        AuthMiddleware::requireAnyRole(['admin', 'manager']);
        $output = ob_get_clean();
        
        $this->assertEquals('', $output);
    }

    public function testGetCurrentUser()
    {
        $mockUser = [
            'id' => '1',
            'role' => 'admin',
            'tenant_id' => '1'
        ];
        
        $reflection = new \ReflectionClass(AuthMiddleware::class);
        $property = $reflection->getProperty('currentUser');
        $property->setAccessible(true);
        $property->setValue(null, $mockUser);
        
        $currentUser = AuthMiddleware::getCurrentUser();
        $this->assertEquals($mockUser, $currentUser);
    }

    public function testGetCurrentUserId()
    {
        $mockUser = [
            'id' => '123',
            'role' => 'admin',
            'tenant_id' => '1'
        ];
        
        $reflection = new \ReflectionClass(AuthMiddleware::class);
        $property = $reflection->getProperty('currentUser');
        $property->setAccessible(true);
        $property->setValue(null, $mockUser);
        
        $userId = AuthMiddleware::getCurrentUserId();
        $this->assertEquals('123', $userId);
    }

    protected function tearDown(): void
    {
        // Reset static properties
        $reflection = new \ReflectionClass(AuthMiddleware::class);
        $property = $reflection->getProperty('currentUser');
        $property->setAccessible(true);
        $property->setValue(null, null);
        
        parent::tearDown();
    }
}
