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

## API Routes (`/api/v1`)

| Method | Path | Action |
|---|---|---|
| GET | `/organisations` | index |
| POST | `/organisations` | store |
| GET | `/organisations/{id}` | show |
| PUT | `/organisations/{id}` | update |
| DELETE | `/organisations/{id}` | destroy |
| GET | `/organisations/{orgId}/branches` | listBranches |
| POST | `/organisations/{orgId}/branches` | createBranch |
| GET | `/branches/{id}` | showBranch |
| PUT | `/branches/{id}` | updateBranch |
| DELETE | `/branches/{id}` | deleteBranch |
| GET | `/branches/{branchId}/locations` | listLocations |
| POST | `/branches/{branchId}/locations` | createLocation |
| GET | `/locations/{id}` | showLocation |
| PUT | `/locations/{id}` | updateLocation |
| DELETE | `/locations/{id}` | deleteLocation |
| GET | `/locations/{locationId}/departments` | listDepartments |
| POST | `/locations/{locationId}/departments` | createDepartment |
| GET | `/departments/{id}` | showDepartment |
| PUT | `/departments/{id}` | updateDepartment |
| DELETE | `/departments/{id}` | deleteDepartment |

---

## Test Coverage

| Test File | Type | Coverage Area |
|---|---|---|
| `Tests/Unit/CreateOrganisationDTOTest.php` | Unit | DTO hydration, defaults |
| `Tests/Unit/OrganisationServiceTest.php` | Unit | list/show delegation, hierarchy read delegation — 20 assertions |
| `Tests/Unit/OrganisationHierarchyDTOTest.php` | Unit | CreateBranchDTO, CreateLocationDTO, CreateDepartmentDTO — 18 assertions |
| `Tests/Unit/OrganisationHierarchyControllerTest.php` | Unit | Controller + service method existence for all hierarchy endpoints — 15 assertions |
| `Tests/Unit/OrganisationHierarchyUpdateTest.php` | Unit | updateBranch, deleteBranch, updateLocation, deleteLocation, updateDepartment, deleteDepartment — 13 assertions |

---

## Status

🟢 **Complete** — Full CRUD for Organisation hierarchy implemented including update/delete for branches, locations, and departments (~80% test coverage). See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
