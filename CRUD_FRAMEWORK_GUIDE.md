# AutoERP - CRUD Framework Implementation Guide

## Overview

This document provides a comprehensive guide to the production-ready CRUD framework implemented in AutoERP. The framework follows Clean Architecture principles with a strict Controller → Service → Repository pattern.

## Architecture Overview

### Layer Responsibilities

1. **Controllers** (`app/Http/Controllers/Api/V1/`)
   - Handle HTTP requests and responses
   - Validate input data
   - Call service layer methods
   - Format and return responses
   - **NO business logic**

2. **Services** (`app/Services/`)
   - Contain all business logic
   - Orchestrate repository operations
   - Handle transactions
   - Enforce business rules
   - Coordinate cross-module interactions
   - **NO direct database access**

3. **Repositories** (`app/Repositories/`)
   - Encapsulate data access logic
   - Execute database queries
   - Apply filters, sorting, pagination
   - Handle eager loading
   - **NO business logic**

4. **Models** (`app/Models/`)
   - Define database structure
   - Handle relationships
   - Provide scopes and accessors
   - **Minimal logic, mostly configuration**

## Core Features

### 1. Dynamic Query Configuration

The framework supports configuration-driven queries through the `BaseRepository`:

```php
$config = [
    // Sparse field selection
    'columns' => ['id', 'name', 'email'],
    
    // Eager loading with field selection
    'relations' => [
        'posts' => ['id', 'title', 'created_at'],
        'comments'
    ],
    
    // Filters
    'filters' => [
        'status' => 'active',
        'created_at' => ['gte' => '2024-01-01'],
        'category_id' => ['in' => [1, 2, 3]]
    ],
    
    // Search
    'search' => [
        'query' => 'john',
        'fields' => ['name', 'email', 'description']
    ],
    
    // Multi-field sorting
    'sorts' => [
        'created_at' => 'desc',
        'name' => 'asc'
    ]
];

$results = $repository->all($config);
```

### 2. API Query Parameters

The `BaseApiController` supports RESTful query parameters:

```
GET /api/v1/users?
    fields=id,name,email                    # Sparse fields
    &with=posts,comments                    # Eager loading
    &filter[status]=active                  # Filters
    &filter[created_at][gte]=2024-01-01    # Date filters
    &search=john                            # Search
    &sort=-created_at,name                  # Sorting (- for desc)
    &paginate=true                          # Enable pagination
    &per_page=20                            # Items per page
```

### 3. Advanced Filtering

Supported filter operators:

- `eq` - Equal to
- `ne`, `neq` - Not equal to
- `gt` - Greater than
- `gte` - Greater than or equal
- `lt` - Less than
- `lte` - Less than or equal
- `like` - Pattern matching
- `not_like` - Inverse pattern matching
- `in` - In array
- `not_in` - Not in array
- `null` - Is null / is not null
- `between` - Between two values
- `not_between` - Not between two values

### 4. Transaction Management

All service operations are automatically wrapped in transactions:

```php
public function create(array $data): Model
{
    DB::beginTransaction();
    
    try {
        // Lifecycle hooks for extensibility
        $data = $this->beforeCreate($data);
        
        $model = $this->repository->create($data);
        
        $this->afterCreate($model, $data);
        
        DB::commit();
        
        return $model->fresh();
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

### 5. Tenant Awareness

The `BaseModel` automatically handles tenant isolation:

```php
// Automatically applies tenant_id on create
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
    // tenant_id is automatically set
]);

// Global scope filters by tenant_id automatically
$users = User::all(); // Only returns current tenant's users
```

## Implementation Example

### Step 1: Create a Model

```php
<?php

namespace App\Models;

class Product extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'name',
        'sku',
        'description',
        'price',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'status' => 'string',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
```

### Step 2: Create a Repository

```php
<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    // Add custom methods if needed
    public function findBySkuOrFail(string $sku): Product
    {
        return $this->model->where('sku', $sku)->firstOrFail();
    }
}
```

### Step 3: Create a Service

```php
<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Model;

class ProductService extends BaseService
{
    public function __construct(ProductRepository $repository)
    {
        parent::__construct($repository);
    }

    // Override lifecycle hooks for custom logic
    protected function beforeCreate(array $data): array
    {
        // Generate SKU if not provided
        if (empty($data['sku'])) {
            $data['sku'] = 'PRD-' . strtoupper(uniqid());
        }
        
        return $data;
    }

    protected function afterCreate(Model $model, array $data): void
    {
        // Trigger events, send notifications, etc.
        event(new ProductCreated($model));
    }

    // Add custom business logic methods
    public function updateStock(int $productId, int $quantity): Model
    {
        // Custom business logic
        $product = $this->repository->findOrFail($productId);
        
        // ...stock update logic...
        
        return $product;
    }
}
```

### Step 4: Create a Controller

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends BaseApiController
{
    protected string $resourceName = 'product';

    public function __construct(ProductService $service)
    {
        parent::__construct($service);
    }

    protected function validateStore(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|unique:products,sku',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);
    }

    protected function validateUpdate(Request $request, int|string $id): array
    {
        return $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'sku' => "nullable|string|unique:products,sku,{$id}",
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|in:active,inactive',
        ]);
    }

    protected function getSearchableFields(): array
    {
        return ['name', 'sku', 'description'];
    }
}
```

