# GitHub Copilot Instructions

This document provides comprehensive guidelines for developing the AutoERP platform. These instructions help ensure consistency, quality, and adherence to architectural principles across the codebase.

## Quick Reference

- **Setup**: See [Getting Started & Build Instructions](#getting-started--build-instructions) for environment setup
- **Build/Test**: See [Build & Development Commands](#build--development-commands) for running the app and tests
- **Architecture**: See [Architecture Principles](#architecture-principles) and [Coding Guidelines](#coding-guidelines)
- **Validation**: See [Validation Before Committing](#validation-before-committing) for pre-commit checks
- **File Structure**: See [Key File Locations](#key-file-locations) for navigating the codebase

## Project Overview

AutoERP is a fully production-ready, enterprise-grade, modular ERP SaaS platform featuring:

- **Purpose**: Enterprise Resource Planning system with comprehensive Inventory Management
- **Target Users**: Multi-tenant businesses requiring organization, vendor, branch, and warehouse management
- **Core Features**: IAM, Inventory Management, CRM, POS, Invoicing, Procurement, Manufacturing, Warehouse Operations, Reporting, and Analytics

## Getting Started & Build Instructions

### Repository Structure

```
/
├── backend/           # Laravel backend application
│   ├── app/          # Application code (Controllers, Services, Repositories, Models, etc.)
│   ├── config/       # Configuration files
│   ├── database/     # Migrations, seeders, factories
│   ├── routes/       # API and web routes
│   ├── tests/        # PHPUnit tests (Unit and Feature)
│   ├── composer.json # PHP dependencies
│   └── phpunit.xml   # PHPUnit configuration
├── frontend/         # Vue.js frontend application
│   ├── src/          # Source code (components, views, stores, services)
│   ├── public/       # Static assets
│   ├── package.json  # JavaScript dependencies
│   └── vite.config.js # Vite configuration
└── .github/          # GitHub configuration and instructions
```

### Prerequisites

- **PHP**: 8.2 or higher
- **Composer**: Latest version
- **Node.js**: 18.x or higher
- **npm**: Latest version
- **Database**: MySQL 8.0+ or PostgreSQL 13+

### Environment Setup

**ALWAYS** follow these steps in order for a fresh setup:

1. **Navigate to backend directory**:
   ```bash
   cd backend
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Create environment file** (if it doesn't exist):
   ```bash
   cp .env.example .env
   ```

4. **Generate application key**:
   ```bash
   php artisan key:generate
   ```

5. **Configure database** in `.env` file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=autoerp
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. **Run migrations**:
   ```bash
   php artisan migrate
   ```

7. **Install Node dependencies**:
   ```bash
   npm install
   ```

### Build & Development Commands

**Backend (Laravel)**

- **Run development server** (backend only):
  ```bash
  cd backend
  php artisan serve
  # Server runs at http://localhost:8000
  ```

- **Run with queue and logs** (recommended for full development):
  ```bash
  cd backend
  composer dev
  # This runs: server, queue listener, logs (pail), and vite concurrently
  ```

- **Build frontend assets**:
  ```bash
  cd backend
  npm run build
  ```

- **Run tests**:
  ```bash
  cd backend
  php artisan test
  ```

- **Run specific test suite**:
  ```bash
  cd backend
  php artisan test --testsuite=Unit
  php artisan test --testsuite=Feature
  ```

- **Run tests with coverage**:
  ```bash
  cd backend
  php artisan test --coverage
  ```

- **Lint code** (StyleCI configuration in `.styleci.yml`):
  ```bash
  cd backend
  # If pint is available:
  ./vendor/bin/pint
  # Otherwise, StyleCI will run automatically on push
  ```

**Frontend (Vue.js)**

- **Run development server**:
  ```bash
  cd frontend
  npm run dev
  # Server runs at http://localhost:5173
  ```

- **Build for production**:
  ```bash
  cd frontend
  npm run build
  ```

- **Preview production build**:
  ```bash
  cd frontend
  npm run preview
  ```

### Quick Setup Script

For automated setup, use the composer setup script from the backend directory:

```bash
cd backend
composer setup
# This runs: composer install, creates .env, generates key, runs migrations, installs npm, builds assets
```

### Common Issues & Workarounds

1. **Database connection errors**: Ensure database exists and credentials in `.env` are correct
2. **Permission issues**: Set correct permissions on `storage/` and `bootstrap/cache/`
   ```bash
   cd backend
   chmod -R 775 storage bootstrap/cache
   ```
3. **Node module issues**: Clear cache and reinstall
   ```bash
   rm -rf node_modules package-lock.json
   npm install
   ```

### Validation Before Committing

**ALWAYS** run these commands before committing changes:

1. **Clear config cache**:
   ```bash
   cd backend
   php artisan config:clear
   ```

2. **Run tests**:
   ```bash
   cd backend
   php artisan test
   ```

3. **Lint code** (if pint is available):
   ```bash
   cd backend
   test -f vendor/bin/pint && ./vendor/bin/pint || echo "Pint not installed, StyleCI will check on push"
   ```

4. **Build frontend** (if frontend changes):
   ```bash
   cd backend
   npm run build
   # OR for standalone frontend:
   cd frontend
   npm run build
   ```

### Key File Locations

**Backend Configuration & Setup:**
- `backend/composer.json` - PHP dependencies and setup scripts
- `backend/.env.example` - Environment configuration template
- `backend/config/` - Application configuration files
- `backend/phpunit.xml` - Test suite configuration
- `backend/.styleci.yml` - Code style configuration

**Backend Application Structure:**
- `backend/app/Http/Controllers/` - API controllers (thin, validation only)
- `backend/app/Services/` - Business logic layer
- `backend/app/Repositories/` - Data access layer
- `backend/app/Models/` - Eloquent models
- `backend/app/Modules/` - Modular features (IAM, Inventory, CRM, POS, Finance, Procurement, Manufacturing, Reporting)
- `backend/app/Policies/` - Authorization policies
- `backend/app/Events/` - Event definitions
- `backend/app/Listeners/` - Event handlers
- `backend/app/Enums/` - Enum definitions
- `backend/database/migrations/` - Database migrations
- `backend/routes/api.php` - API route definitions

**Frontend Configuration & Setup:**
- `frontend/package.json` - JavaScript dependencies and scripts
- `frontend/vite.config.js` - Vite build configuration
- `frontend/index.html` - Main HTML entry point

**Frontend Application Structure:**
- `frontend/src/main.js` - Application entry point
- `frontend/src/App.vue` - Root component
- `frontend/src/router/` - Vue Router configuration
- `frontend/src/stores/` - Pinia state management
- `frontend/src/services/` - API communication layer
- `frontend/src/components/` - Reusable Vue components
- `frontend/src/views/` - Page-level components
- `frontend/src/modules/` - Feature modules (mirrors backend modules)
- `frontend/src/composables/` - Vue composition functions
- `frontend/src/layouts/` - Layout components

**Documentation:**
- `README.md` - Project overview and quick start
- `ARCHITECTURE.md` - Detailed architecture documentation
- `IMPLEMENTATION_GUIDE.md` - Implementation guidelines
- `.github/copilot-instructions.md` - This file

## Technology Stack

### Backend
- **Framework**: Laravel (PHP)
- **Architecture**: Clean Architecture with Domain-Driven Design
- **Pattern**: Controller → Service → Repository
- **Database**: MySQL/PostgreSQL with append-only stock ledgers
- **API**: RESTful with versioning and OpenAPI documentation

### Frontend
- **Framework**: Vue.js 3 with Vite
- **State Management**: Pinia/Vuex
- **UI Components**: Reusable, modular component architecture
- **Styling**: Responsive and accessible layouts with professional theming
- **Internationalization**: Multi-language support (i18n)

### Key Technologies
- Event-driven architecture for asynchronous workflows
- Native Web Push via Service Workers for notifications
- Background job processing with queues
- Multi-tenancy with complete tenant isolation

## Architecture Principles

### Design Patterns
- **Clean Architecture** with clear modular boundaries
- **Controller → Service → Repository** pattern strictly enforced
- **SOLID principles** rigorously applied
- **DRY (Don't Repeat Yourself)** to minimize code duplication
- **KISS (Keep It Simple, Stupid)** for maintainability

### Modular Architecture
- Each module is independently installable, removable, and extendable
- Standardized module contracts and interfaces
- Dependency inversion for loose coupling
- Configuration-driven initialization
- Event-based communication between modules

### Multi-Tenancy
- Complete tenant isolation at all layers
- Support for:
  - Nested multi-organization structures
  - Multi-vendor management
  - Multi-branch operations
  - Multi-warehouse/location tracking
  - Multi-currency operations
  - Multi-language (i18n)
  - Multi-time-zone
  - Multi-unit-of-measure
  - Multi-tax compliance

## Coding Guidelines

### General Principles

1. **Always review existing code** before making changes
   - Read and understand current implementations
   - Review documentation, schemas, and migrations
   - Understand business rules and architectural decisions

2. **Service Layer Orchestration**
   - All cross-module interactions MUST go through the service layer
   - Explicit transactional boundaries
   - Guarantee atomicity, idempotency, and rollback safety
   - Consistent exception propagation

3. **Event-Driven Architecture**
   - Use events STRICTLY for asynchronous workflows
   - Examples: recalculations, notifications, integrations, reporting
   - Never use events for synchronous business logic

### Code Organization

#### Backend (Laravel)

\`\`\`
app/
├── Http/Controllers/      # Thin controllers - validation and routing only
├── Services/             # Business logic and orchestration
├── Repositories/         # Data access layer
├── Models/              # Eloquent models with relationships
├── DTOs/                # Data Transfer Objects
├── Policies/            # Authorization logic
├── Events/              # Event definitions
└── Listeners/           # Event handlers
\`\`\`

**Controller Responsibilities:**
- Request validation
- Call appropriate service methods
- Return standardized responses
- NO business logic

**Service Responsibilities:**
- Business logic implementation
- Transaction management
- Cross-repository orchestration
- Event dispatching
- Error handling

**Repository Responsibilities:**
- Database queries
- Data retrieval and persistence
- Query optimization
- NO business logic

#### Frontend (Vue.js)

\`\`\`
src/
├── components/          # Reusable UI components
├── views/              # Page-level components
├── composables/        # Reusable composition functions
├── stores/             # State management
├── services/           # API communication
├── router/             # Route definitions
└── locales/            # i18n translations
\`\`\`

### Naming Conventions

- **Classes**: PascalCase (e.g., \`ProductService\`, \`InventoryRepository\`)
- **Methods**: camelCase (e.g., \`createProduct\`, \`updateInventory\`)
- **Variables**: camelCase (e.g., \`productId\`, \`stockLevel\`)
- **Constants**: UPPER_SNAKE_CASE (e.g., \`MAX_RETRY_ATTEMPTS\`)
- **Database Tables**: snake_case plural (e.g., \`products\`, \`stock_ledgers\`)
- **Routes**: kebab-case (e.g., \`/api/v1/products\`, \`/inventory-items\`)

### Enum Usage

- Use native language enums for stable business concepts
- Persist as indexed strings, NOT database ENUM types
- Validate at request boundaries
- Cast explicitly at model/domain layer
- Encapsulate behavior within enum classes

Example:
\`\`\`php
enum ProductType: string
{
    case INVENTORY = 'inventory';
    case SERVICE = 'service';
    case COMBO = 'combo';
    case BUNDLE = 'bundle';
    
    public function isPhysical(): bool
    {
        return \$this === self::INVENTORY;
    }
}
\`\`\`

### Error Handling

- Use specific exception classes
- Provide meaningful error messages
- Log errors with appropriate context
- Return standardized error responses
- Never expose sensitive information in errors

## Security Practices

### Authentication & Authorization

- Fine-grained RBAC (Role-Based Access Control) and ABAC (Attribute-Based Access Control)
- Use Laravel policies for authorization
- Implement guards and scopes
- Permission-driven workflows
- Session management with secure cookies

### Data Security

- **Encryption at rest** for sensitive data
- **HTTPS enforcement** for all connections
- **Secure credential storage** using Laravel's encryption
- **Input validation** at all entry points
- **SQL injection prevention** via Eloquent/Query Builder
- **XSS prevention** via proper output escaping
- **CSRF protection** enabled

### API Security

- Rate limiting on all endpoints
- API versioning (e.g., \`/api/v1/\`)
- Authentication tokens with expiration
- Request validation and sanitization
- Audit trails for all modifications

### Sensitive Data

- Never log passwords, tokens, or API keys
- Never commit secrets to version control
- Use environment variables for configuration
- Implement proper secret rotation policies

## Testing Requirements

### Test Coverage

- Write unit tests for all service layer logic
- Integration tests for repository layer
- Feature tests for API endpoints
- Minimum 80% code coverage target

### Test Organization

\`\`\`
tests/
├── Unit/              # Unit tests (fast, isolated)
├── Feature/           # Feature/integration tests
└── Browser/           # End-to-end tests (if applicable)
\`\`\`

### Testing Best Practices

- Use Laravel's testing helpers
- Mock external dependencies
- Test edge cases and error conditions
- Keep tests fast and independent
- Use factories for test data
- Clear test names describing what is tested

### Running Tests

\`\`\`bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
\`\`\`

## Core Modules

### IAM (Identity and Access Management)
- User authentication and registration
- Role and permission management
- Multi-factor authentication support
- Session management

### Inventory Management
- **Stock Ledger**: Append-only architecture for full audit trail
- **Product Types**: Inventory, Service, Combo, Bundle
- **Tracking**: SKU/variant modeling, batch/lot/serial/expiry (FIFO/FEFO)
- **Pricing**: Buying/selling prices, multi-unit pricing, dynamic pricing rules
- **Discounts**: Flat, percentage, tiered (at item and total levels)
- **Adjustments**: VAT, taxes, coupons, additional charges

### CRM (Customer Relationship Management)
- Customer profiles and segmentation
- Contact management
- Sales pipeline tracking
- Customer-specific pricing rules

### Procurement
- Purchase orders
- Goods receipt
- Supplier management
- Vendor evaluation

### POS (Point of Sale)
- Sales order creation
- Quotations
- Invoicing
- Payment processing
- Receipt generation

### Manufacturing
- Bill of materials
- Production orders
- Work orders
- Material consumption

### Warehouse Operations
- Stock transfers between locations
- Stock adjustments
- Cycle counting
- Reorder automation

### Reporting & Analytics
- Customizable dashboards
- Financial reports
- Inventory reports
- Sales analytics

## Database Guidelines

### Migrations

- Always create migrations for schema changes
- Use descriptive migration names
- Include rollback logic
- Test migrations before committing

### Models

- Define relationships explicitly
- Use Eloquent scopes for reusable queries
- Implement soft deletes where appropriate
- Add tenant scoping globally where needed

### Stock Ledger Pattern

The inventory uses an append-only ledger:

\`\`\`php
// NEVER delete or modify stock ledger entries
// Always create new entries for adjustments
StockLedger::create([
    'product_id' => \$productId,
    'quantity' => \$quantity,
    'type' => StockMovementType::PURCHASE,
    'reference_id' => \$purchaseOrderId,
    'tenant_id' => \$tenantId,
]);
\`\`\`

### Audit Trails

- Record who, what, when for all changes
- Use polymorphic relationships for auditable models
- Immutable audit records
- Include old and new values

## API Development

### RESTful Design

- Use proper HTTP methods (GET, POST, PUT, PATCH, DELETE)
- Return appropriate status codes
- Use plural resource names
- Version APIs explicitly

### Request/Response Format

**Request:**
\`\`\`json
{
  "data": {
    "name": "Product Name",
    "price": 99.99
  }
}
\`\`\`

**Success Response:**
\`\`\`json
{
  "success": true,
  "data": { ... },
  "message": "Resource created successfully"
}
\`\`\`

**Error Response:**
\`\`\`json
{
  "success": false,
  "message": "Validation failed",
  "errors": { ... }
}
\`\`\`

### Bulk Operations

Support bulk operations for efficiency:
- CSV import/export
- Bulk create/update/delete via API
- Background processing for large operations

## Frontend Guidelines

### Component Structure

- Keep components focused and single-purpose
- Use composition API (Vue 3)
- Props for data in, events for data out
- Avoid prop drilling - use provide/inject or stores

### State Management

- Use Pinia/Vuex for global state
- Keep component-specific state local
- Actions for async operations
- Getters for derived state

### API Integration

- Centralize API calls in service modules
- Handle loading and error states
- Use interceptors for authentication
- Implement retry logic for failed requests

### Accessibility

- Use semantic HTML
- Provide ARIA labels where needed
- Ensure keyboard navigation
- Test with screen readers
- Maintain sufficient color contrast

### Internationalization (i18n)

- Use i18n for all user-facing text
- Support RTL languages
- Format dates, numbers, and currencies per locale
- Provide language switcher

## Performance Optimization

### Backend

- Eager load relationships to avoid N+1 queries
- Use database indexes appropriately
- Implement caching for frequently accessed data
- Queue long-running tasks
- Use pagination for large datasets

### Frontend

- Lazy load routes and components
- Optimize bundle size
- Use virtual scrolling for long lists
- Debounce user input
- Implement optimistic UI updates

## Notification System

Implement end-to-end push notifications using ONLY native platform capabilities:

- Database notifications table
- Laravel events and listeners
- Queue workers for background processing
- Web Push via Service Workers
- Polling or fallback mechanisms
- NO third-party notification services

## Documentation

### Code Documentation

- Add PHPDoc/JSDoc for public methods
- Document complex business logic
- Explain "why" not just "what"
- Keep comments up-to-date

### API Documentation

- Use OpenAPI/Swagger specifications
- Document all endpoints
- Include request/response examples
- Specify authentication requirements

## Git Workflow

### Commit Messages

- Use conventional commit format
- Be descriptive but concise
- Reference issue numbers when applicable

Example:
\`\`\`
feat(inventory): add batch tracking support

Implements FIFO/FEFO batch tracking for inventory items.
Includes migration, models, and API endpoints.

Closes #123
\`\`\`

### Branch Naming

- \`feature/feature-name\` for new features
- \`bugfix/bug-description\` for bug fixes
- \`hotfix/critical-fix\` for production issues
- \`refactor/area-being-refactored\` for refactoring

## Development Workflow

1. **Understand the requirement** thoroughly
2. **Review existing code** and architecture
3. **Plan your implementation** following established patterns
4. **Write tests first** (TDD approach preferred)
5. **Implement the feature** following coding guidelines
6. **Run tests** and ensure they pass
7. **Test manually** in development environment
8. **Update documentation** as needed
9. **Create pull request** with clear description
10. **Address review feedback** promptly

## Common Patterns

### Creating a New Module

1. Create models with relationships
2. Create migrations and seeders
3. Create repository interface and implementation
4. Create service class with business logic
5. Create DTOs for data transfer
6. Create controller with slim endpoints
7. Define routes
8. Add policies for authorization
9. Create events and listeners if needed
10. Write comprehensive tests
11. Document API endpoints

### Adding a New Feature to Existing Module

1. Review existing module structure
2. Extend repository methods if needed
3. Add service methods for business logic
4. Update controller with new endpoints
5. Add validations
6. Update policies if permissions changed
7. Write tests
8. Update API documentation

## Troubleshooting

### Common Issues

- **N+1 Queries**: Use eager loading with \`with()\`
- **Memory Issues**: Implement chunking for large datasets
- **Slow Queries**: Add indexes, optimize queries
- **Race Conditions**: Use database transactions and locking

### Debugging

- Use Laravel Telescope for request debugging
- Enable query logging during development
- Use Xdebug or similar for step debugging
- Check logs in \`storage/logs/\`

## Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org/)
- [Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Domain-Driven Design](https://martinfowler.com/bliki/DomainDrivenDesign.html)

---

**Remember**: Before making any changes, always review, analyze, and fully understand existing code, documentation, schemas, migrations, services, and architectural decisions. This ensures consistency and prevents introducing technical debt.

**Trust these instructions**: The commands and workflows documented in this file have been validated and should be trusted. Only perform additional searching if the information here is incomplete or found to be incorrect.
