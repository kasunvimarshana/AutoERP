# ModularSaaS - Laravel Vue Enterprise Application

A production-ready, LTS, highly maintainable modular SaaS application built with Laravel 11 and Vue.js 3, following enterprise-grade architecture patterns and best practices.

## 🏗️ Architecture

This application strictly follows a **Modular Architecture** with the **Controller → Service → Repository** pattern, ensuring:

- **Clean Separation of Concerns**: Each layer has a single responsibility
- **Loose Coupling**: Components are independent and easily replaceable
- **High Cohesion**: Related functionality is grouped together
- **Testability**: Each layer can be tested independently
- **Maintainability**: Code is organized, documented, and follows standards

## 📋 Features

### Core Architecture
- ✅ **Modular Structure**: Self-contained modules using `nwidart/laravel-modules`
- ✅ **Repository Pattern**: Data access abstraction layer
- ✅ **Service Layer**: Business logic separation
- ✅ **API Resources**: Consistent data transformation
- ✅ **Request Validation**: Type-safe validation with custom request classes
- ✅ **Trait-Based**: Reusable functionality through traits

### Multi-Tenancy
- ✅ **Database Isolation**: Each tenant has isolated data using `stancl/tenancy`
- ✅ **Domain-Based**: Tenant identification via domains/subdomains
- ✅ **Cache Isolation**: Separated cache per tenant
- ✅ **Filesystem Isolation**: Tenant-specific file storage

### Security & Authorization
- ✅ **Authentication**: Laravel Sanctum for API authentication
- ✅ **RBAC**: Role-Based Access Control using `spatie/laravel-permission`
- ✅ **ABAC Support**: Attribute-Based Access Control capabilities
- ✅ **Encryption**: Data encryption at rest and in transit
- ✅ **Input Validation**: Strict validation on all inputs
- ✅ **Audit Trails**: Comprehensive logging of user actions

### Localization & i18n
- ✅ **Multi-Language**: Full translation support
- ✅ **Module-Level Translations**: Each module has its own translations
- ✅ **Frontend i18n**: Vue.js internationalization ready
- ✅ **Dynamic Language Switching**: Runtime language changes

### Code Quality
- ✅ **Clean Code**: SOLID, DRY, KISS principles
- ✅ **Type Safety**: PHP 8.3+ type declarations
- ✅ **Documentation**: PHPDoc blocks on all methods
- ✅ **Consistent Formatting**: Laravel Pint for code style
- ✅ **Structured Logging**: Comprehensive logging system

## 🚀 Technology Stack

### Backend
- **Framework**: Laravel 11.x (LTS)
- **PHP**: 8.3+
- **Database**: MySQL/PostgreSQL with tenant isolation
- **Authentication**: Laravel Sanctum
- **Permissions**: Spatie Laravel Permission
- **Multi-Tenancy**: Stancl Tenancy
- **Modules**: Nwidart Laravel Modules

### Frontend (Optional)
- **Framework**: Vue.js 3.x
- **Build Tool**: Vite
- **Styling**: Tailwind CSS
- **UI Components**: AdminLTE (optional)
- **State Management**: Pinia
- **Routing**: Vue Router
- **i18n**: Vue I18n

## 📁 Project Structure

