# AutoERP - Architecture Documentation

## Overview
Production-ready, modular, ERP-grade SaaS platform built with Laravel (backend) and Vue.js (frontend).

## Architecture Principles

### Clean Architecture
- **Separation of Concerns**: Each layer has distinct responsibilities
- **Dependency Inversion**: High-level modules independent of low-level implementations
- **Testability**: Easy to test each layer independently

### Modular Architecture
- **Feature-based modules**: Each business domain is a self-contained module
- **Loose coupling**: Modules communicate only through defined contracts
- **High cohesion**: Related functionality grouped together

### Design Patterns
- **Controller → Service → Repository**: Strict layered architecture
- **SOLID Principles**: Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion
- **DRY (Don't Repeat Yourself)**: Code reuse through inheritance and composition
- **KISS (Keep It Simple, Stupid)**: Simple, maintainable solutions

## Backend Structure

```
app/
├── Core/
│   ├── Interfaces/         # Repository and service interfaces
│   ├── Repositories/       # Base repository implementations
│   ├── Services/           # Base service implementations
│   ├── Traits/             # Reusable traits (TenantScoped, etc.)
│   ├── Middleware/         # Custom middleware
│   └── Exceptions/         # Custom exceptions
│
├── Modules/                # Business domain modules
│   ├── Tenancy/           # Multi-tenancy management
│   ├── Auth/              # Authentication & authorization
│   ├── User/              # User management
│   ├── Customer/          # Customer management
│   ├── Vehicle/           # Vehicle management
│   ├── Branch/            # Branch management
│   ├── Vendor/            # Vendor management
│   ├── CRM/               # Customer relationship management
│   ├── Inventory/         # Inventory management
│   ├── POS/               # Point of sale
│   ├── Billing/           # Invoicing & payments
│   ├── Fleet/             # Fleet management
│   └── Analytics/         # Reports & analytics
│
└── Support/               # Helper services and utilities
    ├── Helpers/
    └── Services/
```

### Module Structure
Each module follows the same structure:
```
ModuleName/
├── Controllers/           # HTTP request handlers (thin)
├── Services/              # Business logic (orchestration)
├── Repositories/          # Data access layer
├── Models/                # Eloquent models
├── DTOs/                  # Data Transfer Objects
├── Policies/              # Authorization policies
├── Events/                # Domain events
├── Listeners/             # Event handlers
└── Requests/              # Form request validation
```

## Frontend Structure

```
resources/
├── js/
│   ├── modules/           # Feature-based Vue modules
│   │   ├── auth/
│   │   ├── dashboard/
│   │   ├── crm/
│   │   ├── inventory/
│   │   └── ...
│   │
│   ├── components/        # Shared components
│   ├── layouts/           # Layout components
│   ├── router/            # Vue Router configuration
│   ├── stores/            # Pinia state management
│   ├── services/          # API services
│   ├── composables/       # Vue composables
│   └── locales/           # i18n translations
│
└── css/
    └── app.css            # Tailwind CSS
```

## Key Features

### Multi-Tenancy
- **Tenant Isolation**: Strict data separation using tenant_id
- **Global Scopes**: Automatic query scoping with TenantScoped trait
- **Tenant Context**: Auth-based tenant identification

### Multi-Vendor & Multi-Branch
- **Hierarchical Structure**: Tenant → Vendor → Branch
- **Scoped Operations**: All operations respect vendor/branch context
- **Centralized History**: Cross-branch data visibility

### Authentication & Authorization
- **Laravel Sanctum**: Token-based API authentication
- **RBAC**: Role-Based Access Control using Spatie Laravel Permission
- **ABAC**: Attribute-Based Access Control for fine-grained permissions
- **Policies**: Laravel policies for authorization

### Service Orchestration
- **Transactional Boundaries**: All service methods wrapped in DB transactions
- **Exception Handling**: Consistent error handling and rollback
- **Event-Driven**: Domain events for async workflows
- **Idempotency**: Safe retry mechanisms

### API Design
- **Versioned APIs**: /api/v1/ prefix for version management
- **RESTful**: Standard HTTP methods and status codes
- **Swagger/OpenAPI**: Auto-generated API documentation
- **Pagination**: Built-in pagination support

### Security
- **HTTPS**: Enforced in production
- **Encryption**: Data at rest encryption
- **Validation**: Strict input validation
- **Rate Limiting**: API throttling
- **Audit Trails**: Immutable activity logs
- **CSRF Protection**: Cross-site request forgery prevention

### Internationalization (i18n)
- **Backend**: Laravel localization for messages and validation
- **Frontend**: Vue i18n for UI translations
- **Shared Keys**: Consistent translation keys across stack

### Caching & Performance
- **Redis**: Distributed caching
- **Query Optimization**: Eager loading, indexes
- **Queue Jobs**: Background processing for heavy operations

## Development Workflow

### Prerequisites
- PHP 8.1+
- Node.js 18+
- Composer
- MySQL/PostgreSQL
- Redis (optional, for caching)

### Installation
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Build frontend assets
npm run build

# Start development server
php artisan serve
npm run dev
```

### Development Commands
```bash
# Run tests
php artisan test

# Code formatting
./vendor/bin/pint

# Generate API documentation
php artisan l5-swagger:generate

# Clear caches
php artisan optimize:clear
```

## Module Implementation Guide

### Creating a New Module

1. **Create Module Structure**
```bash
mkdir -p app/Modules/NewModule/{Controllers,Services,Repositories,Models,DTOs,Policies,Events,Listeners,Requests}
```

2. **Create Model**
```php
<?php
namespace App\Modules\NewModule\Models;

use App\Core\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class NewModel extends Model
{
    use TenantScoped;
    
    protected $fillable = ['field1', 'field2'];
}
```

3. **Create Repository**
```php
<?php
namespace App\Modules\NewModule\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\NewModule\Models\NewModel;

class NewRepository extends BaseRepository
{
    protected function model(): string
    {
        return NewModel::class;
    }
}
```

4. **Create Service**
```php
<?php
namespace App\Modules\NewModule\Services;

use App\Core\Services\BaseService;
use App\Modules\NewModule\Repositories\NewRepository;

class NewService extends BaseService
{
    public function __construct(NewRepository $repository)
    {
        $this->repository = $repository;
    }
    
    // Add custom business logic methods
}
```

5. **Create Controller**
```php
<?php
namespace App\Modules\NewModule\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\NewModule\Services\NewService;
use Illuminate\Http\Request;

class NewController extends Controller
{
    protected $service;
    
    public function __construct(NewService $service)
    {
        $this->service = $service;
    }
    
    public function index()
    {
        return response()->json($this->service->getPaginated());
    }
}
```

6. **Add Routes**
```php
Route::prefix('new-module')->group(function () {
    Route::get('/', [NewController::class, 'index']);
    Route::post('/', [NewController::class, 'store']);
    // ... more routes
});
```

## Testing Strategy

### Unit Tests
- Test individual methods in isolation
- Mock dependencies
- Fast execution

### Integration Tests
- Test module interactions
- Use test database
- Test API endpoints

### Feature Tests
- End-to-end scenarios
- Test complete workflows
- Include frontend interactions

## Deployment

### Production Checklist
- [ ] Environment variables configured
- [ ] Database migrations run
- [ ] Assets compiled (`npm run build`)
- [ ] Caches optimized (`php artisan optimize`)
- [ ] Queue workers configured
- [ ] SSL certificates installed
- [ ] Backups configured
- [ ] Monitoring setup

### Docker Deployment
```bash
# Build image
docker build -t autoerp .

# Run containers
docker-compose up -d
```

## Contributing Guidelines

1. Follow PSR-12 coding standards
2. Write meaningful commit messages
3. Add tests for new features
4. Update documentation
5. Create feature branches
6. Submit pull requests for review

## License
Proprietary - All rights reserved

## Support
For support, contact: support@autoerp.com
