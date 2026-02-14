# Dynamic CRUD Framework Documentation

## Overview

The Dynamic CRUD Framework provides a production-ready, reusable backend foundation for building fully dynamic, customizable, and extensible CRUD operations aligned with Clean Architecture and the Controller → Service → Repository pattern.

## Key Features

### 1. **Configuration-Driven Queries**
All query behavior is configuration-driven rather than hardcoded, using the `QueryConfig` DTO.

### 2. **Advanced Capabilities**
- ✅ **Global Search**: Search across multiple fields simultaneously
- ✅ **Field-Level Search**: Search specific fields with custom operators
- ✅ **Advanced Filtering**: Support for =, !=, >, <, >=, <=, LIKE, IN, NOT IN, BETWEEN, IS NULL, IS NOT NULL
- ✅ **Relation-Based Filters**: Filter by related model fields
- ✅ **Multi-Field Sorting**: Sort by multiple fields with ASC/DESC directions
- ✅ **Sparse Field Selection**: Select only specific columns to optimize queries
- ✅ **Eager Loading**: Load relations with selectable fields
- ✅ **Pagination**: Configurable page sizes with cursor support
- ✅ **Tenant-Aware**: Automatic tenant scoping for multi-tenant applications

### 3. **Clean Architecture**
- **Controllers**: Handle HTTP requests and validation
- **Services**: Orchestrate business logic with transactions
- **Repositories**: Encapsulate data access with dynamic query building

## Architecture Components

### 1. QueryConfig DTO

The `QueryConfig` class encapsulates all query parameters for dynamic queries.

**Location**: `app/DTOs/QueryConfig.php`

**Properties**:
```php
public array $select = ['*'];              // Sparse field selection
public ?array $search = null;              // Global search
public array $filters = [];                // Field filters
public array $relationFilters = [];        // Relation-based filters
public array $sort = [];                   // Multi-field sorting
public ?int $perPage = null;              // Pagination size
public int $page = 1;                      // Current page
public array $with = [];                   // Eager loading
public array $withCount = [];              // Relation counts
public bool $paginate = true;              // Enable/disable pagination
public ?int $tenantId = null;             // Tenant scoping
```

**Usage**:
```php
// Create from request
$config = QueryConfig::fromRequest($request->all());

// Manual creation
$config = new QueryConfig();
$config->select = ['id', 'name', 'email'];
$config->filters = ['status' => 'active'];
$config->sort = ['created_at' => 'desc'];
$config->perPage = 20;
```

### 2. DynamicQueryBuilder Trait

Provides methods for building dynamic queries based on `QueryConfig`.

**Location**: `app/Core/Traits/DynamicQueryBuilder.php`

**Key Methods**:
- `applyQueryConfig()`: Apply full QueryConfig to query builder
- `applySearch()`: Global and field-level search
- `applyFilters()`: Advanced filtering with operators
- `applyRelationFilters()`: Filter by relation fields
- `applySorting()`: Multi-field sorting
- `parseWithRelations()`: Eager loading with field selection

### 3. Enhanced BaseRepository

**Location**: `app/Core/BaseRepository.php`

**New Features**:
- Uses `DynamicQueryBuilder` trait
- `query(QueryConfig $config)`: Main dynamic query method
- Configurable allowed fields and relations
- Default searchable fields

**Configuration Properties**:
```php
protected array $allowedFields = [];        // Restrict queryable fields
protected array $allowedRelations = [];     // Restrict loadable relations
protected array $searchableFields = [];     // Default search fields
```

**Example Implementation**:
```php
namespace App\Modules\Inventory\Repositories;

use App\Core\BaseRepository;
use App\Modules\Inventory\Models\Product;

class ProductRepository extends BaseRepository
{
    protected array $allowedFields = [
        'id', 'name', 'sku', 'description', 'price', 'status', 'created_at'
    ];

    protected array $allowedRelations = [
        'category', 'supplier', 'skus', 'batches'
    ];

    protected array $searchableFields = [
        'name', 'sku', 'description'
    ];

    public function __construct(Product $model)
    {
        parent::__construct($model);
    }
}
```

### 4. Enhanced BaseService

**Location**: `app/Core/BaseService.php`

