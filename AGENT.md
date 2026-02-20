# AGENT.md
Enterprise ERP/CRM SaaS Governance & Execution Contract
Version: 1.0
Status: Enforced
Scope: Entire Repository

---

# 1. SYSTEM PURPOSE

This repository implements a production-grade, enterprise, multi-tenant, modular ERP/CRM SaaS platform using:

- Laravel (Stable LTS only)
- Vue (Stable LTS only)
- Native framework features only
- No unstable or experimental dependencies

The system must remain:

- Fully modular
- Fully isolated per tenant
- Fully metadata-driven
- Fully API-first
- Fully stateless
- Enterprise secure
- Horizontally scalable
- Vertically scalable
- Replaceable per module

---

# 2. ARCHITECTURE (STRICT CLEAN ARCHITECTURE)

All modules MUST follow strict layering:

Presentation
→ Application
→ Domain
→ Infrastructure

## 2.1 Layer Responsibilities

### Presentation
- Controllers
- API routes
- Request validation
- Response formatting
- No business logic allowed

### Application
- Use cases
- Services
- Orchestrators
- Transaction management
- Emits domain events

### Domain
- Entities
- Value Objects
- Aggregates
- Enums
- Interfaces (contracts)
- No framework dependency allowed

### Infrastructure
- Repositories
- Database models
- External integrations
- File systems
- Queue adapters

---

# 3. MODULAR PLUGIN SYSTEM (MANDATORY)

Every feature is an isolated module.

## 3.1 Module Directory Structure

Modules/
 └── {ModuleName}/
     ├── Domain/
     ├── Application/
     ├── Infrastructure/
     ├── Presentation/
     ├── Providers/
     ├── routes.php
     ├── config.php
     ├── module.json
     └── README.md

## 3.2 Module Rules

- No direct cross-module calls
- Communication only via:
  - Contracts
  - Events
  - API
- No shared state
- No circular dependencies
- Each module must register itself
- Each module must be enable/disable capable

---

# 4. MULTI-TENANCY (STRICT ISOLATION)

## 4.1 Required Capabilities

- Multi-tenant
- Hierarchical organizations
- Multi-branch
- Multi-location
- Multi-vendor
- Multi-currency
- Multi-language
- Multi-unit

## 4.2 Isolation Enforcement

- Tenant ID mandatory in every table
- Global scope enforced
- Tenant middleware required
- No cross-tenant queries
- Per-request tenant resolution
- JWT per user × device × organization
- Stateless authentication
- Multi-guard support

Failure to enforce tenant isolation is a critical violation.

---

# 5. AUTHORIZATION MODEL

## 5.1 Required

- RBAC (Role Based Access Control)
- ABAC (Attribute Based Access Control)
- Laravel Policies only
- Middleware enforced
- No permission logic in controllers

---

# 6. METADATA-DRIVEN SYSTEM

The following MUST NOT be hardcoded:

- Pricing rules
- Workflow states
- UI components
- Permissions
- Tax logic
- Calculation strategies

All configurable logic must be:

- Database-driven
- Enum-controlled
- Runtime-resolvable
- Replaceable without code changes

---

# 7. CORE DOMAIN MODULES

Minimum required modules:

- Identity
- Tenancy
- Organization
- Product Catalog
- Inventory
- Sales / POS
- Purchasing
- Accounting Foundation
- CRM
- Pricing Engine
- Workflow Engine
- Audit Log
- Notification
- Reporting
- File Management

Each module must define:

- Entities
- Use cases
- Contracts
- Events
- Dependencies

---

# 8. PRODUCT MODEL

Must support:

- Physical goods
- Services
- Digital products
- Bundles
- Composite products

Features:

- Multiple units
- Buy/sell conversion
- Location-based pricing
- Multi-currency
- Pricing strategies:
  - Flat
  - Percentage
  - Tiered
  - Rule-based

All monetary calculations must use high precision decimal handling.

Floating point arithmetic is forbidden for financial operations.

---

# 9. DATA INTEGRITY & CONCURRENCY

Mandatory:

- Database transactions
- Foreign keys
- Unique constraints
- Idempotent APIs
- Optimistic locking
- Pessimistic locking
- Audit logging
- Version tracking

All write operations must be concurrency-safe.

---

# 10. EVENT-DRIVEN DESIGN

Modules must communicate using:

- Laravel Events
- Queues
- Pipelines
- Jobs

Direct synchronous cross-module logic is forbidden.

---

# 11. SECURITY REQUIREMENTS

- CSRF protection
- XSS prevention
- SQL injection prevention
- Rate limiting
- Token rotation
- Secure password hashing
- Secure file upload validation
- Strict validation on every request

---

# 12. API DESIGN RULES

