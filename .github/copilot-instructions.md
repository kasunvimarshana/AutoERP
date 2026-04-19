# Copilot Instructions

## Project Overview

KVAutoERP is a modular SaaS multi-tenant ERP/CRM platform built with **Laravel 13** and **PHP 8.3+**. It uses Clean Architecture organized as independent modules under `app/Modules/`. Currently 8 modules are fully implemented, 2 are infrastructure-only, and 9 have only migration schemas.

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

```
app/Modules/           # All business modules (19 modules)
├── Core/              # Shared kernel: base classes, repository abstractions, technical concerns
├── Tenant/            # Multi-tenancy management, plans, settings, config
├── OrganizationUnit/  # Hierarchical org structures (materialized path)
├── User/              # User CRUD, profiles, roles, permissions, devices
├── Auth/              # OAuth2 (Laravel Passport) login/token/SSO flows
├── Product/           # Product catalog, variants, categories, brands, UoM
├── Finance/           # Double-entry accounting, chart of accounts, journal entries
├── Audit/             # Immutable audit logs, compliance trails
├── Configuration/     # Reference data ownership (countries, currencies, languages, timezones)
├── Shared/            # Minimal shell for truly shared cross-cutting surface only
├── Customer/          # Migration-only stub
├── Supplier/          # Migration-only stub
├── Employee/          # Migration-only stub
├── Pricing/           # Migration-only stub
├── Warehouse/         # Migration-only stub
├── Inventory/         # Migration-only stub
├── Purchase/          # Migration-only stub
├── Sales/             # Migration-only stub
└── Tax/               # Migration-only stub
bootstrap/providers.php  # 12 ServiceProviders registered
composer.json            # PSR-4: "Modules\\" => "app/Modules/"
phpunit.xml              # Test config (SQLite :memory:)
```

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
- **Models**: Extend `Illuminate\Database\Eloquent\Model` directly. `BaseModel` exists in Core but is unused.
- **`HasAudit` trait**: Owned by Audit module and used by 20 models for automatic change tracking.
- **`SoftDeletes`**: Used by 8 models (Account, OrgUnit, OrgUnitAttachment, Product, Tenant, TenantAttachment, User, UserAttachment).
- **`HasUuid` trait**: Defined in Core but currently unused. All models use integer auto-increment PKs.
- **`HasTenant` trait**: Owned by Tenant module and used by tenant-scoped models. Tenant isolation uses `resolve.tenant` middleware with `X-Tenant-ID` header.
- **Multi-tenancy**: `ResolveTenant` middleware reads `X-Tenant-ID` from request headers. Repositories filter `tenant_id` explicitly.
- **Repositories**: Interface in `Domain/RepositoryInterfaces/`, implementation in `Infrastructure/Persistence/Eloquent/Repositories/`.
- **Services**: Contract in `Application/Contracts/`, implementation in `Application/Services/`. Wrap writes in DB transactions.
- **Controllers**: Stay thin — delegate to services.
- **Monetary values**: `DECIMAL(20,6)`.
- **Float comparison**: `abs($value) < PHP_FLOAT_EPSILON` instead of `== 0.0`.

## Registered Providers (bootstrap/providers.php)

AppServiceProvider, CoreServiceProvider, ConfigurationServiceProvider, SharedServiceProvider, AuditServiceProvider, AuthModuleServiceProvider, TenantServiceProvider, TenantConfigServiceProvider, UserServiceProvider, OrganizationUnitServiceProvider, ProductServiceProvider, FinanceServiceProvider

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
