# Implementation Guide - AutoERP

## Overview

This guide provides comprehensive instructions for understanding and extending the enterprise-grade modular SaaS ERP platform built with Laravel 11 and following Clean Architecture principles.

## Architecture Summary

### Core Patterns Implemented

1. **Clean Architecture**
   - Clear separation between layers
   - Dependency inversion via interfaces
   - Business logic isolated in services
   - Data access abstracted in repositories

2. **Controller → Service → Repository Pattern**
   - **Controllers**: Handle HTTP requests/responses only
   - **Services**: Contain all business logic and transactions
   - **Repositories**: Abstract data access and queries
   - **Models**: Domain entities with relationships

3. **Multi-Tenancy**
   - Single database with tenant_id column
   - Automatic tenant isolation via global scopes
   - Tenant context set per request
   - Cascade deletes for data integrity

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── API/
│   │   │   ├── APIController.php (Base)
│   │   │   └── TenantController.php
│   │   └── Controller.php
│   └── Middleware/
│       └── SetTenantContext.php
├── Models/
│   ├── Tenant.php
│   └── User.php
├── Repositories/
│   ├── Contracts/
│   │   ├── BaseRepositoryInterface.php
│   │   └── TenantRepositoryInterface.php
│   ├── Eloquent/
│   │   └── TenantRepository.php
│   └── BaseRepository.php
├── Services/
│   ├── BaseService.php
│   └── TenantService.php
├── Scopes/
│   └── TenantScope.php
├── Traits/
│   └── HasTenant.php
└── Providers/
    ├── AppServiceProvider.php
    └── RepositoryServiceProvider.php
```

## Creating a New Module

Follow this step-by-step guide to create a new module (using "Product" as an example).

### Step 1: Create the Model and Migration

```bash
php artisan make:model Product -m
```

Update the migration:

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('sku')->unique();
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->timestamps();
    $table->softDeletes();
    
    $table->index('tenant_id');
    $table->index('sku');
});
```

Update the Product model:

```php
<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'sku',
        'description',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];
}
```

### Step 2: Create Repository Interface

Create `app/Repositories/Contracts/ProductRepositoryInterface.php`:

```php
<?php

namespace App\Repositories\Contracts;

use App\Models\Product;

interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    public function findBySku(string $sku): ?Product;
}
```

### Step 3: Create Repository Implementation

Create `app/Repositories/Eloquent/ProductRepository.php`:

```php
<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\BaseRepository;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->model->where('sku', $sku)->first();
    }

    protected function filterSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
```

### Step 4: Create Service

Create `app/Services/ProductService.php`:

```php
<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ProductService extends BaseService
{
    public function __construct(ProductRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->repository->findBySku($sku);
    }

    protected function beforeCreate(array $data): array
    {
        // Add custom logic before creating product
        // e.g., generate SKU if not provided
        
        return $data;
    }

    protected function afterCreate($model, array $data): void
    {
        // Fire ProductCreated event
        event(new \App\Events\ProductCreated($model));
    }
}
```

### Step 5: Create Controller

Create `app/Http/Controllers/API/ProductController.php`:

```php
<?php

namespace App\Http\Controllers\API;

use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ProductController extends APIController
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['search', 'sort_by', 'sort_direction']);
            
            $products = $this->productService->getPaginated($perPage, $filters);
            
            return $this->successResponse($products);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $product = $this->productService->create($request->all());
            return $this->createdResponse($product);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $product = $this->productService->getById($id);
            return $this->successResponse($product);
        } catch (\Exception $e) {
            return $this->notFoundResponse();
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'sku' => 'sometimes|required|string|max:255|unique:products,sku,' . $id,
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        try {
            $product = $this->productService->update($id, $request->all());
            return $this->successResponse($product);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->productService->delete($id);
            return $this->noContentResponse();
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
```

### Step 6: Register Repository Binding

Add to `app/Providers/RepositoryServiceProvider.php`:

```php
public function register(): void
{
    // Existing bindings...
    
    // Add Product Repository
    $this->app->bind(
        \App\Repositories\Contracts\ProductRepositoryInterface::class,
        \App\Repositories\Eloquent\ProductRepository::class
    );
}
```

### Step 7: Add API Routes

Add to `routes/api.php`:

```php
Route::prefix('v1')->group(function () {
    // Existing routes...
    
    // Product routes
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });
});
```

### Step 8: Run Migration

```bash
php artisan migrate
```

## API Standards

### Request Format

All API endpoints follow RESTful conventions:

- `GET /api/v1/{resource}` - List resources (paginated)
- `POST /api/v1/{resource}` - Create resource
- `GET /api/v1/{resource}/{id}` - Get single resource
- `PUT /api/v1/{resource}/{id}` - Update resource
- `DELETE /api/v1/{resource}/{id}` - Delete resource

### Response Format

