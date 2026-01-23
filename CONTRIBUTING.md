# Contributing to ModularSaaS

Thank you for considering contributing to ModularSaaS! This document provides guidelines and best practices for contributing to this project.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Architecture Guidelines](#architecture-guidelines)
- [Testing Guidelines](#testing-guidelines)
- [Pull Request Process](#pull-request-process)
- [Commit Message Guidelines](#commit-message-guidelines)

## Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to the project maintainers.

## Getting Started

### Prerequisites

Before you begin, ensure you have:
- PHP 8.3+
- Composer 2.x
- Node.js 18+ & npm
- MySQL 8+ or PostgreSQL 13+
- Git

### Setting Up Development Environment

1. **Fork and clone the repository**
   ```bash
   git clone https://github.com/YOUR_USERNAME/ModularSaaS-LaravelVue.git
   cd ModularSaaS-LaravelVue
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Set up database**
   ```bash
   # Create database
   mysql -u root -p -e "CREATE DATABASE modular_saas"
   
   # Run migrations
   php artisan migrate
   
   # Seed database (optional)
   php artisan db:seed
   ```

5. **Run tests**
   ```bash
   php artisan test
   ```

## Development Workflow

### Branching Strategy

We use Git Flow:

- `main`: Production-ready code
- `develop`: Integration branch for features
- `feature/*`: New features
- `bugfix/*`: Bug fixes
- `hotfix/*`: Urgent production fixes
- `release/*`: Release preparation

### Creating a Feature Branch

```bash
git checkout develop
git pull origin develop
git checkout -b feature/your-feature-name
```

### Working on Your Feature

1. Make your changes
2. Write/update tests
3. Run tests: `php artisan test`
4. Run code formatter: `./vendor/bin/pint`
5. Commit your changes

## Coding Standards

### PHP Standards

We follow **PSR-12** coding standard with additional Laravel conventions.

**Key Points**:
- Use strict types: `declare(strict_types=1);`
- Type hint everything (parameters, return types, properties)
- Use PHPDoc blocks for documentation
- Follow SOLID principles
- Keep methods small and focused

**Example**:
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Model;

/**
 * User Service
 * 
 * Handles business logic for user operations
 */
class UserService
{
    /**
     * Create a new user
     *
     * @param array<string, mixed> $data
     * @return Model
     */
    public function create(array $data): Model
    {
        // Implementation
    }
}
```

### Naming Conventions

**Classes**:
- PascalCase
- Descriptive names
- Controllers: `UserController`
- Services: `UserService`
- Repositories: `UserRepository`
- Models: `User` (singular)

**Methods**:
- camelCase
- Verb-based for actions: `createUser()`, `deletePost()`
- Get for retrievals: `getUsers()`, `getUserById()`

**Variables**:
- camelCase
- Descriptive: `$userEmail` not `$ue`
- Boolean prefix: `$isActive`, `$hasPermission`

**Constants**:
- UPPER_SNAKE_CASE
- Example: `MAX_LOGIN_ATTEMPTS`

### Code Formatting

We use **Laravel Pint** for automatic code formatting.

```bash
# Format all files
./vendor/bin/pint

# Format specific file
./vendor/bin/pint app/Services/UserService.php

# Check without fixing
./vendor/bin/pint --test
```

## Architecture Guidelines

### Module Creation

When creating a new module:

```bash
php artisan module:make ModuleName
```

Then organize it following this structure:

```
ModuleName/
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   ├── Repositories/
│   ├── Services/
│   ├── Requests/
│   └── Resources/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── lang/
│   ├── en/
│   ├── es/
│   └── fr/
├── routes/
│   ├── api.php
│   └── web.php
└── tests/
    ├── Feature/
    └── Unit/
```

### Controller → Service → Repository Pattern

**Always follow this flow**:

```
Request → Controller → Service → Repository → Database
```

**Controller** (HTTP layer):
```php
public function store(StoreUserRequest $request): JsonResponse
{
    $user = $this->userService->create($request->validated());
    return $this->createdResponse(new UserResource($user));
}
```

**Service** (Business logic):
```php
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
        throw $e;
    }
}
```

**Repository** (Data access):
```php
public function create(array $data): Model
{
    return $this->model->create($data);
}
```

### Dependency Injection

**Always use constructor injection**:

```php
public function __construct(
    private readonly UserService $userService,
    private readonly RoleService $roleService
) {}
```

### Error Handling

**Use try-catch appropriately**:

```php
try {
    $result = $this->service->doSomething();
} catch (ValidationException $e) {
    return $this->validationErrorResponse($e->errors());
} catch (NotFoundException $e) {
    return $this->notFoundResponse($e->getMessage());
} catch (Exception $e) {
    Log::error('Unexpected error', ['error' => $e->getMessage()]);
    return $this->errorResponse('An unexpected error occurred');
}
```

## Testing Guidelines

### Test Coverage

**Required**:
- All service methods must have unit tests
- All API endpoints must have feature tests
- Critical business logic must have 100% coverage

### Writing Tests

**Feature Test Example**:
```php
public function test_user_can_be_created(): void
{
    $data = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/users', $data);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'name', 'email'],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);
}
```

**Unit Test Example**:
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

# Specific test file
php artisan test tests/Feature/UserApiTest.php

# Specific test method
php artisan test --filter=test_user_can_be_created

# With coverage
php artisan test --coverage

# Parallel testing
php artisan test --parallel
```

## Pull Request Process

### Before Submitting

1. **Update documentation** if adding features
2. **Write tests** for new functionality
3. **Run all tests** and ensure they pass
4. **Format code** using Pint
5. **Update CHANGELOG.md** (if applicable)
6. **Rebase** on latest develop branch

### PR Template

When creating a PR, include:

**Title**: Brief description (e.g., "Add user profile management feature")

**Description**:
```markdown
## Changes
- Added UserProfileController
- Added profile update endpoint
- Added profile image upload

## Testing
- Added feature tests for profile endpoints
- All existing tests pass

## Screenshots
[If UI changes]

## Checklist
- [ ] Tests added/updated
- [ ] Documentation updated
- [ ] Code formatted with Pint
- [ ] No breaking changes
- [ ] CHANGELOG.md updated
```

### Review Process

1. Automated tests must pass
2. Code review by at least one maintainer
3. No merge conflicts
4. Approved by maintainer
5. Squash and merge

## Commit Message Guidelines

We follow **Conventional Commits** specification.

### Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding/updating tests
- `chore`: Maintenance tasks

### Examples

```bash
# Feature
git commit -m "feat(user): add email verification endpoint"

# Bug fix
git commit -m "fix(auth): resolve token expiration issue"

# Documentation
git commit -m "docs(readme): update installation instructions"

# Refactoring
git commit -m "refactor(services): extract common logic to base service"
```

### Detailed Commit

```
feat(user): add user profile management

- Add UserProfileController
- Add profile update endpoint
- Add profile image upload
- Add profile validation rules

Closes #123
```

## Security

### Reporting Security Issues

**DO NOT** create public issues for security vulnerabilities.

Instead, email security concerns to the project maintainers.

### Security Best Practices

1. **Never commit secrets**: Use `.env` for sensitive data
2. **Validate all inputs**: Use FormRequests
3. **Sanitize outputs**: Use Resources
4. **Use prepared statements**: Use Eloquent/Query Builder
5. **Keep dependencies updated**: Run `composer update` regularly

## Additional Guidelines

### Documentation

- **PHPDoc**: All methods must have PHPDoc blocks
- **Inline comments**: Explain complex logic
- **README**: Update if adding major features
- **CHANGELOG**: Document all changes

### Performance

- **N+1 Queries**: Use eager loading
- **Heavy Operations**: Use queues
- **Caching**: Cache expensive operations
- **Pagination**: Always paginate large datasets

### Logging

```php
// Info level for normal operations
Log::info('User created', ['user_id' => $user->id]);

// Warning for recoverable issues
Log::warning('Failed login attempt', ['email' => $email]);

// Error for exceptions
Log::error('Payment failed', ['error' => $e->getMessage()]);
```

## Getting Help

- **Documentation**: Read the README and ARCHITECTURE docs
- **Issues**: Search existing issues before creating new ones
- **Discussions**: Use GitHub Discussions for questions
- **Email**: Contact maintainers for sensitive matters

## Recognition

Contributors will be recognized in:
- CONTRIBUTORS.md file
- GitHub contributors page
- Release notes (for significant contributions)

Thank you for contributing to ModularSaaS! 🎉
