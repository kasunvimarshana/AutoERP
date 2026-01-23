# Architecture Documentation

## Overview

This ModularSaaS application follows a **Clean Architecture** approach with strict separation of concerns through the **Controller → Service → Repository** pattern. This document explains the architectural decisions and patterns used throughout the application.

## Architectural Principles

### 1. SOLID Principles

- **Single Responsibility**: Each class has one reason to change
- **Open/Closed**: Open for extension, closed for modification
- **Liskov Substitution**: Derived classes are substitutable for their base classes
- **Interface Segregation**: Clients should not depend on interfaces they don't use
- **Dependency Inversion**: Depend on abstractions, not concretions

### 2. DRY (Don't Repeat Yourself)

- Shared functionality extracted into base classes and traits
- Reusable components across modules
- Configuration centralization

### 3. KISS (Keep It Simple, Stupid)

- Simple, understandable code structure
- Avoid over-engineering
- Clear naming conventions

## Layer Architecture

```
┌──────────────────────────────────────────┐
│           HTTP Layer (Routes)             │
│  - API Routes                             │
│  - Web Routes                             │
│  - Tenant Routes                          │
└──────────────────┬───────────────────────┘
                   │
┌──────────────────▼───────────────────────┐
│        Presentation Layer                 │
│  - Controllers                            │
│  - Form Requests (Validation)             │
│  - Resources (Response Transformation)    │
└──────────────────┬───────────────────────┘
                   │
┌──────────────────▼───────────────────────┐
│         Business Logic Layer              │
│  - Services                               │
│  - DTOs (Data Transfer Objects)           │
│  - Business Rules                         │
│  - Transactions                           │
└──────────────────┬───────────────────────┘
                   │
┌──────────────────▼───────────────────────┐
│         Data Access Layer                 │
│  - Repositories                           │
│  - Query Logic                            │
│  - Data Filtering                         │
└──────────────────┬───────────────────────┘
                   │
┌──────────────────▼───────────────────────┐
│            Domain Layer                   │
│  - Models (Eloquent)                      │
│  - Database Schema                        │
│  - Relationships                          │
└───────────────────────────────────────────┘
```

## Pattern Implementations

### Repository Pattern

**Purpose**: Abstract data access logic from business logic

**Structure**:
```php
// Interface defines contract
interface RepositoryInterface {
    public function all(): Collection;
    public function find(int $id): ?Model;
    public function create(array $data): Model;
    // ... other methods
}

// Base implementation for common operations
abstract class BaseRepository implements RepositoryInterface {
    protected Model $model;
    // ... implementations
}

// Concrete implementation for specific entity
class UserRepository extends BaseRepository {
    protected function makeModel(): Model {
        return new User();
    }
    // ... additional methods specific to User
}
```

**Benefits**:
- Testable (easy to mock)
- Flexible (easy to swap implementations)
- Maintainable (single place for data access)

### Service Pattern

**Purpose**: Contain business logic and orchestrate repositories

**Structure**:
```php
// Interface defines contract
interface ServiceInterface {
    public function getAll(array $filters = []): mixed;
    public function getById(int $id): mixed;
    // ... other methods
}

// Base implementation
abstract class BaseService implements ServiceInterface {
    protected RepositoryInterface $repository;
    // ... implementations with transactions, logging
}

// Concrete implementation
class UserService extends BaseService {
    public function __construct(UserRepository $repository) {
        parent::__construct($repository);
    }
    // ... additional business logic
}
```

**Benefits**:
- Single place for business rules
- Transaction management
- Easy to test business logic
- Reusable across controllers

### Controller Pattern

**Purpose**: Handle HTTP requests and responses

**Structure**:
```php
class UserController extends Controller {
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function index(Request $request): JsonResponse {
        $users = $this->userService->getAll($filters);
        return $this->successResponse(
            UserResource::collection($users)
        );
    }
}
```

**Responsibilities**:
- Route request to service
- Validate input (via FormRequests)
- Transform output (via Resources)
- Return HTTP responses

### Request Validation Pattern

**Purpose**: Validate and authorize incoming requests

```php
class StoreUserRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
        ];
    }
}
```

**Benefits**:
- Separated validation logic
- Reusable validation rules
- Type-safe validated data

### Resource Pattern

**Purpose**: Transform models for API responses

```php
class UserResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->whenLoaded('roles'),
        ];
    }
}
```

**Benefits**:
- Consistent API responses
- Hide sensitive data
- Control output structure

## Modular Architecture

### Module Structure

Each module is self-contained with:

```
ModuleName/
├── app/
│   ├── Http/
│   │   └── Controllers/        # HTTP handlers
│   ├── Models/                  # Domain models
│   ├── Repositories/            # Data access
│   ├── Services/                # Business logic
│   ├── Requests/                # Validation
│   └── Resources/               # Response transformation
├── database/
│   ├── migrations/              # Database schema
│   ├── seeders/                 # Test data
│   └── factories/               # Model factories
├── lang/                        # Translations
├── resources/                   # Views, assets
├── routes/                      # Module routes
└── tests/                       # Module tests
```

### Module Communication

**Principle**: Modules should be loosely coupled

**Approaches**:
1. **Events**: Modules communicate via events
2. **Service Contracts**: Define interfaces for inter-module communication
3. **API Calls**: Modules expose API endpoints

**Example**:
```php
// User module fires event
event(new UserCreated($user));

// Order module listens
class CreateCustomerAccount {
    public function handle(UserCreated $event) {
        // Create customer account
    }
}
```

