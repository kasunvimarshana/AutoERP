# AutoERP Architecture Guide

## System Architecture Overview

AutoERP follows a **clean architecture** approach with clear separation of concerns across multiple layers. This document provides detailed insights into the architectural decisions and patterns used.

## Layered Architecture

### 1. Presentation Layer (Controllers)

**Responsibility**: HTTP request/response handling, input validation, and API responses

**Location**: `app/Modules/*/Http/Controllers`

**Key Principles**:
- Controllers are thin and delegate business logic to services
- Use Form Request classes for validation
- Return standardized JSON responses using BaseController methods
- Handle authentication and authorization

**Example**:
```php
class CustomerController extends BaseController
{
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        try {
            $customer = $this->customerService->create($request->validated());
            return $this->created($customer, 'Customer created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
```

### 2. Business Logic Layer (Services)

**Responsibility**: Business rules, orchestration, transaction management, and event dispatching

**Location**: `app/Modules/*/Services`

**Key Principles**:
- Encapsulate complex business logic
- Orchestrate multiple repository calls
- Manage database transactions
- Dispatch domain events
- Implement cross-cutting concerns

**Transaction Pattern**:
```php
public function create(array $data): Model
{
    try {
        DB::beginTransaction();
        
        $record = $this->repository->create($data);
        $this->afterCreate($record, $data);
        
        DB::commit();
        
        event(new EntityCreated($record));
        
        return $record;
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Creation failed', ['error' => $e->getMessage()]);
        throw $e;
    }
}
```

### 3. Data Access Layer (Repositories)

**Responsibility**: Database queries, data retrieval, and persistence

**Location**: `app/Modules/*/Repositories`

**Key Principles**:
- Abstract database operations
- Provide clean API for data access
- Handle query optimization
- Return Eloquent collections or models

**Example**:
```php
class CustomerRepository extends BaseRepository
{
    public function search(array $criteria)
    {
        $query = $this->model->query();
        
        if (!empty($criteria['search'])) {
            $query->where('email', 'like', "%{$criteria['search']}%");
        }
        
        return $query->paginate($criteria['per_page'] ?? 15);
    }
}
```

### 4. Domain Layer (Models)

**Responsibility**: Data representation, relationships, and domain logic

**Location**: `app/Modules/*/Models`

**Key Principles**:
- Define relationships
- Implement scopes
- Add accessor/mutator methods
- Configure model behavior

## Module Structure

Each module follows a consistent structure:

```
Modules/
└── CustomerManagement/
    ├── Models/              # Eloquent models
    ├── Repositories/        # Data access layer
    ├── Services/            # Business logic
    ├── Http/
    │   ├── Controllers/     # HTTP controllers
    │   └── Requests/        # Form validation
    ├── Events/              # Domain events
    ├── Listeners/           # Event handlers
    ├── Policies/            # Authorization rules
    └── Database/
        └── Migrations/      # Database migrations
```

## Cross-Module Communication

### Event-Driven Communication

Modules communicate through events to maintain loose coupling:

```php
// In CustomerService
event(new CustomerCreated($customer));

// In NotificationListener
public function handle(CustomerCreated $event)
{
    Mail::to($event->customer->email)->send(new WelcomeEmail($event->customer));
}
```

### Service-to-Service Communication

When synchronous communication is needed:

```php
class OrderService extends BaseService
{
    protected CustomerService $customerService;
    protected VehicleService $vehicleService;
    
    public function __construct(
        OrderRepository $repository,
        CustomerService $customerService,
        VehicleService $vehicleService
    ) {
        parent::__construct($repository);
        $this->customerService = $customerService;
        $this->vehicleService = $vehicleService;
    }
    
    public function createOrder(array $data): Order
    {
        DB::beginTransaction();
        try {
            $customer = $this->customerService->findById($data['customer_id']);
            $vehicle = $this->vehicleService->findById($data['vehicle_id']);
            
            $order = $this->repository->create($data);
            
            // Update customer lifetime value
            $this->customerService->updateLifetimeValue(
                $customer->id, 
                $order->total
            );
            
            DB::commit();
            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

## Database Design Patterns

### 1. UUID Support

All entities have both `id` (auto-increment) and `uuid` (universally unique):

```php
Schema::create('customers', function (Blueprint $table) {
    $table->id();                    // Primary key for performance
    $table->uuid('uuid')->unique();  // Public identifier
    // ...
});
```

### 2. Soft Deletes

Critical entities use soft deletes for data retention:

```php
use SoftDeletes;

