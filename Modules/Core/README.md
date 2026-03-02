# Core Module

Handles tenant and organisation management for the multi-tenant ERP SaaS platform.

## Responsibilities

- Tenant lifecycle (create, activate, deactivate, plan changes)
- Organisation management per tenant
- Tenant context resolution (middleware)
- Row-level isolation enforcement

## Architecture

```
Core/
├── Domain/
│   ├── ValueObjects/TenantId.php      — Strongly-typed tenant identifier
│   ├── Entities/Tenant.php            — Pure domain entity (no Eloquent)
│   ├── Entities/Organisation.php      — Pure domain entity
│   └── Contracts/TenantRepositoryInterface.php
├── Infrastructure/
│   ├── Models/Tenant.php              — Eloquent model
│   ├── Models/Organisation.php        — Eloquent model
│   ├── Repositories/TenantRepository.php
│   └── Database/Migrations/
├── Interfaces/
│   └── Http/Controllers/TenantController.php
└── Providers/CoreServiceProvider.php
```

## API Endpoints

| Method | Endpoint               | Description          |
|--------|------------------------|----------------------|
| GET    | /api/v1/tenants        | List all tenants     |
| POST   | /api/v1/tenants        | Create tenant        |
| GET    | /api/v1/tenants/{id}   | Get tenant by ID     |
| PUT    | /api/v1/tenants/{id}   | Update tenant        |
| DELETE | /api/v1/tenants/{id}   | Soft-delete tenant   |

## Database Tables

- `tenants` — Tenant registry
- `organisations` — Organisations per tenant (multi-org support)

## Multi-Tenancy

Every request to tenant-scoped endpoints must include:
- `X-Tenant-ID: {id}` header, or
- `tenant_id` claim in the JWT token

The `TenantMiddleware` validates the tenant and binds it to the IoC container.
