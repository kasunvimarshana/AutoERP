# Contributing to AutoERP

Thank you for your interest in contributing to AutoERP! This document provides guidelines and instructions for contributing to the project.

## Table of Contents

1. [Code of Conduct](#code-of-conduct)
2. [Getting Started](#getting-started)
3. [Development Workflow](#development-workflow)
4. [Coding Standards](#coding-standards)
5. [Testing](#testing)
6. [Documentation](#documentation)
7. [Pull Request Process](#pull-request-process)
8. [Issue Reporting](#issue-reporting)
9. [Security Issues](#security-issues)

## Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inspiring community for all. Please be respectful and constructive in your interactions.

### Expected Behavior

- Be respectful and inclusive
- Be collaborative and constructive
- Focus on what is best for the community
- Show empathy towards other community members

### Unacceptable Behavior

- Harassment or discrimination
- Trolling or insulting comments
- Public or private harassment
- Publishing others' private information
- Other conduct which could reasonably be considered inappropriate

## Getting Started

### Prerequisites

Before contributing, ensure you have:

- PHP 8.1 or higher
- Composer 2.x
- Node.js 18.x or higher
- PostgreSQL 15+ or MySQL 8+
- Redis 7.x
- Git

### Fork and Clone

1. Fork the repository on GitHub
2. Clone your fork locally:

```bash
git clone https://github.com/YOUR_USERNAME/AutoERP.git
cd AutoERP
```

3. Add the upstream repository:

```bash
git remote add upstream https://github.com/kasunvimarshana/AutoERP.git
```

### Set Up Development Environment

Follow the setup instructions in [SETUP_GUIDE.md](SETUP_GUIDE.md).

```bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Build assets
npm run dev
```

## Development Workflow

### Creating a Branch

Create a feature branch from `main`:

```bash
git checkout main
git pull upstream main
git checkout -b feature/your-feature-name
```

**Branch Naming Convention:**
- `feature/` - New features
- `bugfix/` - Bug fixes
- `hotfix/` - Critical production fixes
- `refactor/` - Code refactoring
- `docs/` - Documentation updates

### Making Changes

1. Make your changes in your feature branch
2. Follow coding standards (see below)
3. Write or update tests
4. Update documentation
5. Commit your changes

### Commit Messages

Follow the Conventional Commits specification:

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation only
- `style`: Code style (formatting, missing semicolons, etc.)
- `refactor`: Code refactoring
- `perf`: Performance improvement
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

**Examples:**

```
feat(crm): add customer search functionality

Implement advanced search with filters for customers including
name, email, status, and custom tags.

Closes #123
```

```
fix(inventory): correct FIFO stock allocation

Fixed an issue where FIFO was not correctly allocating stock
from oldest batches first.

Fixes #456
```

### Keeping Your Branch Updated

Regularly sync your branch with upstream:

```bash
git fetch upstream
git rebase upstream/main
```

## Coding Standards

### PHP Code Style

We follow **PSR-12** coding standard.

#### Automated Formatting

Use Laravel Pint for automatic code formatting:

```bash
# Format all files
./vendor/bin/pint

# Check without fixing
./vendor/bin/pint --test

# Format specific directory
./vendor/bin/pint app/Modules/CRM
```

#### Manual Guidelines

**Indentation:**
- Use 4 spaces for indentation
- No tabs

**Naming Conventions:**
- Classes: `PascalCase` (e.g., `CustomerController`)
- Methods: `camelCase` (e.g., `createCustomer`)
- Variables: `camelCase` (e.g., `$customerData`)
- Constants: `UPPER_SNAKE_CASE` (e.g., `MAX_ITEMS`)
- Database tables: `snake_case` plural (e.g., `customers`)
- Database columns: `snake_case` (e.g., `first_name`)

**Type Hints:**

Always use type hints and return types:

```php
public function createCustomer(array $data): Customer
{
    // Implementation
}
```

**Documentation:**

Add PHPDoc comments for classes and complex methods:

```php
/**
 * Create a new customer.
 *
 * @param array $data Customer data
 * @return Customer The created customer
 * @throws ValidationException If validation fails
 */
public function createCustomer(array $data): Customer
{
    // Implementation
}
```

### JavaScript/Vue Code Style

We follow **Airbnb JavaScript Style Guide**.

#### ESLint Configuration

Run ESLint to check code:

```bash
npm run lint

# Fix automatically
npm run lint:fix
```

#### Guidelines

- Use 2 spaces for indentation
- Use single quotes for strings
- Use template literals for string concatenation
- Use arrow functions where appropriate
- Use const/let, not var

### Database Guidelines

#### Migrations

- One migration per change
- Use descriptive names: `create_customers_table`, `add_status_to_customers`
- Always provide `down()` method
- Foreign keys should have proper constraints

```php
Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('email')->unique();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index('email');
    $table->index(['tenant_id', 'status']);
});
```

#### Model Conventions

```php
class Customer extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;
    
    protected $fillable = ['name', 'email', 'phone'];
    protected $guarded = ['id', 'tenant_id'];
    protected $casts = [
        'created_at' => 'datetime',
        'verified_at' => 'datetime',
    ];
    
    // Relationships
    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }
}
```

## Testing

### Writing Tests

We use **PHPUnit** for backend testing.

#### Test Structure

```
tests/
├── Feature/          # End-to-end tests
│   ├── CustomerTest.php
│   └── InventoryTest.php
└── Unit/             # Unit tests
    ├── Services/
    └── Repositories/
```

#### Feature Test Example

```php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_create_customer(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/api/v1/customers', [
                'type' => 'individual',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
            ]);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'email'],
            ]);
        
        $this->assertDatabaseHas('customers', [
            'email' => 'john@example.com',
        ]);
    }
}
```

#### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/CustomerTest.php

# Run specific test method
php artisan test --filter test_can_create_customer

# Run with coverage
php artisan test --coverage

# Run in parallel
php artisan test --parallel
```

### Test Coverage

Aim for:
- Minimum 80% code coverage
- 100% coverage for critical business logic
- All public methods tested

## Documentation

### Code Documentation

- Add PHPDoc comments to all classes and public methods
- Document complex algorithms
- Explain non-obvious business rules
- Include `@throws` tags for exceptions

### User Documentation

When adding features, update:
- README.md (if it affects setup or usage)
- ARCHITECTURE.md (if it changes architecture)
- API_REFERENCE.md (if it adds/changes API endpoints)
- SETUP_GUIDE.md (if it affects setup)

### Inline Comments

Use inline comments sparingly for:
- Complex logic explanations
- Business rule clarifications
- Temporary workarounds (with TODO tag)

```php
// TODO: Optimize this query when dataset grows beyond 10k records
$customers = Customer::with('contacts')->paginate(100);
```

## Pull Request Process

### Before Submitting

1. **Update your branch:**
   ```bash
   git fetch upstream
   git rebase upstream/main
   ```

2. **Run tests:**
   ```bash
   php artisan test
   npm run test
   ```

3. **Check code style:**
   ```bash
   ./vendor/bin/pint
   npm run lint:fix
   ```

4. **Review your changes:**
   ```bash
   git diff upstream/main
   ```

### Creating Pull Request

1. Push your branch to your fork:
   ```bash
   git push origin feature/your-feature-name
   ```

2. Go to GitHub and create a Pull Request

3. Fill out the PR template:

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Related Issues
Closes #123

## Testing
- [ ] Tests pass locally
- [ ] New tests added
- [ ] Manual testing completed

## Checklist
- [ ] Code follows project style
- [ ] Self-review completed
- [ ] Comments added where needed
- [ ] Documentation updated
- [ ] No new warnings
```

### PR Review Process

1. **Automated Checks:**
   - CI/CD pipeline runs
   - Code quality checks
   - Security scans

2. **Code Review:**
   - At least one approval required
   - Address review comments
   - Request re-review if needed

3. **Merge:**
   - Squash and merge to keep history clean
   - Delete branch after merge

## Issue Reporting

### Before Creating an Issue

1. Search existing issues
2. Check documentation
3. Verify it's reproducible

### Creating an Issue

Use issue templates:

**Bug Report:**

```markdown
## Bug Description
Clear description of the bug

## Steps to Reproduce
1. Step one
2. Step two
3. ...

## Expected Behavior
What should happen

## Actual Behavior
What actually happens

## Environment
- OS: [e.g., Ubuntu 22.04]
- PHP Version: [e.g., 8.2]
- Laravel Version: [e.g., 10.x]

## Additional Context
Screenshots, logs, etc.
```

**Feature Request:**

```markdown
## Feature Description
Clear description of the feature

## Use Case
Why is this feature needed?

## Proposed Solution
How should it work?

## Alternatives Considered
Other approaches you've thought of

## Additional Context
Mockups, examples, etc.
```

## Security Issues

**DO NOT** create public issues for security vulnerabilities.

Instead:
1. Email: security@autoerp.com
2. Include detailed description
3. Provide steps to reproduce
4. Allow time for fix before disclosure

See [SECURITY.md](SECURITY.md) for full security policy.

## Recognition

Contributors will be recognized in:
- CONTRIBUTORS.md file
- Release notes
- Project website

## Questions?

- **Documentation**: Check [ARCHITECTURE.md](ARCHITECTURE.md), [SETUP_GUIDE.md](SETUP_GUIDE.md)
- **Slack**: autoerp.slack.com
- **Email**: dev@autoerp.com

## License

By contributing, you agree that your contributions will be licensed under the same license as the project.

---

Thank you for contributing to AutoERP! 🎉