- RESTful structure
- Versioned APIs
- Consistent response format
- Structured error responses
- Idempotent endpoints
- Pagination enforced
- No hidden logic

---

# 13. TESTING REQUIREMENTS

Each module must include:

- Unit tests
- Feature tests
- Authorization tests
- Tenant isolation tests
- Concurrency tests

No module is complete without test coverage.

---

# 14. CI/CD ENFORCEMENT

All pull requests must:

- Pass linting
- Pass static analysis
- Pass unit tests
- Pass feature tests
- Validate JSON formatting
- Validate YAML workflows
- Validate dependency versions

No direct commits to production branch.

---

# 15. PROHIBITED PRACTICES

- Business logic inside controllers
- Query builder usage in controllers
- Global state usage
- Static service calls across modules
- Hardcoded pricing
- Direct model coupling between modules
- Bypassing policies
- Partial implementations

Violations must be refactored immediately.

---

# 16. DOCUMENTATION REQUIREMENTS

Each module must contain:

- Purpose
- Scope
- Domain boundaries
- Entity definitions
- Use case definitions
- Event list
- Configuration parameters
- Installation instructions

Documentation must match implementation state.

---

# 17. DEFINITION OF DONE

A module is complete only if:

- Architecture boundaries respected
- No duplication exists
- Tenant isolation enforced
- Authorization enforced
- Events emitted correctly
- Concurrency handled
- Tests pass
- Documentation updated
- CI passes
- No technical debt introduced

---

# 18. AUTONOMOUS AGENT RULES

Any AI agent modifying this repository must:

1. Analyze entire affected module before modifying.
2. Refactor existing violations before adding new code.
3. Preserve tenant isolation at all times.
4. Update documentation.
5. Avoid introducing coupling.
6. Maintain clean architecture boundaries.
7. Maintain strict modular independence.

---

# 19. FINAL DIRECTIVE

This system must evolve into a:

- Fully modular
- Fully pluggable
- Fully configurable
- Fully enterprise-grade
- Fully isolated multi-tenant SaaS ERP/CRM

---

# 20. REFERENCES

These references provide guidance on modular design, Laravel best practices, multi-tenancy, ERP/CRM design, and other principles relevant to this repository:

- https://blog.cleancoder.com/atom.xml 
- https://en.wikipedia.org/wiki/Modular_design 
- https://en.wikipedia.org/wiki/Plug-in_(computing) 
- https://laravel.com/blog/building-a-multi-tenant-architecture-platform-to-scale-the-emmys 
- https://en.wikipedia.org/wiki/Enterprise_resource_planning 
- https://dev.to/rafaelogic/building-a-polymorphic-translatable-model-in-laravel-with-autoloaded-translations-3d99 
- https://sevalla.com/blog/building-modular-systems-laravel 
- https://github.com/laravel/laravel 
- https://laravel.com/docs/12.x/packages 
- https://swagger.io 
- https://en.wikipedia.org/wiki/SOLID 
- https://laravel.com/docs/12.x/filesystem 
- https://laravel-news.com/uploading-files-laravel 
- https://adminlte.io 
- https://tailwindcss.com 
- https://dev.to/bhaidar/understanding-database-locking-and-concurrency-in-laravel-a-deep-dive-2k4m 
- https://laravel-news.com/managing-data-races-with-pessimistic-locking-in-laravel 
- https://dev.to/tegos/pessimistic-optimistic-locking-in-laravel-23dk 
- https://dev.to/takeshiyu/handling-decimal-calculations-in-php-84-with-the-new-bcmath-object-api-442j 
- https://dev.to/keljtanoski/modular-laravel-3dkf 
- https://laravel.com/docs/12.x/processes 
- https://laravel.com/docs/12.x/helpers#pipeline 
- https://dev.to/preciousaang/multi-guard-authentication-with-laravel-12-1jg3 
- https://laravel.com/docs/12.x/authentication 
- https://laravel.com/docs/12.x/localization 
- https://sevalla.com/blog/building-modular-systems-laravel 
- https://www.laravelpackage.com 
- https://laravel-news.com/building-your-own-laravel-packages 
- https://dev.to/rafaelogic/building-a-polymorphic-translatable-model-in-laravel-with-autoloaded-translations-3d99 
- https://freek.dev/1567-pragmatically-testing-multi-guard-authentication-in-laravel 
- https://laravel-news.com/laravel-gates-policies-guards-explained 
- https://dev.to/codeanddeploy/how-to-create-a-custom-dynamic-middleware-for-spatie-laravel-permission-2a08 
- https://laravel.com/docs/12.x/authorization

No shortcuts.
No architectural violations.
No regression in modular integrity.

This file is binding governance for all contributors and AI agents.
