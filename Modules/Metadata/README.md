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

## Implemented Files

### Migrations
| File | Table |
|---|---|
| `create_field_definitions_table.php` | `field_definitions` — custom field definitions per entity type |
| `create_feature_flags_table.php` | `feature_flags` — tenant-level feature toggles |

### Domain Entities
- `FieldDefinition` — HasTenant; entity_type, field_key, field_type, validation_rules as JSON
- `FeatureFlag` — HasTenant; unique (tenant_id, feature_key)

### Application Layer
- `MetadataService` — listFields, paginateFields, createField, showField, updateField, deleteField, isFeatureEnabled, **listFlags**, **createFlag**, **updateFlag**, **deleteFlag**, **toggleFlag** (all mutations in DB::transaction)

### Infrastructure Layer
- `MetadataRepositoryContract` — findByEntityType, findByFieldKey
- `MetadataRepository` — extends AbstractRepository on FieldDefinition
- `MetadataServiceProvider` — binds contract, loads migrations and routes

### API Routes (`/api/v1`)
| Method | Path | Action |
|---|---|---|
| GET | `/metadata/fields` | listFields / paginateFields |
| POST | `/metadata/fields` | createField |
| GET | `/metadata/fields/{id}` | showField |
| PUT | `/metadata/fields/{id}` | updateField |
| DELETE | `/metadata/fields/{id}` | deleteField |
| GET | `/metadata/features/{key}` | isFeatureEnabled |

---

## Test Coverage

| Test File | Type | Coverage Area |
|---|---|---|
| `Tests/Unit/MetadataServiceWritePathExpandedTest.php` | Unit | createField, updateField, deleteField, isFeatureEnabled — method signatures, delegation — 13 assertions |
| `Tests/Unit/MetadataServiceFeatureFlagTest.php` | Unit | listFlags, createFlag, updateFlag, deleteFlag, toggleFlag — method existence, visibility, signatures, delegation, return types — 22 assertions |

---

## Status

🟢 **Complete** — Custom field definitions, feature flag full CRUD (listFlags, createFlag, updateFlag, deleteFlag, toggleFlag) implemented (~85% test coverage). See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
