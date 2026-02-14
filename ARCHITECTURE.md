# AutoERP - Architecture Documentation

## System Architecture

### Overview
AutoERP is designed using Clean Architecture principles with a modular, layered approach that ensures:
- Separation of concerns
- Loose coupling
- High testability
- Long-term maintainability
- Scalability

### Architecture Layers

```
┌─────────────────────────────────────────────────┐
│         Presentation Layer (Controllers)        │
│  - API Controllers                              │
│  - Request Validation                           │
│  - Response Formatting                          │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│         Application Layer (Services)            │
│  - Business Logic                               │
│  - Transaction Management                       │
│  - Event Dispatching                            │
│  - Cross-module Orchestration                   │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│         Domain Layer (Repositories)             │
│  - Data Access                                  │
│  - Eloquent Queries                             │
│  - Data Persistence                             │
└────────────────┬────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────┐
│         Infrastructure Layer (Database)         │
│  - PostgreSQL/MySQL                             │
│  - Migrations                                   │
│  - Schema Management                            │
└─────────────────────────────────────────────────┘
```

## Design Patterns

### Controller→Service→Repository Pattern

This is the core architectural pattern used throughout the application.

#### Controllers (Thin Layer)
**Purpose**: Handle HTTP communication
**Responsibilities**:
- Receive HTTP requests
- Validate input data
- Call appropriate service methods
- Format and return responses
- Handle HTTP-specific concerns

**Example**:
```php
class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function store(Request $request)
    {
        // Validate
        $validated = $request->validate([...]);
        
        // Delegate to service
        $product = $this->productService->createProduct($validated);
        
        // Return response
        return response()->json(['data' => $product], 201);
    }
}
```

#### Services (Business Logic Layer)
**Purpose**: Implement business logic and orchestration
**Responsibilities**:
- Contain all business rules
- Orchestrate multiple repositories
- Handle transactions
- Emit domain events
- Ensure data consistency
- Cross-cutting concerns

**Example**:
```php
class ProductService
{
    protected $productRepository;

    public function createProduct(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Business logic
            $data['tenant_id'] = auth()->user()->tenant_id;
            
            // Repository call
            $product = $this->productRepository->create($data);
            
            // Event emission
            event(new ProductCreated($product));
            
            // Logging
            Log::info('Product created', ['id' => $product->id]);
            
            return $product;
        });
    }
}
```

#### Repositories (Data Access Layer)
**Purpose**: Abstract database operations
**Responsibilities**:
- Execute database queries
- Handle Eloquent operations
- Provide clean data access interface
- No business logic
- Return models or collections

**Example**:
```php
class ProductRepository
{
    public function create(array $data)
    {
        return Product::create($data);
    }

    public function find($id)
    {
        return Product::with(['variants', 'inventory'])->findOrFail($id);
    }
}
```

## Multi-Tenancy Architecture

### Tenant Isolation Strategy

The system uses **single-database multi-tenancy** with tenant_id column isolation:

```
┌─────────────────────────────────────────────┐
│              Tenant A Request               │
└────────────────┬────────────────────────────┘
                 │
┌────────────────▼────────────────────────────┐
│         TenantAwareMiddleware               │
│  - Validates tenant access                  │
│  - Sets tenant context                      │
└────────────────┬────────────────────────────┘
                 │
┌────────────────▼────────────────────────────┐
│           Global Scope Filter               │
│  WHERE tenant_id = auth()->user()->tenant_id│
└────────────────┬────────────────────────────┘
                 │
┌────────────────▼────────────────────────────┐
│         Tenant A Data Only                  │
└─────────────────────────────────────────────┘
```

### Implementation Details

1. **Global Scopes on Models**
```php
protected static function booted(): void
{
    static::addGlobalScope('tenant', function ($builder) {
        if (auth()->check() && auth()->user()->tenant_id) {
            $builder->where('tenant_id', auth()->user()->tenant_id);
        }
    });
}
```

2. **Middleware Protection**
```php
Route::middleware(['auth:sanctum', 'tenant.aware'])->group(...)
```

3. **Automatic tenant_id Assignment**
```php
if (auth()->check()) {
    $data['tenant_id'] = auth()->user()->tenant_id;
}
```

## Transaction Management

All business operations that modify data use database transactions:

```php
public function complexOperation(array $data)
{
    return DB::transaction(function () use ($data) {
        // Multiple operations
        $customer = $this->customerRepository->create($data);
        $invoice = $this->invoiceRepository->create([...]);
        $inventory = $this->inventoryRepository->update([...]);
        
        // All succeed or all rollback
        return $customer;
    });
}
```

### Transaction Guarantees
- **Atomicity**: All or nothing execution
- **Consistency**: Data integrity maintained
- **Isolation**: Concurrent operations don't interfere
- **Durability**: Committed changes are permanent

## Event-Driven Architecture

### Event Flow