protected $fillable = [...];
protected $dates = ['deleted_at'];
```

### 3. Multi-Tenancy

Tenant isolation at the database level:

```php
Schema::create('vehicles', function (Blueprint $table) {
    $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
    // ...
});

// In model
public function scopeForTenant($query, int $tenantId)
{
    return $query->where('tenant_id', $tenantId);
}
```

### 4. Audit Trails

Using Spatie Activity Log:

```php
use LogsActivity;

public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['*'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
}
```

## Security Patterns

### 1. Authorization

Policy-based authorization:

```php
// In Policy
public function update(User $user, Customer $customer): bool
{
    return $user->tenant_id === $customer->tenant_id;
}

// In Controller
$this->authorize('update', $customer);
```

### 2. Input Validation

Form Request validation:

```php
class StoreCustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|unique:customers,email',
            'phone' => 'required|string|max:20',
        ];
    }
}
```

### 3. Mass Assignment Protection

Strict fillable/guarded definitions:

```php
protected $fillable = ['first_name', 'last_name', 'email'];
protected $guarded = ['id', 'tenant_id'];
```

## API Design Patterns

### 1. RESTful Resources

```
GET    /api/v1/customers          # List customers
POST   /api/v1/customers          # Create customer
GET    /api/v1/customers/{id}     # Get customer
PUT    /api/v1/customers/{id}     # Update customer
DELETE /api/v1/customers/{id}     # Delete customer
```

### 2. Custom Actions

```
POST /api/v1/vehicles/{id}/transfer-ownership
POST /api/v1/vehicles/{id}/update-mileage
```

### 3. Standardized Responses

```json
{
  "success": true,
  "message": "Customer created successfully",
  "data": {
    "id": 1,
    "uuid": "...",
    "first_name": "John",
    "last_name": "Doe"
  }
}
```

## Performance Optimization

### 1. Eager Loading

```php
$customers = Customer::with('vehicles')->get();
```

### 2. Query Optimization

```php
// Add indexes in migrations
$table->index('email');
$table->index(['tenant_id', 'status']);
```

### 3. Caching Strategy

```php
Cache::remember('customers.active', 3600, function () {
    return Customer::where('status', 'active')->get();
});
```

## Testing Strategy

### 1. Unit Tests

Test individual components in isolation:

```php
public function test_can_create_customer()
{
    $repository = new CustomerRepository(new Customer);
    $customer = $repository->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
    ]);
    
    $this->assertInstanceOf(Customer::class, $customer);
}
```

### 2. Feature Tests

Test complete workflows:

```php
public function test_can_transfer_vehicle_ownership()
{
    $response = $this->postJson('/api/v1/vehicles/1/transfer-ownership', [
        'new_customer_id' => 2,
        'reason' => 'sale',
    ]);
    
    $response->assertStatus(200);
    $this->assertDatabaseHas('vehicles', [
        'id' => 1,
        'current_customer_id' => 2,
    ]);
}
```

## Deployment Architecture

### Development Environment

```
Developer Machine
├── Laravel (php artisan serve)
├── Vue.js Dev Server (npm run dev)
└── SQLite Database
```

### Production Environment

```
Load Balancer
├── Web Servers (Nginx)
│   ├── PHP-FPM (Laravel)
│   └── Static Assets (Vue.js build)
├── Application Servers
│   ├── Queue Workers
│   └── Scheduled Tasks
├── Database Cluster (MySQL/PostgreSQL)
├── Cache Layer (Redis)
└── File Storage (S3)
```

## Scalability Considerations

### Horizontal Scaling

- Stateless application design
- Session storage in Redis
- File uploads to cloud storage
- Queue-based background processing

### Vertical Scaling

- Database query optimization
- Proper indexing
- Connection pooling
- OpCache configuration

## Monitoring and Logging

### Structured Logging

```php
Log::info('Customer created', [
    'customer_id' => $customer->id,
    'tenant_id' => $customer->tenant_id,
    'user_id' => auth()->id(),
]);
```

### Error Tracking

- Exception logging
- Failed job tracking
- Performance monitoring
- Uptime monitoring

## Best Practices Checklist

- ✅ Follow SOLID principles
- ✅ Use dependency injection
- ✅ Write meaningful tests
- ✅ Document complex logic
- ✅ Use type hints and return types
- ✅ Handle exceptions properly
- ✅ Validate all inputs
- ✅ Use database transactions
- ✅ Implement proper logging
- ✅ Follow PSR standards

## Conclusion

This architecture provides a solid foundation for building scalable, maintainable, and secure SaaS applications. The modular approach allows for easy extension and modification while maintaining code quality and consistency.
