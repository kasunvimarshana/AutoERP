# Dynamic CRUD Framework Documentation

## Overview

This document describes the **production-ready, reusable backend CRUD framework** that provides fully dynamic, customizable, and extensible capabilities while adhering to Clean Architecture and the Controller → Service → Repository pattern.

## Features

The framework provides:

✅ **Global and field-level search** - Search across multiple fields or specific columns  
✅ **Advanced filtering** - Filter by exact match, partial match, ranges, or custom logic  
✅ **Relation-based filters** - Filter based on related model data  
✅ **Multi-field sorting** - Sort by multiple columns in ascending or descending order  
✅ **Sparse field selection** - Return only requested fields to optimize payload size  
✅ **Configurable eager loading** - Load related models on-demand with nested support  
✅ **Pagination** - Configurable page size with full metadata  
✅ **Tenant-aware** - Automatic tenant scoping through global scopes  
✅ **Secure** - Built-in validation and consistent error handling  
✅ **Scalable** - Configuration-driven, no hardcoded logic

## Architecture

### Three-Layer Pattern

```
┌─────────────────────────────────────────┐
│         BaseCrudController              │  ← HTTP Layer
│  - Request validation                   │
│  - Response formatting                  │
│  - Error handling                       │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│            BaseService                  │  ← Business Logic Layer
│  - Transaction management               │
│  - Business rules                       │
│  - Cross-cutting concerns               │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│         BaseRepository                  │  ← Data Access Layer
│  - CRUD operations                      │
│  - Query building                       │
│  - Spatie Query Builder integration     │
└─────────────────────────────────────────┘
```

## Core Components

### 1. BaseRepository

**Location**: `app/Core/Repositories/BaseRepository.php`

Provides standard CRUD operations plus advanced query capabilities:

#### Standard Methods

```php
// Basic CRUD
$repository->find(int $id): ?Model
$repository->findOrFail(int $id): Model
$repository->all(): Collection
$repository->paginate(int $perPage = 15): LengthAwarePaginator
$repository->create(array $attributes): Model
$repository->update(int $id, array $attributes): bool
$repository->delete(int $id): bool

// Basic filtering
$repository->findWhere(array $criteria): Collection
$repository->findWhereFirst(array $criteria): ?Model
```

#### Advanced Query Methods

```php
// Advanced query with Spatie Query Builder
$repository->queryWithFilters(array $config): QueryBuilder

// Paginate with filters
$repository->paginateWithFilters(array $config): LengthAwarePaginator

// Get all with filters
$repository->getAllWithFilters(array $config): Collection
```

### 2. BaseCrudController

**Location**: `app/Core/Http/Controllers/BaseCrudController.php`

Complete RESTful CRUD implementation with:

- `index()` - List resources with filtering, sorting, pagination
- `store()` - Create new resource with validation
- `show()` - Retrieve single resource with eager loading
- `update()` - Update resource with validation
- `destroy()` - Delete resource

### 3. BaseService

**Location**: `app/Core/Services/BaseService.php`

Orchestrates business logic with automatic transaction management.

## Usage Guide

### Step 1: Create a Controller

Extend `BaseCrudController` and implement two methods:

```php
<?php

namespace App\Modules\YourModule\Http\Controllers;

use App\Core\Http\Controllers\BaseCrudController;
use App\Modules\YourModule\Services\YourService;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

class YourController extends BaseCrudController
{
    public function __construct(YourService $service)
    {
        parent::__construct($service);
    }

    /**
     * Define query configuration
     */
    protected function getQueryConfig(): array
    {
        return [
            // Fields that can be filtered
            'allowedFilters' => [
                'name',              // Partial match on name
                'email',             // Partial match on email
                AllowedFilter::exact('status'), // Exact match on status
                AllowedFilter::scope('active'), // Use model scope
                
                // Custom callback filter
                AllowedFilter::callback('created_after', function ($query, $value) {
                    $query->where('created_at', '>=', $value);
                }),
                
                // Relation filter
                AllowedFilter::callback('has_items', function ($query, $value) {
                    if ($value === 'true') {
                        $query->has('items');
                    }
                }),
            ],

            // Fields that can be sorted
            'allowedSorts' => [
                'name',
                'created_at',
                'updated_at',
                AllowedSort::field('organization_name', 'organizations.name'),
            ],

            // Relations that can be eager loaded
            'allowedIncludes' => [
                'organization',
                'branch',
                'items',
                'items.category', // Nested includes
            ],

            // Fields that can be selected (sparse fieldsets)
            'allowedFields' => [
                'your_models' => ['id', 'name', 'email', 'status'],
                'organizations' => ['id', 'name'],
            ],

            // Default sort order
            'defaultSort' => '-created_at',

            // Default items per page
            'perPage' => 15,

            // Fields to search globally
            'globalSearch' => ['name', 'email', 'description'],
        ];
    }

    /**
     * Define validation rules
     */
    protected function getValidationRules(string $action, $id = null): array
    {
        if ($action === 'create') {
            return [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:your_table,email',
                'status' => 'sometimes|in:active,inactive',
            ];
        }

        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:your_table,email,' . $id,
            'status' => 'sometimes|in:active,inactive',
        ];
    }
}
```