```
┌─────────────┐      ┌──────────────┐      ┌───────────────┐
│   Service   │─────▶│    Event     │─────▶│   Listeners   │
│  (Emit)     │      │  Dispatcher  │      │  (Handle)     │
└─────────────┘      └──────────────┘      └───────────────┘
                                                    │
                                     ┌──────────────┴──────────────┐
                                     ▼                             ▼
                              ┌──────────┐                  ┌─────────────┐
                              │  Logging │                  │ Notification│
                              └──────────┘                  └─────────────┘
```

### Use Cases for Events
- Audit trail logging
- Sending notifications
- Updating derived data
- Triggering workflows
- Third-party integrations
- Analytics tracking

## Security Architecture

### Authentication Flow

```
┌──────────┐      ┌──────────┐      ┌──────────┐      ┌──────────┐
│  Client  │─────▶│   API    │─────▶│  Sanctum │─────▶│   User   │
│          │      │ Endpoint │      │  Auth    │      │  Session │
└──────────┘      └──────────┘      └──────────┘      └──────────┘
     │                                                        │
     └────────────── Bearer Token ◀─────────────────────────┘
```

### Authorization Layers
1. **Route Middleware**: `auth:sanctum`
2. **Tenant Middleware**: `tenant.aware`
3. **Policy Classes**: Fine-grained permissions
4. **Global Scopes**: Data isolation

### Security Measures
- Password hashing (bcrypt)
- CSRF protection
- Rate limiting
- SQL injection prevention (Eloquent)
- XSS protection (Vue escaping)
- Secure session handling
- Token-based API auth

## Database Design

### Schema Principles
- **Normalized structure**: Reduce redundancy
- **Proper indexes**: Optimize queries
- **Foreign keys**: Maintain referential integrity
- **Soft deletes**: Preserve data history
- **Timestamps**: Track changes
- **JSON columns**: Flexible attributes (PostgreSQL JSONB)

### Key Relationships

```
Tenant ──┬──▶ Users
         ├──▶ Vendors
         ├──▶ Branches
         ├──▶ Customers
         └──▶ Products

Vendor ──▶ Branches

Product ──┬──▶ InventoryItems
          └──▶ Variants (self-reference)

InventoryItem ──▶ InventoryMovements

Invoice ──┬──▶ InvoiceItems
          └──▶ Customer
```

## API Design

### RESTful Principles
- **Resource-based URLs**: `/api/v1/products`
- **HTTP verbs**: GET, POST, PUT, DELETE
- **Status codes**: 200, 201, 400, 401, 404, 500
- **Versioning**: `/api/v1/`
- **Consistent responses**: `{ data: {...}, message: "..." }`

### Response Format
```json
{
  "data": {
    "id": 1,
    "name": "Product Name"
  },
  "message": "Success",
  "meta": {
    "page": 1,
    "per_page": 15
  }
}
```

## Frontend Architecture

### Vue.js Structure

```
resources/js/
├── components/       # Reusable UI components
├── views/           # Page components
├── stores/          # Pinia state management
├── router/          # Vue Router configuration
├── services/        # API communication
└── main.js          # Application entry point
```

### State Management (Pinia)
- **Stores**: Centralized state
- **Getters**: Computed state
- **Actions**: Async operations
- **Reactivity**: Vue 3 Composition API

### Component Communication
- **Props down**: Parent to child
- **Events up**: Child to parent
- **Store**: Global state
- **Provide/Inject**: Deep hierarchies

## Scalability Considerations

### Horizontal Scaling
- Stateless API design
- Session storage in database/Redis
- Load balancer ready
- CDN for static assets

### Vertical Scaling
- Database optimization
- Query optimization
- Caching strategy (Redis)
- Queue workers for background jobs

### Future Enhancements
- Database-per-tenant option
- Read replicas
- Microservices extraction
- Event sourcing
- CQRS pattern

## Development Workflow

### Code Organization
1. Start with migration (database schema)
2. Create model with relationships
3. Create repository for data access
4. Create service for business logic
5. Create controller for HTTP handling
6. Add routes
7. Write tests
8. Document API

### Testing Strategy
- **Unit tests**: Services and repositories
- **Integration tests**: API endpoints
- **Feature tests**: User workflows
- **E2E tests**: Full application flows

## Best Practices

### Do's ✅
- Keep controllers thin
- Put business logic in services
- Use transactions for multi-step operations
- Emit events for side effects
- Write descriptive method names
- Use type hints
- Follow PSR-12 standards
- Write tests

### Don'ts ❌
- Don't put business logic in controllers
- Don't put business logic in models
- Don't bypass the service layer
- Don't ignore transactions
- Don't hardcode tenant_id
- Don't skip validation
- Don't ignore errors

## Monitoring and Logging

### Log Levels
- **DEBUG**: Detailed information
- **INFO**: Important events
- **WARNING**: Exceptional occurrences
- **ERROR**: Runtime errors
- **CRITICAL**: Critical conditions

### What to Log
- User actions (audit trail)
- Business operations
- Security events
- Performance metrics
- Error stack traces

## Conclusion

This architecture provides:
- **Maintainability**: Clear separation of concerns
- **Scalability**: Can grow with business needs
- **Testability**: Each layer can be tested independently
- **Security**: Multiple layers of protection
- **Performance**: Optimized data access
- **Flexibility**: Easy to modify and extend
