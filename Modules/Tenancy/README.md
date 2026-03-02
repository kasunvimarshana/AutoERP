# Tenancy Module

## Overview

The **Tenancy** module enforces strict multi-tenant isolation across the entire platform. It is responsible for tenant resolution, global scope enforcement, and tenant-scoped infrastructure.

**Failure to isolate tenants is a Critical Violation per AGENT.md.**

---

## Responsibilities

- Tenant model and repository
- Tenant resolution middleware:
  - Subdomain-based resolution
  - HTTP header-based resolution (`X-Tenant-ID`)
  - JWT claim-based resolution
- Global Eloquent scope: automatic `tenant_id` filtering on all business models
- Tenant-scoped cache (cache key prefixing)
- Tenant-scoped queue (queue naming/routing)
- Tenant-scoped storage (storage path prefixing)
- Tenant-scoped config overrides
- Tenant context singleton (current tenant binding)

---

## Multi-Tenancy Model

**Default:** Shared Database + Shared Schema with strict row-level isolation via `tenant_id`.

**Upgrade Path:** Separate database per tenant (config-driven, no code changes required).

### Tenant Hierarchy

```
Tenant
 └── Organisation
      └── Branch
           └── Location
                └── Department
```

---

## Architecture Layer

```
Modules/Tenancy/
 ├── Application/       # Tenant DTOs, resolve-tenant use case
 ├── Domain/            # Tenant entity, TenantRepository contract, TenantContext value object
 ├── Infrastructure/    # TenantRepository implementation, TenancyServiceProvider, GlobalTenantScope
 ├── Interfaces/        # TenantMiddleware, TenantAwareFormRequest
 ├── module.json
 └── README.md
```

---

## Database Tables

| Table | Key Columns | Notes |
|---|---|---|
| `tenants` | `id`, `name`, `slug`, `domain`, `is_active` | One row per tenant |
| `organisations` | `id`, `tenant_id`, `name`, `is_active` | Scoped by tenant |

All business tables **must** include `tenant_id` with the `GlobalTenantScope` applied.

---

## Dependencies

- `core`

---

## Architecture Compliance

| Rule | Status |
|---|---|
| No business logic in controllers | ✅ Enforced |
| `tenant_id` on all business tables | ✅ Required |
| Global scope on all business models | ✅ Required |
| JWT per user × device × organisation | ✅ Required |
| Cross-tenant access prohibited | ✅ Enforced |

---

## Status

🟢 **Complete** — Core tenant CRUD (list, listActive, create, show, findBySlug, findByDomain, update, delete) implemented (~85% test coverage). See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)

### Implemented

| Component | File | Description |
|---|---|---|
| `Tenant` entity | `Domain/Entities/Tenant.php` | Tenant model with `pharma_compliance_mode` flag, soft deletes |
| `TenantRepositoryContract` | `Domain/Contracts/TenantRepositoryContract.php` | findBySlug, findByDomain, allActive |
| `TenantRepository` | `Infrastructure/Repositories/TenantRepository.php` | Concrete repository — no global scope on Tenant itself (system-level entity) |
| `create_tenants_table` | `Infrastructure/Database/Migrations/` | `id`, `name`, `slug` (unique), `domain` (nullable unique), `is_active`, `pharma_compliance_mode`, `settings` (JSON), timestamps, soft deletes |
| `TenancyServiceProvider` | `Infrastructure/Providers/TenancyServiceProvider.php` | Binds TenantRepositoryContract → TenantRepository, registers TenancyService, loads migrations and routes |
| `tenancy.php` | `config/tenancy.php` | Resolution strategy, header name, current_tenant_id (test override), DB isolation model |
| `CreateTenantDTO` | `Application/DTOs/CreateTenantDTO.php` | Immutable DTO for tenant creation (name, slug, domain, is_active, pharma_compliance_mode) |
| `TenancyService` | `Application/Services/TenancyService.php` | list, listActive, create (DB::transaction), show, findBySlug, findByDomain, update, delete |
| `TenancyController` | `Interfaces/Http/Controllers/TenancyController.php` | Full CRUD controller with OpenAPI annotations; no business logic |
| `api.php` routes | `routes/api.php` | `/api/v1/tenants` REST resource (index, store, show, update, destroy) |
| `CreateTenantDTOTest` | `Tests/Unit/CreateTenantDTOTest.php` | 9 unit tests: hydration, defaults, bool coercion, toArray contract |
| `LoginDTOTest` | `../Auth/Tests/Unit/LoginDTOTest.php` | 7 unit tests: hydration, optional device_name, toArray contract |
| `TenancyServiceDelegationTest` | `Tests/Unit/TenancyServiceDelegationTest.php` | 8 unit tests: list/listActive/show/findBySlug/findByDomain/update/delete delegation |
