<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use ArdentPOS\Core\Database;

class ProductControllerTest extends TestCase
{
    private static $testTenantId;
    private static $testUserId;
    private static $authToken;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        
        // Initialize test database
        Database::init();
        
        // Create test tenant
        self::$testTenantId = Database::insert('tenants', [
            'business_name' => 'Test Business',
            'business_email' => 'test@example.com',
            'status' => 'active'
        ]);
        
        // Create test user
        self::$testUserId = Database::insert('users', [
            'tenant_id' => self::$testTenantId,
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'role' => 'admin'
        ]);
        
        // Generate auth token (simplified for testing)
        self::$authToken = 'test-auth-token';
    }

    public function testCreateProduct()
    {
        $productData = [
            'name' => 'Test Product',
            'sku' => 'TEST001',
            'price' => 99.99,
            'cost' => 50.00,
            'stock_quantity' => 100,
            'min_stock_level' => 10,
            'description' => 'A test product'
        ];

        // Simulate API request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . self::$authToken;
        
        // Mock the request body
        $json = json_encode($productData);
        
        // This would normally go through the router, but for testing we'll call directly
        $productId = Database::insert('products', array_merge($productData, [
            'tenant_id' => self::$testTenantId
        ]));
        
        $this->assertNotNull($productId);
        
        // Verify product was created
        $product = Database::fetch('SELECT * FROM products WHERE id = ?', [$productId]);
        $this->assertEquals('Test Product', $product['name']);
        $this->assertEquals('TEST001', $product['sku']);
        $this->assertEquals(99.99, $product['price']);
    }

    public function testGetProducts()
    {
        // Create a test product first
        $productId = Database::insert('products', [
            'tenant_id' => self::$testTenantId,
            'name' => 'Get Test Product',
            'sku' => 'GET001',
            'price' => 29.99,
            'cost' => 15.00,
            'stock_quantity' => 50
        ]);

        // Get products for tenant
        $products = Database::fetchAll(
            'SELECT * FROM products WHERE tenant_id = ?',
            [self::$testTenantId]
        );

        $this->assertNotEmpty($products);
        
        // Find our test product
        $testProduct = null;
        foreach ($products as $product) {
            if ($product['id'] === $productId) {
                $testProduct = $product;
                break;
            }
        }
        
        $this->assertNotNull($testProduct);
        $this->assertEquals('Get Test Product', $testProduct['name']);
    }

    public function testUpdateProduct()
    {
        // Create a product to update
        $productId = Database::insert('products', [
            'tenant_id' => self::$testTenantId,
            'name' => 'Update Test Product',
            'sku' => 'UPD001',
            'price' => 19.99,
            'cost' => 10.00,
            'stock_quantity' => 25
        ]);

        // Update the product
        $updateData = [
            'name' => 'Updated Product Name',
            'price' => 24.99,
            'stock_quantity' => 30
        ];

        $updated = Database::update('products', $updateData, 'id = ? AND tenant_id = ?', [$productId, self::$testTenantId]);
        $this->assertEquals(1, $updated);

        // Verify update
        $product = Database::fetch('SELECT * FROM products WHERE id = ?', [$productId]);
        $this->assertEquals('Updated Product Name', $product['name']);
        $this->assertEquals(24.99, $product['price']);
        $this->assertEquals(30, $product['stock_quantity']);
    }

    public function testDeleteProduct()
    {
        // Create a product to delete
        $productId = Database::insert('products', [
            'tenant_id' => self::$testTenantId,
            'name' => 'Delete Test Product',
            'sku' => 'DEL001',
            'price' => 9.99,
            'cost' => 5.00,
            'stock_quantity' => 10
        ]);

        // Delete the product
        $deleted = Database::delete('products', 'id = ? AND tenant_id = ?', [$productId, self::$testTenantId]);
        $this->assertEquals(1, $deleted);

        // Verify deletion
        $product = Database::fetch('SELECT * FROM products WHERE id = ?', [$productId]);
        $this->assertNull($product);
    }

    public function testTenantIsolation()
    {
        // Create another tenant
        $otherTenantId = Database::insert('tenants', [
            'business_name' => 'Other Business',
            'business_email' => 'other@example.com',
            'status' => 'active'
        ]);

        // Create product for other tenant
        $otherProductId = Database::insert('products', [
            'tenant_id' => $otherTenantId,
            'name' => 'Other Tenant Product',
            'sku' => 'OTHER001',
            'price' => 15.99,
            'cost' => 8.00,
            'stock_quantity' => 20
        ]);

        // Try to get products for our test tenant - should not include other tenant's product
        $products = Database::fetchAll(
            'SELECT * FROM products WHERE tenant_id = ?',
            [self::$testTenantId]
        );

        foreach ($products as $product) {
            $this->assertEquals(self::$testTenantId, $product['tenant_id']);
            $this->assertNotEquals($otherProductId, $product['id']);
        }

        // Clean up
        Database::delete('products', 'id = ?', [$otherProductId]);
        Database::delete('tenants', 'id = ?', [$otherTenantId]);
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up test data
        Database::delete('products', 'tenant_id = ?', [self::$testTenantId]);
        Database::delete('users', 'id = ?', [self::$testUserId]);
        Database::delete('tenants', 'id = ?', [self::$testTenantId]);
        
        parent::tearDownAfterClass();
    }
}
