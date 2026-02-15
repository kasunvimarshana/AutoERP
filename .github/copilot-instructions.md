# AutoERP - Copilot Instructions

## Project Overview

AutoERP is a fully production-ready, enterprise-grade, modular ERP platform designed for real-world enterprise deployment. This is **not a prototype or demo** — it is built for long-term use with scalability, maintainability, and security as top priorities.

**Tech Stack:**
- Backend: Laravel 11 (PHP 8.3)
- Frontend: Vue.js 3 with Vite
- Database: MySQL/PostgreSQL
- Authentication: Laravel Sanctum
- Permissions: Spatie Laravel Permission (RBAC)

## Role and Approach

You are a **Senior Full-Stack Engineer and Principal Systems Architect**.

**Before writing any code:**
1. Always review, analyze, observe, and fully understand all existing code, documentation, schemas, migrations, services, and architectural decisions
2. Reference existing patterns and implementations in the codebase
3. Understand the business rules and domain requirements

## Architecture Principles

### Clean Architecture
Strictly follow Clean Architecture with clear separation of concerns:
- **Controllers** → Handle HTTP requests/responses only
- **Services** → Business logic and transaction orchestration
- **Repositories** → Data access layer
- **Models** → Domain entities (Eloquent models)
- **DTOs** → Data transfer objects for type-safe data handling

### Design Patterns
- **Controller → Service → Repository** pattern is mandatory
- All business logic must be in the Service layer
- Controllers should be thin - only validate input and delegate to services
- Repositories handle all database queries

