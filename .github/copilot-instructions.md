# GitHub Copilot Instructions for ModularSaaS-LaravelVue

This document provides GitHub Copilot coding agent with project-specific guidelines, patterns, and conventions for this repository.

## Project Overview

ModularSaaS is a production-ready, enterprise-grade modular SaaS application built with Laravel 11 and Vue.js 3. It follows a strict **Modular Architecture** with the **Controller → Service → Repository** pattern, implementing multi-tenancy, RBAC, and enterprise security standards.

## Core Architecture Pattern

### Controller → Service → Repository Flow

**ALWAYS follow this strict flow**:

```
HTTP Request → Controller → Service → Repository → Model → Database
```

**Controller** (HTTP/Presentation Layer):
- Handle HTTP requests and responses ONLY
- Validate input using FormRequest classes
- Delegate business logic to Services
- Transform output using API Resources
- Never access repositories directly
- Never contain business logic

**Service** (Business Logic Layer):
- Contain ALL business logic and orchestration
- Manage database transactions
- Call repositories for data operations
- Trigger events and notifications
- Handle complex business rules
- Never access models directly

**Repository** (Data Access Layer):
- Handle database queries ONLY
- No business logic
- Return models or collections
- Implement query scopes and filters
- Abstract database operations

### Example Implementation

```php
// Controller - Minimal, delegates to service
public function store(StoreUserRequest $request): JsonResponse
{
    $user = $this->userService->create($request->validated());
    return $this->createdResponse(new UserResource($user));
}

// Service - Business logic with transaction
public function create(array $data): Model
{
    DB::beginTransaction();
    try {
        $user = $this->repository->create($data);
        event(new UserCreated($user));
        DB::commit();
        return $user;
    } catch (Exception $e) {
        DB::rollBack();
        throw new ServiceException($e->getMessage());
    }
}

// Repository - Data access only
public function create(array $data): Model
{
    return $this->model->create($data);
}
```

## Module Structure

### Creating New Modules

Use Laravel Modules package: `php artisan module:make ModuleName`

**Required Structure**:
```
ModuleName/
├── app/
│   ├── Http/
│   │   ├── Controllers/    # HTTP handlers
│   │   ├── Requests/       # Form validation
│   │   └── Resources/      # API transformations
│   ├── Models/             # Eloquent models
│   ├── Repositories/       # Data access
│   └── Services/          # Business logic
├── database/
│   ├── migrations/        # Schema definitions
│   ├── seeders/          # Test data
│   └── factories/        # Model factories
├── lang/
│   ├── en/              # English translations
│   ├── es/              # Spanish translations
│   └── fr/              # French translations
├── routes/
│   ├── api.php          # API routes
│   └── web.php          # Web routes
└── tests/
    ├── Feature/         # Integration tests
    └── Unit/           # Unit tests
```

## Coding Standards

### PHP Standards

**MUST follow**:
- PSR-12 coding standard
- Strict types: `declare(strict_types=1);` at the top of every PHP file
- Type hints on ALL parameters, return types, and properties (PHP 8.2+ required, 8.3+ recommended)
- PHPDoc blocks on ALL classes and methods
- SOLID principles

**Code Formatting**:
- Use Laravel Pint: `./vendor/bin/pint`
- Run Pint before committing

### Naming Conventions

**Classes** (PascalCase):
- Controllers: `UserController`, `PostController`
- Services: `UserService`, `AuthenticationService`
- Repositories: `UserRepository`, `PostRepository`
- Models: Singular form - `User`, `Post`, `Comment`
- Requests: `StoreUserRequest`, `UpdateUserRequest`
- Resources: `UserResource`, `PostResource`

**Methods** (camelCase):
- Actions: `createUser()`, `deletePost()`, `sendEmail()`
- Retrievals: `getUsers()`, `getUserById()`, `findByEmail()`
- Boolean checks: `isActive()`, `hasPermission()`, `canAccess()`

**Variables** (camelCase):
- Descriptive: `$userEmail`, `$postTitle` (not `$ue`, `$pt`)
- Booleans: `$isActive`, `$hasPermission`, `$canEdit`
- Collections: Plural - `$users`, `$posts`

**Constants** (UPPER_SNAKE_CASE):
- `MAX_LOGIN_ATTEMPTS`, `DEFAULT_CACHE_TTL`

### Dependency Injection

**ALWAYS use constructor injection** with readonly properties (PHP 8.1+):

```php
public function __construct(
    private readonly UserService $userService,
    private readonly RoleService $roleService,
    private readonly CacheHelper $cache
) {}
```

## Multi-Tenancy

### Tenant Awareness

