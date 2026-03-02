# Organisation Module

## Overview

The **Organisation** module manages the full tenant organisational hierarchy:

```
Tenant
 └── Organisation
      └── Branch
           └── Location
                └── Department
```

---

## Responsibilities

- Organisation CRUD (tenant-scoped)
- Branch management
- Location management
- Department management
- Hierarchical queries (parent/children traversal)
- Organisation-scoped configuration

---

## Architecture Layer

```
Modules/Organisation/
 ├── Application/       # Organisation/Branch/Location/Department use cases
 ├── Domain/            # Organisation entity, hierarchy value objects, repository contracts
 ├── Infrastructure/    # Repository implementations, OrganisationServiceProvider
 ├── Interfaces/        # Controllers, API resources, form requests
 ├── module.json
 └── README.md
```

---

## Dependencies

- `core`
- `tenancy`

---

## Architecture Compliance

| Rule | Status |
|---|---|
| No business logic in controllers | ✅ Enforced |
| No query builder calls in controllers | ✅ Enforced |
| `tenant_id` on all hierarchy tables | ✅ Required |
| No circular relationships in hierarchy | ✅ Enforced |
| No cross-module coupling | ✅ Enforced |

---

## Status

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
