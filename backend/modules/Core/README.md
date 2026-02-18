# Core Module

The Core module is the foundation of the AutoERP system, providing essential multi-tenancy, audit trails, base classes, and core services.

## Features

### 1. Multi-Tenancy Implementation

Complete multi-tenant architecture with database isolation:

- **Tenant Model**: Manages tenant information with UUID, settings, and subscription data
- **TenantContext Service**: Runtime tenant resolution and database connection switching
- **IdentifyTenant Middleware**: Automatically identifies tenant from headers, subdomain, or custom domain
- **RequireTenant Middleware**: Ensures tenant context is available for protected routes
- **HasTenant Trait**: Automatic tenant scoping for Eloquent models

### 2. Audit Trail System

Immutable audit logging for compliance and debugging with complete change tracking.

### 3. Base Classes

Production-ready base classes for Controllers, Services, Repositories, Models, DTOs, and Events.

### 4. Core Services

- **TenantContext**: Runtime tenant resolution
- **CacheService**: Tenant-prefixed caching
- **ConfigurationService**: Tenant-specific settings
- **FeatureFlagService**: Feature toggles per tenant
- **LoggingService**: Structured logging

## Directory Structure

```
Core/
├── Models/              # Eloquent models (Tenant, AuditLog, BaseModel)
├── Services/            # Business services
├── Repositories/        # Data access layer
├── Traits/              # Reusable traits (HasTenant, HasAudit)
├── Http/
│   ├── Controllers/     # API controllers
│   └── Middleware/      # HTTP middleware
├── DTOs/                # Data Transfer Objects
├── Events/              # Domain events
├── Providers/           # Service provider
├── database/
│   ├── migrations/      # Central database migrations
│   └── factories/       # Model factories
├── routes/              # API routes
└── tests/               # Unit and feature tests
```

## Quick Start

### Using Multi-Tenancy

```php
use Modules\Core\Traits\HasTenant;

class Invoice extends Model
{
    use HasTenant; // Automatic tenant scoping
}

$invoice = Invoice::create(['amount' => 100]); // tenant_id auto-set
$invoices = Invoice::all(); // Only current tenant's invoices
```

### Using Audit Trails

```php
use Modules\Core\Traits\HasAudit;

class Product extends Model
{
    use HasAudit; // Automatic change tracking
    
    protected $auditExclude = ['password']; // Exclude fields
}

$product->update(['price' => 99.99]); // Automatically logged
```

### Using Base Classes

```php
// Controller
use Modules\Core\Http\Controllers\BaseController;

class ProductController extends BaseController
{
    public function store(Request $request)
    {
        return $this->created($product, 'Product created');
    }
}

// Service
use Modules\Core\Services\BaseService;

class ProductService extends BaseService
{
    public function create(array $data)
    {
        return $this->transaction(function () use ($data) {
            $product = Product::create($data);
            $this->dispatchEvent(new ProductCreated($product));
            return $product;
        });
    }
}
```

## API Endpoints

### Tenant Management (Admin Only)
- `GET /api/tenants` - List tenants
- `POST /api/tenants` - Create tenant
- `GET /api/tenants/{uuid}` - Show tenant
- `PUT /api/tenants/{uuid}` - Update tenant
- `DELETE /api/tenants/{uuid}` - Delete tenant
- `POST /api/tenants/{uuid}/suspend` - Suspend tenant
- `POST /api/tenants/{uuid}/activate` - Activate tenant

### Configuration
- `GET /api/configuration` - List configurations
- `GET /api/configuration/{key}` - Get configuration
- `POST /api/configuration` - Set configuration
- `DELETE /api/configuration/{key}` - Delete configuration

### Audit Logs
- `GET /api/audit-logs` - List audit logs
- `GET /api/audit-logs/{id}` - Show audit log

### Health Check
- `GET /api/health` - System health check

## Testing

```bash
# Run all Core tests
php artisan test modules/Core/tests

# Run unit tests
php artisan test modules/Core/tests/Unit

# Run feature tests
php artisan test modules/Core/tests/Feature
```

## Configuration

The module is automatically loaded via `ModuleServiceProvider`. No additional configuration required.

## Database Schema

### Tenants Table
- id, uuid, name, domain, database, status, settings, plan
- trial_ends_at, subscription_ends_at
- timestamps, soft deletes

### Audit Logs Table
- id, tenant_id, user_id, user_type
- auditable_type, auditable_id, event
- old_values, new_values (JSON)
- url, ip_address, user_agent, tags
- created_at (immutable)

## Architecture

Built following SOLID principles and Laravel best practices:
- Repository Pattern for data access
- Service Pattern for business logic
- DTO Pattern for data transfer
- Event-Driven architecture
- Middleware for request handling
- Dependency Injection

## Security

- Tenant data isolation at database level
- Immutable audit logs
- Request tracking (IP, user agent)
- Middleware-based access control
- Soft deletes for data recovery

## License

MIT License
