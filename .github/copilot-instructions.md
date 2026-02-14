# GitHub Copilot Instructions for AutoERP

## Project Overview

AutoERP is a modular, scalable, multi-tenant Enterprise Resource Planning (ERP) SaaS platform designed with microservices architecture, secure APIs, and cloud-native deployment capabilities. This project consolidates best practices and patterns from multiple related ERP systems to create a unified, enterprise-grade solution.

## Technology Stack

### Backend
- **Framework**: Laravel 11.x (PHP 8.3+)
- **Authentication**: Laravel Sanctum (token-based API authentication)
- **Multi-tenancy**: Spatie Laravel Multitenancy
- **Permissions**: Spatie Laravel Permission (RBAC/ABAC)
- **Activity Logging**: Spatie Laravel Activity Log
- **Database**: MySQL 8.0+ / PostgreSQL 14+ / SQLite (development)
- **Queue**: Redis, Database, or Amazon SQS
- **Cache**: Redis or Memcached
- **API Documentation**: Swagger/OpenAPI

### Frontend
- **Framework**: Vue.js 3 (Composition API)
- **Language**: TypeScript
- **State Management**: Pinia
- **Routing**: Vue Router 4
- **HTTP Client**: Axios
- **Styling**: Tailwind CSS
- **Build Tool**: Vite

### DevOps & Infrastructure
- **Containerization**: Docker
- **Orchestration**: Docker Compose, Kubernetes
- **CI/CD**: GitHub Actions
- **Cloud Platforms**: AWS, Azure, Google Cloud
- **Monitoring**: CloudWatch, Prometheus, Grafana

## Architecture Patterns

### Clean Architecture (Layered Approach)

Follow strict separation of concerns across four layers:

1. **Presentation Layer (Controllers)**
   - Handle HTTP requests and responses
   - Validate input using Form Request classes
   - Delegate business logic to services
   - Return standardized JSON responses
   - Keep controllers thin (< 50 lines per method)

2. **Business Logic Layer (Services)**
   - Implement business rules and workflows
   - Orchestrate repository calls
   - Manage database transactions
   - Dispatch domain events
   - Handle cross-cutting concerns

3. **Data Access Layer (Repositories)**
   - Abstract database operations
   - Provide clean API for data access
   - Handle query optimization
   - Return Eloquent models or collections

4. **Domain Layer (Models)**
   - Define data structure and relationships
   - Implement domain logic
   - Configure model behavior (soft deletes, timestamps, etc.)

### Controller → Service → Repository Pattern

**Always follow this pattern for data operations:**

```php
// Controller (extends BaseController with response helpers)
public function store(StoreRequest $request): JsonResponse
{
    try {
        $resource = $this->service->create($request->validated());
        // created() is a BaseController helper method
        return $this->created($resource, 'Resource created successfully');
    } catch (\Exception $e) {
        // error() is a BaseController helper method
        return $this->error($e->getMessage(), 500);
    }
}

// Service
public function create(array $data): Model
{
    DB::beginTransaction();
    try {
        $resource = $this->repository->create($data);
        $this->afterCreate($resource, $data);
        DB::commit();
        event(new ResourceCreated($resource));
        return $resource;
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Creation failed', ['error' => $e->getMessage()]);
        throw $e;
    }
}

// Repository
public function create(array $data): Model
{
    return $this->model->create($data);
}
```

### Module Structure

Organize code by business domain (modular monolith):

```
app/Modules/
├── CustomerManagement/
│   ├── Models/
│   ├── Repositories/
│   ├── Services/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Requests/
│   ├── Events/
│   ├── Listeners/
│   ├── Policies/
│   └── Database/
│       └── Migrations/
```

## Code Style & Best Practices

### General Principles

