# User Model Usage Guidelines

## Overview

This document clarifies the usage of the User model in the ModularSaaS application. The system uses a single, centralized User model to maintain consistency and avoid duplication.

## The User Model

### App\Models\User

**Location**: `/app/Models/User.php`

**Purpose**: The single, authoritative User model used throughout the entire application for authentication, authorization, and user management.

**Features**:
- ✅ Strict types declaration (`declare(strict_types=1)`)
- ✅ Full PHPDoc documentation
- ✅ Implements `MustVerifyEmail` interface
- ✅ Uses `AuditTrait` for automatic audit logging
- ✅ Uses `HasApiTokens` for Sanctum authentication
- ✅ Uses `HasRoles` for Spatie permission system
- ✅ Configured in `config/auth.php` as default model

**Traits**:
```php
use HasFactory;
use Notifiable;
use HasApiTokens;
use HasRoles;
use AuditTrait;
```

**When to Use**:
- ✅ Authentication module (login, register, password reset)
- ✅ Authorization checks (permissions, roles, policies)
- ✅ API token management
- ✅ User module operations (CRUD)
- ✅ All application services
- ✅ Configuration files
- ✅ Middleware and policies
- ✅ Tests and factories

## Architecture Decision

### Why Single Model?

The decision to use a single User model provides several benefits:

1. **Single Source of Truth**: One model definition ensures consistency across the entire application
2. **No Synchronization Issues**: Eliminates the need to keep multiple models in sync
3. **Simpler Maintenance**: Changes only need to be made in one place
4. **Clearer Ownership**: Unambiguous model responsibility
5. **Easier Testing**: One factory, one set of tests
6. **Better Performance**: No confusion about which model to use

### Modular Architecture Maintained

While we use a single User model, the modular architecture is still maintained through:

- **Module-Specific Controllers**: Each module has its own controllers
- **Module-Specific Services**: Business logic is modular
- **Module-Specific Repositories**: Data access patterns per module
- **Module-Specific Tests**: Independent test suites
- **Module-Specific Routes**: Isolated routing

The User model is a **shared domain entity** that multiple modules interact with, similar to how multiple modules might interact with a shared database or cache system.

## Best Practices

### Rule 1: Always Use App\Models\User

All user-related operations should use the centralized model:

```php
// ✅ CORRECT
use App\Models\User;

class UserService
{
    public function create(array $data): User
    {
        return User::create($data);
    }
}
```

```php
// ❌ WRONG - Don't create module-specific User models
namespace Modules\User\Models;
class User extends Authenticatable { } // Don't do this!
```

### Rule 2: Use Module-Specific Business Logic

Keep module-specific logic in services and controllers:

```php
// ✅ CORRECT - Module-specific service
namespace Modules\User\Services;

use App\Models\User;

class UserService extends BaseService
{
    public function create(array $data): User
    {
        // Module-specific business logic
        DB::beginTransaction();
        try {
            $user = $this->repository->create($data);
            event(new UserCreated($user));
            DB::commit();
            return $user;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

### Rule 3: Config Files Use App\Models\User

Configuration files reference the centralized model:

```php
// config/auth.php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => env('AUTH_MODEL', App\Models\User::class), // ✅ CORRECT
    ],
],
```

### Rule 4: Policies and Middleware Use App\Models\User

Security components use the centralized model:

```php
// ✅ CORRECT
use App\Models\User;

class UserPolicy
{
    public function update(User $user, User $targetUser): bool
    {
        return $user->id === $targetUser->id || $user->hasRole('admin');
    }
}
```

### Rule 5: Factory Usage

Use the centralized factory:

```php
// database/factories/UserFactory.php
use App\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;
    
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ];
    }
}

// In tests
$user = User::factory()->create();
```

## Code Examples

### Authentication Use Case

```php
// Modules/Auth/app/Services/AuthService.php
use App\Models\User;