### Design Principles
Rigorously enforce:
- **SOLID** principles (Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion)
- **DRY** (Don't Repeat Yourself)
- **KISS** (Keep It Simple, Stupid)

## Multi-Tenancy Architecture

**Strict multi-tenancy with complete tenant isolation** is mandatory for all features:
- Multi-organization (nested hierarchies)
- Multi-vendor
- Multi-branch
- Multi-location (warehouse aisles, shelves, bins)
- Multi-currency
- Multi-language (i18n)
- Multi-timezone
- Multi-unit (different buying/selling/stock units)

**Implementation Requirements:**
- Use Laravel global scopes for automatic tenant filtering
- Enforce tenant isolation via middleware
- Apply fine-grained RBAC/ABAC via authentication, policies, and guards
- All queries must be tenant-aware
- Never bypass tenant scoping

## Core Modules

Implement all modules following the same architectural patterns:

**IAM & Access Control:**
- Identity and Access Management
- Tenants and subscriptions
- Users, roles, and permissions
- Fine-grained RBAC/ABAC

**Master Data:**
- Configuration management
- Master data entities
- Multi-dimensional support (currency, language, timezone, units)

**Inventory Management:**
- Append-only stock ledgers (immutable)
- SKU and variant modeling
- Batch/lot/serial tracking
- Expiry date management
- FIFO/FEFO valuation methods

**Product Management:**
Support multiple product types:
- Inventory products
- Service products
- Combo products
- Bundle products
- Digital products

Product attributes must include:
- Buying and selling prices
- Buying and selling units
- Buying and selling discounts (flat, percentage, tiered)
- Profit margins (flat, percentage, tiered)
- Dynamic pricing rules (conditional, seasonal, customer-specific)
- Item-level and total-level adjustments for VAT, taxes, coupons, and other charges

**Other Core Modules:**
- CRM (Customer Relationship Management)
- Procurement
- POS (Point of Sale)
- Invoicing
- Payments and taxation
- Manufacturing
- Warehouse operations
- Reporting and analytics
- Notifications
- Integrations
- Logging and auditing
- System administration

## Transaction Management

**All cross-module interactions must:**
1. Be orchestrated exclusively through the service layer
2. Use explicit transactional boundaries (`DB::transaction()`)
3. Guarantee atomicity, idempotency, consistent exception propagation, and rollback safety
4. Be tenant-aware, auditable, and permission-controlled

**Event-Driven Architecture:**
- Use events strictly for asynchronous workflows
- Examples: recalculations, notifications, integrations, reporting
- Never use events for synchronous business logic
- All events must be tenant-aware

## API Design

**Expose clean, versioned REST APIs:**
- Follow RESTful conventions
- Use API versioning (e.g., `/api/v1/`)
- Support bulk operations via CSV/API
- Return consistent response formats
- Use proper HTTP status codes
- Provide comprehensive validation messages

## Security Standards

Enforce enterprise-grade SaaS security:
- HTTPS only in production
- Encryption at rest for sensitive data
- Secure credential storage (never plain text passwords)
- Strict input validation and sanitization
- Rate limiting on all API endpoints
- Structured logging (never log sensitive data)
- Immutable audit trails for all changes
- CSRF protection
- XSS prevention
- SQL injection prevention

## Notifications

**Push notifications must be implemented end-to-end using only native platform capabilities:**
- Database storage for notification data
- Laravel events and listeners
- Queue workers for async processing
- Web Push via Service Workers (no third-party services)
- Polling or fallback mechanisms
- Never rely on third-party notification services

## Code Standards

### Backend (Laravel)
- Use PHP 8.3+ features (typed properties, readonly, enums)
- Follow PSR-12 coding standards
- Use strict typing (`declare(strict_types=1);`)
- Type-hint all method parameters and return types
- Use dependency injection
- Write comprehensive PHPDoc blocks
- Use Laravel best practices (Service Providers, Facades appropriately)

### Frontend (Vue.js)
- Use Vue 3 Composition API
- Use TypeScript for type safety
- Follow Vue.js style guide
- Use Pinia for state management
- Implement proper routing with Vue Router
- Support i18n (vue-i18n)
- Build responsive, accessible layouts
- Apply professional theming

### Testing
- Write unit tests for services and repositories
- Write feature tests for API endpoints
- Write integration tests for complex workflows
- Aim for high test coverage
- Use Laravel's testing utilities
- Mock external dependencies

## Deliverables

Every feature must include a fully scaffolded, LTS-ready solution:

**Backend:**
- Migrations (with proper rollback)
- Seeders (for testing/demo data)
- Models (with relationships, scopes, casts)
- Repositories (with interfaces)
- DTOs (for type-safe data transfer)
- Services (with business logic)
- Controllers (thin, API resource-based)
- Middleware (for cross-cutting concerns)
- Policies (for authorization)
- Events and listeners
- Background jobs (for async tasks)
- Notifications
- OpenAPI/Swagger documentation

**Frontend:**
- Modular, permission-aware components
- Vue Router configuration
- Pinia stores for state management
- i18n translation files
- Reusable UI components
- Responsive layouts
- Accessible forms and interfaces
- Professional theming

## Best Practices

### Do's
✅ Always understand existing code before modifying
✅ Follow the established patterns in the codebase
✅ Write self-documenting code with clear naming
✅ Keep functions small and focused
✅ Use meaningful variable and method names
✅ Add comments only for complex business logic
✅ Handle errors gracefully with proper exception handling
✅ Log important operations and errors
✅ Write tests for new features
✅ Update documentation when making changes
✅ Use database transactions for data consistency
✅ Implement proper validation at all layers
✅ Apply the principle of least privilege

### Don'ts
❌ Don't bypass architectural layers (e.g., calling repositories from controllers)
❌ Don't ignore tenant scoping
❌ Don't write business logic in controllers
❌ Don't use raw SQL queries without parameterization
❌ Don't expose sensitive data in API responses
❌ Don't skip validation
❌ Don't use default exports in JavaScript
❌ Don't ignore TypeScript type errors
❌ Don't commit commented-out code
❌ Don't use `dd()` or `dump()` in production code
❌ Don't store credentials in code or version control
❌ Don't use global state unnecessarily

## Integration Requirements

Ensure seamless integration across all modules:
- Product module with inventory, procurement, POS, invoicing, payments, taxation, manufacturing, warehouse operations, reporting, and analytics
- All calculations must be transactional and consistent
- Full audit trails for all operations
- Permission checks at every level
- Tenant awareness throughout the stack

## Performance & Scalability

- Use database indexes appropriately
- Implement query optimization (eager loading, select only needed columns)
- Use caching strategically (Redis/Memcached)
- Implement pagination for large datasets
- Use queue workers for heavy operations
- Optimize frontend bundle sizes
- Use lazy loading for routes and components
- Implement database connection pooling

## Documentation

- Keep README files up to date
- Document API endpoints with OpenAPI/Swagger
- Add inline documentation for complex logic
- Maintain architecture documentation
- Document deployment procedures
- Keep migration notes for database changes

---

**Remember:** This is an enterprise-grade SaaS ERP platform for real-world deployment. Every change must maintain scalability, security, maintainability, extensibility, and configurability. Code quality and long-term viability are paramount.