- Use `stancl/tenancy` package for multi-tenancy
- All models in modules MUST be tenant-aware
- Use `TenantAware` trait for automatic tenant scoping
- Tenant context is managed via domains/subdomains
- Each tenant has isolated database, cache, and file storage

**Example**:
```php
use App\Core\Traits\TenantAware;

class Post extends Model
{
    use TenantAware;
    
    // Model automatically scoped to current tenant
}
```

### Tenant Isolation

- Database: Separate tables/schemas per tenant
- Cache: Tenant-specific keys using `CacheHelper`
- Storage: Tenant-specific directories
- Queue Jobs: Pass tenant context

## Security & Authorization

### Authentication

- Use Laravel Sanctum for API authentication
- Token-based auth for API endpoints
- All API routes MUST require authentication unless explicitly public

### Authorization

- Use Spatie Laravel Permission for RBAC
- Define policies for all resources
- Use middleware: `CheckPermission`, `CheckRole`
- Implement ABAC where granular control needed

**Example**:
```php
// In routes
Route::middleware(['auth:sanctum', 'permission:user.create'])
    ->post('/users', [UserController::class, 'store']);

// In Policy
public function update(User $user, Post $post): bool
{
    return $user->hasPermissionTo('post.update') 
        && $post->user_id === $user->id;
}
```

### Input Validation

- ALWAYS use FormRequest classes for validation
- Never trust user input
- Validate in request, not controller
- Use custom validation rules when needed

### Audit Trail

- Use `AuditTrait` for automatic logging
- Log all create, update, delete operations
- Include user context in logs
- Store immutable audit records

## Testing Requirements

### Test Coverage Requirements

**MANDATORY**:
- All service methods MUST have unit tests
- All API endpoints MUST have feature tests
- Critical business logic MUST have 100% coverage
- Test both success and failure scenarios

### Writing Tests

**Feature Tests** (API endpoints):
```php
public function test_user_can_be_created(): void
{
    $data = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/users', $data);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'name', 'email'],
        ]);

    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
}
```

**Unit Tests** (Service logic):
```php
public function test_service_creates_user_with_hashed_password(): void
{
    $data = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ];

    $user = $this->userService->create($data);

    $this->assertNotEquals('password', $user->password);
    $this->assertTrue(Hash::check('password', $user->password));
}
```

### Running Tests

```bash
# All tests
php artisan test

# Specific suite
php artisan test --testsuite=Feature

# With coverage
php artisan test --coverage

# Parallel
php artisan test --parallel
```

## Localization (i18n)

### Multi-Language Support

- Support: English (en), Spanish (es), French (fr)
- Each module has its own translations in `lang/` directory
- Use translation keys, not hardcoded strings

**Usage**:
```php
// In code
__('user::messages.user_created')

// In views
{{ __('user::validation.email_required') }}

// In Vue components
$t('user.messages.user_created')
```

### Translation Files

```php
// Modules/User/lang/en/messages.php
return [
    'user_created' => 'User created successfully',
    'user_updated' => 'User updated successfully',
];
```

## Error Handling

### Exception Hierarchy

Use custom exceptions from `App\Core\Exceptions`:
- `ServiceException` - Business logic errors
- `RepositoryException` - Data access errors
- `TenantException` - Multi-tenancy errors
- `ValidationException` - Input validation errors

**Example**:
```php
use App\Core\Exceptions\ServiceException;

try {
    $result = $this->repository->create($data);
} catch (Exception $e) {
    Log::error('User creation failed', ['error' => $e->getMessage()]);
    throw new ServiceException('Failed to create user: ' . $e->getMessage());
}
```

### API Error Responses

Use `ApiResponse` trait for consistent responses:

```php
// Success
return $this->successResponse($data, 'Operation successful');

// Created
return $this->createdResponse($data, 'Resource created');

// Error
return $this->errorResponse('Error message', 400);

// Not Found
return $this->notFoundResponse('Resource not found');

// Validation Error
return $this->validationErrorResponse($errors);
```

## Performance Best Practices

### Database Optimization

- **Avoid N+1 queries**: Always use eager loading
```php
// Bad
$users = User::all();
foreach ($users as $user) {
    $user->posts; // N+1 query
}

// Good
$users = User::with('posts')->get();
```

- **Pagination**: Always paginate large datasets
```php
return $this->repository->paginate(15);
```

### Caching

- Use `CacheHelper` for tenant-aware caching
- Cache expensive operations
- Set appropriate TTLs using `CacheDuration` enum

```php
use App\Core\Helpers\CacheHelper;
use App\Core\Enums\CacheDuration;

$users = CacheHelper::remember(
    'users.active',
    CacheDuration::ONE_HOUR->value,
    fn() => $this->repository->getActive()
);
```

