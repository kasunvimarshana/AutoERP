# Tenancy Module Tests

This directory contains comprehensive tests for the Tenancy module.

## Test Structure

```
Tests/
├── Unit/
│   ├── TenantTest.php              # Tenant model tests
│   ├── TenantServiceTest.php       # TenantService business logic tests
│   └── TenantRepositoryTest.php    # TenantRepository data access tests
│
└── Feature/
    ├── TenantControllerTest.php     # API endpoint tests
    ├── TenantIsolationTest.php      # Tenant data isolation tests
    ├── TenantFeatureAccessTest.php  # Feature access and plan management tests
    └── TenantContextTest.php        # Tenant context resolution tests
```

## Test Coverage

### Unit Tests

#### TenantTest.php
- Model instantiation and attribute casting
- Settings management (get/set)
- Status checks (isActive, onTrial, hasActiveSubscription)
- Status transitions (activate, suspend)
- Factory states (smallBusiness, enterprise, suspended, etc.)
- Timestamp and relationship tracking

#### TenantServiceTest.php
- CRUD operations (create, update, delete, find)
- Business logic validation
- Event dispatching (TenantCreated, TenantUpdated, TenantDeleted)
- Search functionality
- Pagination
- Status management (activate, deactivate, suspend)
- Transaction rollback on failures

#### TenantRepositoryTest.php
- Data access methods
- Finding by ID, slug, domain
- Filtering active tenants
- Search across multiple fields
- Pagination with and without filters
- Complex data handling (settings, features, limits)

### Feature Tests

#### TenantControllerTest.php
- REST API endpoints (index, store, show, update, destroy)
- Authorization checks
- Validation rules
- Search endpoint
- Status management endpoints (activate, deactivate, suspend)
- Pagination support
- Error handling (404, 422, 403, 401)

#### TenantIsolationTest.php
- Cross-tenant data isolation
- Tenant context switching
- Query scoping to current tenant
- Prevention of cross-tenant data access
- Bulk operation isolation
- Super admin access
- Relationship isolation

#### TenantFeatureAccessTest.php
- Feature flag management
- Plan limits enforcement
- Plan upgrades and downgrades
- Trial period management
- Subscription expiration handling
- Custom feature settings
- Feature-based access control

#### TenantContextTest.php
- Context initialization and clearing
- Tenant resolution (by slug, domain, user)
- Context switching between tenants
- Context persistence
- Settings/features/limits access in context
- Guest user handling
- Context isolation per request

## Running Tests

### Run All Tenancy Tests
```bash
php artisan test --testsuite=Tenancy
```

### Run Specific Test File
```bash
php artisan test Modules/Tenancy/Tests/Unit/TenantTest.php
php artisan test Modules/Tenancy/Tests/Feature/TenantControllerTest.php
```

### Run with Coverage
```bash
php artisan test --testsuite=Tenancy --coverage
```

### Run Unit Tests Only
```bash
php artisan test Modules/Tenancy/Tests/Unit
```

### Run Feature Tests Only
```bash
php artisan test Modules/Tenancy/Tests/Feature
```

### Run Specific Test Method
```bash
php artisan test --filter=test_it_creates_tenant_successfully
```

## Test Database Setup

Tests use SQLite in-memory database by default. Configuration in `phpunit.xml`:

```xml
<php>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
</php>
```

## Factory Usage

The tests use `TenantFactory` which provides several useful states:

```php
// Create active tenant
$tenant = Tenant::factory()->create();

// Create tenant on trial
$tenant = Tenant::factory()->onTrial()->create();

// Create suspended tenant
$tenant = Tenant::factory()->suspended()->create();

// Create inactive tenant
$tenant = Tenant::factory()->inactive()->create();

// Create tenant with expired subscription
$tenant = Tenant::factory()->expired()->create();

// Create small business tenant
$tenant = Tenant::factory()->smallBusiness()->create();

// Create enterprise tenant
$tenant = Tenant::factory()->enterprise()->create();
```

## Test Dependencies

Tests require:
- PHPUnit 11.x
- Laravel Testing utilities
- RefreshDatabase trait for database cleanup
- Mockery for mocking (included with Laravel)

## Writing New Tests

When adding new tests, follow these guidelines:

1. **Use descriptive test names**: `test_it_does_something_specific`
2. **Follow AAA pattern**: Arrange, Act, Assert
3. **Use factories**: Don't manually create test data
4. **Test edge cases**: Not just happy paths
5. **Keep tests isolated**: Each test should be independent
6. **Use appropriate assertions**: Be specific about what you're testing

Example:
```php
public function test_it_creates_tenant_with_valid_data(): void
{
    // Arrange
    $data = [
        'name' => 'Test Company',
        'slug' => 'test-company',
        'domain' => 'test.example.com',
    ];

    // Act
    $tenant = $this->tenantService->create($data);

    // Assert
    $this->assertNotNull($tenant);
    $this->assertEquals('Test Company', $tenant->name);
    $this->assertDatabaseHas('tenants', ['slug' => 'test-company']);
}
```

## Continuous Integration

These tests are designed to run in CI/CD pipelines. They:
- Use in-memory database for speed
- Clean up after themselves (RefreshDatabase)
- Don't depend on external services
- Can run in parallel

## Coverage Goals

Target coverage levels:
- **Overall**: 80%+
- **Critical paths**: 100%
  - Tenant isolation
  - Data scoping
  - Authorization
  - Feature access control

## Troubleshooting

### Tests failing with database errors
```bash
# Clear caches and migrate fresh
php artisan cache:clear
php artisan config:clear
php artisan test --testsuite=Tenancy
```

### Factory not found errors
```bash
# Make sure factory is registered in Tenant model
composer dump-autoload
```

### Tenant context not working
Check that:
1. `tenancy()` helper is available
2. Tenant middleware is registered
3. User model has `tenant_id` field

## Related Documentation

- [Module Development Guide](../../../MODULE_DEVELOPMENT_GUIDE.md)
- [Testing Guidelines](.github/instructions/module-tests.instructions.md)
- [Tenancy Architecture](../../../TENANCY_ARCHITECTURE.md)
