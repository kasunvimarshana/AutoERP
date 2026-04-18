# Copilot Instructions

## Project Overview

KVAutoERP is an enterprise-grade SaaS multi-tenant ERP/CRM platform built with **Laravel 13** and **PHP 8.3+**. It uses Clean Architecture with DDD, organized as independent modules under `app/Modules/`. The platform supports procurement, sales, inventory, finance (double-entry accounting), product management, warehouse management, pricing, and more.

## Build & Test Commands

Always run these commands from the repository root.

```bash
# Install PHP dependencies (always run first)
composer install

# Install JS dependencies
npm install --ignore-scripts

# Build frontend assets
npm run build

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
├── Core/              # Shared kernel: BaseModel, HasUuid/HasTenant/HasAudit traits
├── Tenant/            # Multi-tenancy management
├── OrganizationUnit/  # Hierarchical org structures (materialized path)
├── User/              # Authentication, authorization, profiles
├── Auth/              # OAuth2 (Laravel Passport) login/token flows
├── Customer/          # Customer master data, AR linkage
├── Supplier/          # Supplier master data, AP linkage
├── Employee/          # Employee management
├── Product/           # Product catalog, variants, categories, UoM
├── Pricing/           # Price lists, tiered pricing, modifiers
├── Warehouse/         # Warehouses and location hierarchies
├── Inventory/         # Stock levels, movements, batch/lot/serial tracking
├── Purchase/          # Procurement: POs, GRNs, purchase invoices, returns
├── Sales/             # Order-to-cash: SOs, shipments, invoices, returns
├── Finance/           # Double-entry accounting, chart of accounts, journal entries
├── Tax/               # Tax groups, rates, rules
├── Audit/             # Audit logs, compliance trails
├── Configuration/     # System settings, org-unit config
└── Shared/            # Cross-module contracts, DTOs, events
bootstrap/providers.php  # All module ServiceProviders registered here
composer.json            # PSR-4: "Modules\\" => "app/Modules/"
phpunit.xml              # Test config (SQLite :memory:)
```

## Module Architecture

Each module follows a strict layered structure:

```
app/Modules/<Module>/
├── Domain/                  # Entities, RepositoryInterfaces, Events, Exceptions, ValueObjects
├── Application/             # Contracts (service interfaces), Services, DTOs, UseCases
├── Infrastructure/
│   ├── Persistence/Eloquent/
│   │   ├── Models/          # Eloquent models (extend BaseModel)
│   │   ├── Repositories/    # Implements Domain interfaces
│   │   └── Traits/
│   ├── Http/
│   │   ├── Controllers/     # Thin controllers, delegate to services
│   │   └── Resources/       # API resources
│   └── Providers/           # ServiceProvider: binds interfaces, loads migrations/routes
├── database/migrations/     # Module-scoped migrations
├── routes/api.php           # Module API routes
└── config/
```

**Layer rules**: Domain has no framework imports. Application depends only on Domain. Infrastructure implements Domain interfaces. Cross-module communication uses events only.

## Key Conventions

- **PHP**: `declare(strict_types=1);` in every file. Strong typing on all parameters/returns.
- **Namespaces**: `Modules\<Module>\...` (not `App\Modules\...`).
- **Primary keys**: UUID via `HasUuid` trait (non-incrementing string PKs).
- **Multi-tenancy**: `HasTenant` trait applies global scope. Repositories call `withoutGlobalScopes()` and filter `tenant_id` explicitly.
- **Auditing**: `HasAudit` trait for automatic change tracking.
- **Models**: Extend `BaseModel`, use `HasUuid`, `HasTenant`, `HasAudit` traits.
- **Repositories**: Interface in `Domain/RepositoryInterfaces/`, Eloquent impl in `Infrastructure/Persistence/Eloquent/Repositories/`.
- **Services**: Contract in `Application/Contracts/`, implementation in `Application/Services/`. Wrap writes in DB transactions.
- **Controllers**: Extend `BaseController`; stay thin — delegate to services.
- **ServiceProviders**: Bind interfaces in `register()`. In `boot()`, load migrations from `__DIR__.'/../../database/migrations'` and routes from `__DIR__.'/../../routes/api.php'`.
- **Float comparison**: Use `abs($value) < PHP_FLOAT_EPSILON` instead of `== 0.0`.
- **Monetary values**: `DECIMAL(20,6)` — never `FLOAT`.

## Creating a New Module

1. Create directory structure under `app/Modules/<ModuleName>/` following the layout above.
2. Create `<ModuleName>ServiceProvider` in `Infrastructure/Providers/`:
   - Bind repository interfaces → Eloquent implementations in `register()`.
   - Load migrations and routes in `boot()`.
3. Register the provider in `bootstrap/providers.php`.

## File Naming

| Type | Pattern | Example |
|------|---------|---------|
| Entity | `<Name>.php` | `Product.php` |
| Model | `<Name>Model.php` | `ProductModel.php` |
| Repository | `Eloquent<Name>Repository.php` | `EloquentProductRepository.php` |
| Service | `<Name>Service.php` | `ProductService.php` |
| Controller | `<Name>Controller.php` | `ProductController.php` |
| Migration | `YYYY_MM_DD_NNNNNN_create_<table>_table.php` | `2026_04_01_000001_create_products_table.php` |

## What to Avoid

- Do not create circular dependencies between modules.
- Do not import Infrastructure classes in the Domain layer.
- Do not bypass the repository pattern for database access.
- Do not hardcode tenant IDs — always derive from auth context or request headers.
- Do not modify Core module traits without considering impact on all modules.
- Do not use `float` for monetary or quantity fields — use `DECIMAL`.

## Key Dependencies

- **laravel/passport** — OAuth2 API authentication
- **laravel/reverb** — Real-time WebSocket broadcasting
- **darkaonline/l5-swagger** — OpenAPI/Swagger API documentation
