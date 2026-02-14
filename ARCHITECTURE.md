# AutoERP - Architecture Documentation

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Architectural Patterns](#architectural-patterns)
3. [Module Structure](#module-structure)
4. [Multi-Tenancy Architecture](#multi-tenancy-architecture)
5. [Database Architecture](#database-architecture)
6. [API Architecture](#api-architecture)
7. [Frontend Architecture](#frontend-architecture)
8. [Security Architecture](#security-architecture)
9. [Implementation Guidelines](#implementation-guidelines)

---

## Architecture Overview

The AutoERP platform follows a **Clean Architecture** approach with strict separation of concerns across multiple layers:

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                        │
│   (Controllers, API Resources, Form Requests, Middleware)   │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                   Application Layer                          │
│        (Services, Use Cases, DTOs, Events, Listeners)        │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                     Domain Layer                             │
│     (Models, Business Logic, Domain Events, Policies)        │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                  Infrastructure Layer                        │
│    (Repositories, External Services, Database, Cache, Queue) │
└─────────────────────────────────────────────────────────────┘
```

### Key Principles

1. **Controller → Service → Repository Pattern**
2. **SOLID Principles** (Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion)
3. **DRY** (Don't Repeat Yourself)
4. **KISS** (Keep It Simple, Stupid)
5. **Separation of Concerns**
6. **Dependency Injection**
7. **Event-Driven Architecture**

---

## Architectural Patterns

### 1. Controller → Service → Repository Pattern

#### Controller Layer (Thin)
- Handles HTTP request/response
- Validates input using Form Requests
- Authorizes actions using Policies
- Delegates to Service layer
- Transforms responses using API Resources

#### Service Layer (Business Logic)
- Implements business logic
- Manages transactions
- Orchestrates cross-module communication
- Dispatches domain events
- Handles error management

#### Repository Layer (Data Access)
- Abstracts data access
- Encapsulates query logic
- Optimizes database queries
- No business logic

### 2. Multi-Tenant Architecture

**Strategy**: Single Database with Tenant ID

All tenant-aware tables include `tenant_id` column with global scopes for automatic filtering.

```php
// Tenant Global Scope
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (auth()->check() && auth()->user()->tenant_id) {
            $builder->where($model->getTable() . '.tenant_id', 
                auth()->user()->tenant_id);
        }
    }
}

// Applied via Trait
trait HasTenant
{
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope());
        
        static::creating(function ($model) {
            if (auth()->check() && !$model->tenant_id) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
```

### 3. Event-Driven Architecture

Domain events for loose coupling and asynchronous processing:

```php
// Define Event
class OrderCreated
{
    public function __construct(public Order $order) {}
}

// Define Listener
class SendOrderConfirmationEmail
{
    public function handle(OrderCreated $event)
    {
        // Send email
    }
}

// Dispatch in Service
public function createOrder(array $data): Order
{
    DB::beginTransaction();
    try {
        $order = $this->repository->create($data);
        event(new OrderCreated($order));
        DB::commit();
        return $order;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

---

## Module Structure

```
Modules/
├── Tenancy/              # Multi-tenancy management
│   ├── Models/
│   ├── Repositories/
│   ├── Services/
│   ├── Http/
│   ├── Events/
│   ├── Policies/
│   ├── Database/
│   └── Tests/
│
├── IAM/                  # Identity & Access Management
│   ├── Models/
│   ├── Repositories/
│   ├── Services/
│   ├── Http/
│   ├── Events/
│   ├── Policies/
│   ├── Database/
│   └── Tests/
│
├── CRM/                  # Customer Relationship Management
├── Inventory/            # Inventory & Procurement
├── Billing/              # Invoicing & Payments
├── POS/                  # Point of Sale
├── Fleet/                # Fleet Management (optional)
├── Analytics/            # Reporting & Analytics
└── Settings/             # System Configuration
```

### Module Internal Structure

Each module follows this structure:

```
ModuleName/
├── Models/               # Eloquent models
├── Repositories/
│   ├── Contracts/        # Repository interfaces
│   └── Eloquent/         # Eloquent implementations
├── Services/             # Business logic
├── DTOs/                 # Data Transfer Objects
├── Http/
│   ├── Controllers/      # API controllers
│   ├── Requests/         # Form validation
│   └── Resources/        # API responses
├── Events/               # Domain events
├── Listeners/            # Event handlers
├── Policies/             # Authorization
├── Database/
│   ├── Migrations/
│   ├── Seeders/
│   └── Factories/
├── Tests/
│   ├── Feature/
│   └── Unit/
├── Routes/
│   └── api.php
└── README.md            # Module documentation
```

---

## Multi-Tenancy Architecture

### Tenant Isolation Strategy

1. **Database Level**: Single database with `tenant_id` column
2. **Application Level**: Global scopes on all tenant-aware models
3. **Cache Level**: Tenant-prefixed cache keys
4. **Queue Level**: Tenant context in queued jobs
5. **API Level**: Tenant identification via subdomain or header

### Tenant Context Management

```php
// Middleware
class SetTenantContext
{
    public function handle($request, Closure $next)
    {
        if ($user = auth()->user()) {
            app()->instance('tenant_id', $user->tenant_id);
        }
        
        return $next($request);
    }
}

// Cache Helper
function tenant_cache_key(string $key): string
{
    $tenantId = app('tenant_id');
    return "tenant:{$tenantId}:{$key}";
}
```

### Multi-Dimensional Support

- **Multi-Organization**: Organizations within tenants
- **Multi-Vendor**: Multiple vendors per tenant
- **Multi-Branch**: Multiple branches/locations
- **Multi-Currency**: Currency support with exchange rates
- **Multi-Language**: i18n with locale fallback
- **Multi-Unit**: Unit of measure conversions

---

## Database Architecture

### Schema Design Principles

1. **Normalization**: Properly normalized with JSONB for flexible attributes
2. **Tenant Awareness**: All tenant-aware tables include `tenant_id`
3. **Soft Deletes**: For audit trails and data retention
4. **Timestamps**: `created_at`, `updated_at` on all tables
5. **Indexing**: Multi-tenant queries properly indexed
6. **Foreign Keys**: Enforced with cascading rules

### Append-Only Ledger Pattern

For critical data (inventory, transactions):

```php
// Stock Movement (append-only)
Schema::create('stock_movements', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained();
    $table->foreignId('sku_id')->constrained();
    $table->foreignId('location_id')->constrained();
    $table->enum('type', ['receipt', 'sales', 'transfer', 'adjustment', 'return']);
    $table->decimal('quantity', 15, 4);
    $table->string('reference_type');
    $table->unsignedBigInteger('reference_id');
    $table->json('metadata')->nullable();
    $table->timestamps();
    
    $table->index(['tenant_id', 'sku_id', 'location_id']);
    $table->index(['reference_type', 'reference_id']);
});

// Derived Stock Balance (read-optimized)
Schema::create('stock_balances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained();
    $table->foreignId('sku_id')->constrained();
    $table->foreignId('location_id')->constrained();
    $table->decimal('quantity', 15, 4);
    $table->decimal('reserved_quantity', 15, 4)->default(0);
    $table->timestamp('last_movement_at');
    $table->timestamps();
    
    $table->unique(['tenant_id', 'sku_id', 'location_id']);
});
```

---

## API Architecture

### RESTful API Design

```
POST   /api/v1/{resource}           # Create
GET    /api/v1/{resource}           # List (with pagination)
GET    /api/v1/{resource}/{id}      # Show
PUT    /api/v1/{resource}/{id}      # Update
DELETE /api/v1/{resource}/{id}      # Delete
```

### API Response Format

```json
{
  "data": {
    "id": 1,
    "name": "Product Name",
    "attributes": {}
  },
  "meta": {
    "timestamp": "2026-02-02T14:18:45Z"
  },
  "links": {
    "self": "/api/v1/products/1"
  }
}
```

### API Features

- Pagination (cursor-based and offset-based)
- Filtering
- Sorting
- Sparse fieldsets
- Including related resources
- Rate limiting
- Versioning (v1, v2)

---

## Frontend Architecture

### Vue.js 3 + TypeScript

```
frontend/
├── src/
│   ├── modules/          # Feature-based modules
│   │   ├── auth/
│   │   ├── crm/
│   │   ├── inventory/
│   │   └── ...
│   ├── stores/           # Pinia state management
│   ├── router/           # Vue Router
│   ├── composables/      # Reusable composition functions
│   ├── components/       # Shared components
│   ├── layouts/          # Layout components
│   ├── locales/          # i18n translations
│   ├── types/            # TypeScript types
│   └── utils/            # Utility functions
```

### State Management (Pinia)

```typescript
// stores/productStore.ts
import { defineStore } from 'pinia'
import type { Product } from '@/types/models'

export const useProductStore = defineStore('product', {
  state: () => ({
    products: [] as Product[],
    loading: false,
    error: null as string | null
  }),
  
  actions: {
    async fetchProducts() {
      this.loading = true
      try {
        const response = await api.get('/products')
        this.products = response.data.data
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    }
  }
})
```

---

## Security Architecture

### Authentication

- **Session-based** (web): Laravel built-in
- **Token-based** (API): Laravel Sanctum
- **MFA**: TOTP, SMS, Email

### Authorization

- **RBAC** (Role-Based Access Control): via Spatie Laravel Permission
- **ABAC** (Attribute-Based Access Control): context-aware policies
- **Policies**: Laravel Policy classes for all models

### Security Measures

1. **HTTPS enforcement**
2. **Encryption at rest**: database encryption
3. **Encryption in transit**: TLS 1.3
4. **Rate limiting**: API throttling
5. **CORS configuration**
6. **Security headers**: CSP, HSTS, X-Frame-Options
7. **Input validation**: strict validation rules
8. **Output encoding**: XSS prevention
9. **SQL injection prevention**: parameterized queries
10. **CSRF protection**: built-in Laravel protection

### Audit Trail

```php
// Audit Log
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained();
    $table->foreignId('user_id')->nullable()->constrained();
    $table->string('action'); // create, update, delete
    $table->string('model_type');
    $table->unsignedBigInteger('model_id');
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->ipAddress('ip_address');
    $table->string('user_agent')->nullable();
    $table->timestamps();
    
    $table->index(['tenant_id', 'model_type', 'model_id']);
    $table->index('created_at');
});
```

---

## Implementation Guidelines

### 1. Creating a New Module

```bash
# Create module structure
php artisan module:make CRM

# This creates:
# Modules/CRM/
#   ├── Models/
#   ├── Repositories/
#   ├── Services/
#   ├── Http/
#   ├── Events/
#   ├── Policies/
#   └── Database/
```

### 2. Implementing Controller → Service → Repository

**Step 1: Create Repository Interface**

```php
// Modules/CRM/Repositories/Contracts/CustomerRepositoryInterface.php
interface CustomerRepositoryInterface
{
    public function getAll(array $filters = []): Collection;
    public function find(int $id): ?Customer;
    public function create(array $data): Customer;
    public function update(Customer $customer, array $data): Customer;
    public function delete(Customer $customer): bool;
}
```

**Step 2: Implement Repository**

```php
// Modules/CRM/Repositories/Eloquent/CustomerRepository.php
class CustomerRepository implements CustomerRepositoryInterface
{
    public function getAll(array $filters = []): Collection
    {
        return Customer::query()
            ->when(isset($filters['search']), 
                fn($q) => $q->where('name', 'like', "%{$filters['search']}%"))
            ->when(isset($filters['status']), 
                fn($q) => $q->where('status', $filters['status']))
            ->get();
    }
    
    public function find(int $id): ?Customer
    {
        return Customer::find($id);
    }
    
    public function create(array $data): Customer
    {
        return Customer::create($data);
    }
    
    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer->fresh();
    }
    
    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }
}
```

**Step 3: Create Service**

```php
// Modules/CRM/Services/CustomerService.php
class CustomerService
{
    public function __construct(
        private CustomerRepositoryInterface $repository
    ) {}
    
    public function getAllCustomers(array $filters = []): Collection
    {
        return $this->repository->getAll($filters);
    }
    
    public function getCustomer(int $id): Customer
    {
        $customer = $this->repository->find($id);
        
        if (!$customer) {
            throw new ModelNotFoundException('Customer not found');
        }
        
        return $customer;
    }
    
    public function createCustomer(array $data): Customer
    {
        DB::beginTransaction();
        try {
            $customer = $this->repository->create($data);
            event(new CustomerCreated($customer));
            DB::commit();
            return $customer;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function updateCustomer(int $id, array $data): Customer
    {
        DB::beginTransaction();
        try {
            $customer = $this->getCustomer($id);
            $updatedCustomer = $this->repository->update($customer, $data);
            event(new CustomerUpdated($updatedCustomer));
            DB::commit();
            return $updatedCustomer;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function deleteCustomer(int $id): bool
    {
        DB::beginTransaction();
        try {
            $customer = $this->getCustomer($id);
            $result = $this->repository->delete($customer);
            event(new CustomerDeleted($customer));
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

**Step 4: Create Controller**

```php
// Modules/CRM/Http/Controllers/CustomerController.php
class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $service
    ) {
        $this->authorizeResource(Customer::class);
    }
    
    public function index(Request $request)
    {
        $customers = $this->service->getAllCustomers($request->all());
        return CustomerResource::collection($customers);
    }
    
    public function show(int $id)
    {
        $customer = $this->service->getCustomer($id);
        return new CustomerResource($customer);
    }
    
    public function store(StoreCustomerRequest $request)
    {
        $customer = $this->service->createCustomer($request->validated());
        return new CustomerResource($customer);
    }
    
    public function update(UpdateCustomerRequest $request, int $id)
    {
        $customer = $this->service->updateCustomer($id, $request->validated());
        return new CustomerResource($customer);
    }
    
    public function destroy(int $id)
    {
        $this->service->deleteCustomer($id);
        return response()->json(null, 204);
    }
}
```

### 3. Testing Guidelines

**Unit Test (Repository)**

```php
class CustomerRepositoryTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_create_customer()
    {
        $repository = new CustomerRepository();
        $data = ['name' => 'Test Customer', 'email' => 'test@example.com'];
        
        $customer = $repository->create($data);
        
        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertDatabaseHas('customers', $data);
    }
}
```

**Feature Test (API)**

```php
class CustomerApiTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_list_customers()
    {
        Customer::factory()->count(5)->create();
        
        $response = $this->getJson('/api/v1/customers');
        
        $response->assertStatus(200)
                 ->assertJsonCount(5, 'data');
    }
}
```

---

## Conclusion

This architecture provides a solid foundation for building a scalable, maintainable, and extensible enterprise SaaS ERP platform. Follow these patterns and guidelines consistently across all modules to ensure code quality and long-term maintainability.

For detailed module-specific documentation, refer to the README.md file in each module directory.

---

**Document Version**: 1.0  
**Last Updated**: 2026-02-02  
**Maintainer**: AutoERP Development Team
