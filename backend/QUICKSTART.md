# Quick Start Guide - Laravel ERP Backend

## ✅ Completed Setup

### Structure Created
- **12 Domain Modules** with complete subdirectories
- **4 Base Classes** (Model, Repository, Service, Controller)
- **Custom Migrations** for Organizations and Branches
- **Package Configurations** (Sanctum, Tenancy, Permissions, L5Swagger)

## 🚀 Next Steps

### 1. Environment Setup
```bash
cd /home/runner/work/autoerp/autoerp/backend

# Copy environment file (if not already done)
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=autoerp
DB_USERNAME=root
DB_PASSWORD=
```

### 2. Run Migrations
```bash
# Run all migrations
php artisan migrate

# Or run with fresh database
php artisan migrate:fresh
```

### 3. Create Your First Module Entity

#### Example: Customer Module

**Step 1: Create Model**
```bash
# File: app/Modules/Customer/Models/Customer.php
```
```php
<?php

namespace App\Modules\Customer\Models;

use App\Base\BaseModel;

class Customer extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
```

**Step 2: Create Repository**
```bash
# File: app/Modules/Customer/Repositories/CustomerRepository.php
```
```php
<?php

namespace App\Modules\Customer\Repositories;

use App\Base\BaseRepository;
use App\Modules\Customer\Models\Customer;

class CustomerRepository extends BaseRepository
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email)
    {
        return $this->findBy('email', $email);
    }

    public function getActiveCustomers()
    {
        return $this->model->where('is_active', true)->get();
    }
}
```

**Step 3: Create Service**
```bash
# File: app/Modules/Customer/Services/CustomerService.php
```
```php
<?php

namespace App\Modules\Customer\Services;

use App\Base\BaseService;
use App\Modules\Customer\Repositories\CustomerRepository;

class CustomerService extends BaseService
{
    public function __construct(CustomerRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createCustomer(array $data)
    {
        return $this->executeInTransaction(function() use ($data) {
            // Add tenant_id from authenticated user
            $data['tenant_id'] = auth()->user()->tenant_id;
            
            return $this->repository->create($data);
        });
    }

    public function getActiveCustomers()
    {
        return $this->repository->getActiveCustomers();
    }
}
```

**Step 4: Create Request Validation**
```bash
# File: app/Modules/Customer/Http/Requests/CreateCustomerRequest.php
```
```php
<?php

namespace App\Modules\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCustomerRequest extends FormRequest
{
    public function authorize()
    {
        // Check if user has permission to create customers
        return $this->user()->can('create-customer');
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ];
    }
}
```

```bash
# File: app/Modules/Customer/Http/Requests/UpdateCustomerRequest.php
```
```php
<?php

namespace App\Modules\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize()
    {
        // Check if user has permission to update customers
        return $this->user()->can('update-customer');
    }

    public function rules()
    {
        $customerId = $this->route('customer');
        
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:customers,email,' . $customerId,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ];
    }
}
```

**Step 5: Create Controller**
```bash
# File: app/Modules/Customer/Http/Controllers/CustomerController.php
```
```php
<?php

namespace App\Modules\Customer\Http\Controllers;

use App\Base\BaseController;
use App\Modules\Customer\Services\CustomerService;
use App\Modules\Customer\Http\Requests\CreateCustomerRequest;
use App\Modules\Customer\Http\Requests\UpdateCustomerRequest;
use Illuminate\Http\JsonResponse;

class CustomerController extends BaseController
{
    protected CustomerService $customerService;

    public function __construct(CustomerService $service)
    {
        parent::__construct($service);
        $this->customerService = $service;
    }

    public function store(CreateCustomerRequest $request): JsonResponse
    {
        try {
            $data = $this->customerService->createCustomer($request->validated());
            return $this->successResponse($data, 'Customer created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function update(UpdateCustomerRequest $request, string $id): JsonResponse
    {
        try {
            $result = $this->customerService->update($id, $request->validated());
            
            if (!$result) {
                return $this->errorResponse('Customer not found or update failed', 404);
            }
            
            $data = $this->customerService->findById($id);
            return $this->successResponse($data, 'Customer updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function active(): JsonResponse
    {
        try {
            $data = $this->customerService->getActiveCustomers();
            return $this->successResponse($data, 'Active customers retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
```