**New Methods**:
- `query(QueryConfig $config)`: Execute dynamic queries
- `getAll(array $with = [])`: Get all records
- `getPaginated(int $perPage, array $with = [])`: Get paginated records
- `findById(int $id, array $with = [])`: Find with eager loading
- `create(array $data)`: Transactional create
- `update(int $id, array $data)`: Transactional update
- `delete(int $id)`: Transactional delete

**Example Implementation**:
```php
namespace App\Modules\Inventory\Services;

use App\Core\BaseService;
use App\Modules\Inventory\Repositories\ProductRepository;

class ProductService extends BaseService
{
    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    // Inherit all CRUD methods from BaseService
    // Add custom business logic as needed
}
```

### 5. GenericCrudController

**Location**: `app/Core/GenericCrudController.php`

A ready-to-use controller that provides all CRUD operations with dynamic query support.

**Methods**:
- `index(Request $request)`: List with filtering, search, sort, pagination
- `store(Request $request)`: Create new resource
- `show(Request $request, int $id)`: Get single resource
- `update(Request $request, int $id)`: Update resource
- `destroy(int $id)`: Delete resource

**Example Implementation**:
```php
namespace App\Modules\Inventory\Http\Controllers;

use App\Core\GenericCrudController;
use App\Modules\Inventory\Services\ProductService;
use App\Modules\Inventory\Resources\ProductResource;

class ProductController extends GenericCrudController
{
    protected string $resourceClass = ProductResource::class;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }
}
```

## API Usage Examples

### 1. Simple List
```
GET /api/products
```

### 2. Pagination
```
GET /api/products?per_page=20&page=2
```

### 3. Field Selection (Sparse Fields)
```
GET /api/products?select=id,name,price
```

### 4. Global Search
```
GET /api/products?search=laptop&search_fields=name,description,sku
```

### 5. Field Filters
```
GET /api/products?filters[status]=active&filters[price][operator]=>&filters[price][value]=100
```

### 6. Relation Filters
```
GET /api/products?relation_filters[category.name]=Electronics
```

### 7. Multi-Field Sorting
```
GET /api/products?sort=price:desc,name:asc
```

### 8. Eager Loading
```
GET /api/products?with=category,supplier,skus:id,sku_code,price
```

### 9. Relation Counts
```
GET /api/products?with_count=skus,batches
```

### 10. Complex Query
Note: This is shown on multiple lines for readability, but should be a single URL in practice.
```
GET /api/products?select=id,name,price&search=electronics&search_fields=name,description&filters[status]=active&filters[price][operator]=>&filters[price][value]=50&sort=price:desc&with=category:id,name&per_page=25&page=1
```

## Query Parameter Reference

### Select (Sparse Fields)
```
?select=field1,field2,field3
?select[]=field1&select[]=field2
```

### Search
```
?search=term&search_fields=field1,field2
```

### Filters

**Simple Equality**:
```
?filters[field]=value
```

**With Operator**:
```
?filters[field][operator]==&filters[field][value]=value
?filters[field][operator]=>&filters[field][value]=100
?filters[field][operator]=LIKE&filters[field][value]=pattern
```

**Supported Operators**:
- `=` - Equal
- `!=` - Not equal
- `>` - Greater than
- `<` - Less than
- `>=` - Greater than or equal
- `<=` - Less than or equal
- `LIKE` - Pattern match
- `STARTS WITH` - Starts with pattern
- `ENDS WITH` - Ends with pattern
- `IN` - In array
- `NOT IN` - Not in array
- `BETWEEN` - Between two values
- `NOT BETWEEN` - Not between two values
- `IS NULL` - Is null
- `IS NOT NULL` - Is not null

### Relation Filters
```
?relation_filters[relation.field]=value
?relation_filters[category.name]=Electronics
```

### Sorting
```
?sort=field:asc
?sort=field:desc
?sort=field1:asc,field2:desc
```

### Pagination
```
?per_page=25&page=2
?paginate=false  // Disable pagination
```

### Eager Loading
```
?with=relation1,relation2
?with=relation:field1,field2  // With field selection
?with_count=relation1,relation2  // Include counts
```

## Security Considerations

### 1. Field Validation
Define `$allowedFields` in repositories to prevent unauthorized field access:
```php
protected array $allowedFields = ['id', 'name', 'email'];
```