1. **SOLID Principles**: Single Responsibility, Open-Closed, Liskov Substitution, Interface Segregation, Dependency Inversion
2. **DRY (Don't Repeat Yourself)**: Extract common logic into base classes or traits
3. **KISS (Keep It Simple, Stupid)**: Prefer simplicity over cleverness
4. **YAGNI (You Aren't Gonna Need It)**: Don't add functionality until it's needed

### PHP/Laravel Conventions

- Follow PSR-12 coding standards
- Use type hints for all parameters and return types
- Use strict typing: `declare(strict_types=1);`
- Prefer dependency injection over facades
- Use meaningful variable and method names
- Keep methods small (< 20 lines ideally)
- Add PHPDoc blocks for complex methods
- Use early returns to reduce nesting

### TypeScript/Vue.js Conventions

- Use TypeScript for all new Vue components
- Define interfaces for all data structures
- Use Composition API over Options API
- Keep components small and focused (< 200 lines)
- Use composables for reusable logic
- Implement proper error handling
- Use TypeScript strict mode

## Multi-Tenancy Implementation

### Database-Level Isolation

```php
// All tenant-scoped models must include tenant_id
Schema::create('resources', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    // ... other columns
});

// Global scope for tenant isolation
protected static function booted()
{
    static::addGlobalScope('tenant', function (Builder $builder) {
        if (auth()->check() && auth()->user()->tenant_id) {
            $builder->where('tenant_id', auth()->user()->tenant_id);
        }
    });
}
```

### Subscription Management

- Support multiple subscription tiers (trial, basic, professional, enterprise)
- Implement usage limits (users, storage, API calls)
- Handle subscription lifecycle (activation, renewal, cancellation, suspension)
- Track billing and payment history

## Security Requirements

### Authentication & Authorization

1. **Token-Based Authentication**
   - Use Laravel Sanctum for API authentication
   - Implement token rotation and expiration
   - Support multi-device sessions

2. **Role-Based Access Control (RBAC)**
   - Define granular permissions (create, read, update, delete per resource)
   - Implement role hierarchy (super_admin > admin > manager > user)
   - Use policy classes for authorization logic

3. **Input Validation**
   - Validate all user inputs using Form Requests
   - Sanitize data before storage
   - Prevent mass assignment vulnerabilities

4. **Security Headers**
   - Implement CORS properly
   - Enable CSRF protection
   - Use HTTPS in production
   - Set secure cookie flags

### Data Protection

- Encrypt sensitive data at rest
- Hash passwords using bcrypt/argon2
- Implement audit trails for sensitive operations
- Support data export (GDPR compliance)
- Implement soft deletes for data recovery

## API Design Standards

### RESTful Conventions

```
GET    /api/v1/resources           # List resources
POST   /api/v1/resources           # Create resource
GET    /api/v1/resources/{id}      # Get resource
PUT    /api/v1/resources/{id}      # Update resource
DELETE /api/v1/resources/{id}      # Delete resource

# Custom actions
POST   /api/v1/resources/{id}/action
```

### Response Format

```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "attributes": {}
  },
  "meta": {
    "current_page": 1,
    "total": 100
  }
}
```

### Error Handling

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### Versioning

- Use URL versioning: `/api/v1/`, `/api/v2/`
- Maintain backward compatibility
- Document breaking changes

## Database Design Patterns

### Standard Fields

```php
// Every table should have these fields
$table->id();                              // Primary key
$table->uuid('uuid')->unique();           // Public identifier
$table->foreignId('tenant_id')->nullable()->constrained();
$table->timestamps();                      // created_at, updated_at
$table->softDeletes();                     // deleted_at
```

### Indexes

- Index foreign keys
- Index frequently queried columns
- Use composite indexes for multi-column queries
- Consider full-text search indexes for text fields

### Relationships

- Use Eloquent relationships over raw queries
- Implement eager loading to prevent N+1 queries
- Use polymorphic relationships where appropriate

## Event-Driven Architecture

### Domain Events

```php
// Event class definition
namespace App\Modules\CustomerManagement\Events;

use App\Modules\CustomerManagement\Models\Customer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Customer $customer
    ) {}
}

// Dispatch events after significant operations
event(new CustomerCreated($customer));
event(new OrderPlaced($order));
event(new PaymentReceived($payment));
```

### Event Listeners

```php
// Handle events asynchronously
class SendWelcomeEmail implements ShouldQueue
{
    public function handle(CustomerCreated $event)
    {
        Mail::to($event->customer->email)
            ->send(new WelcomeEmail($event->customer));
    }
}
```

### Queue Workers

- Process jobs asynchronously
- Implement retry logic with exponential backoff
- Monitor failed jobs
- Use job chaining for dependent operations

## Testing Strategy

### Unit Tests

- Test individual components in isolation
- Mock dependencies
- Aim for 80%+ code coverage
- Focus on business logic

### Feature Tests

- Test complete user workflows
- Use database transactions for test isolation
- Test API endpoints with authentication
- Verify response structure and status codes

### Integration Tests

- Test cross-module interactions
- Verify event dispatching and handling
- Test queue jobs
- Verify email sending

## Performance Optimization

### Database Optimization

```php
// Use eager loading
$customers = Customer::with(['vehicles', 'orders'])->get();

// Use select to limit columns
$users = User::select('id', 'name', 'email')->get();

// Use chunking for large datasets
Customer::chunk(100, function ($customers) {
    foreach ($customers as $customer) {
        // Process customer
    }
});
```

### Caching Strategy

```php
// Cache expensive queries
Cache::remember('customers.active', 3600, function () {
    return Customer::where('status', 'active')->get();
});

// Invalidate cache on updates
Cache::forget('customers.active');
```

### Query Optimization

- Use database indexes
- Avoid N+1 queries
- Use database views for complex queries
- Implement pagination
- Use select statements instead of get() when appropriate

## Frontend Best Practices

### Component Structure

```typescript
// Use TypeScript interfaces
interface User {
  id: number;
  name: string;
  email: string;
  status: string;
}

// Use Composition API
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

const users = ref<User[]>([]);
const activeUsers = computed(() => 
  users.value.filter(u => u.status === 'active')
);

onMounted(async () => {
  users.value = await fetchUsers();
});
</script>
```

### State Management

- Use Pinia for global state
- Keep component-specific state local
- Implement proper typing for stores
- Use getters for derived state

### API Integration

```typescript
// Type definitions
interface ListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: string;
  order?: 'asc' | 'desc';
}

interface CreateCustomerDto {
  name: string;
  email: string;
  phone?: string;
  // ... other fields
}

// Create typed API services
export const customerApi = {
  async list(params?: ListParams): Promise<Customer[]> {
    const { data } = await axios.get('/api/v1/customers', { params });
    return data.data;
  },
  
  async create(customer: CreateCustomerDto): Promise<Customer> {
    const { data } = await axios.post('/api/v1/customers', customer);
    return data.data;
  }
};
```

## Deployment Guidelines

### Environment Configuration

- Use `.env` files for environment-specific configuration
- Never commit sensitive data to version control
- Use environment variables for secrets
- Document all required environment variables

### Docker Deployment

```yaml
# docker-compose.yml structure
services:
  app:
    # Laravel application
  frontend:
    # Vue.js frontend
  mysql:
    # Database
  redis:
    # Cache & Queue
  nginx:
    # Web server
```

### Production Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure proper database credentials
- [ ] Set up queue workers
- [ ] Configure scheduled tasks (cron)
- [ ] Enable OPcache
- [ ] Set up SSL/TLS certificates
- [ ] Configure backup strategy
- [ ] Set up monitoring and logging
- [ ] Configure rate limiting

## Monitoring & Logging

### Structured Logging

```php
Log::info('Operation performed', [
    'user_id' => auth()->id(),
    'tenant_id' => auth()->user()->tenant_id,
    'resource_id' => $resource->id,
    'action' => 'create',
    'ip_address' => request()->ip(),
]);
```

### Error Tracking

- Log all exceptions with context
- Monitor failed jobs
- Track API response times
- Set up alerts for critical errors

## ERP-Specific Modules

### Core Modules to Implement

1. **Customer Management**
   - Customer profiles (individual/business)
   - Contact management
   - Credit limits and terms

2. **Inventory Management**
   - Product catalog
   - Stock tracking
   - Warehouse management
   - Supplier management

3. **Order Management**
   - Sales orders
   - Purchase orders
   - Order fulfillment
   - Shipping and tracking

4. **Financial Management**
   - Invoicing
   - Payments and receipts
   - General ledger
   - Financial reporting

5. **CRM (Customer Relationship Management)**
   - Lead management
   - Opportunity tracking
   - Communication history
   - Marketing campaigns

6. **Reporting & Analytics**
   - Dashboard metrics
   - Custom reports
   - Data export
   - Business intelligence

## Documentation Requirements

### Code Documentation

- Add PHPDoc blocks for all public methods
- Document complex algorithms
- Include examples in documentation
- Keep README files up to date

### API Documentation

- Use Swagger/OpenAPI for API documentation
- Document all endpoints
- Include request/response examples
- Document authentication requirements

### Architecture Documentation

- Maintain architecture decision records (ADRs)
- Document system diagrams
- Update setup instructions
- Document deployment procedures

## Base Class Patterns

### BaseService Pattern

```php
namespace App\Core\Services;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;

abstract class BaseService
{
    public function __construct(
        protected BaseRepository $repository
    ) {}

    public function findById(int $id): ?Model
    {
        return $this->repository->findById($id);
    }

    public function all(array $criteria = [])
    {
        return $this->repository->all($criteria);
    }

    public function create(array $data): Model
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): Model
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    // Override in child classes for custom logic
    protected function afterCreate(Model $model, array $data): void
    {
        // Hook for post-creation logic
    }

    protected function afterUpdate(Model $model, array $data): void
    {
        // Hook for post-update logic
    }
}
```

### BaseRepository Pattern

```php
namespace App\Core\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

abstract class BaseRepository
{
    public function __construct(
        protected Model $model
    ) {}

    public function findById(int $id): ?Model
    {
        return $this->model->find($id);
    }

    public function all(array $criteria = []): Collection
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $query = $this->applySearch($query, $criteria['search']);
        }

        if (!empty($criteria['sort_by'])) {
            $query->orderBy(
                $criteria['sort_by'],
                $criteria['order'] ?? 'asc'
            );
        }

        return $query->get();
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Model
    {
        $model = $this->findById($id);
        $model->update($data);
        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        return $this->model->destroy($id) > 0;
    }

    // Override in child classes for custom search logic
    protected function applySearch($query, string $search)
    {
        return $query;
    }
}
```

## Common Patterns to Follow

### Service Pattern Example

```php
namespace App\Modules\CustomerManagement\Services;

use App\Core\Services\BaseService;
use App\Modules\CustomerManagement\Models\Customer;
use App\Modules\CustomerManagement\Repositories\CustomerRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerService extends BaseService
{
    public function __construct(
        protected CustomerRepository $repository
    ) {
        parent::__construct($repository);
    }

    public function create(array $data): Customer
    {
        DB::beginTransaction();
        try {
            $customer = $this->repository->create($data);
            
            // Additional business logic (implement these methods in the service)
            $this->assignDefaultSettings($customer);
            $this->sendWelcomeNotification($customer);
            
            DB::commit();
            
            event(new CustomerCreated($customer));
            
            return $customer;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Customer creation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }
}
```

### Repository Pattern Example

```php
namespace App\Modules\CustomerManagement\Repositories;

use App\Modules\CustomerManagement\Models\Customer;

class CustomerRepository extends BaseRepository
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
    }

    public function search(array $criteria)
    {
        $query = $this->model->query();
        
        if (!empty($criteria['search'])) {
            $query->where(function ($q) use ($criteria) {
                $q->where('name', 'like', "%{$criteria['search']}%")
                  ->orWhere('email', 'like', "%{$criteria['search']}%");
            });
        }
        
        return $query->paginate($criteria['per_page'] ?? 15);
    }
}
```

## Git Workflow

### Branch Naming

- `feature/feature-name` - New features
- `bugfix/bug-description` - Bug fixes
- `hotfix/critical-fix` - Production hotfixes
- `refactor/component-name` - Code refactoring

### Commit Messages

Follow conventional commits format:

```
type(scope): subject

body (optional)

footer (optional)
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

Example:
```
feat(customer): add customer search functionality

- Implement search by name and email
- Add pagination support
- Include unit tests

Closes #123
```

## Code Review Guidelines

### What to Look For

- Code follows established patterns
- Proper error handling implemented
- Tests are included and passing
- No security vulnerabilities
- Performance considerations addressed
- Documentation is updated
- No code duplication

### Before Submitting PR

- [ ] All tests pass
- [ ] Code is properly formatted
- [ ] No console.log or dd() statements
- [ ] Environment variables documented
- [ ] Database migrations are reversible
- [ ] API changes are documented

## Troubleshooting Common Issues

### Performance Issues

1. Check for N+1 queries (use Laravel Debugbar)
2. Review database indexes
3. Check cache hit rates
4. Profile slow queries
5. Review queue job performance

### Multi-Tenancy Issues

1. Verify tenant_id is properly set
2. Check global scopes are applied
3. Verify middleware is active
4. Test cross-tenant data access

### Authentication Issues

1. Verify token is valid and not expired
2. Check CORS configuration
3. Verify sanctum middleware is applied
4. Check token abilities/permissions

## Additional Resources

- Laravel Documentation: https://laravel.com/docs
- Vue.js Documentation: https://vuejs.org/guide
- TypeScript Documentation: https://www.typescriptlang.org/docs
- Tailwind CSS: https://tailwindcss.com/docs
- Spatie Packages: https://spatie.be/docs

## Notes for AI Copilot

When assisting with this project:

1. **Always follow the layered architecture**: Controller → Service → Repository
2. **Implement proper transaction management**: Wrap create/update operations in DB transactions
3. **Dispatch domain events**: After significant operations
4. **Include tenant isolation**: For all tenant-scoped resources
5. **Write defensive code**: Validate inputs, handle errors gracefully
6. **Type everything**: Use type hints in PHP and TypeScript
7. **Test thoroughly**: Write tests for new features
8. **Document changes**: Update relevant documentation
9. **Follow existing patterns**: Maintain consistency with existing code
10. **Security first**: Always consider security implications

When generating code:
- Use the established module structure
- Follow the coding standards
- Include proper error handling
- Add appropriate tests
- Update documentation
- Consider performance implications
- Implement proper logging
- Apply security best practices

---

## Quick Reference

### Key Sections
- [Architecture Patterns](#architecture-patterns) - Clean architecture and layered design
- [Controller → Service → Repository Pattern](#controller--service--repository-pattern) - Core data flow
- [Base Class Patterns](#base-class-patterns) - BaseService and BaseRepository implementations
- [Multi-Tenancy Implementation](#multi-tenancy-implementation) - Tenant isolation strategies
- [Security Requirements](#security-requirements) - Authentication, authorization, and data protection
- [API Design Standards](#api-design-standards) - RESTful conventions and response formats
- [Event-Driven Architecture](#event-driven-architecture) - Domain events and listeners
- [Testing Strategy](#testing-strategy) - Unit, feature, and integration tests
- [ERP-Specific Modules](#erp-specific-modules) - Core business modules

### Essential Patterns at a Glance

**Controller Example**: Validate input → Call service → Return response  
**Service Example**: Begin transaction → Repository operations → Dispatch events → Commit  
**Repository Example**: Query building → Data retrieval → Return models  
**Event Example**: Create event class → Dispatch after operation → Handle asynchronously  

### Remember
- Always use transactions for write operations
- Always implement tenant isolation for multi-tenant resources
- Always validate and sanitize user inputs
- Always log important operations
- Always write tests for new features
- Always consider security implications

For detailed information on any topic, refer to the relevant section above.