Success Response:
```json
{
  "success": true,
  "message": "Resource created successfully",
  "data": {
    "id": 1,
    "name": "Product Name",
    ...
  }
}
```

Error Response:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required"]
  }
}
```

### HTTP Status Codes

- `200` OK - Success
- `201` Created - Resource created
- `204` No Content - Success with no response body
- `400` Bad Request - Generic client error
- `401` Unauthorized - Authentication required
- `403` Forbidden - Permission denied
- `404` Not Found - Resource not found
- `422` Unprocessable Entity - Validation failed
- `500` Internal Server Error - Server error

## Database Conventions

### Naming Conventions

- Tables: plural snake_case (`products`, `product_categories`)
- Columns: snake_case (`created_at`, `user_id`)
- Foreign keys: `{table_singular}_id` (`tenant_id`, `user_id`)
- Pivot tables: alphabetically ordered (`product_tag`, not `tag_product`)

### Required Columns

Every tenant-aware table must have:
- `id` - Primary key
- `tenant_id` - Foreign key to tenants table
- `created_at` - Timestamp
- `updated_at` - Timestamp
- `deleted_at` - Soft delete timestamp (optional but recommended)

### Indexes

Always add indexes for:
- `tenant_id` (for tenant isolation queries)
- Foreign keys
- Frequently queried columns
- Unique constraints

## Testing

### Repository Tests

Test repositories in isolation:

```php
public function test_can_find_product_by_sku()
{
    $product = Product::factory()->create(['sku' => 'TEST-123']);
    $repository = new ProductRepository(new Product);
    
    $found = $repository->findBySku('TEST-123');
    
    $this->assertNotNull($found);
    $this->assertEquals('TEST-123', $found->sku);
}
```

### Service Tests

Test services with mocked repositories:

```php
public function test_can_create_product()
{
    $repository = Mockery::mock(ProductRepositoryInterface::class);
    $service = new ProductService($repository);
    
    $repository->shouldReceive('create')
        ->once()
        ->andReturn(new Product(['name' => 'Test']));
    
    $product = $service->create(['name' => 'Test']);
    
    $this->assertEquals('Test', $product->name);
}
```

### API Tests

Test complete API flow:

```php
public function test_can_list_products()
{
    Product::factory()->count(5)->create();
    
    $response = $this->getJson('/api/v1/products');
    
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'success',
                 'message',
                 'data' => [
                     'data' => [],
                     'total',
                 ]
             ]);
}
```

## Best Practices

### 1. Keep Controllers Thin

Controllers should only:
- Validate input
- Call service methods
- Return formatted responses

### 2. Business Logic in Services

All business logic must be in service classes:
- Transactions
- Complex operations
- Cross-module interactions
- Event dispatching

### 3. Data Access in Repositories

Repositories should only:
- Query the database
- Return models or collections
- No business logic

### 4. Use Events for Side Effects

For operations that need to trigger side effects:
- Send emails
- Update related records
- Generate reports
- Fire webhooks

Use domain events instead of doing it directly in services.

### 5. Validate Early

Always validate input:
- In controllers (for API validation)
- In services (for business rules)

### 6. Transaction Safety

Wrap multi-step operations in transactions:

```php
DB::transaction(function () use ($data) {
    $order = $this->orderRepository->create($data);
    $this->inventoryService->reduceStock($order);
    $this->paymentService->processPayment($order);
    return $order;
});
```

### 7. Consistent Naming

- Repository methods: `find`, `create`, `update`, `delete`, `get*`
- Service methods: Business-focused names (`placeOrder`, `cancelSubscription`)
- Controller methods: RESTful (`index`, `store`, `show`, `update`, `destroy`)

## Environment Setup

### Required Packages

```json
{
  "laravel/sanctum": "^4.3",
  "spatie/laravel-permission": "^6.24",
  "spatie/laravel-activitylog": "^4.11"
}
```

### Environment Variables

```env
APP_NAME="AutoERP"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=erp_saas
DB_USERNAME=postgres
DB_PASSWORD=

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

## Commands

```bash
# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Run tests
php artisan test

# Code formatting
./vendor/bin/pint

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Next Steps

1. **Authentication** - Implement login, register, logout endpoints
2. **Authorization** - Add middleware and policies to routes
3. **More Modules** - Create User, Organization, Product modules
4. **Events** - Implement event classes and listeners
5. **Tests** - Write comprehensive tests for all modules
6. **Documentation** - Generate API documentation with Swagger
7. **Frontend** - Build Vue.js frontend consuming the APIs

## Support

For questions or issues, refer to:
- ARCHITECTURE.md - Architectural guidelines
- REQUIREMENTS_CONSOLIDATED.md - Full requirements
- IMPLEMENTATION_ROADMAP.md - Development timeline

---

**Version**: 1.0  
**Last Updated**: 2026-02-02  
**Status**: Foundation Complete