### Step 2: Register Routes

```php
// routes/api.php
use App\Modules\YourModule\Http\Controllers\YourController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('your-resources', YourController::class);
});
```

### Step 3: Use the API

All endpoints are automatically available with advanced features!

## API Examples

### 1. Basic Listing

```http
GET /api/v1/customers-enhanced
```

Response:
```json
{
  "success": true,
  "message": "Resources retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [...],
    "total": 100,
    "per_page": 15
  }
}
```

### 2. Field-Level Filtering

Filter by exact status:
```http
GET /api/v1/customers-enhanced?filter[status]=active
```

Filter by partial name match:
```http
GET /api/v1/customers-enhanced?filter[name]=john
```

Multiple filters:
```http
GET /api/v1/customers-enhanced?filter[status]=active&filter[type]=individual
```

### 3. Global Search

Search across all configured fields:
```http
GET /api/v1/customers-enhanced?search=john
```

This searches `name`, `email`, `phone`, and `customer_code` fields.

### 4. Multi-Field Sorting

Sort by name ascending:
```http
GET /api/v1/customers-enhanced?sort=name
```

Sort by created_at descending (note the `-` prefix):
```http
GET /api/v1/customers-enhanced?sort=-created_at
```

Multiple sorts:
```http
GET /api/v1/customers-enhanced?sort=-created_at,name
```

### 5. Eager Loading Relations

Load organization and branch:
```http
GET /api/v1/customers-enhanced?include=organization,branch
```

Load nested relations:
```http
GET /api/v1/customers-enhanced?include=vehicles,vehicles.serviceHistory
```

### 6. Sparse Fieldsets

Return only specific fields:
```http
GET /api/v1/customers-enhanced?fields[customers]=id,name,email
```

For multiple resources:
```http
GET /api/v1/customers-enhanced?fields[customers]=id,name&fields[organizations]=id,name
```

### 7. Pagination

```http
GET /api/v1/customers-enhanced?page[number]=2&page[size]=20
```

Or using legacy format:
```http
GET /api/v1/customers-enhanced?per_page=20
```

### 8. Combined Query

All features together:
```http
GET /api/v1/customers-enhanced
  ?filter[status]=active
  &filter[type]=individual
  &search=john
  &sort=-created_at,name
  &include=organization,branch,vehicles
  &fields[customers]=id,name,email,status
  &page[number]=1
  &page[size]=20
```

### 9. Custom Date Range Filter

```http
GET /api/v1/customers-enhanced
  ?filter[created_after]=2024-01-01
  &filter[created_before]=2024-12-31
```

### 10. Relation-Based Filter

Get customers who have vehicles:
```http
GET /api/v1/customers-enhanced?filter[has_vehicles]=true
```

## Advanced Features

### Custom Callback Filters

Define complex filtering logic:

```php
'allowedFilters' => [
    // Price range filter
    AllowedFilter::callback('price_range', function ($query, $value) {
        [$min, $max] = explode(',', $value);
        $query->whereBetween('price', [(float)$min, (float)$max]);
    }),
    
    // Date range filter
    AllowedFilter::callback('date_range', function ($query, $value) {
        [$start, $end] = explode(',', $value);
        $query->whereBetween('created_at', [$start, $end]);
    }),
    
    // Complex relation filter
    AllowedFilter::callback('with_pending_orders', function ($query, $value) {
        if ($value === 'true') {
            $query->whereHas('orders', function ($q) {
                $q->where('status', 'pending');
            });
        }
    }),
],
```

### Model Scopes as Filters

Define scopes in your model:

```php
// In your model
public function scopeActive($query)
{
    return $query->where('status', 'active');
}

public function scopePremium($query)
{
    return $query->where('tier', 'premium');
}
```

Then use them:

```php
'allowedFilters' => [
    AllowedFilter::scope('active'),
    AllowedFilter::scope('premium'),
],
```

