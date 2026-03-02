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

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
