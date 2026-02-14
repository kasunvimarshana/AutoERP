# Contributing to AutoERP

Thank you for your interest in contributing to AutoERP! This document provides guidelines for contributing to the project.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Testing Guidelines](#testing-guidelines)
- [Commit Guidelines](#commit-guidelines)
- [Pull Request Process](#pull-request-process)
- [Documentation](#documentation)
- [Community](#community)

## Code of Conduct

This project adheres to the Contributor Covenant Code of Conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to conduct@autoerp.com.

### Our Pledge

We pledge to make participation in our project a harassment-free experience for everyone, regardless of age, body size, disability, ethnicity, gender identity and expression, level of experience, nationality, personal appearance, race, religion, or sexual identity and orientation.

## Getting Started

### Prerequisites

Before contributing, ensure you have:
- PHP 8.3+
- Node.js 20+
- Docker and Docker Compose
- Git
- A code editor (VS Code recommended)

### Setting Up Development Environment

1. **Fork the repository**
   ```bash
   # Click the "Fork" button on GitHub
   ```

2. **Clone your fork**
   ```bash
   git clone https://github.com/YOUR_USERNAME/AutoERP.git
   cd AutoERP
   ```

3. **Add upstream remote**
   ```bash
   git remote add upstream https://github.com/kasunvimarshana/AutoERP.git
   ```

4. **Install dependencies**
   ```bash
   # Using Docker (recommended)
   docker-compose up -d
   docker-compose exec app composer install
   docker-compose exec app npm install
   
   # Or manually
   composer install
   npm install
   ```

5. **Setup environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan migrate --seed
   ```

6. **Run tests to verify setup**
   ```bash
   php artisan test
   npm run test
   ```

## Development Workflow

### Branching Strategy

We follow Git Flow:

- `main` - Production-ready code
- `develop` - Integration branch for features
- `feature/*` - New features
- `bugfix/*` - Bug fixes
- `hotfix/*` - Urgent production fixes
- `release/*` - Release preparation

### Creating a Feature Branch

```bash
# Update develop branch
git checkout develop
git pull upstream develop

# Create feature branch
git checkout -b feature/your-feature-name

# Make your changes...

# Push to your fork
git push origin feature/your-feature-name
```

### Keeping Your Fork Updated

```bash
git checkout develop
git pull upstream develop
git push origin develop

# Rebase your feature branch
git checkout feature/your-feature-name
git rebase develop
```

## Coding Standards

### PHP Standards

We follow PSR-12 coding standards.

#### Code Style

```php
<?php

namespace App\Services;

use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function __construct(
        private CustomerRepository $customerRepository
    ) {}
    
    public function create(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $customer = $this->customerRepository->create($data);
            event(new CustomerCreated($customer));
            return $customer;
        });
    }
}
```

#### Code Quality Tools

- **PHPStan**: Static analysis
  ```bash
  ./vendor/bin/phpstan analyse
  ```

- **PHP CS Fixer**: Code style fixer
  ```bash
  ./vendor/bin/php-cs-fixer fix
  ```

- **Psalm**: Static analysis (alternative)
  ```bash
  ./vendor/bin/psalm
  ```

### TypeScript/JavaScript Standards

We follow the Vue.js 3 Style Guide and TypeScript best practices.

#### Code Style

```typescript
// composables/useCustomer.ts
import { ref, computed } from 'vue'
import type { Customer } from '@/types'
import { customerApi } from '@/services/api'

export function useCustomer() {
  const customers = ref<Customer[]>([])
  const loading = ref(false)
  const error = ref<Error | null>(null)
  
  const fetchCustomers = async () => {
    loading.value = true
    try {
      customers.value = await customerApi.getAll()
    } catch (e) {
      error.value = e as Error
    } finally {
      loading.value = false
    }
  }
  
  return {
    customers,
    loading,
    error,
    fetchCustomers
  }
}
```

#### Code Quality Tools

- **ESLint**: Linting
  ```bash
  npm run lint
  ```

- **Prettier**: Code formatting
  ```bash
  npm run format
  ```

- **TypeScript Compiler**: Type checking
  ```bash
  npm run type-check
  ```

### General Guidelines

1. **Single Responsibility**: Each class/function should have one clear purpose
2. **DRY Principle**: Don't Repeat Yourself
3. **SOLID Principles**: Follow SOLID design principles
4. **Meaningful Names**: Use descriptive variable and function names
5. **Comments**: Write comments for complex logic, not obvious code
6. **Error Handling**: Always handle errors gracefully

## Testing Guidelines

### Test Coverage Requirements

- **Minimum Coverage**: 70% for backend, 60% for frontend
- **Critical Paths**: 100% coverage for authentication, payment, and billing

### Writing Tests

#### Backend Tests (PHPUnit/Pest)

```php
<?php

use App\Models\Customer;
use App\Services\CustomerService;

it('creates a customer successfully', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '1234567890'
    ];
    
    $customer = $this->customerService->create($data);
    
    expect($customer)->toBeInstanceOf(Customer::class);
    expect($customer->name)->toBe('John Doe');
    $this->assertDatabaseHas('customers', ['email' => 'john@example.com']);
});

it('throws exception when email already exists', function () {
    Customer::factory()->create(['email' => 'john@example.com']);
    
    $data = ['email' => 'john@example.com'];
    
    $this->customerService->create($data);
})->throws(ValidationException::class);
```

#### Frontend Tests (Vitest)

```typescript
import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import CustomerForm from '@/components/CustomerForm.vue'

describe('CustomerForm', () => {
  it('renders form fields', () => {
    const wrapper = mount(CustomerForm)
    expect(wrapper.find('input[name="name"]').exists()).toBe(true)
    expect(wrapper.find('input[name="email"]').exists()).toBe(true)
  })
  
  it('emits submit event with form data', async () => {
    const wrapper = mount(CustomerForm)
    
    await wrapper.find('input[name="name"]').setValue('John Doe')
    await wrapper.find('input[name="email"]').setValue('john@example.com')
    await wrapper.find('form').trigger('submit')
    
    expect(wrapper.emitted('submit')).toBeTruthy()
    expect(wrapper.emitted('submit')[0]).toEqual([{
      name: 'John Doe',
      email: 'john@example.com'
    }])
  })
})
```

### Running Tests

```bash
# Backend tests
php artisan test                    # All tests
php artisan test --testsuite=Unit   # Unit tests only
php artisan test --coverage         # With coverage

# Frontend tests
npm run test                        # Unit tests
npm run test:e2e                    # E2E tests
npm run test:coverage               # With coverage
```

## Commit Guidelines

We follow Conventional Commits specification.

### Commit Message Format

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
- `perf`: Performance improvements
- `test`: Adding or updating tests
- `chore`: Maintenance tasks
- `ci`: CI/CD changes

### Examples

```bash
# Feature
git commit -m "feat(auth): add two-factor authentication"

# Bug fix
git commit -m "fix(invoice): correct tax calculation for multi-line items"

# Documentation
git commit -m "docs(readme): update installation instructions"

# Breaking change
git commit -m "feat(api): change authentication endpoint

BREAKING CHANGE: /api/login moved to /api/v1/auth/login"
```

### Commit Best Practices

1. Write in present tense ("add feature" not "added feature")
2. Keep subject line under 50 characters
3. Use body to explain what and why, not how
4. Reference issues and PRs in footer

## Pull Request Process

### Before Submitting

1. **Update your branch**
   ```bash
   git checkout develop
   git pull upstream develop
   git checkout your-branch
   git rebase develop
   ```

2. **Run tests and linters**
   ```bash
   php artisan test
   npm run lint
   npm run type-check
   ./vendor/bin/phpstan analyse
   ```

3. **Update documentation** if needed

4. **Write meaningful commit messages**

### Submitting a Pull Request

1. **Push your branch**
   ```bash
   git push origin feature/your-feature-name
   ```

2. **Create Pull Request** on GitHub

3. **Fill in PR template** with:
   - Description of changes
   - Related issues
   - Screenshots (for UI changes)
   - Testing instructions
   - Breaking changes (if any)

### PR Title Format

```
<type>(<scope>): <description>
```

Example: `feat(auth): implement two-factor authentication`

### PR Review Process

1. **Automated Checks**: CI/CD pipeline runs tests and linters
2. **Code Review**: At least one maintainer reviews the code
3. **Changes Requested**: Address feedback and push updates
4. **Approval**: Once approved, PR will be merged

### What We Look For

- Code quality and adherence to standards
- Test coverage for new code
- Documentation updates
- No breaking changes without discussion
- Security considerations
- Performance implications

## Documentation

### Code Documentation

- **PHPDoc** for all public methods
- **JSDoc/TSDoc** for TypeScript functions
- **Inline comments** for complex logic

Example:
```php
/**
 * Create a new customer.
 *
 * @param array<string, mixed> $data Customer data
 * @return Customer The created customer
 * @throws ValidationException If data is invalid
 */
public function create(array $data): Customer
{
    // Implementation
}
```

### User Documentation

- Update relevant `.md` files
- Add examples for new features
- Include screenshots for UI changes
- Update API documentation

## Community

### Getting Help

- **GitHub Discussions**: For questions and discussions
- **GitHub Issues**: For bug reports and feature requests
- **Discord**: Real-time chat (link in README)
- **Email**: support@autoerp.com

### Reporting Bugs

When reporting bugs, include:
- Clear description
- Steps to reproduce
- Expected vs actual behavior
- Environment details (OS, PHP version, etc.)
- Error messages and logs
- Screenshots if applicable

### Suggesting Features

When suggesting features:
- Check if it already exists
- Provide use case and benefits
- Consider implementation complexity
- Be open to discussion

## Recognition

Contributors will be:
- Listed in CONTRIBUTORS.md
- Mentioned in release notes
- Invited to contributor meetings (for regular contributors)

## Questions?

If you have any questions about contributing, feel free to:
- Open a GitHub Discussion
- Contact us at contribute@autoerp.com

Thank you for contributing to AutoERP! 🎉