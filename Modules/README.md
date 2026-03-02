# Modules Directory

This directory contains all platform modules following the Clean Architecture modular monolith pattern defined in [AGENT.md](../AGENT.md).

---

## Module Structure Standard

Each module **must** follow this directory structure:

```
Modules/
 └── {ModuleName}/
     ├── Application/       # Use cases, commands, queries, DTOs, service orchestration
     ├── Domain/            # Entities, value objects, domain events, repository contracts, business rules
     ├── Infrastructure/    # Repository implementations, external service adapters, persistence
     ├── Interfaces/        # HTTP controllers, API resources, form requests, console commands
     ├── module.json        # Module manifest
     └── README.md          # Module documentation
```

---

## Module Manifest (`module.json`)

Every module must have a `module.json` manifest with the following fields:

| Field | Required | Description |
|---|---|---|
| `name` | ✅ | PascalCase module name |
| `alias` | ✅ | kebab-case module alias |
| `description` | ✅ | What the module does |
| `keywords` | ✅ | Search/discovery keywords |
| `priority` | ✅ | Boot priority order |
| `version` | ✅ | Semantic version |
| `active` | ✅ | Whether module is enabled |
| `order` | ✅ | Service provider load order |
| `providers` | ✅ | Service provider class names |
| `requires` | ✅ | Dependency module aliases |

---

## Module Load Order (Dependency Graph)

```
Core (1)
  └── Tenancy (2)
       ├── Auth (3)                           ← requires: Core, Tenancy
       ├── Organisation (4)                   ← requires: Core, Tenancy
       ├── Metadata (5)                       ← requires: Core, Tenancy
       │    ├── Workflow (6)                  ← requires: Core, Tenancy, Metadata
       │    └── Product (7)                   ← requires: Core, Tenancy, Metadata
       ├── Accounting (8)                     ← requires: Core, Tenancy
       ├── Pricing (9)                        ← requires: Core, Tenancy, Product
       ├── Inventory (10)                     ← requires: Core, Tenancy, Product
       │    └── Warehouse (11)                ← requires: Core, Tenancy, Inventory
       ├── Sales (12)                         ← requires: Core, Tenancy, Product, Inventory, Pricing, Accounting
       │    └── POS (13)                      ← requires: Core, Tenancy, Sales, Pricing, Accounting
       ├── CRM (14)                           ← requires: Core, Tenancy, Workflow
       ├── Procurement (15)                   ← requires: Core, Tenancy, Product, Inventory, Accounting, Workflow
       ├── Reporting (16)                     ← requires: Core, Tenancy
       ├── Notification (17)                  ← requires: Core, Tenancy
       ├── Integration (18)                   ← requires: Core, Tenancy
       └── Plugin (19)                        ← requires: Core, Tenancy
```

---

## Layer Responsibilities

| Layer | Responsibility |
|---|---|
| **Application** | Use cases, commands, queries, DTOs, service orchestration — no framework dependency |
| **Domain** | Pure domain entities, value objects, domain events, repository contracts, business rules — no infrastructure |
| **Infrastructure** | Repository implementations, Eloquent models, service providers, external adapters — no business logic |
| **Interfaces** | HTTP controllers (input validation + authorization + response only), API resources, form requests, console commands |

---

## Cross-Module Communication Rules

- ✅ Via domain events (event bus)
- ✅ Via published contracts (interfaces in `Domain/`)
- ❌ Never via direct class instantiation from another module
- ❌ Never via direct database queries into another module's tables

---

## Definition of Done (Per Module)

A module is **complete** only if:

- [ ] Clean Architecture respected; no layer violations
- [ ] No code duplication
- [ ] Tenant isolation enforced (`tenant_id` + global scope)
- [ ] Authorization enforced (policy classes)
- [ ] Concurrency handled (pessimistic/optimistic locking)
- [ ] Financial precision validated (BCMath, no float)
- [ ] Domain events emitted correctly
- [ ] API documented (OpenAPI/Swagger)
- [ ] Unit tests pass
- [ ] Feature tests pass
- [ ] Authorization tests pass
- [ ] Tenant isolation tests pass
- [ ] Concurrency tests pass
- [ ] Financial precision tests pass
- [ ] Module README updated
- [ ] `IMPLEMENTATION_STATUS.md` updated
- [ ] No technical debt introduced
