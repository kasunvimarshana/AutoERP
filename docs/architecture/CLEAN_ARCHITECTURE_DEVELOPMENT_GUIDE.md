# Clean Architecture Development Guide

**Purpose**: Guide for developers implementing new features following Clean Architecture principles

## Quick Reference

### The Three-Layer Pattern

```
Controller → Service → Repository → Database
```

**Controller**: HTTP concerns, authorization, tenant extraction  
**Service**: Business logic, workflow orchestration  
**Repository**: Data access, query building  

## Step-by-Step Implementation Guide

### Step 1: Create the Repository

**Location**: `modules/{ModuleName}/Repositories/{EntityName}Repository.php`

```php
<?php

declare(strict_types=1);

namespace Modules\{ModuleName}\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\{ModuleName}\Models\{EntityName};

/**
 * {EntityName} Repository
 *
 * Handles data access operations for {entity description}.
 */
class {EntityName}Repository extends BaseRepository
{
    protected function makeModel(): Model
    {
        return new {EntityName};
    }

    /**
     * Search {entities} with filters.
     *
     * @param  array  $filters  Search filters (must include tenant_id)
     * @param  int  $perPage  Results per page
     *
     * @throws \InvalidArgumentException if tenant_id is not provided
     */
    public function search{EntityName}s(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        // CRITICAL: Always enforce tenant isolation
        if (empty($filters['tenant_id'])) {
            throw new \InvalidArgumentException(
                'tenant_id is required to maintain tenant isolation'
            );
        }

        $query = $this->model->query()
            ->with([/* relationships */])
            ->where('tenant_id', $filters['tenant_id']);

        // Apply additional filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        return $query->latest()->paginate($perPage);
    }
}
```

### Step 2: Create the Service

**Location**: `modules/{ModuleName}/Services/{EntityName}Service.php`

```php
<?php

declare(strict_types=1);

namespace Modules\{ModuleName}\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Helpers\TransactionHelper;
use Modules\{ModuleName}\Models\{EntityName};
use Modules\{ModuleName}\Repositories\{EntityName}Repository;

/**
 * {EntityName} Service
 *
 * Handles business logic for {entity description}.
 */
class {EntityName}Service
{
    public function __construct(
        private {EntityName}Repository ${entity}Repository
    ) {}

    /**
     * Get paginated {entities} with filters.
     *
     * @param  string  $tenantId  Tenant ID (REQUIRED for security)
     * @param  array  $filters  Additional filters
     * @param  int  $perPage  Results per page
     */
    public function getPaginated{EntityName}s(
        string $tenantId,
        array $filters,
        int $perPage = 15
    ): LengthAwarePaginator {
        // CRITICAL: Always set tenant_id
        $filters['tenant_id'] = $tenantId;
        
        return $this->{entity}Repository->search{EntityName}s($filters, $perPage);
    }

    /**
     * Create new {entity}.
     */
    public function create(array $data): {EntityName}
    {
        return TransactionHelper::execute(function () use ($data) {
            // Business logic here
            // Validation
            // Event dispatching
            
            return $this->{entity}Repository->create($data);
        });
    }

    /**
     * Update {entity}.
     */
    public function update(string $id, array $data): {EntityName}
    {
        return TransactionHelper::execute(function () use ($id, $data) {
            ${entity} = $this->{entity}Repository->findOrFail($id);
            
            // Business logic here
            // Validation
            // Event dispatching
            
            $this->{entity}Repository->update($id, $data);
            
            return ${entity}->fresh();
        });
    }

    /**
     * Delete {entity}.
     */
    public function delete(string $id): bool
    {
        ${entity} = $this->{entity}Repository->findOrFail($id);
        
        // Business logic/validation here
        
        return $this->{entity}Repository->delete($id);
    }
}
```

### Step 3: Create the Controller

**Location**: `modules/{ModuleName}/Http/Controllers/{EntityName}Controller.php`