### Queue Jobs

- Use queues for heavy operations
- Email sending, file processing, reports
- Pass tenant context in job payload

## Vue.js Frontend (Optional)

### Framework & Tools

- Vue.js 3 with Composition API
- Vite for building
- Tailwind CSS for styling
- Pinia for state management
- Vue Router for routing
- Vue I18n for internationalization

### Build Commands

```bash
# Development
npm run dev

# Production build
npm run build
```

## Database Migrations

### Best Practices

- One migration per change
- Reversible migrations (up/down methods)
- Use descriptive names
- Include foreign key constraints
- Add indexes for queried columns

**Naming**:
- Create: `create_users_table`
- Add column: `add_status_to_users_table`
- Modify: `modify_users_table_add_indexes`

## Git Workflow

### Commit Messages

Follow Conventional Commits:

```
<type>(<scope>): <subject>

feat(user): add email verification endpoint
fix(auth): resolve token expiration issue
docs(readme): update installation instructions
refactor(service): extract common logic to base service
test(user): add user creation tests
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation only
- `style`: Code formatting (no logic change)
- `refactor`: Code refactoring
- `test`: Adding/updating tests
- `chore`: Maintenance tasks

## Documentation Requirements

### PHPDoc Requirements

**All classes and methods MUST have PHPDoc**:

```php
/**
 * User Service
 * 
 * Handles business logic for user management including
 * creation, updates, role assignment, and permissions.
 */
class UserService
{
    /**
     * Create a new user
     *
     * @param array<string, mixed> $data User data
     * @return Model The created user model
     * @throws ServiceException If user creation fails
     */
    public function create(array $data): Model
    {
        // Implementation
    }
}
```

### README Updates

- Update README.md for major features
- Include examples and usage
- Update API documentation section
- Keep installation instructions current

## Common Patterns

### DTOs (Data Transfer Objects)

Use for complex data structures:

```php
use App\Core\DTOs\BaseDTO;

class UserDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,
    ) {}
}
```

### Enums

Use for type-safe constants (PHP 8.1+):

```php
enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
}

// Usage
$user->status = UserStatus::ACTIVE->value;
```

### Query Scopes

Use for reusable query logic:

```php
use App\Core\Scopes\ActiveScope;
use App\Core\Scopes\Filterable;

class User extends Model
{
    use Filterable;
    
    protected static function booted(): void
    {
        static::addGlobalScope(new ActiveScope());
    }
    
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }
}
```

## Tools & Commands

### Development

```bash
# Start development server
php artisan serve

# Watch and compile assets
npm run dev

# Run all development services
composer dev
```

### Code Quality

```bash
# Format code
./vendor/bin/pint

# Run tests
php artisan test

# Static analysis (if configured)
./vendor/bin/phpstan analyse
```

### Module Management

```bash
# Create module
php artisan module:make ModuleName

# List modules
php artisan module:list

# Enable module
php artisan module:enable ModuleName

# Disable module
php artisan module:disable ModuleName
```

## Important Files

- **ARCHITECTURE.md** - Detailed architecture documentation
- **CONTRIBUTING.md** - Contribution guidelines
- **SECURITY.md** - Security implementation guide
- **DEPLOYMENT.md** - Production deployment guide
- **README.md** - Project overview and quick start

## Key Dependencies

### Backend
- Laravel 11.x (LTS)
- PHP 8.2+ (required), 8.3+ (recommended for advanced features)
- nwidart/laravel-modules - Modular architecture
- spatie/laravel-permission - RBAC
- stancl/tenancy - Multi-tenancy
- laravel/sanctum - API authentication

### Frontend
- Vue.js 3.x
- Vite
- Tailwind CSS

## Final Reminders

1. **ALWAYS** follow Controller → Service → Repository pattern
2. **NEVER** put business logic in controllers or repositories
3. **ALWAYS** use strict types and type hints
4. **ALWAYS** write tests for new functionality
5. **ALWAYS** use dependency injection
6. **ALWAYS** consider multi-tenancy implications
7. **ALWAYS** validate and sanitize input
8. **ALWAYS** use FormRequests for validation
9. **ALWAYS** use API Resources for responses
10. **ALWAYS** format code with Pint before committing
11. **ALWAYS** add PHPDoc comments
12. **ALWAYS** check for N+1 queries
13. **ALWAYS** use transactions for multi-step operations
14. **ALWAYS** handle exceptions properly
15. **ALWAYS** follow PSR-12 coding standards

## Questions?

Refer to:
- Project documentation in root directory
- Laravel documentation: https://laravel.com/docs
- Vue.js documentation: https://vuejs.org
- Laravel Modules: https://nwidart.com/laravel-modules/