## Multi-Tenancy Architecture

### Tenant Isolation

**Database Strategy**: Separate databases per tenant

```
┌─────────────────────────────────────┐
│         Central Database             │
│  - Tenants table                     │
│  - Domains table                     │
│  - Central users (if any)            │
└─────────────────────────────────────┘

┌─────────────────┐  ┌─────────────────┐
│  Tenant 1 DB    │  │  Tenant 2 DB    │
│  - Users        │  │  - Users        │
│  - Orders       │  │  - Orders       │
│  - Products     │  │  - Products     │
└─────────────────┘  └─────────────────┘
```

**Tenant Identification**: Domain-based
```
tenant1.app.com → Tenant 1
tenant2.app.com → Tenant 2
```

### Tenant-Aware Models

```php
class Product extends Model {
    use TenantAware;  // Automatically scopes to current tenant
}
```

## Security Architecture

### Authentication

**Token-Based**: Laravel Sanctum
```
Client → API with Bearer Token → Sanctum verifies → Controller
```

### Authorization

**RBAC (Role-Based Access Control)**:
```
User → has → Roles → have → Permissions
```

**Example**:
```php
// Check permission
if ($user->can('user.create')) {
    // Allow action
}

// Check role
if ($user->hasRole('admin')) {
    // Allow action
}
```

### Data Security

1. **Input Validation**: All inputs validated via FormRequests
2. **Output Sanitization**: Resources control output
3. **SQL Injection Prevention**: Eloquent ORM + prepared statements
4. **XSS Prevention**: Blade templates auto-escape
5. **CSRF Protection**: Token validation on state-changing requests

## Logging & Audit Trail

### Structured Logging

```php
Log::info('User created', [
    'user_id' => $user->id,
    'ip' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);
```

### Audit Trail

**Automatic**: Via `AuditTrait` on models

```php
class User extends Model {
    use AuditTrait;  // Logs create, update, delete
}
```

**Logged Information**:
- Who (user_id)
- What (action)
- When (timestamp)
- Where (IP address)
- Original values (before update)
- New values (after update)

## Testing Strategy

### Test Pyramid

```
        ┌───────┐
        │  E2E  │  (Few)
        └───────┘
       ┌─────────┐
       │Integration│  (Some)
       └─────────┘
      ┌───────────┐
      │   Unit     │  (Many)
      └───────────┘
```

### Test Types

1. **Unit Tests**: Test individual methods
2. **Feature Tests**: Test API endpoints
3. **Integration Tests**: Test module interactions

### Test Structure

```php
public function test_user_can_be_created(): void {
    // Arrange
    $data = ['name' => 'Test', 'email' => 'test@test.com'];
    
    // Act
    $response = $this->postJson('/api/v1/users', $data);
    
    // Assert
    $response->assertStatus(201);
    $this->assertDatabaseHas('users', ['email' => 'test@test.com']);
}
```

## Performance Optimization

### Caching Strategy

1. **Config Cache**: `php artisan config:cache`
2. **Route Cache**: `php artisan route:cache`
3. **View Cache**: `php artisan view:cache`
4. **Query Result Cache**: Redis/Memcached
5. **Tenant-Scoped Cache**: Separate cache per tenant

### Database Optimization

1. **Indexes**: On frequently queried columns
2. **Eager Loading**: Prevent N+1 queries
3. **Query Optimization**: Use query builder efficiently
4. **Pagination**: Limit result sets

### Code Optimization

1. **Lazy Loading**: Load resources when needed
2. **Queueing**: Background jobs for heavy operations
3. **Asset Bundling**: Vite for frontend assets
4. **CDN**: Static assets served via CDN

## Scalability Considerations

### Horizontal Scaling

- **Stateless Application**: Sessions in database/Redis
- **Load Balancer**: Distribute traffic
- **Database Replication**: Read replicas
- **Queue Workers**: Multiple workers

### Vertical Scaling

- **PHP OPcache**: Bytecode caching
- **Database Optimization**: Query optimization
- **Server Resources**: CPU, RAM upgrades

## Deployment Architecture

```
┌──────────────┐
│ Load Balancer│
└──────┬───────┘
       │
   ┌───┴───┬───────┬───────┐
   │       │       │       │
┌──▼───┐ ┌─▼───┐ ┌─▼───┐ ┌─▼───┐
│ App  │ │ App │ │ App │ │ App │
│Server│ │Server│ │Server│ │Server│
└──┬───┘ └──┬──┘ └──┬──┘ └──┬──┘
   │        │       │       │
   └────────┴───────┴───────┘
            │
    ┌───────▼────────┐
    │   Database     │
    │   (Primary)    │
    └───────┬────────┘
            │
    ┌───────▼────────┐
    │   Database     │
    │  (Read Replica)│
    └────────────────┘
```

## Monitoring & Logging

### Application Monitoring

- **Logs**: Structured logging to files/services
- **Metrics**: Performance metrics
- **Alerts**: Error notifications
- **APM**: Application Performance Monitoring (optional)

### Infrastructure Monitoring

- **Server Health**: CPU, Memory, Disk
- **Database**: Query performance, connections
- **Queue**: Job processing, failures
- **Cache**: Hit/miss ratio

## Conclusion

This architecture provides:
- **Maintainability**: Clear structure, easy to understand
- **Scalability**: Horizontal and vertical scaling options
- **Testability**: Each layer independently testable
- **Security**: Multiple layers of protection
- **Performance**: Optimized at every layer
- **Flexibility**: Easy to extend and modify
