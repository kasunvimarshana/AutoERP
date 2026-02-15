# AutoERP - Developer Quick Start Guide

## Prerequisites

- PHP 8.3+
- Composer
- Node.js 20+
- NPM/Yarn
- SQLite (for development) or MySQL 8.0+/PostgreSQL 14+

## Initial Setup

### 1. Clone the Repository

```bash
git clone https://github.com/kasunvimarshana/AutoERP.git
cd AutoERP
```

### 2. Backend Setup

```bash
cd backend

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Create database (SQLite for dev)
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed demo data
php artisan db:seed

# Start development server
php artisan serve
```

The backend API will be available at `http://localhost:8000`

### 3. Frontend Setup

```bash
cd frontend

# Install dependencies
npm install

# Start development server
npm run dev
```

The frontend will be available at `http://localhost:5173`

## Demo Credentials

After seeding, you can login with these credentials:

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@demo.autoerp.local | password |
| Admin | admin@demo.autoerp.local | password |
| Manager | manager@demo.autoerp.local | password |
| User | user@demo.autoerp.local | password |

## Testing the API

### 1. Health Check

```bash
curl http://localhost:8000/api/v1/health
```

Expected response:
```json
{
  "status": "ok",
  "timestamp": "2026-02-03T05:35:00Z",
  "version": "1.0.0"
}
```

### 2. Login

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "admin@demo.autoerp.local",
    "password": "password"
  }'
```

Expected response:
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": { ... },
    "access_token": "1|...",
    "token_type": "Bearer"
  }
}
```

### 3. Get Current User

```bash
TOKEN="your-token-here"

curl -X GET http://localhost:8000/api/v1/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

### 4. List Products

```bash
curl -X GET http://localhost:8000/api/v1/products \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## Development Workflow

### Running Tests

```bash
cd backend
php artisan test
```

### Code Style

```bash
# Check code style
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint
```

### Database Management

```bash
# Create new migration
php artisan make:migration create_example_table

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh migrate (drop all tables and remigrate)
php artisan migrate:fresh --seed

# Create seeder
php artisan make:seeder ExampleSeeder

# Run specific seeder
php artisan db:seed --class=ExampleSeeder
```

### Creating New Modules

Follow the Clean Architecture pattern:

1. **Create Migration**
```bash
php artisan make:migration create_examples_table
```

2. **Create Model**
```php
// app/Modules/Example/Models/Example.php
namespace App\Modules\Example\Models;

use Illuminate\Database\Eloquent\Model;
use App\Core\Traits\TenantScoped;
use App\Core\Traits\Auditable;
use App\Core\Traits\HasUuid;

class Example extends Model
{
    use TenantScoped, Auditable, HasUuid;
    
    protected $fillable = ['name', 'description'];
}
```

3. **Create Repository Interface**
```php
// app/Modules/Example/Repositories/ExampleRepositoryInterface.php
namespace App\Modules\Example\Repositories;

use App\Core\Repositories\BaseRepositoryInterface;

interface ExampleRepositoryInterface extends BaseRepositoryInterface
{
    public function findByName(string $name);
}
```

4. **Create Repository**
```php
// app/Modules/Example/Repositories/ExampleRepository.php
namespace App\Modules\Example\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Example\Models\Example;

class ExampleRepository extends BaseRepository implements ExampleRepositoryInterface
{
    public function __construct(Example $model)
    {
        parent::__construct($model);
    }
    
    public function findByName(string $name)
    {
        return $this->model->where('name', $name)->first();
    }
}
```

5. **Create Service**
```php
// app/Modules/Example/Services/ExampleService.php
namespace App\Modules\Example\Services;

use App\Core\Services\BaseService;
use App\Modules\Example\Repositories\ExampleRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ExampleService extends BaseService
{
    public function __construct(ExampleRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
    
    public function create(array $data)
    {
        DB::beginTransaction();
        
        try {
            $example = $this->repository->create($data);
            DB::commit();
            return $example;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

6. **Create Controller**
```php
// app/Http/Controllers/Api/Example/ExampleController.php
namespace App\Http\Controllers\Api\Example;

use App\Http\Controllers\BaseController;
use App\Modules\Example\Services\ExampleService;
use Illuminate\Http\Request;