```php
<?php

declare(strict_types=1);

namespace Modules\{ModuleName}\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\{ModuleName}\Http\Resources\{EntityName}Resource;
use Modules\{ModuleName}\Models\{EntityName};
use Modules\{ModuleName}\Services\{EntityName}Service;

/**
 * {EntityName} Controller
 *
 * Handles HTTP requests for {entity description}.
 */
class {EntityName}Controller extends Controller
{
    public function __construct(
        private {EntityName}Service ${entity}Service
    ) {}

    /**
     * Display a listing of {entities}.
     */
    public function index(Request $request): JsonResponse
    {
        // STEP 1: Authorization
        $this->authorize('viewAny', {EntityName}::class);

        // STEP 2: Extract tenant context (CRITICAL)
        $tenantId = $request->user()->currentTenant()->id;
        
        // STEP 3: Build filters from request
        $filters = [
            'status' => $request->get('status'),
            'organization_id' => $request->get('organization_id'),
            'search' => $request->get('search'),
        ];

        // STEP 4: Delegate to service (passing tenant_id)
        $perPage = $request->get('per_page', 15);
        ${entities} = $this->{entity}Service->getPaginated{EntityName}s(
            $tenantId,
            $filters,
            $perPage
        );

        // STEP 5: Transform and return response
        return ApiResponse::paginated(
            ${entities}->setCollection(
                ${entities}->getCollection()->map(
                    fn (${entity}) => new {EntityName}Resource(${entity})
                )
            ),
            '{EntityName}s retrieved successfully'
        );
    }

    /**
     * Store a newly created {entity}.
     */
    public function store(Request $request): JsonResponse
    {
        // Validation should be in a FormRequest
        $data = $request->validated();
        
        // Add tenant and organization context
        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['organization_id'] = $data['organization_id'] 
            ?? $request->user()->currentOrganization()->id;

        ${entity} = $this->{entity}Service->create($data);

        return ApiResponse::created(
            new {EntityName}Resource(${entity}),
            '{EntityName} created successfully'
        );
    }

    /**
     * Display the specified {entity}.
     */
    public function show({EntityName} ${entity}): JsonResponse
    {
        $this->authorize('view', ${entity});

        ${entity}->load([/* relationships */]);

        return ApiResponse::success(
            new {EntityName}Resource(${entity}),
            '{EntityName} retrieved successfully'
        );
    }

    /**
     * Update the specified {entity}.
     */
    public function update(Request $request, {EntityName} ${entity}): JsonResponse
    {
        $this->authorize('update', ${entity});

        $data = $request->validated();

        ${entity} = $this->{entity}Service->update(${entity}->id, $data);

        return ApiResponse::success(
            new {EntityName}Resource(${entity}),
            '{EntityName} updated successfully'
        );
    }

    /**
     * Remove the specified {entity}.
     */
    public function destroy({EntityName} ${entity}): JsonResponse
    {
        $this->authorize('delete', ${entity});

        $this->{entity}Service->delete(${entity}->id);

        return ApiResponse::success(
            null,
            '{EntityName} deleted successfully'
        );
    }
}
```

## Security Checklist

When implementing any feature, ensure:

### ✅ Controller Level
- [ ] Authorization check using policies
- [ ] Extract `tenant_id` from authenticated user
- [ ] Pass `tenant_id` to service methods
- [ ] Use FormRequests for validation
- [ ] Transform responses with Resources

### ✅ Service Level
- [ ] Accept `tenant_id` as required parameter
- [ ] Set `tenant_id` in filters/data before calling repository
- [ ] Use `TransactionHelper::execute()` for data modifications
- [ ] Implement business logic and validation
- [ ] Dispatch domain events where appropriate

### ✅ Repository Level
- [ ] Validate `tenant_id` is present in filters
- [ ] Throw `InvalidArgumentException` if `tenant_id` is missing
- [ ] Apply `tenant_id` filter to all queries
- [ ] Use parameterized queries (no string interpolation)
- [ ] Validate dynamic parameters against allowlists

## Common Patterns

### Pattern 1: List with Filters

```php
// Controller
public function index(Request $request): JsonResponse
{
    $this->authorize('viewAny', Entity::class);
    $tenantId = $request->user()->currentTenant()->id;
    
    $filters = [/* extract from request */];
    $entities = $this->service->getPaginatedEntities($tenantId, $filters, $perPage);
    
    return ApiResponse::paginated(/* transform */);
}

// Service
public function getPaginatedEntities(string $tenantId, array $filters, int $perPage)
{
    $filters['tenant_id'] = $tenantId;
    return $this->repository->searchEntities($filters, $perPage);
}

// Repository
public function searchEntities(array $filters, int $perPage)
{
    if (empty($filters['tenant_id'])) {
        throw new \InvalidArgumentException('tenant_id required');
    }
    return $this->model->where('tenant_id', $filters['tenant_id'])->/* ... */;
}
```

### Pattern 2: Create with Validation