### Step 5: Register Routes

```php
<?php

use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('products', ProductController::class);
    
    // Bulk delete
    Route::delete('products/bulk', [ProductController::class, 'bulkDestroy']);
});
```

## API Response Format

### Success Response

```json
{
    "success": true,
    "message": "Product created successfully",
    "data": {
        "id": 1,
        "name": "Product Name",
        "sku": "PRD-123",
        "price": "99.99",
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### Paginated Response

```json
{
    "success": true,
    "data": [/* items */],
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 5,
        "per_page": 15,
        "to": 15,
        "total": 73
    },
    "links": {
        "first": "http://api.example.com/products?page=1",
        "last": "http://api.example.com/products?page=5",
        "prev": null,
        "next": "http://api.example.com/products?page=2"
    }
}
```

### Error Response

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "name": ["The name field is required."],
        "price": ["The price must be at least 0."]
    }
}
```

## Security Considerations

### 1. Tenant Isolation

All models extend `BaseModel` which automatically applies tenant scoping to prevent cross-tenant data access.

### 2. Input Validation

All create and update operations must be validated at the controller level before reaching the service layer.

### 3. Authorization

Use Laravel policies to control access:

```php
// In controller constructor
$this->authorizeResource(Product::class, 'product');
```

### 4. SQL Injection Prevention

All queries use Eloquent ORM with parameter binding, preventing SQL injection attacks.

## Testing

### Unit Testing a Repository

```php
public function test_repository_can_filter_by_status()
{
    $product = Product::factory()->create(['status' => 'active']);
    
    $repository = new ProductRepository(new Product());
    $results = $repository->findBy('status', 'active');
    
    $this->assertCount(1, $results);
    $this->assertEquals($product->id, $results->first()->id);
}
```

### Unit Testing a Service

```php
public function test_service_creates_product_with_auto_generated_sku()
{
    $service = new ProductService(new ProductRepository(new Product()));
    
    $product = $service->create([
        'name' => 'Test Product',
        'price' => 99.99,
        'status' => 'active',
    ]);
    
    $this->assertNotNull($product->sku);
    $this->assertStringStartsWith('PRD-', $product->sku);
}
```

### Feature Testing an API Endpoint

```php
public function test_can_list_products_with_filters()
{
    Product::factory()->count(10)->create(['status' => 'active']);
    Product::factory()->count(5)->create(['status' => 'inactive']);
    
    $response = $this->getJson('/api/v1/products?filter[status]=active');
    
    $response->assertOk()
        ->assertJsonStructure(['success', 'data', 'meta'])
        ->assertJsonCount(10, 'data');
}
```

## Best Practices

### 1. Keep Controllers Thin

Controllers should only:
- Validate input
- Call service methods
- Format responses

### 2. Business Logic in Services

All business logic, orchestration, and cross-module interactions belong in services.

### 3. Data Access in Repositories

Repositories should only handle database queries and data retrieval.

### 4. Use Lifecycle Hooks

Override `before*` and `after*` methods in services for custom logic:
- `beforeCreate()` / `afterCreate()`
- `beforeUpdate()` / `afterUpdate()`
- `beforeDelete()` / `afterDelete()`

### 5. Leverage Configuration

Use the configuration array instead of creating custom query methods:

```php
// Good
$config = ['filters' => ['status' => 'active']];
$products = $repository->all($config);

// Avoid
$products = $repository->getAllActive();
```

### 6. Transaction Safety

Always use transactions for multi-step operations in services.

### 7. Consistent Error Handling

Let exceptions bubble up to the controller where they're caught and formatted into proper API responses.

## Performance Optimization

### 1. Eager Loading

Always specify relations to avoid N+1 queries:

```php
$config = [
    'relations' => ['category', 'supplier']
];
```

### 2. Sparse Fields

Request only needed fields:

```php
$config = [
    'columns' => ['id', 'name', 'price']
];
```

### 3. Pagination

Always paginate large datasets:

```php
$products = $repository->paginate(50, $config);
```

### 4. Caching

Implement caching at the service layer for frequently accessed data.

### 5. Indexes

Ensure database indexes on frequently filtered and sorted columns.

## Extending the Framework

### Adding Custom Query Methods

```php
class ProductRepository extends BaseRepository
{
    public function findFeatured(int $limit = 10): Collection
    {
        return $this->model
            ->where('featured', true)
            ->limit($limit)
            ->get();
    }
}
```

### Adding Custom Business Logic

```php
class ProductService extends BaseService
{
    public function markAsFeatured(int $productId): Model
    {
        DB::beginTransaction();
        
        try {
            $product = $this->repository->findOrFail($productId);
            $product->update(['featured' => true]);
            
            event(new ProductFeatured($product));
            
            DB::commit();
            
            return $product->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

### Custom Controller Methods

```php
class ProductController extends BaseApiController
{
    public function featured(Request $request): JsonResponse
    {
        try {
            $products = $this->service->getFeatured();
            return $this->successResponse($products);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
```

## Conclusion

This CRUD framework provides a solid, scalable foundation for building enterprise-grade ERP applications. It enforces clean architecture, ensures maintainability, and provides all the advanced features needed for modern SaaS applications while remaining flexible and extensible.

For questions or contributions, please refer to the [CONTRIBUTING.md](CONTRIBUTING.md) guide.
