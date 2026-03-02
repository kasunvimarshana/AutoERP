# Metadata Module

## Overview

The **Metadata** module is the backbone of the platform's metadata-driven architecture. It enables runtime-configurable custom fields, dynamic forms, validation rules, and feature toggles — all without redeployment.

---

## Responsibilities

- Custom field definitions per entity type (tenant-scoped)
- Dynamic form schema management
- Conditional field visibility rules
- Computed field definitions
- Validation rule engine
- Workflow state definitions (used by Workflow module)
- UI layout definitions
- Feature toggle management (tenant and feature-level)

---

## Architecture Layer

```
Modules/Metadata/
 ├── Application/       # Custom field CRUD use cases, feature toggle resolution
 ├── Domain/            # FieldDefinition entity, FeatureFlag entity, repository contracts
 ├── Infrastructure/    # Repository implementations, MetadataServiceProvider
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
| All metadata is database-driven (no hardcoded business rules) | ✅ Enforced |
| All configurable logic replaceable without redeployment | ✅ Required |
| Tenant-scoped metadata | ✅ Enforced |
| No cross-module coupling | ✅ Enforced |

---

## Status

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
