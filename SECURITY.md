# Security Implementation Guide

## Overview

This document provides comprehensive security implementation guidelines for the ModularSaaS application, covering authentication, authorization, data protection, and security best practices.

## Table of Contents

1. [Authentication](#authentication)
2. [Authorization (RBAC/ABAC)](#authorization-rbacabac)
3. [Data Protection](#data-protection)
4. [Input Validation](#input-validation)
5. [Encryption](#encryption)
6. [Audit Logging](#audit-logging)
7. [Multi-Tenancy Security](#multi-tenancy-security)
8. [API Security](#api-security)
9. [Best Practices](#best-practices)

## Authentication

### Laravel Sanctum Setup

The application uses Laravel Sanctum for token-based API authentication.

#### Issuing Tokens

```php
use Illuminate\Support\Facades\Hash;
use App\Models\User;

// Login endpoint
public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials',
        ], 401);
    }

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'success' => true,
        'data' => [
            'user' => $user,
            'token' => $token,
        ],
    ]);
}
```

#### Protecting Routes

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
```

#### Token Revocation

```php
// Revoke current token
$request->user()->currentAccessToken()->delete();

// Revoke all tokens
$request->user()->tokens()->delete();
```

## Authorization (RBAC/ABAC)

### Role-Based Access Control (RBAC)

#### Defining Roles and Permissions

```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Create roles
$superAdmin = Role::create(['name' => 'super-admin']);
$admin = Role::create(['name' => 'admin']);
$user = Role::create(['name' => 'user']);

// Create permissions
$permissions = [
    'user.create',
    'user.read',
    'user.update',
    'user.delete',
];

foreach ($permissions as $permission) {
    Permission::create(['name' => $permission]);
}

// Assign permissions to roles
$superAdmin->givePermissionTo(Permission::all());
$admin->givePermissionTo(['user.read', 'user.update']);
```

#### Checking Permissions

```php
// In controller
if (!$user->can('user.create')) {
    abort(403, 'Unauthorized action');
}

// In blade
@can('user.create')
    <!-- Show create button -->
@endcan

// Using middleware
Route::middleware(['auth:sanctum', 'permission:user.create'])
    ->post('/users', [UserController::class, 'store']);
```

### Attribute-Based Access Control (ABAC)

#### Using Policies

```php
// app/Policies/UserPolicy.php
namespace App\Policies;

use App\Core\Policies\BasePolicy;

class UserPolicy extends BasePolicy
{
    public function update($currentUser, $targetUser)
    {
        // Super admin can update any user
        if ($this->isSuperAdmin($currentUser)) {
            return true;
        }

        // Users can update themselves
        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        // Admins can update users in same tenant
        if ($this->isAdmin($currentUser) && 
            $this->isSameTenant($currentUser, $targetUser)) {
            return true;
        }

        return false;
    }
}
```

#### Registering Policies

```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    User::class => UserPolicy::class,
];
```

#### Using Policies in Controllers

```php
public function update(Request $request, User $user)
{
    $this->authorize('update', $user);
    
    // Update logic here
}
```

## Data Protection

### Mass Assignment Protection

```php
// In models
protected $fillable = ['name', 'email'];
// OR
protected $guarded = ['id', 'is_admin'];
```

### SQL Injection Prevention

Always use Eloquent ORM or Query Builder with parameter binding:

```php
// ✅ GOOD - Parameterized query
User::where('email', $email)->first();

// ✅ GOOD - Query builder with bindings
DB::table('users')->where('email', '=', $email)->first();

// ❌ BAD - Raw SQL without bindings
DB::select("SELECT * FROM users WHERE email = '$email'");
```

### XSS Prevention

```php
// In Blade templates - auto-escaped
{{ $user->name }}

// Raw output (avoid unless necessary)
{!! $user->bio !!}

// In API responses - use Resources
class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name, // Automatically escaped
        ];
    }
}
```

## Input Validation

### Using Form Requests

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered',
            'password.min' => 'Password must be at least 8 characters',
        ];
    }
}
```

### Custom Validation Rules

```php
namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class StrongPassword implements Rule
{
    public function passes($attribute, $value)
    {
        return preg_match(
            '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            $value
        );
    }

    public function message()
    {
        return 'Password must contain uppercase, lowercase, number, and special character';
    }
}
```

## Encryption

### Using Encryption Helper

```php
use App\Core\Helpers\EncryptionHelper;

// Encrypt data
$encrypted = EncryptionHelper::encrypt($sensitiveData);

// Decrypt data
$decrypted = EncryptionHelper::decrypt($encrypted);

// Hash password
$hashed = EncryptionHelper::hashPassword($password);

// Verify password
$valid = EncryptionHelper::verifyPassword($password, $hashed);
```

### Database Encryption

```php
// In model
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function socialSecurityNumber(): Attribute
{
    return Attribute::make(
        get: fn ($value) => decrypt($value),
        set: fn ($value) => encrypt($value),
    );
}
```

## Audit Logging

### Using Audit Trait

```php
namespace App\Models;

use App\Core\Traits\AuditTrait;

class User extends Model
{
    use AuditTrait;
    
    // Automatically logs create, update, delete operations
}
```

### Custom Audit Logging

```php
use Illuminate\Support\Facades\Log;

Log::info('User action', [
    'action' => 'login',
    'user_id' => $user->id,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
    'timestamp' => now(),
]);
```

### Structured Logging

```php
Log::channel('audit')->info('Resource accessed', [
    'user_id' => auth()->id(),
    'resource_type' => 'User',
    'resource_id' => $user->id,
    'action' => 'view',
    'tenant_id' => tenant('id'),
    'ip_address' => request()->ip(),
]);
```

## Multi-Tenancy Security

### Tenant Isolation

```php
// Using TenantAware trait in models
use App\Core\Traits\TenantAware;

class Product extends Model
{
    use TenantAware;
    
    // Automatically scoped to current tenant
}
```

### Middleware Protection

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
});
```

### Preventing Cross-Tenant Access

```php
public function show(Product $product)
{
    // Verify product belongs to current tenant
    if ($product->tenant_id !== tenant('id')) {
        abort(403, 'Access denied');
    }
    
    return new ProductResource($product);
}
```

## API Security

### Rate Limiting

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        'throttle:api', // 60 requests per minute
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];

// Custom rate limits
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});
```

### CORS Configuration

```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### API Versioning

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::apiResource('users', UserController::class);
});

Route::prefix('v2')->group(function () {
    Route::apiResource('users', V2\UserController::class);
});
```

## Best Practices

### 1. Never Trust User Input

Always validate and sanitize all user inputs.

### 2. Use HTTPS in Production

```env
APP_URL=https://yourdomain.com
SESSION_SECURE_COOKIE=true
```

### 3. Keep Dependencies Updated

```bash
composer update
npm update
```

### 4. Use Environment Variables

```php
// ✅ GOOD
$apiKey = env('API_KEY');

// ❌ BAD
$apiKey = 'hardcoded-key';
```

### 5. Implement Password Policies

```php
'password' => [
    'required',
    'min:8',
    'confirmed',
    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/',
]
```

### 6. Regular Security Audits

```bash
# Run security audit
composer audit

# Update security vulnerabilities
composer update --with-dependencies
```

### 7. Secure Session Configuration

```env
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
```

### 8. Database Security

```php
// Use prepared statements
DB::table('users')->where('id', $id)->update(['status' => $status]);

// Enable query logging in development only
if (app()->environment('local')) {
    DB::enableQueryLog();
}
```

### 9. Error Handling

```php
// app/Exceptions/Handler.php
public function render($request, Throwable $exception)
{
    if ($request->wantsJson()) {
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }
        
        // Hide sensitive error details in production
        if (app()->environment('production')) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
            ], 500);
        }
    }
    
    return parent::render($request, $exception);
}
```

### 10. Security Headers

Add security headers in middleware:

```php
return $next($request)
    ->header('X-Content-Type-Options', 'nosniff')
    ->header('X-Frame-Options', 'DENY')
    ->header('X-XSS-Protection', '1; mode=block')
    ->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
```

## Security Checklist

- [ ] All routes protected with authentication
- [ ] Authorization implemented (RBAC/ABAC)
- [ ] Input validation on all endpoints
- [ ] SQL injection protection via ORM
- [ ] XSS protection enabled
- [ ] CSRF protection enabled
- [ ] Rate limiting configured
- [ ] HTTPS enabled in production
- [ ] Secure session configuration
- [ ] Password hashing implemented
- [ ] Audit logging enabled
- [ ] Tenant isolation enforced
- [ ] Error messages sanitized
- [ ] Security headers configured
- [ ] Dependencies up to date
- [ ] Regular security audits scheduled

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [Spatie Permission Documentation](https://spatie.be/docs/laravel-permission)