```
ModularSaaS-LaravelVue/
├── app/
│   ├── Core/                          # Core application foundation
│   │   ├── Contracts/                 # Interfaces
│   │   │   ├── RepositoryInterface.php
│   │   │   └── ServiceInterface.php
│   │   ├── Repositories/              # Base repository implementations
│   │   │   └── BaseRepository.php
│   │   ├── Services/                  # Base service implementations
│   │   │   └── BaseService.php
│   │   ├── Traits/                    # Reusable traits
│   │   │   ├── ApiResponse.php        # Consistent API responses
│   │   │   ├── AuditTrait.php         # Audit logging
│   │   │   └── TenantAware.php        # Multi-tenancy support
│   │   ├── DTOs/                      # Data Transfer Objects
│   │   ├── Enums/                     # Enumerations
│   │   └── Exceptions/                # Custom exceptions
│   ├── Http/
│   │   └── Controllers/
│   │       └── Controller.php         # Base API controller
│   └── Providers/
│       └── TenancyServiceProvider.php # Tenancy configuration
├── Modules/                           # Modular application components
│   └── User/                          # Example: User module
│       ├── app/
│       │   ├── Http/
│       │   │   └── Controllers/       # Module controllers
│       │   │       └── UserController.php
│       │   ├── Models/                # Eloquent models
│       │   │   └── User.php
│       │   ├── Repositories/          # Data access layer
│       │   │   └── UserRepository.php
│       │   ├── Services/              # Business logic layer
│       │   │   └── UserService.php
│       │   ├── Requests/              # Form request validation
│       │   │   ├── StoreUserRequest.php
│       │   │   └── UpdateUserRequest.php
│       │   └── Resources/             # API resources
│       │       └── UserResource.php
│       ├── database/
│       │   ├── migrations/            # Module migrations
│       │   └── seeders/               # Module seeders
│       ├── lang/                      # Module translations
│       │   ├── en/
│       │   ├── es/
│       │   └── fr/
│       ├── resources/                 # Module views/assets
│       ├── routes/
│       │   ├── api.php               # API routes
│       │   └── web.php               # Web routes
│       └── tests/                    # Module tests
├── config/
│   ├── modules.php                   # Module configuration
│   ├── permission.php                # Permission configuration
│   └── tenancy.php                   # Tenancy configuration
├── database/
│   └── migrations/                   # Core migrations
├── routes/
│   ├── api.php                       # Core API routes
│   ├── web.php                       # Core web routes
│   └── tenant.php                    # Tenant-specific routes
└── tests/                            # Application tests
```

## 🔧 Installation

### Prerequisites
- PHP 8.3+
- Composer 2.x
- Node.js 18+ & npm
- MySQL 8+ or PostgreSQL 13+

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/kasunvimarshana/ModularSaaS-LaravelVue.git
   cd ModularSaaS-LaravelVue
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   Edit `.env` and set your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=modular_saas
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed initial data (optional)**
   ```bash
   php artisan db:seed
   ```

8. **Install Sanctum**
   ```bash
   php artisan install:api
   ```

9. **Build frontend assets**
   ```bash
   npm run build
   ```

## 🎯 Usage

### Development Server

**Backend (Laravel)**
```bash
php artisan serve
```

**Frontend (Vite)**
```bash
npm run dev
```

### Creating a New Module

```bash
php artisan module:make ModuleName
```

This creates a complete module structure with:
- Controllers, Models, Services, Repositories
- Routes (API & Web)
- Migrations, Seeders
- Views, Assets
- Configuration

### Module Structure Best Practices

Each module should follow this pattern:

```
YourModule/
├── app/
│   ├── Http/Controllers/     # Handle HTTP requests
│   ├── Services/             # Business logic
│   ├── Repositories/         # Data access
│   ├── Models/               # Eloquent models
│   ├── Requests/             # Form validations
│   └── Resources/            # API transformations
```

**Example Implementation:**

```php
// Controller → calls Service
public function store(StoreRequest $request): JsonResponse
{
    $result = $this->service->create($request->validated());
    return $this->createdResponse($result);
}

// Service → orchestrates business logic, calls Repository
public function create(array $data): Model
{
    DB::beginTransaction();
    try {
        $record = $this->repository->create($data);
        DB::commit();
        return $record;
    } catch (Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

// Repository → data access only
public function create(array $data): Model
{
    return $this->model->create($data);
}
```

## 🔐 Security Features

### Authentication
- Token-based authentication using Laravel Sanctum
- API token management
- Password hashing with bcrypt

