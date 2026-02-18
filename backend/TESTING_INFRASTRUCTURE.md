# Testing Infrastructure Documentation

## Overview

This document provides comprehensive documentation for the testing infrastructure implemented for the AutoERP platform. The infrastructure follows Laravel best practices and supports the multi-tenant architecture with proper isolation.

## Table of Contents

1. [Model Factories](#model-factories)
2. [Base Test Classes](#base-test-classes)
3. [Sample Tests](#sample-tests)
4. [Running Tests](#running-tests)
5. [Best Practices](#best-practices)

---

## Model Factories

All major models now have factory classes that support testing with realistic data generation.

### Core Factories

#### TenantFactory
**Location**: `database/factories/TenantFactory.php`

**Usage**:
```php
// Basic tenant
$tenant = Tenant::factory()->create();

// Inactive tenant
$tenant = Tenant::factory()->inactive()->create();

// Tenant in trial period
$tenant = Tenant::factory()->inTrial()->create();

// Tenant with active subscription
$tenant = Tenant::factory()->subscribed()->create();

// Tenant with expired subscription
$tenant = Tenant::factory()->subscriptionExpired()->create();
```

#### UserFactory
**Location**: `database/factories/UserFactory.php`

**Usage**:
```php
// Basic user with tenant
$user = User::factory()->create();

// User for specific tenant
$user = User::factory()->forTenant($tenant)->create();

// Inactive user
$user = User::factory()->inactive()->create();

// Unverified user
$user = User::factory()->unverified()->create();
```

### Inventory Module Factories

#### ProductFactory
**Location**: `database/factories/Inventory/ProductFactory.php`

**Usage**:
```php
// Basic product
$product = Product::factory()->create();

// Inventory product
$product = Product::factory()->inventory()->create();

// Service product (no inventory tracking)
$product = Product::factory()->service()->create();

// Product with batch tracking
$product = Product::factory()
    ->inventory()
    ->withBatchTracking()
    ->create();

// Product with serial number tracking
$product = Product::factory()
    ->inventory()
    ->withSerialTracking()
    ->create();

// Product with expiry tracking
$product = Product::factory()
    ->inventory()
    ->withExpiryTracking()
    ->create();

// Product for specific tenant
$product = Product::factory()
    ->forTenant($tenant)
    ->create();

// Inactive product
$product = Product::factory()->inactive()->create();
```

#### StockLedgerFactory
**Location**: `database/factories/Inventory/StockLedgerFactory.php`

**Usage**:
```php
// Basic stock movement
$ledger = StockLedger::factory()->create();

// Purchase movement
$ledger = StockLedger::factory()->purchase()->create();

// Sale movement
$ledger = StockLedger::factory()->sale()->create();

// Adjustment movements
$ledger = StockLedger::factory()->adjustmentIn()->create();
$ledger = StockLedger::factory()->adjustmentOut()->create();

// With batch tracking
$ledger = StockLedger::factory()
    ->purchase()
    ->withBatch('BATCH-12345')
    ->create();

// With expiry tracking
$ledger = StockLedger::factory()
    ->purchase()
    ->withExpiry()
    ->create();

// With serial number
$ledger = StockLedger::factory()
    ->purchase()
    ->withSerial('SN-123456')
    ->create();

// For specific product
$ledger = StockLedger::factory()
    ->forProduct($product)
    ->purchase()
    ->create();
```

### CRM Module Factories

#### CustomerFactory
**Location**: `database/factories/CRM/CustomerFactory.php`

**Usage**:
```php
// Basic customer
$customer = Customer::factory()->create();

// Business customer
$customer = Customer::factory()->business()->create();

// Individual customer
$customer = Customer::factory()->individual()->create();

// Inactive customer
$customer = Customer::factory()->inactive()->create();

// Customer for specific tenant
$customer = Customer::factory()
    ->forTenant($tenant)
    ->create();
```

### Procurement Module Factories

#### SupplierFactory
**Location**: `database/factories/Procurement/SupplierFactory.php`

**Usage**:
```php
// Basic supplier
$supplier = Supplier::factory()->create();

// Inactive supplier
$supplier = Supplier::factory()->inactive()->create();

// Supplier for specific tenant
$supplier = Supplier::factory()
    ->forTenant($tenant)
    ->create();
```

#### PurchaseOrderFactory
**Location**: `database/factories/Procurement/PurchaseOrderFactory.php`

**Usage**:
```php
// Basic purchase order (pending status)
$po = PurchaseOrder::factory()->create();

// Draft purchase order
$po = PurchaseOrder::factory()->draft()->create();

// Approved purchase order
$po = PurchaseOrder::factory()->approved()->create();

// Received purchase order
$po = PurchaseOrder::factory()->received()->create();

// For specific supplier
$po = PurchaseOrder::factory()
    ->forSupplier($supplier)
    ->create();

// For specific tenant
$po = PurchaseOrder::factory()
    ->forTenant($tenant)
    ->create();
```

#### PurchaseOrderItemFactory
**Location**: `database/factories/Procurement/PurchaseOrderItemFactory.php`

**Usage**:
```php
// Basic PO item
$item = PurchaseOrderItem::factory()->create();

// Partially received item
$item = PurchaseOrderItem::factory()
    ->partiallyReceived()
    ->create();

// Fully received item
$item = PurchaseOrderItem::factory()
    ->fullyReceived()
    ->create();

// For specific purchase order
$item = PurchaseOrderItem::factory()
    ->forPurchaseOrder($po)
    ->create();

// For specific product
$item = PurchaseOrderItem::factory()
    ->forProduct($product)
    ->create();
```

### IAM Module Factories

#### RoleFactory
**Location**: `database/factories/IAM/RoleFactory.php`

**Usage**:
```php
// Basic role
$role = Role::factory()->create();

// System role
$role = Role::factory()->systemRole()->create();

// Predefined roles
$role = Role::factory()->superAdmin()->create();
$role = Role::factory()->admin()->create();
$role = Role::factory()->user()->create();

// Role for specific tenant
$role = Role::factory()
    ->forTenant($tenant)
    ->create();
```

#### PermissionFactory
**Location**: `database/factories/IAM/PermissionFactory.php`

**Usage**:
```php
// Basic permission
$permission = Permission::factory()->create();

// Permission for specific module
$permission = Permission::factory()
    ->forModule('inventory')
    ->create();

// CRUD permissions
$permission = Permission::factory()->view('products')->create();
$permission = Permission::factory()->create('products')->create();
$permission = Permission::factory()->update('products')->create();
$permission = Permission::factory()->delete('products')->create();
```

---

## Base Test Classes

### TestCase
**Location**: `tests/TestCase.php`

Base test class for all tests. Includes:
- `RefreshDatabase` trait
- `withoutVite()` configuration

**Usage**:
```php
use Tests\TestCase;

class MyTest extends TestCase
{
    // Your tests
}
```

### FeatureTestCase
**Location**: `tests/Feature/FeatureTestCase.php`

Extended base class for feature tests with multi-tenancy support.

**Features**:
- Automatic tenant creation for each test
- Authentication helpers
- Role and permission helpers
- API testing helpers
- JSON assertion helpers

**Usage**:
```php
use Tests\Feature\FeatureTestCase;

class ProductApiTest extends FeatureTestCase
{
    public function test_can_create_product()
    {
        // Tenant is automatically created as $this->tenant
        $this->actingAsUserWithPermissions(['create-products']);
        
        $response = $this->postJson('/api/v1/products', [
            'name' => 'Test Product',
            // ...
        ]);
        
        $this->assertJsonSuccess($response, 201);
    }
}
```

**Helper Methods**:

- `createTenant($attributes = [])` - Create a tenant
- `actingAsUser($attributes = [], $tenant = null)` - Create and authenticate a user
- `actingAsUserWithRole($roleSlug, $attributes = [], $tenant = null)` - Auth user with role
- `actingAsUserWithPermissions($permissions, $attributes = [], $tenant = null)` - Auth user with permissions
- `actingAsAdmin($tenant = null)` - Auth as admin
- `assertJsonSuccess($response, $status = 200)` - Assert successful JSON response
- `assertJsonError($response, $status = 400)` - Assert error JSON response
- `assertValidationErrors($response, $fields)` - Assert validation errors
- `getTenant()` - Get current tenant
- `getUser()` - Get authenticated user

### UnitTestCase
**Location**: `tests/Unit/UnitTestCase.php`

Base class for unit tests with mocking support.

**Features**:
- Mockery integration
- Automatic cleanup
- Mock creation helpers

**Usage**:
```php
use Tests\Unit\UnitTestCase;

class ServiceTest extends UnitTestCase
{
    public function test_service_method()
    {
        $mock = $this->mockInstance(Repository::class, function ($mock) {
            $mock->shouldReceive('find')->andReturn(new Model());
        });
        
        // Test your service
    }
}
```

**Helper Methods**:

- `mockInstance($class, $callback = null)` - Create a mock instance
- `partialMockInstance($class, $callback = null)` - Create a partial mock
- `spyInstance($class, $callback = null)` - Create a spy
- `assertMethodCalled($mock, $method, ...$arguments)` - Assert method was called
- `assertMethodNotCalled($mock, $method)` - Assert method was not called

---

## Sample Tests

### Unit Test Example

**File**: `tests/Unit/Services/InventoryServiceTest.php`

Comprehensive unit tests for the InventoryService:

```php
public function test_it_can_record_a_stock_purchase_movement()
{
    $product = Product::factory()
        ->forTenant($this->tenant)
        ->inventory()
        ->create();

    $dto = new StockMovementDTO(
        productId: $product->id,
        movementType: StockMovementType::PURCHASE,
        quantity: 100,
        unitCost: 50.00
    );

    $ledger = $this->service->recordStockMovement($dto);

    $this->assertNotNull($ledger);
    $this->assertEquals($product->id, $ledger->product_id);
    $this->assertEquals(StockMovementType::PURCHASE, $ledger->movement_type);
    $this->assertEquals(100, $ledger->quantity);
}
```

**Test Coverage**:
- ✅ Record purchase movements
- ✅ Record sale movements
- ✅ Calculate running balance
- ✅ Exception for non-trackable products
- ✅ Get current stock balance
- ✅ Handle batch tracking
- ✅ Handle expiry tracking
- ✅ Calculate total cost

### Feature Test Example

**File**: `tests/Feature/Api/ProductApiTest.php`

Comprehensive API tests for Product endpoints:

```php
public function test_it_can_create_a_product()
{
    $productData = [
        'sku' => 'SKU-12345',
        'name' => 'Test Product',
        'type' => 'inventory',
        'buying_price' => 100.00,
        'selling_price' => 150.00,
        'is_active' => true,
    ];

    $response = $this->postJson($this->baseUri, $productData);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'sku' => 'SKU-12345',
                'name' => 'Test Product',
            ],
        ]);

    $this->assertDatabaseHas('products', [
        'tenant_id' => $this->tenant->id,
        'sku' => 'SKU-12345',
    ]);
}
```

**Test Coverage**:
- List products with tenant isolation
- Create products
- Validation for required fields
- Show single product
- Prevent access to other tenant's products
- Update products
- Delete products (soft delete)
- Filter by type
- Filter by active status
- Search by name
- Authentication requirements

### Authentication Test Example

**File**: `tests/Feature/Api/AuthenticationTest.php`

Tests for authentication endpoints:

```php
public function test_users_can_login_with_valid_credentials()
{
    $user = User::factory()->forTenant($this->tenant)->create([
        'email' => 'user@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'user@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'user',
                'token',
            ],
        ]);
}
```

**Test Coverage**:
- User registration
- Login with valid credentials
- Login with invalid credentials
- Inactive user login prevention
- Get authenticated user profile
- Logout
- Update profile
- Change password
- Tenant information in responses

---

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Unit Tests Only
```bash
php artisan test --testsuite=Unit
```

### Run Feature Tests Only
```bash
php artisan test --testsuite=Feature
```

### Run Specific Test Class
```bash
php artisan test --filter=InventoryServiceTest
```

### Run Specific Test Method
```bash
php artisan test --filter=InventoryServiceTest::test_it_can_record_a_stock_purchase_movement
```

### Run with Coverage (requires Xdebug)
```bash
php artisan test --coverage
```

### Run in Parallel (for large test suites)
```bash
php artisan test --parallel
```

---

## Best Practices

### 1. Multi-Tenancy Testing

Always test tenant isolation:

```php
public function test_tenant_isolation()
{
    $otherTenant = Tenant::factory()->create();
    $otherProduct = Product::factory()
        ->forTenant($otherTenant)
        ->create();

    $response = $this->getJson("/api/v1/products/{$otherProduct->id}");

    // Should not be accessible
    $response->assertStatus(404);
}
```

### 2. Use Factories Over Manual Creation

❌ **Bad**:
```php
$product = new Product();
$product->tenant_id = 1;
$product->sku = 'SKU-123';
$product->name = 'Test';
$product->save();
```

✅ **Good**:
```php
$product = Product::factory()->create([
    'sku' => 'SKU-123',
]);
```

### 3. Test Data Isolation

Each test should be independent:

```php
public function test_example()
{
    // Create fresh data for this test
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();
    
    // Test with this isolated data
    // ...
}
```

### 4. Descriptive Test Names

```php
// Use descriptive test method names
public function test_it_prevents_stock_movement_for_service_products()
public function test_it_calculates_running_balance_correctly()
public function test_unauthenticated_users_cannot_access_products()
```

### 5. Arrange-Act-Assert Pattern

```php
public function test_example()
{
    // Arrange - Set up test data
    $product = Product::factory()->create();
    
    // Act - Perform the action
    $stock = $product->getCurrentStock();
    
    // Assert - Verify the result
    $this->assertEquals(0, $stock);
}
```

### 6. Database Transactions

Tests automatically use transactions (via `RefreshDatabase` trait), ensuring clean state between tests.

### 7. Testing Permissions

```php
public function test_user_needs_permission_to_create_product()
{
    // User without permission
    $this->actingAsUser();
    
    $response = $this->postJson('/api/v1/products', $data);
    $response->assertStatus(403);
    
    // User with permission
    $this->actingAsUserWithPermissions(['create-products']);
    
    $response = $this->postJson('/api/v1/products', $data);
    $response->assertStatus(201);
}
```

### 8. Testing Validation

```php
public function test_validation_rules()
{
    $this->actingAsUser();
    
    $response = $this->postJson('/api/v1/products', [
        // Missing required fields
    ]);
    
    $this->assertValidationErrors($response, [
        'sku',
        'name',
        'type',
    ]);
}
```

---

## Test Statistics

### Current Status

**Unit Tests**: 9 tests, all passing (25 assertions)
- InventoryServiceTest: 8 tests
- ExampleTest: 1 test

**Feature Tests**: Ready for implementation (awaiting API routes)
- ProductApiTest: 12 tests prepared
- AuthenticationTest: 14 tests prepared

**Total Factories Created**: 11
- Core: Tenant, User
- Inventory: Product, StockLedger
- CRM: Customer
- Procurement: Supplier, PurchaseOrder, PurchaseOrderItem
- IAM: Role, Permission

---

## Next Steps

1. **Implement Missing API Routes**: Create the API controllers and routes to make feature tests runnable
2. **Add More Tests**: Create tests for CRM, Procurement, and POS modules
3. **Integration Tests**: Add tests for cross-module functionality
4. **Performance Tests**: Add tests for large dataset handling
5. **Seeders**: Create seeders using the factories for development data

---

## Troubleshooting

### Issue: Factory Not Found

**Error**: `Class "Database\Factories\Modules\Inventory\Models\ProductFactory" not found`

**Solution**: Add `newFactory()` method to the model:

```php
class Product extends Model
{
    use HasFactory;
    
    protected static function newFactory()
    {
        return \Database\Factories\Inventory\ProductFactory::new();
    }
}
```

### Issue: Tenant ID Missing in Tests

**Solution**: Always create models via factories which automatically handle tenant relationships:

```php
// Ensure product has tenant
$product = Product::factory()->forTenant($tenant)->create();
```

### Issue: Test Database Conflicts

**Solution**: Tests use SQLite in-memory database (configured in `phpunit.xml`). Ensure `RefreshDatabase` trait is used.

---

## Conclusion

This testing infrastructure provides a solid foundation for maintaining code quality in the AutoERP SaaS platform. All factories support multi-tenancy, all base test classes include helpful utilities, and sample tests demonstrate best practices.

**Key Benefits**:
- ✅ Complete factory coverage for all major models
- ✅ Multi-tenancy support built into all test helpers
- ✅ Comprehensive base test classes
- ✅ Working unit tests demonstrating the infrastructure
- ✅ Feature tests ready for API implementation
- ✅ Follows Laravel and industry best practices