public function register(array $data): array
{
    $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
    ]);
    
    $user->assignRole($data['role'] ?? 'user');
    
    return [
        'user' => $user,
        'token' => $user->createToken('auth-token')->plainTextToken,
    ];
}
```

### User Management Use Case

```php
// Modules/User/app/Services/UserService.php
use App\Models\User;

public function create(array $data): Model
{
    $user = $this->repository->create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
    ]);
    
    return $user;
}
```

### Policy Use Case

```php
// Modules/Auth/app/Policies/UserPolicy.php
use App\Models\User;

public function update(User $currentUser, User $targetUser): bool
{
    // Super admin can update anyone
    if ($currentUser->hasRole('super-admin')) {
        return true;
    }
    
    // Users can update themselves
    if ($currentUser->id === $targetUser->id) {
        return true;
    }
    
    return false;
}
```

### Repository Use Case

```php
// Modules/User/app/Repositories/UserRepository.php
use App\Models\User;
use App\Core\Repositories\BaseRepository;

class UserRepository extends BaseRepository
{
    protected function makeModel(): Model
    {
        return new User;
    }
    
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }
}
```

## Testing

### Feature Tests

```php
// Modules/User/tests/Feature/UserApiTest.php
use App\Models\User;

public function test_user_can_be_created(): void
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
    
    $response->assertStatus(201);
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
}
```

### Unit Tests

```php
// tests/Unit/UserTest.php
use App\Models\User;

public function test_user_has_roles(): void
{
    $user = User::factory()->create();
    $user->assignRole('admin');
    
    $this->assertTrue($user->hasRole('admin'));
}
```

## Troubleshooting

### Issue: Cannot find User class

**Problem**: Import statement pointing to non-existent module model.

**Solution**: Update import to use centralized model:
```php
// Change from:
use Modules\User\Models\User;

// To:
use App\Models\User;
```

### Issue: Factory not found

**Problem**: Trying to use module-specific factory that doesn't exist.

**Solution**: Use the centralized factory:
```php
// ✅ CORRECT
use App\Models\User;
$user = User::factory()->create();
```

### Issue: Type hint mismatch

**Problem**: Type hints expecting module-specific model.

**Solution**: Update all type hints to use centralized model:
```php
// Change from:
public function update(Modules\User\Models\User $user): void

// To:
public function update(App\Models\User $user): void
// Or simply:
public function update(User $user): void
// (with use App\Models\User; at top)
```

## Migration from Dual Models

If you previously had dual User models, here's how to migrate:

### Step 1: Update Imports

Find and replace all module-specific imports:
```bash
# Find files with module-specific imports
grep -r "use Modules\\\\User\\\\Models\\\\User" --include="*.php"

# Update to:
use App\Models\User;
```

### Step 2: Update Type Hints

Update all type hints in repositories, services, and controllers:
```php
// Before:
use Modules\User\Models\User;

public function findByEmail(string $email): ?User

// After:
use App\Models\User;

public function findByEmail(string $email): ?User
```

### Step 3: Remove Module Model

Delete the module-specific User model:
```bash
rm Modules/User/app/Models/User.php
rm Modules/User/database/factories/UserFactory.php
```

### Step 4: Run Tests

Verify everything works:
```bash
vendor/bin/phpunit
```

## Summary

- **App\Models\User**: The single, authoritative User model for the entire application
- **Location**: `/app/Models/User.php`
- **Usage**: Use everywhere for all user-related operations
- **Benefits**: Single source of truth, easier maintenance, no synchronization issues
- **Modular Architecture**: Still maintained through module-specific services, controllers, and repositories

The User model is a **shared domain entity** that all modules can use, similar to how modules share the framework, database, and other core infrastructure.

## Related Documentation

- [ARCHITECTURE.md](ARCHITECTURE.md) - System architecture
- [SECURITY.md](SECURITY.md) - Security implementation
- [AUTH_SETUP_GUIDE.md](AUTH_SETUP_GUIDE.md) - Authentication setup
- [Modules/Auth/README.md](Modules/Auth/README.md) - Auth module docs
- [AUTH_AUDIT_SUMMARY.md](AUTH_AUDIT_SUMMARY.md) - Complete audit details