### 2. Relation Validation
Define `$allowedRelations` to control eager loading:
```php
protected array $allowedRelations = ['profile', 'roles'];
```

### 3. Tenant Isolation
Automatically apply tenant scoping:
```php
$config->tenantId = auth()->user()->tenant_id;
```

### 4. Rate Limiting
Apply to all API endpoints:
```php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Routes
});
```

## Testing Examples

### Unit Test Example
```php
use Tests\TestCase;
use App\DTOs\QueryConfig;
use App\Modules\Inventory\Repositories\ProductRepository;

class ProductRepositoryTest extends TestCase
{
    public function test_query_with_filters()
    {
        $config = new QueryConfig();
        $config->filters = ['status' => 'active'];
        $config->perPage = 10;

        $repository = new ProductRepository(new Product());
        $results = $repository->query($config);

        $this->assertCount(10, $results->items());
        $this->assertTrue($results->every(fn($p) => $p->status === 'active'));
    }
}
```

### Integration Test Example
```php
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_with_search_and_filters()
    {
        $response = $this->getJson('/api/products?search=laptop&filters[status]=active&per_page=20');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'data' => [],
                         'current_page',
                         'per_page',
                         'total'
                     ]
                 ]);
    }
}
```

## Migration Guide

### Converting Existing Modules

1. **Update Repository**:
```php
// Before
class ProductRepository
{
    public function __construct(Product $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->all();
    }
}

// After
class ProductRepository extends BaseRepository
{
    protected array $allowedFields = ['id', 'name', 'price'];
    protected array $searchableFields = ['name', 'sku'];

    public function __construct(Product $model)
    {
        parent::__construct($model);
    }
}
```

2. **Update Service**:
```php
// Before
class ProductService
{
    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAllProducts()
    {
        return $this->repository->getAll();
    }
}

// After
class ProductService extends BaseService
{
    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }
    
    // Inherit all methods from BaseService
}
```

3. **Update Controller**:
```php
// Option 1: Extend GenericCrudController
class ProductController extends GenericCrudController
{
    protected string $resourceClass = ProductResource::class;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }
}

// Option 2: Use in existing controller
class ProductController extends BaseController
{
    public function index(Request $request)
    {
        $config = $this->buildQueryConfig($request);
        $products = $this->service->query($config);
        return $this->success(ProductResource::collection($products));
    }
}
```

## Best Practices

1. **Always define allowed fields and relations** in repositories for security
2. **Use transactions** for all write operations (already handled by BaseService)
3. **Validate input** using Form Request classes before passing to services
4. **Use Resource classes** for consistent API responses
5. **Enable pagination** for list endpoints to prevent performance issues
6. **Document** which fields and relations are available for your API
7. **Log** all service operations for audit trails (already handled by BaseService)
8. **Test** all query scenarios including edge cases

## Performance Optimization

### 1. Eager Loading
Always specify relations to avoid N+1 queries:
```
?with=category,supplier
```

### 2. Field Selection
Select only needed fields:
```
?select=id,name,price
```

### 3. Pagination
Use reasonable page sizes:
```
?per_page=25
```

### 4. Indexing
Ensure database indexes on frequently filtered and sorted fields:
```php
$table->index(['status', 'created_at']);
```

### 5. Caching
Consider caching frequently accessed data (implement in service layer)

## Troubleshooting

### Error: "Invalid query config: Invalid select field: xxx"
**Solution**: Add the field to `$allowedFields` in your repository

### Error: "Invalid relation: xxx"
**Solution**: Add the relation to `$allowedRelations` in your repository

### Performance Issues with Large Datasets
**Solutions**:
- Enable pagination
- Use field selection
- Add database indexes
- Implement caching

## Summary

This framework provides:
- ✅ **Fully Dynamic CRUD**: Configuration-driven, not hardcoded
- ✅ **Advanced Queries**: Search, filter, sort, paginate, eager load
- ✅ **Clean Architecture**: Controller → Service → Repository
- ✅ **Secure**: Field and relation validation, tenant isolation
- ✅ **Scalable**: Optimized for performance and maintainability
- ✅ **Reusable**: Minimal code for new modules
- ✅ **Testable**: Clean separation of concerns
- ✅ **Production-Ready**: Transaction support, logging, error handling

The framework is ready for use across all ERP-grade SaaS modules and supports autonomous AI development workflows.