```php
// Controller
public function store(StoreEntityRequest $request): JsonResponse
{
    $data = $request->validated();
    $data['tenant_id'] = $request->user()->currentTenant()->id;
    
    $entity = $this->service->create($data);
    
    return ApiResponse::created(new EntityResource($entity));
}

// Service
public function create(array $data): Entity
{
    return TransactionHelper::execute(function () use ($data) {
        // Validate business rules
        $this->validateBusinessRules($data);
        
        // Create
        $entity = $this->repository->create($data);
        
        // Dispatch events
        event(new EntityCreated($entity));
        
        return $entity;
    });
}
```

### Pattern 3: Update with Optimistic Locking

```php
// Service
public function update(string $id, array $data): Entity
{
    return TransactionHelper::execute(function () use ($id, $data) {
        $entity = $this->repository->findOrFail($id);
        
        // Check version for optimistic locking
        if (isset($data['version']) && $data['version'] !== $entity->version) {
            throw new ConcurrencyException('Entity has been modified');
        }
        
        // Increment version
        $data['version'] = $entity->version + 1;
        
        $this->repository->update($id, $data);
        
        return $entity->fresh();
    });
}
```

## Anti-Patterns to Avoid

### ❌ DON'T: Direct Model Queries in Controller
```php
// WRONG
public function index(Request $request)
{
    $items = Item::where('tenant_id', $request->user()->tenant_id)->get();
    return $items;
}
```

### ❌ DON'T: Optional Tenant Filtering
```php
// WRONG
if (!empty($filters['tenant_id'])) {
    $query->where('tenant_id', $filters['tenant_id']);
}
```

### ❌ DON'T: String Interpolation in Queries
```php
// WRONG
DB::raw("status = '{$status}'")

// RIGHT
->where('status', $status)
```

### ❌ DON'T: Business Logic in Controllers
```php
// WRONG
public function store(Request $request)
{
    $data = $request->all();
    if ($data['quantity'] < 10) {
        $data['status'] = 'low_stock';
    }
    Item::create($data);
}

// RIGHT - Move to service
public function create(array $data): Item
{
    $data['status'] = $this->determineStatus($data['quantity']);
    return $this->repository->create($data);
}
```

### ❌ DON'T: Data Access in Services
```php
// WRONG
public function getItems()
{
    return DB::table('items')->get();
}

// RIGHT - Use repository
public function getItems(string $tenantId)
{
    return $this->repository->getAllItems($tenantId);
}
```

## Testing Guide

### Unit Test: Repository
```php
public function test_repository_enforces_tenant_isolation()
{
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('tenant_id is required');
    
    $this->repository->searchEntities([], 15);
}

public function test_repository_filters_by_tenant()
{
    $entity = Entity::factory()->create(['tenant_id' => 'tenant-1']);
    
    $results = $this->repository->searchEntities(
        ['tenant_id' => 'tenant-1'],
        15
    );
    
    $this->assertCount(1, $results);
    $this->assertEquals($entity->id, $results->first()->id);
}
```

### Unit Test: Service
```php
public function test_service_passes_tenant_id_to_repository()
{
    $this->repositoryMock
        ->shouldReceive('searchEntities')
        ->once()
        ->with(['tenant_id' => 'tenant-123'], 15)
        ->andReturn(collect());
    
    $this->service->getPaginatedEntities('tenant-123', [], 15);
}
```

### Integration Test: Controller
```php
public function test_tenant_isolation_in_list_endpoint()
{
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    $user1 = User::factory()->for($tenant1)->create();
    $user2 = User::factory()->for($tenant2)->create();
    
    $entity1 = Entity::factory()->for($tenant1)->create();
    $entity2 = Entity::factory()->for($tenant2)->create();
    
    // User 1 should only see tenant 1 entities
    $response = $this->actingAs($user1)->getJson('/api/entities');
    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $entity1->id);
    
    // User 2 should only see tenant 2 entities
    $response = $this->actingAs($user2)->getJson('/api/entities');
    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $entity2->id);
}
```

## Additional Resources

- **Base Repository**: `modules/Core/Repositories/BaseRepository.php`
- **Transaction Helper**: `modules/Core/Helpers/TransactionHelper.php`
- **API Response Helper**: `modules/Core/Http/Responses/ApiResponse.php`
- **Example Implementation**: `modules/Inventory/` (StockItem, Warehouse)

## Questions?

Refer to the Clean Architecture Remediation Report for architectural decisions and rationale.
