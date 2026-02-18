# Automatic Tenant Scoping

## Overview

The AutoERP platform implements automatic tenant scoping at the ORM level to prevent cross-tenant data leaks. This is a critical security feature that ensures users can only access data belonging to their tenant.

## How It Works

### 1. TenantScope Global Scope

The `TenantScope` class is an Eloquent global scope that automatically filters all queries to include only records belonging to the current tenant.

```php
// Automatically applied - no manual filtering needed
$products = Product::all(); // Only returns products for current tenant
```

### 2. BelongsToTenant Trait

Models that belong to a tenant should use the `BelongsToTenant` trait:

```php
use Modules\Core\Traits\BelongsToTenant;

class Product extends BaseModel
{
    use BelongsToTenant;
    
    protected $fillable = ['tenant_id', 'name', ...];
}
```

This trait:
- Automatically applies the `TenantScope` global scope
- Auto-fills `tenant_id` when creating new records
- Adds helpful query methods (`forTenant`, `forAllTenants`)

### 3. Automatic tenant_id Assignment

When creating a new model, the `tenant_id` is automatically set:

```php
// tenant_id is automatically set from TenantContext
$product = Product::create([
    'name' => 'Widget',
    // tenant_id is auto-filled
]);
```

## Usage Examples

### Basic Queries (Automatically Scoped)

```php
// All of these are automatically scoped to current tenant
Product::all();
Product::where('status', 'active')->get();
Product::find($id);
Product::with('variants')->paginate(15);
```

### Bypassing Tenant Scope (Admin Operations)

```php
// Get products for all tenants (requires admin privileges)
Product::forAllTenants()->get();

// Get products for a specific tenant
Product::forTenant($tenantId)->get();

// Use withoutTenantScope() method
Product::withoutTenantScope()->where('sku', 'ABC123')->first();
```

### Cross-Tenant Operations (Use With Caution)

```php
// Example: Admin viewing all products across tenants
if ($user->hasRole('super-admin')) {
    $allProducts = Product::forAllTenants()
        ->orderBy('created_at', 'desc')
        ->paginate(50);
}
```

## Adding Tenant Scoping to Models

### Step 1: Add the Trait

```php
use Modules\Core\Traits\BelongsToTenant;

class YourModel extends BaseModel
{
    use BelongsToTenant;
}
```

### Step 2: Ensure tenant_id is in Fillable

```php
protected $fillable = [
    'tenant_id',
    // ... other fields
];
```

### Step 3: Migration Should Have tenant_id Column

```php
Schema::create('your_table', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
    // ... other columns
});
```

## Security Considerations

### DO's ✅

1. **Always use the trait** for tenant-scoped models
2. **Verify permissions** before using `forAllTenants()`
3. **Test cross-tenant isolation** thoroughly
4. **Use database constraints** (foreign keys to tenants table)

```php
// Good - Properly scoped with permission check
if ($user->can('view-all-tenants')) {
    return Product::forAllTenants()->get();
}
return Product::all(); // Current tenant only
```

### DON'Ts ❌

1. **Don't bypass scopes** without permission checks
2. **Don't manually filter** by tenant_id (use the trait)
3. **Don't forget to add trait** to new models

```php
// Bad - Manual filtering is error-prone
Product::where('tenant_id', $tenantId)->get();

// Good - Use the trait's methods
Product::forTenant($tenantId)->get();
```

## Testing Tenant Isolation

### Unit Test Example

```php
public function test_tenant_scope_prevents_cross_tenant_access()
{
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    // Create products for different tenants
    $product1 = Product::factory()->create(['tenant_id' => $tenant1->id]);
    $product2 = Product::factory()->create(['tenant_id' => $tenant2->id]);
    
    // Set current tenant
    app(TenantContext::class)->setTenant($tenant1);
    
    // Should only see tenant1's products
    $products = Product::all();
    
    $this->assertCount(1, $products);
    $this->assertEquals($product1->id, $products->first()->id);
    $this->assertNotContains($product2->id, $products->pluck('id'));
}
```

## Advanced Usage

### Conditional Scoping Based on User Role

```php
class ProductRepository extends BaseRepository
{
    public function getAllProducts()
    {
        $user = auth()->user();
        
        if ($user->hasRole('super-admin')) {
            // Admin can see all
            return Product::forAllTenants()->paginate(50);
        }
        
        if ($user->hasRole('tenant-admin')) {
            // Tenant admin sees their tenant
            return Product::paginate(50);
        }
        
        // Regular users see filtered products
        return Product::where('status', 'active')->paginate(20);
    }
}
```

### Multi-Tenant Reporting

```php
// Generate cross-tenant report (admin only)
if ($user->hasRole('super-admin')) {
    $report = Product::forAllTenants()
        ->selectRaw('tenant_id, COUNT(*) as count, SUM(selling_price * quantity) as revenue')
        ->groupBy('tenant_id')
        ->with('tenant:id,name')
        ->get();
}
```

## Troubleshooting

### Issue: Queries returning empty results

**Cause**: TenantContext not set or incorrect tenant_id

**Solution**:
```php
// Check current tenant
$currentTenant = app(TenantContext::class)->getTenantId();
dd($currentTenant); // Should not be null

// Verify model has tenant_id
dd(Product::first()->tenant_id);
```

### Issue: Need to access data across tenants

**Solution**: Use proper admin checks
```php
if (!auth()->user()->hasRole('super-admin')) {
    throw UnauthorizedException::forAction('view cross-tenant data');
}

$data = Model::forAllTenants()->get();
```

## Performance Considerations

The tenant scope adds a WHERE clause to all queries:

```sql
SELECT * FROM products WHERE tenant_id = 1 AND status = 'active';
```

### Optimization Tips

1. **Add indexes** on tenant_id columns:
```php
$table->index(['tenant_id', 'created_at']);
```

2. **Use composite indexes** for common queries:
```php
$table->index(['tenant_id', 'status', 'created_at']);
```

3. **Monitor query performance** with Laravel Telescope or query logging

## Migration Guide

### Existing Models Without Tenant Scoping

1. Add the trait to the model
2. Ensure tenant_id column exists
3. Run data migration if needed:

```php
// Data migration to populate tenant_id
Product::whereNull('tenant_id')->chunkById(100, function ($products) {
    foreach ($products as $product) {
        // Determine tenant from related data
        $tenantId = $product->user->tenant_id;
        $product->update(['tenant_id' => $tenantId]);
    }
});
```

## Related Documentation

- [Multi-Tenancy Architecture](../../../ARCHITECTURE.md#multi-tenancy-strategy)
- [Security Best Practices](../../../SECURITY.md)
- [Domain Exceptions](./Exceptions/README.md)