### Authorization
- Role-based permissions (Admin, Manager, User, etc.)
- Permission-based access control
- Policy-based authorization

### Data Protection
- Input validation on all endpoints
- SQL injection prevention via Eloquent ORM
- XSS protection
- CSRF protection
- Rate limiting

### Audit Trail
- Automatic logging of create, update, delete operations
- User action tracking
- Immutable audit logs
- Structured logging for analysis

## 🌍 Multi-Tenancy

### Tenant Isolation
- **Database**: Each tenant has separate tables/schemas
- **Cache**: Tenant-scoped cache keys
- **Storage**: Tenant-specific file directories
- **Queue**: Tenant context in background jobs

### Tenant Identification
- Domain-based (e.g., `tenant1.app.com`, `tenant2.app.com`)
- Subdomain-based
- Path-based (optional)

### Creating Tenants
```php
use Stancl\Tenancy\Database\Models\Tenant;

$tenant = Tenant::create([
    'id' => 'tenant1',
]);

$tenant->domains()->create([
    'domain' => 'tenant1.app.com',
]);
```

## 🌐 Localization

### Supported Languages
- English (en) - Default
- Spanish (es)
- French (fr)

### Adding Translations
Each module has its own translations in `Modules/{Module}/lang/{locale}/`:

```php
// lang/en/messages.php
return [
    'user_created' => 'User created successfully',
];

// lang/es/messages.php
return [
    'user_created' => 'Usuario creado exitosamente',
];
```

### Usage in Code
```php
__('user::messages.user_created')
```

## 🧪 Testing

### Running Tests
```bash
# All tests
php artisan test

# Specific test suite
php artisan test --testsuite=Feature

# With coverage
php artisan test --coverage
```

### Writing Tests
```php
public function test_can_create_user(): void
{
    $data = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ];

    $response = $this->postJson('/api/v1/users', $data);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'name', 'email'],
        ]);
}
```

## 📝 Code Quality

### Standards
- **PSR-12**: Coding standard
- **PHPDoc**: Full documentation
- **Type Declarations**: Strict types
- **SOLID Principles**: Applied throughout

### Tools
```bash
# Format code
./vendor/bin/pint

# Static analysis (install separately)
./vendor/bin/phpstan analyse

# Code sniffer
./vendor/bin/phpcs
```

## 🚢 Deployment

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure proper database credentials
- [ ] Set up queue workers
- [ ] Configure caching (Redis/Memcached)
- [ ] Set up supervisor for queues
- [ ] Configure SSL certificates
- [ ] Enable HTTPS
- [ ] Set up backups
- [ ] Configure monitoring
- [ ] Run migrations
- [ ] Optimize autoloader: `composer install --optimize-autoloader --no-dev`
- [ ] Cache config: `php artisan config:cache`
- [ ] Cache routes: `php artisan route:cache`
- [ ] Cache views: `php artisan view:cache`

## 📚 API Documentation

### Base URL
```
http://localhost:8000/api/v1
```

### Authentication
All API endpoints require authentication using Bearer tokens:
```
Authorization: Bearer {token}
```

### Example Endpoints

**Users**
- `GET /users` - List all users
- `POST /users` - Create a user
- `GET /users/{id}` - Get user details
- `PUT /users/{id}` - Update user
- `DELETE /users/{id}` - Delete user
- `POST /users/{id}/assign-role` - Assign role
- `POST /users/{id}/revoke-role` - Revoke role

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit changes: `git commit -am 'Add some feature'`
4. Push to branch: `git push origin feature/your-feature`
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License.

## 👨‍💻 Author

Built with ❤️ following Laravel and Vue.js best practices.

## 🔗 Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org)
- [Tailwind CSS](https://tailwindcss.com)
- [Laravel Modules](https://nwidart.com/laravel-modules/)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)
- [Stancl Tenancy](https://tenancyforlaravel.com)