Query:
```http
GET /api/v1/customers-enhanced?filter[active]=true&filter[premium]=true
```

### Custom Sorts

```php
'allowedSorts' => [
    'name',
    // Sort by related model field
    AllowedSort::field('organization_name', 'organizations.name'),
    
    // Custom sort logic
    AllowedSort::custom('popularity', function ($query, $descending) {
        $direction = $descending ? 'DESC' : 'ASC';
        $query->orderBy('views_count', $direction)
              ->orderBy('likes_count', $direction);
    }),
],
```

## Response Format

### Success Response

```json
{
  "success": true,
  "message": "Resources retrieved successfully",
  "data": {
    // Resource data or paginated data
  }
}
```

### Error Response

```json
{
  "error": true,
  "message": "Error description",
  "code": 500
}
```

### Validation Error Response

```json
{
  "error": true,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "name": ["The name field is required."]
  },
  "code": 422
}
```

## Security Features

1. **Tenant Isolation**: Automatic tenant scoping via global scopes
2. **Whitelist Approach**: Only explicitly allowed filters/sorts/includes work
3. **Validation**: All inputs validated before processing
4. **SQL Injection Protection**: Eloquent ORM prevents injection
5. **Rate Limiting**: Apply at route/middleware level
6. **Authentication**: Sanctum token-based auth

## Performance Optimization

### Eager Loading

Prevent N+1 queries by eager loading:

```php
'allowedIncludes' => [
    'organization',
    'branch',
    'items',
],
```

Request: `?include=organization,branch,items`

### Sparse Fieldsets

Reduce payload size:

```php
'allowedFields' => [
    'customers' => ['id', 'name', 'email'],
],
```

Request: `?fields[customers]=id,name,email`

### Pagination

Always paginate large datasets:

```php
'perPage' => 15, // Default page size
```

Request: `?page[size]=50` (max should be enforced)

## Testing

Example test for enhanced CRUD:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerEnhancedTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_filter_by_status()
    {
        Customer::factory()->create(['status' => 'active']);
        Customer::factory()->create(['status' => 'inactive']);

        $response = $this->getJson('/api/v1/customers-enhanced?filter[status]=active');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data.data');
    }

    public function test_can_sort_by_multiple_fields()
    {
        $response = $this->getJson('/api/v1/customers-enhanced?sort=-created_at,name');

        $response->assertStatus(200);
    }

    public function test_can_eager_load_relations()
    {
        $response = $this->getJson('/api/v1/customers-enhanced?include=organization,branch');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'data' => [
                             '*' => ['organization', 'branch']
                         ]
                     ]
                 ]);
    }
}
```

## Example Implementation

See `app/Modules/CRM/Http/Controllers/CustomerEnhancedController.php` for a complete, working example demonstrating all features.

## Best Practices

1. **Always define allowed filters/sorts/includes** - Never allow arbitrary fields
2. **Use exact filters for enums** - `AllowedFilter::exact('status')`
3. **Validate all inputs** - Define comprehensive validation rules
4. **Limit page size** - Prevent memory issues with max page size
5. **Index database columns** - Index fields used in filters and sorts
6. **Use eager loading** - Prevent N+1 query problems
7. **Monitor performance** - Log slow queries and optimize
8. **Document your API** - Use Swagger/OpenAPI annotations

## Extending the Framework

### Add Custom Methods

You can add custom endpoints alongside CRUD:

```php
class CustomerEnhancedController extends BaseCrudController
{
    // ... existing code ...

    public function export(Request $request): BinaryFileResponse
    {
        $config = $this->getQueryConfig();
        $customers = $this->service->repository->getAllWithFilters($config);
        
        // Export logic...
        return response()->download($filePath);
    }
}
```

### Override Base Methods

Customize behavior while keeping the framework:

```php
public function store(Request $request): JsonResponse
{
    // Custom logic before
    $validated = $this->validateRequest($request, 'create');
    $validated['customer_code'] = $this->generateCustomerCode();
    
    // Call parent or implement custom
    $resource = $this->service->repository->create($validated);
    
    // Custom logic after
    event(new CustomerCreated($resource));
    
    return $this->successResponse($resource, 'Customer created successfully', 201);
}
```

## Conclusion

This CRUD framework provides a **production-ready, scalable, and maintainable** foundation for building RESTful APIs with advanced query capabilities. It follows Clean Architecture principles, maintains strict separation of concerns, and enables rapid development while ensuring consistency across your application.

For questions or contributions, please refer to the main documentation or open an issue.
