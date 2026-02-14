# Laravel ERP Backend Architecture

## Overview
This Laravel backend follows **Clean Architecture** principles with a modular domain-driven design for an ERP-grade SaaS platform.

## Directory Structure

### Base Classes (`/app/Base`)
Foundation classes that all modules extend:

- **BaseModel.php** - Base Eloquent model with:
  - UUID primary keys
  - Soft deletes
  - Common scopes (active, filter)
  - Automatic timestamp casting

- **BaseRepository.php** - Repository pattern with CRUD operations:
  - `all()`, `paginate()`, `find()`, `findBy()`, `findWhere()`
  - `create()`, `update()`, `delete()`
  - `restore()`, `forceDelete()`
  - `exists()`, `count()`

- **BaseService.php** - Business logic layer with:
  - Transaction management
  - Error handling and logging
  - CRUD operations via repository
  - `executeInTransaction()` helper

- **BaseController.php** - REST API controller with:
  - Standard REST methods (index, show, store, update, destroy)
  - JSON response helpers
  - Pagination support
  - Error handling

## Modules (`/app/Modules`)

Each module follows the same structure for consistency:

### Module List
1. **Auth** - Authentication & authorization
2. **Tenant** - Multi-tenancy management
3. **User** - User management
4. **Role** - Role management
5. **Permission** - Permission management
6. **Customer** - Customer relationship management
7. **Vehicle** - Vehicle management
8. **Inventory** - Inventory management
9. **Product** - Product catalog
10. **Order** - Order management
11. **Invoice** - Invoicing
12. **Payment** - Payment processing

### Module Structure
```
ModuleName/
├── Models/              # Eloquent models
├── Repositories/        # Data access layer
├── Services/            # Business logic
├── Http/
│   ├── Controllers/     # API controllers
│   └── Requests/        # Form request validation
├── Policies/            # Authorization policies
├── Events/              # Domain events
├── Listeners/           # Event listeners
└── DTOs/                # Data Transfer Objects
```

## Database Migrations

### Published Package Migrations
- **Sanctum** - API authentication (`personal_access_tokens`)
- **Tenancy** - Multi-tenancy (`tenants`, `domains`)
- **Spatie Permission** - Roles & permissions

### Custom Migrations
1. **Organizations** (`2026_01_30_230100_create_organizations_table.php`)
   - Multi-tenant organizations
   - Fields: name, type, contact info, settings
   - Foreign key to tenants

2. **Branches** (`2026_01_30_230200_create_branches_table.php`)
   - Organization branches/locations
   - Fields: name, code, contact info, manager details
   - Foreign keys to tenants & organizations

## Published Configurations

### Config Files
- `config/sanctum.php` - API token authentication
- `config/permission.php` - Roles & permissions
- `config/l5-swagger.php` - API documentation

## Usage Guidelines

### Creating a New Entity

1. **Create Model** (extends BaseModel):
```php
namespace App\Modules\YourModule\Models;

use App\Base\BaseModel;

class YourModel extends BaseModel
{
    protected $fillable = ['field1', 'field2'];
}
```

2. **Create Repository** (extends BaseRepository):
```php
namespace App\Modules\YourModule\Repositories;

use App\Base\BaseRepository;
use App\Modules\YourModule\Models\YourModel;

class YourRepository extends BaseRepository
{
    public function __construct(YourModel $model)
    {
        parent::__construct($model);
    }
    
    // Add custom query methods here
}
```

3. **Create Service** (extends BaseService):
```php
namespace App\Modules\YourModule\Services;

use App\Base\BaseService;
use App\Modules\YourModule\Repositories\YourRepository;

class YourService extends BaseService
{
    public function __construct(YourRepository $repository)
    {
        parent::__construct($repository);
    }
    
    // Add custom business logic here
}
```

4. **Create Controller** (extends BaseController):
```php
namespace App\Modules\YourModule\Http\Controllers;

use App\Base\BaseController;
use App\Modules\YourModule\Services\YourService;

class YourController extends BaseController
{
    public function __construct(YourService $service)
    {
        parent::__construct($service);
    }
    
    // Inherits: index, show, store, update, destroy
    // Add custom actions here
}
```

5. **Create Form Request** (validation):
```php
namespace App\Modules\YourModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class YourRequest extends FormRequest
{
    public function rules()
    {
        return [
            'field1' => 'required|string',
            'field2' => 'nullable|integer',
        ];
    }
}
```

## Architecture Benefits

### Separation of Concerns
- **Models**: Data structure and relationships
- **Repositories**: Data access abstraction
- **Services**: Business logic and transactions
- **Controllers**: HTTP handling and responses

### Reusability
- Base classes provide common functionality
- Consistent patterns across all modules
- Easy to extend and customize

### Testability
- Each layer can be tested independently
- Repository pattern enables easy mocking
- Service layer contains isolated business logic

### Maintainability
- Clear module boundaries
- Consistent structure across domains
- Easy to locate and modify code

## Next Steps

1. **Run Migrations**:
   ```bash
   php artisan migrate
   ```

2. **Define Routes** in `routes/api.php`:
   ```php
   Route::apiResource('resource', YourController::class);
   ```

3. **Register Service Providers** (if needed)

4. **Implement Authentication** using Sanctum

5. **Configure Multi-tenancy** with Stancl/Tenancy

6. **Set up API Documentation** with L5-Swagger

## Additional Features

- UUID primary keys by default
- Soft deletes enabled
- Automatic timestamp handling
- Transaction management
- Comprehensive error logging
- Standardized JSON responses
- Pagination support
- Query filtering and scoping

---

**Generated**: 2026-01-30
**Laravel Version**: 11.x
**Architecture**: Clean Architecture + Repository Pattern
**Pattern**: Domain-Driven Design (DDD)