class ExampleController extends BaseController
{
    protected $service;
    
    public function __construct(ExampleService $service)
    {
        $this->service = $service;
    }
    
    public function index()
    {
        $examples = $this->service->getAll();
        return $this->successResponse($examples);
    }
    
    public function store(Request $request)
    {
        $example = $this->service->create($request->validated());
        return $this->successResponse($example, 'Created successfully', 201);
    }
}
```

7. **Add Routes**
```php
// routes/api.php
Route::prefix('v1')->middleware(['auth:sanctum', 'tenant.context'])->group(function () {
    Route::apiResource('examples', ExampleController::class);
});
```

8. **Create Policy**
```bash
php artisan make:policy ExamplePolicy --model=Example
```

## Project Structure

```
AutoERP/
├── backend/
│   ├── app/
│   │   ├── Core/                  # Base classes and traits
│   │   │   ├── Repositories/      # Base repository
│   │   │   ├── Services/          # Base service
│   │   │   ├── Traits/            # Reusable traits
│   │   │   └── Exceptions/        # Custom exceptions
│   │   ├── Modules/               # Domain modules
│   │   │   ├── IAM/
│   │   │   ├── Tenant/
│   │   │   ├── Product/
│   │   │   ├── Inventory/
│   │   │   ├── CRM/
│   │   │   ├── Procurement/
│   │   │   ├── Sales/
│   │   │   ├── Invoice/
│   │   │   ├── Payment/
│   │   │   └── POS/
│   │   ├── Http/
│   │   │   ├── Controllers/       # API controllers
│   │   │   ├── Middleware/        # Custom middleware
│   │   │   ├── Requests/          # Form requests
│   │   │   └── Resources/         # API resources
│   │   └── Models/                # Shared models
│   ├── config/                    # Configuration
│   ├── database/
│   │   ├── migrations/            # Database migrations
│   │   └── seeders/               # Database seeders
│   ├── routes/                    # Route definitions
│   └── tests/                     # Tests
└── frontend/
    ├── src/
    │   ├── components/            # Vue components
    │   ├── views/                 # Page views
    │   ├── router/                # Vue Router
    │   ├── store/                 # Pinia stores
    │   ├── services/              # API services
    │   └── locales/               # i18n translations
    └── public/                    # Static assets
```

## API Documentation

The API follows RESTful conventions:

- **GET** `/api/v1/resource` - List resources
- **GET** `/api/v1/resource/{id}` - Get single resource
- **POST** `/api/v1/resource` - Create resource
- **PUT/PATCH** `/api/v1/resource/{id}` - Update resource
- **DELETE** `/api/v1/resource/{id}` - Delete resource

All responses follow this format:

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

Error responses:

```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... }
}
```

## Environment Variables

Key environment variables in `.env`:

```env
APP_NAME=AutoERP
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
# Or for MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=autoerp
# DB_USERNAME=root
# DB_PASSWORD=

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
```

## Troubleshooting

### Common Issues

1. **"Class not found" errors**
```bash
composer dump-autoload
```

2. **Permission errors**
```bash
chmod -R 775 storage bootstrap/cache
```

3. **Database locked (SQLite)**
```bash
# Stop all connections or delete the database and recreate
rm database/database.sqlite
touch database/database.sqlite
php artisan migrate --seed
```

4. **CORS errors from frontend**

Update `config/cors.php` or add to `.env`:
```env
SANCTUM_STATEFUL_DOMAINS=localhost:5173
```

## Next Steps

1. Explore the codebase in `app/Modules/`
2. Review existing migrations in `database/migrations/`
3. Check API routes in `routes/api.php`
4. Read the architecture documentation in `ARCHITECTURE.md`
5. Review implementation progress in `IMPLEMENTATION_STATUS.md`

## Getting Help

- Read the documentation in the `/docs` directory
- Check the issue tracker on GitHub
- Review the `ARCHITECTURE.md` file for system design
- Consult the `IMPLEMENTATION_STATUS.md` for current progress

## Contributing

1. Follow the existing code style
2. Write tests for new features
3. Update documentation
4. Use meaningful commit messages
5. Create pull requests for review

---

Happy coding! 🚀
