# Copilot Instructions for KVAutoERP

## Project Overview

KVAutoERP is an enterprise-grade SaaS multi-tenant ERP/CRM platform built with Laravel, following Clean Architecture and Domain-Driven Design (DDD) principles. The codebase uses a modular architecture where each business domain is a self-contained module under `app/Modules/`.

## Architecture

### Layered Structure (per module)

Each module follows strict Clean Architecture with four layers:

```
app/Modules/<Module>/
├── Domain/                    # Business rules (no framework dependencies)
│   ├── Entities/              # Domain entities (pure PHP classes)
│   ├── ValueObjects/          # Immutable value types
│   ├── Events/                # Domain events
│   ├── Exceptions/            # Domain-specific exceptions
│   └── RepositoryInterfaces/  # Repository contracts
├── Application/               # Use cases and orchestration
│   ├── Contracts/             # Service interfaces
│   ├── Services/              # Application services
│   ├── DTOs/                  # Data Transfer Objects
│   └── UseCases/              # Use case implementations
├── Infrastructure/            # Framework and external concerns
│   ├── Persistence/Eloquent/
│   │   ├── Models/            # Eloquent models
│   │   ├── Repositories/      # Repository implementations
│   │   └── Traits/            # Reusable model traits
│   ├── Http/
│   │   ├── Controllers/       # API controllers
│   │   ├── Middleware/         # HTTP middleware
│   │   └── Resources/         # API resources (JSON transformers)
│   ├── Providers/             # Service providers (DI bindings)
│   └── Broadcasting/          # Real-time channels
├── config/                    # Module configuration
├── database/migrations/       # Database migrations
└── routes/api.php             # API route definitions
```

### Core Principles

- **SOLID** — Strictly follow all five SOLID principles.
- **DRY** — Extract shared behavior into traits, base classes, or services.
- **KISS** — Prefer simple, readable solutions over clever abstractions.
- **Interface-driven** — Code against contracts, not concrete implementations.
- **High cohesion, loose coupling** — Modules must not depend on other modules' internals.

## Coding Conventions

### PHP

- Use `declare(strict_types=1);` in all PHP files.
- Target PHP 8.2+ (readonly properties, enums, named arguments, match expressions).
- Strong typing for all method signatures (parameter types and return types).
- Use `abs($value) < PHP_FLOAT_EPSILON` instead of `== 0.0` for float comparisons.

### Models and Database

- All models extend `BaseModel` from `Core/Infrastructure/Persistence/Eloquent/Models/`.
- UUID primary keys via `HasUuid` trait (non-incrementing, string key type).
- `HasTenant` trait for tenant-scoped models.
- `HasAudit` trait for automatic change tracking.
- Minimum 3NF/BCNF normalization.
- Migrations in `app/Modules/<Module>/database/migrations/`.

### Repositories

- Define interfaces in `Domain/RepositoryInterfaces/`.
- Implement in `Infrastructure/Persistence/Eloquent/Repositories/`.
- Call `withoutGlobalScopes()` in queries, then filter by `tenant_id` explicitly.
- Bind interfaces to implementations in the module ServiceProvider.

### Services

- Define contracts in `Application/Contracts/`.
- Implement in `Application/Services/`.
- Wrap write operations in database transactions.
- Dispatch domain events after successful operations.

### Controllers and Routes

- Extend `BaseController` or `AuthorizedController` from Core.
- Keep controllers thin — delegate logic to services.
- Use API Resources for response formatting.
- Define routes in `routes/api.php` per module using `Route::prefix('api')->group(...)`.

### Service Providers

- Located at `Infrastructure/Providers/<Module>ServiceProvider.php`.
- Bind interfaces in `register()`.
- Load migrations and routes in `boot()`.

## Multi-Tenancy

- Enforced via `HasTenant` trait (global scope using `auth()->user()->tenant_id` or `X-Tenant-ID` header).
- Repositories bypass the global scope and filter by `tenant_id` explicitly.
- On creating, `tenant_id` auto-fills only if empty.

## Key Technologies

- **Laravel Passport** — OAuth2-based API authentication.
- **Laravel Reverb** — WebSocket real-time event broadcasting.
- **L5 Swagger** — OpenAPI documentation for all endpoints.

## Testing

- PHPUnit for unit and integration tests.
- Test domain entities, value objects, and services in isolation.
- Test repository implementations against the database.