**Step 6: Create Migration**
```bash
php artisan make:migration create_customers_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
```

**Step 7: Register Routes**
```bash
# File: routes/api.php
```
```php
use App\Modules\Customer\Http\Controllers\CustomerController;

Route::middleware(['auth:sanctum'])->group(function () {
    // Standard REST routes
    Route::apiResource('customers', CustomerController::class);
    
    // Custom routes
    Route::get('customers-active', [CustomerController::class, 'active']);
});
```

### 4. Service Provider (Optional)
```bash
php artisan make:provider CustomerServiceProvider
```

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Repositories\CustomerRepository;
use App\Modules\Customer\Services\CustomerService;

class CustomerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(CustomerRepository::class, function ($app) {
            return new CustomerRepository(new Customer());
        });

        $this->app->bind(CustomerService::class, function ($app) {
            return new CustomerService($app->make(CustomerRepository::class));
        });
    }
}
```

### 5. Testing
```bash
# Create test
php artisan make:test CustomerTest

# Run tests
php artisan test
```

### 6. API Documentation with Swagger
```bash
# Generate API documentation
php artisan l5-swagger:generate

# Access at: http://your-domain/api/documentation
```

### 7. Common Artisan Commands
```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Run migrations
php artisan migrate
php artisan migrate:rollback
php artisan migrate:fresh

# Generate app key
php artisan key:generate

# List routes
php artisan route:list

# Database seeding
php artisan db:seed
```

## 📋 Module Checklist

When creating a new module, follow this checklist:

- [ ] Create Model (extends BaseModel)
- [ ] Create Repository (extends BaseRepository)
- [ ] Create Service (extends BaseService)
- [ ] Create Controller (extends BaseController)
- [ ] Create Request Validation classes
- [ ] Create Migration
- [ ] Create Policy (if authorization needed)
- [ ] Create Events (if needed)
- [ ] Create Listeners (if needed)
- [ ] Create DTOs (for complex data transfer)
- [ ] Register Routes in routes/api.php
- [ ] Write Tests
- [ ] Add API Documentation annotations

## 🔐 Authentication Setup

### Configure Sanctum
```php
// config/sanctum.php - Already published

// In User model, add:
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```

### Login Endpoint Example
```php
public function login(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    if (!Auth::attempt($credentials)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $user = Auth::user();
    $token = $user->createToken('auth-token')->plainTextToken;

    return response()->json([
        'user' => $user,
        'token' => $token
    ]);
}
```

## 🏢 Multi-Tenancy Setup

### Configure Tenancy
The package is already published. Configure in `config/tenancy.php`:

```php
// Tenant identification
'tenant_route_namespace' => 'App\\Http\\Controllers\\Tenant',

// Database per tenant
'database' => [
    'based_on' => null,
    'prefix' => 'tenant',
    'suffix' => '',
],
```

### Create Tenant
```php
$tenant = Tenant::create([
    'id' => 'acme',
    'name' => 'Acme Corporation'
]);

$tenant->domains()->create([
    'domain' => 'acme.yourdomain.com'
]);
```

## 📊 Database Structure

### Core Tables Created
- `tenants` - Multi-tenant management
- `domains` - Tenant domain mapping
- `organizations` - Organizations per tenant
- `branches` - Branch locations per organization
- `personal_access_tokens` - API authentication
- `permissions` - Permission management
- `roles` - Role management
- `model_has_permissions` - Model permission assignments
- `model_has_roles` - Model role assignments
- `role_has_permissions` - Role permission assignments

## 🎯 Best Practices

1. **Always use transactions** for complex operations
2. **Validate input** with FormRequest classes
3. **Use policies** for authorization
4. **Log errors** properly
5. **Write tests** for critical functionality
6. **Document APIs** with Swagger annotations
7. **Use DTOs** for complex data structures
8. **Emit events** for side effects
9. **Keep controllers thin** - logic in services
10. **Use eager loading** to avoid N+1 queries

## 📖 Additional Resources

- Laravel Documentation: https://laravel.com/docs
- Sanctum: https://laravel.com/docs/sanctum
- Tenancy: https://tenancyforlaravel.com/docs
- Spatie Permission: https://spatie.be/docs/laravel-permission
- L5 Swagger: https://github.com/DarkaOnLine/L5-Swagger

---

**Happy Coding! 🚀**
