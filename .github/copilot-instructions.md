# Copilot Instructions

## Project Overview

KVAutoERP is a modular SaaS multi-tenant ERP/CRM platform built with **Laravel 12** and **PHP 8.2+**. It uses Clean Architecture organized as independent modules under `app/Modules/`. Currently 9 modules are fully implemented, 1 is infrastructure-only, and 9 have only migration schemas.

## Build & Test Commands

Always run these commands from the repository root.

```bash
# Install PHP dependencies (always run first)
composer install

# Run all tests (uses SQLite :memory: by default via phpunit.xml)
./vendor/bin/phpunit

# Run tests for a specific module
./vendor/bin/phpunit --filter=<ModuleName>

# Lint PHP code
./vendor/bin/pint

# Generate .env if missing
cp .env.example .env && php artisan key:generate
```

**Important**: Always run `composer install` before running tests or any artisan command. Tests use SQLite in-memory database — no external DB is needed.

## Project Layout

## Module Architecture

Each fully implemented module follows this layered structure:

```
app/Modules/<Module>/
├── Domain/
│   ├── Entities/              # Pure PHP domain objects
│   ├── RepositoryInterfaces/  # Persistence contracts
│   ├── Events/                # Domain events
│   ├── Exceptions/            # Domain-specific exceptions
│   └── ValueObjects/          # Immutable value types
├── Application/
│   ├── Contracts/             # Service interfaces
│   ├── Services/              # Service implementations
│   └── DTOs/                  # Data transfer objects
├── Infrastructure/
│   ├── Persistence/Eloquent/
│   │   ├── Models/            # Eloquent models (extend Model directly)
│   │   └── Repositories/      # Implements Domain repository interfaces
│   ├── Http/
│   │   ├── Controllers/       # Thin controllers delegating to services
│   │   ├── Resources/         # API resources
│   │   ├── Requests/          # Form request validation
│   │   └── Middleware/         # Module-specific middleware
│   ├── Providers/             # ServiceProvider (bindings, migrations, routes)
│   └── Broadcasting/          # Channel definitions (where applicable)
├── database/migrations/
└── routes/api.php
```

## Key Conventions

- **`declare(strict_types=1);`** in every PHP file. Strong typing on all parameters/returns.
- **Namespaces**: `Modules\<Module>\...` (not `App\Modules\...`).
- **`HasTenant` trait**: Owned by Tenant module and used by tenant-scoped models. Tenant isolation uses `resolve.tenant` middleware with `X-Tenant-ID` header.
- **Multi-tenancy**: `ResolveTenant` middleware reads `X-Tenant-ID` from request headers. Repositories filter `tenant_id` explicitly.
- **Repositories**: Interface in `Domain/RepositoryInterfaces/`, implementation in `Infrastructure/Persistence/Eloquent/Repositories/`.
- **Services**: Contract in `Application/Contracts/`, implementation in `Application/Services/`. Wrap writes in DB transactions.
- **Controllers**: Stay thin — delegate to services.
- **Monetary values**: `DECIMAL(20,6)`.
- **Float comparison**: `abs($value) < PHP_FLOAT_EPSILON` instead of `== 0.0`.

## Registered Providers (bootstrap/providers.php)

## File Naming

| Type | Pattern | Example |
|------|---------|---------|
| Entity | `<Name>.php` | `Product.php` |
| Model | `<Name>Model.php` | `ProductModel.php` |
| Repository | `Eloquent<Name>Repository.php` | `EloquentProductRepository.php` |
| Service | `<Name>Service.php` | `ProductService.php` |
| Controller | `<Name>Controller.php` | `ProductController.php` |

## What to Avoid

- Do not create circular dependencies between modules.
- Do not import Infrastructure classes in the Domain layer.
- Do not bypass the repository pattern for database access.
- Do not hardcode tenant IDs — derive from auth context or request headers.
- Do not modify Core module traits without considering impact on all modules.

## Key Dependencies

- **laravel/passport** — OAuth2 API authentication
- **laravel/reverb** — Real-time WebSocket broadcasting
- **darkaonline/l5-swagger** — OpenAPI/Swagger API documentation
