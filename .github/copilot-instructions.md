# Copilot Instructions

## Project Overview

This repository is a **production-grade, enterprise, multi-tenant, modular ERP/CRM SaaS platform** built with:

- **Backend:** Laravel (LTS only — currently Laravel 12.x)
- **Frontend:** React (LTS only)
- **Architecture:** Modular Monolith (plugin-ready)
- **Database:** MySQL / PostgreSQL — shared DB, row-level tenant isolation
- **Auth:** JWT, stateless, multi-guard
- **Authorization:** RBAC + ABAC (Policy classes only)
- **Financial Precision:** BCMath — arbitrary precision, no floating-point arithmetic
- **API:** RESTful, versioned at `/api/v1`, OpenAPI/Swagger documented

The platform covers: Inventory Management, Pharmaceutical Inventory, Warehouse Management (WMS), Sales & POS, Accounting & Finance, CRM, Procurement, Workflow Engine, and third-party ERP/CRM integration via open APIs.

> **Current State (as of 2026-02-27):** All 19 modules are **🔴 Planned** — no backend or frontend code has been scaffolded yet. All existing work is documentation and governance only.

---

## Governing Documents (Read First)

| Document | Purpose | Authority |
|---|---|---|
| [`AGENT.md`](../AGENT.md) | Full governance contract — binding rules for all contributors and AI agents | **Primary Authority** |
| [`KB.md`](../KB.md) | Complete domain knowledge base — 38 sections covering all ERP/CRM domains | **Authoritative Reference** |
| [`IMPLEMENTATION_STATUS.md`](../IMPLEMENTATION_STATUS.md) | Module-by-module implementation progress tracker | **Must be updated after every change** |
| [`README.md`](../README.md) | Repository overview and module table | Context |
| [`CLAUDE.md`](../CLAUDE.md) | Claude AI agent guide (mirrors AGENT.md + KB.md) | Context |

**Always read AGENT.md and KB.md before making any changes.** These are binding contracts.

---

## System Mission

Build a system that is:

- Modular Monolith (plugin-ready)
- Fully metadata-driven
- API-first and stateless
- Horizontally and vertically scalable
- Financially precise
- Strictly tenant-isolated
- Replaceable per module
- Zero architectural debt tolerance

---

## Repository Structure

```
/
├── AGENT.md                        # Governance contract (primary authority)
├── KB.md                           # Domain knowledge base (38 sections)
├── IMPLEMENTATION_STATUS.md        # Module progress tracker
├── README.md                       # Repository overview
├── CLAUDE.md                       # Claude AI agent guide
├── AGENT.old.md                    # Legacy (superseded by AGENT.md v4.0)
├── AGENT.old_01.md                 # Legacy (superseded by AGENT.md v4.0)
├── KNOWLEDGE_BASE.md               # Legacy KB (superseded by KB.md v2.0)
├── KNOWLEDGE_BASE_01.md            # Legacy KB supplement (superseded by KB.md v2.0)
├── KNOWLEDGE_BASE_02.md            # Legacy KB supplement (superseded by KB.md v2.0)
├── .github/
│   └── copilot-instructions.md     # This file — Copilot-specific instructions
└── Modules/
    ├── README.md                   # Module load-order diagram
    ├── Core/                       # Foundation, base abstractions (priority: 1)
    ├── Tenancy/                    # Multi-tenant isolation (priority: 2)
    ├── Auth/                       # JWT, RBAC, ABAC (priority: 3)
    ├── Organisation/               # Tenant→Organisation→Branch→Location→Dept (priority: 4)
    ├── Metadata/                   # Custom fields, dynamic forms (priority: 5)
    ├── Workflow/                   # State machine, approvals, SLA (priority: 6)
    ├── Product/                    # Product catalog, UOM, variants (priority: 7)
    ├── Accounting/                 # Double-entry bookkeeping, journals (priority: 8)
    ├── Pricing/                    # Rule-based pricing & discounts (priority: 9)
    ├── Inventory/                  # Ledger-driven IMS + pharma compliance (priority: 10)
    ├── Warehouse/                  # WMS: bins, putaway, picking (priority: 11)
    ├── Sales/                      # Quotation → Order → Delivery → Invoice → Payment (priority: 12)
    ├── POS/                        # Offline-first POS terminal (priority: 13)
    ├── CRM/                        # Lead → Opportunity → Proposal → Closed (priority: 14)
    ├── Procurement/                # PO → Receipt → Bill, three-way match (priority: 15)
    ├── Reporting/                  # Financial statements, inventory reports (priority: 16)
    ├── Notification/               # Multi-channel notification engine (priority: 17)
    ├── Integration/                # Webhooks, e-commerce sync (priority: 18)
    └── Plugin/                     # Plugin marketplace (priority: 19)
```

Each module follows this structure:

```
Modules/{ModuleName}/
├── Application/       # Use cases, commands, queries, DTOs, service orchestration
├── Domain/            # Entities, value objects, domain events, repository contracts, business rules
├── Infrastructure/    # Repository implementations, external service adapters, persistence
├── Interfaces/        # HTTP controllers, API resources, form requests, console commands
├── module.json        # Module manifest (name, version, dependencies, priority/load-order)
└── README.md          # Module documentation (must be updated after every change)
```

---

## Module Manifest (`module.json`)

Every module must have a `module.json` manifest with the following required fields:

| Field | Type | Description |
|---|---|---|
| `name` | string | PascalCase module name (e.g., `"Inventory"`) |
| `alias` | string | kebab-case module alias (e.g., `"inventory"`) |
| `description` | string | What the module does |
| `keywords` | array | Search/discovery keywords |
| `priority` | integer | Boot priority — must be strictly greater than all dependency priorities |
| `version` | string | Semantic version (e.g., `"1.0.0"`) |
| `active` | boolean | Whether the module is enabled |
| `order` | integer | Service provider load order (must match `priority`) |
| `providers` | array | Service provider class names |
| `requires` | array | Dependency module aliases (kebab-case) |

---

## Core Architecture

### Governing Principles

- SOLID, DRY, KISS
- Explicit domain boundaries; clear separation of concerns
- Deterministic behavior; immutable financial calculations

### Mandatory Application Flow

**Every feature in every module must follow this exact pipeline:**

```
Controller → Service → Handler (Pipeline) → Repository → Entity
```

| Layer | File Location | Responsibilities | Violations |
|---|---|---|---|
| **Controller** | `Interfaces/Http/Controllers/` | Input validation, authorization, response formatting | ❌ No business logic — ❌ No query builder |
| **Service** | `Application/Services/` | Use case orchestration, transaction boundaries, pipeline calls | ❌ No direct DB queries |
| **Handler (Pipeline)** | `Application/Handlers/` or `Application/Pipelines/` | Single-responsibility steps, transformations, domain rules, reusable units | ❌ No cross-module direct calls |
| **Repository** | `Infrastructure/Repositories/` | Data access only, tenant-aware queries | ❌ No domain logic — ❌ No business rules |
| **Entity** | `Domain/Entities/` | Pure domain model, relationships, attribute casting | ❌ No orchestration logic |

- Each module must be independently replaceable.
- Controllers contain **no business logic** and **no query builder calls**.
- Cross-module communication via contracts and events only — no direct coupling.
- Domain logic must be isolated from infrastructure.
- No circular dependencies.

---

## Multi-Tenancy (Critical — Zero Tolerance)

### Hierarchy Model

```
Tenant
 └── Organisation
      └── Branch
           └── Location
                └── Department
```

### Mandatory Enforcement

- Every business table must have `tenant_id` with a **global scope enforced at the Eloquent model level**.
- Tenant-scoped: cache, queues, storage, configs.
- JWT per user × device × organisation; stateless authentication.
- Tenant resolution via: subdomain, header, or JWT claim.
- Unauthorized cross-tenant data access is a **Critical Violation** — zero tolerance.
- Tenant isolation must be enforced at the **repository layer**, not the controller layer.

### Multi-Tenant Database Models

| Model | Description |
|---|---|
| Shared DB, Shared Schema | Row-level isolation via `tenant_id` — lowest cost, requires strict global scope |
| Shared DB, Separate Schema | Per-tenant schema — better isolation, complex migrations |
| Separate DB per Tenant | Maximum isolation — highest operational overhead |

**Default:** Shared DB + strict row-level isolation via `tenant_id`. Optional DB-per-tenant upgrade path supported.

---

## Authorization Model

- Hybrid RBAC (roles & permissions) + ABAC (policy-based attributes)
- Policy classes only — no permission logic in controllers; no hardcoded role checks
- Multi-guard authentication; scoped API keys; tenant-level feature flags; feature-level gating
- Dynamic middleware for permission enforcement
- Tenant-scoped permissions enforced at the repository layer

---

## Financial Precision (Non-Negotiable)

- **All** financial and quantity calculations must use **BCMath** (PHP's arbitrary-precision math library).
- **Floating-point arithmetic is strictly forbidden** in any financial or quantity context.
- Precision rules:
  - Standard calculations: **minimum 4 decimal places**
  - Intermediate calculations (that will be further divided or multiplied before final rounding): **8+ decimal places**
  - Final monetary values: **rounded to currency standard precision (typically 2 decimal places)**
- All calculations must be deterministic and reversible.
- Double-entry bookkeeping: every transaction must debit one account and credit another; Total Debits = Total Credits at all times.

---

## Metadata-Driven Core

All configurable logic must be database-driven, enum-controlled, runtime-resolvable, and replaceable without redeployment. This includes:

- Dynamic forms & custom fields
- Validation rules & conditional visibility
- Computed fields
- Workflow states & approval chains
- Pricing rules & tax rules
- Notification templates
- UI layout definitions
- Feature toggles

**Hardcoded business rules are prohibited.**

---

## Module Load Order & Dependencies

Modules are loaded in strict priority order. A module's `priority` value must be strictly greater than the `priority` values of all its declared dependencies. The table below reflects the authoritative values in each module's `module.json`.

| Priority | Module | Declared Dependencies (`requires`) |
|---|---|---|
| 1 | Core | *(none)* |
| 2 | Tenancy | core |
| 3 | Auth | core, tenancy |
| 4 | Organisation | core, tenancy |
| 5 | Metadata | core, tenancy |
| 6 | Workflow | core, tenancy, metadata |
| 7 | Product | core, tenancy, metadata |
| 8 | Accounting | core, tenancy |
| 9 | Pricing | core, tenancy, product |
| 10 | Inventory | core, tenancy, product |
| 11 | Warehouse | core, tenancy, inventory |
| 12 | Sales | core, tenancy, product, inventory, pricing, accounting |
| 13 | POS | core, tenancy, sales, pricing, accounting |
| 14 | CRM | core, tenancy, workflow |
| 15 | Procurement | core, tenancy, product, inventory, accounting, workflow |
| 16 | Reporting | core, tenancy |
| 17 | Notification | core, tenancy |
| 18 | Integration | core, tenancy |
| 19 | Plugin | core, tenancy |

**No circular dependencies are permitted.** If a dependency would create a cycle, it must be resolved via domain events or shared contracts in the Core module.

---

## Cross-Module Communication Rules

| Permitted | Prohibited |
|---|---|
| ✅ Domain events (event bus) | ❌ Direct class instantiation from another module |
| ✅ Published contracts (interfaces in `Domain/`) | ❌ Direct database queries into another module's tables |

Tight coupling between modules is a **Critical Violation** and must be refactored immediately.

---

## Domain Rules by Area

### Product Domain
- Product types: Physical (Stockable), Consumable, Service, Digital, Bundle (Kit), Composite (Manufactured), Variant-based
- Base UOM (`uom`) is **required** on every product
- `buying_uom` — optional; falls back to base UOM if absent
- `selling_uom` — optional; falls back to base UOM if absent
- UOM conversions live in the `uom_conversions` table: `product_id`, `from_uom`, `to_uom`, `factor`
- No global UOM assumptions; no implicit conversions; product-specific factors only
- Direct and inverse reciprocal conversion paths both supported
- All calculations use BCMath with a minimum of 4 decimal places; use higher precision (8+ decimal places) for intermediate calculations that will be further divided or multiplied before final rounding. Final monetary values must be rounded to the currency's standard precision (typically 2 decimal places). Deterministic and reversible.
- Optional traceability (Serial / Batch / Lot) — mandatory in pharmaceutical compliance mode
- Optional Barcode / QR / GS1 compatibility; 0..n images per product

### Pricing & Discounts
- Prices and discounts may vary by: location, batch, lot, date range, customer tier, minimum quantity
- Discount formats: flat (fixed) amount or percentage — no other formats
- All price/discount arithmetic must use BCMath (no floating-point)

### Inventory Management (IMS)
- Inventory is ledger-driven, transactional, and immutable (no direct edits to historical entries)
- Stock flow: reservation → deduction (never skip reservation)
- Deduction must be atomic (pessimistic locking inside a DB transaction)
- Costing methods: FIFO / LIFO / Weighted Average
- Critical flows: Purchase Receipt, Sales Shipment, Internal Transfer, Adjustment, Return
- Full capabilities: multi-warehouse, multi-bin locations, stock ledger, reservations, transfers, adjustments, cycle counting, expiry tracking, damage handling, reorder rules, procurement suggestions, backorders, drop-shipping
- Concurrency: pessimistic locking for stock deduction; optimistic locking for updates; idempotent stock APIs

### Warehouse Management (WMS)
- Bin-level and location-level real-time tracking
- Automated receiving and intelligent putaway suggestions
- Picking strategies: batch, wave, zone; optimized route generation
- Packing validation; reverse logistics (returns, restocking, damage classification)
- Labor performance tracking; warehouse layout optimization
- Integration with ERP, transportation, and e-commerce platforms

### Sales Flow
```
Quotation → Sales Order → Delivery → Invoice → Payment
```
Capabilities: POS terminal mode, offline-first design with local transaction queue and sync reconciliation, draft/hold receipts, split payments, refund handling, backorders, cash drawer tracking, receipt templating, commission engine, rule-based discount engine, tax calculation, loyalty system, gift cards, coupons, e-commerce API compatibility.

### CRM Pipeline
```
Lead → Opportunity → Proposal → Closed Won / Closed Lost
```
Capabilities: leads, opportunities, pipeline stages, activities, campaign tracking & attribution, email integration, SLA tracking & timers, notes & attachments, customer segmentation.

### Procurement Flow
```
Purchase Request → RFQ → Vendor Selection → Purchase Order → Goods Receipt → Vendor Bill → Payment
```
Capabilities: purchase requests, RFQ, vendor comparison, purchase orders, goods receipt, three-way matching (PO / Receipt / Invoice), vendor bills, vendor scoring, price comparison.

### Workflow Engine
```
State → Event → Transition → Guard → Action
```
No hardcoded approval logic. All state machine definitions must be database-driven. Supports: state machine flows, approval chains, escalation rules, SLA enforcement, event-based triggers, background jobs, scheduled tasks.

### Accounting
- Double-entry bookkeeping (mandatory)
- Chart of accounts per tenant
- Immutable journal entries; auto-posting rules; tax engine; fiscal periods
- Trial balance, P&L, balance sheet
- Accounting must reconcile with inventory valuation at all times
- Financial integrity cannot be bypassed

---

## Pharmaceutical Compliance Mode

When pharmaceutical compliance mode is enabled for a tenant, the following rules become mandatory (no override possible):

- Lot tracking and expiry date are **mandatory**
- FEFO (First-Expired, First-Out) is **enforced** (replaces standard FIFO/LIFO)
- Serial tracking required where applicable
- Audit trail **cannot be disabled**
- Regulatory reports must be available (FDA / DEA / DSCSA aligned)
- Quarantine workflows for expired or recalled items are enforced
- Expiry override logging and high-risk medication access logging are required

**Compliance is NOT optional.** See KB.md §11.8 for the full specification.

---

## Customization & Rule Engine

Declarative rule pattern: `IF condition THEN action`

Used for: pricing, discounts, taxes, commissions, inventory reservation logic. All configurable without redeployment.

---

## Frontend Architecture

- Micro-frontend ready; module federation compatible
- Feature-based component architecture
- No business logic duplication from backend; strict API contract adherence
- Dashboards may use Tailwind CSS-based admin templates (e.g., TailAdmin) or Bootstrap-based templates (e.g., AdminLTE)

---

## API Design Standard

- RESTful, versioned at `/api/v1`
- Every public endpoint must have a full OpenAPI spec (request schema + response schema + error format)
- Standard response envelope required on all responses
- Pagination required on all list endpoints
- Idempotent endpoints — no hidden state changes on re-submission
- Integration capabilities: webhooks, event publishing, third-party connectors, e-commerce sync, payment gateways

---

## Security Baseline

**General (mandatory for all modules):**
CSRF protection, XSS prevention, SQL injection prevention, rate limiting, token rotation, Argon2/bcrypt password hashing, strict file upload validation, signed URLs, audit logging, suspicious activity detection, RBAC enforcement, ABAC policy enforcement, tenant isolation enforcement.

**Pharmaceutical-specific (mandatory when pharma compliance mode is enabled):**
Full audit trail of stock mutations, user action logging, tamper-resistant records, expiry override logging, high-risk medication access logging.

---

## Data Integrity & Concurrency

Mandatory controls: DB transactions, foreign keys, unique constraints, optimistic locking, pessimistic locking, idempotency keys, version tracking, immutable logs.

- All stock mutations must execute inside database transactions with guaranteed atomicity.
- Deadlock-aware retry mechanisms required.
- All writes must be safe under parallel load; stock and accounting must **never** be inconsistent.

---

## Performance & Scalability

ERP bottlenecks occur in: inventory deduction, accounting posting, reporting aggregation, and workflow engines.

Mitigation strategies:
- Event-driven design; queue processing
- Read replicas; caching abstraction
- Partitioned reporting tables; background reconciliation jobs

The application layer must remain stateless to support horizontal scaling.

---

## Audit & Compliance

Mandatory: immutable logs, versioned records, traceable financial flows, historical state reconstruction, regulatory export capability. Audit trail is **non-optional**.

---

## Enterprise Reporting

Reports must support: aggregated financial statements, inventory valuation reports, aging reports, tax summaries, custom report builder, export formats (CSV, PDF). Reports must be tenant-scoped, filterable, exportable, auditable, and must **never break transactional integrity**.

---

## Plugin Marketplace

A marketplace-ready ERP requires:
- Module manifest definition (`module.json`)
- Dependency graph validation
- Version compatibility rules
- Sandboxed execution
- Tenant-scoped enablement
- Upgrade migration paths

---

## Reusability Principles

Reusable ERP modules must:
- Avoid business-specific assumptions
- Be configuration-driven; avoid hardcoded logic
- Expose contracts, not implementations
- Remain independently replaceable
- Support multi-industry, multi-country tax extension, and marketplace plugin ecosystem

---

## Coding Conventions

- Follow SOLID principles strictly.
- Meaningful, descriptive names — no abbreviations.
- No hardcoded IDs or business rules; all configurable logic must be database-driven.
- No partial implementations; no TODOs without a tracking issue.
- Cross-module tight coupling is **prohibited**.

---

## Testing Requirements

Every module must include:

| Test Type | Purpose |
|---|---|
| Unit tests | Validate individual domain rules, services, handlers |
| Feature tests | End-to-end API flow validation |
| Authorization tests | Verify RBAC + ABAC enforcement |
| Tenant isolation tests | Confirm no cross-tenant data leakage |
| Concurrency tests | Validate pessimistic/optimistic locking under parallel load |
| Financial precision tests | Assert BCMath arithmetic is correct and deterministic |

No module is complete without all six test types.

---

## Prohibited Practices

The following are **immediately refactorable** violations:

| Violation | Category |
|---|---|
| Business logic in a controller | Architecture |
| Query builder calls in a controller | Architecture |
| Cross-module tight coupling or direct DB access between modules | Architecture |
| Hardcoded IDs, tenant conditions, or business rules | Architecture |
| Floating-point arithmetic in financial or quantity calculations | Financial Precision |
| Partial implementations or TODOs without a tracking issue | Completeness |
| Silent exception swallowing | Reliability |
| Implicit UOM conversion (no explicit factor) | Domain Correctness |
| Duplicate stock deduction logic | Domain Correctness |
| Skipping DB transactions for inventory mutations | Data Integrity |
| Cross-tenant data access | Tenant Isolation |

---

## Autonomous Agent Execution Rules

Any AI agent working on this repository must:

**Before making any changes:**

1. **Read the entire affected module's README.md** to understand its current state and architecture compliance table.
2. **Read AGENT.md** to confirm applicable governance rules.
3. **Check IMPLEMENTATION_STATUS.md** to understand the current implementation state.
4. **Refactor any violations** before adding new features.

**When making changes:**

5. **Preserve tenant isolation** at every layer — no exceptions.
6. **Maintain architecture boundaries** — Controller → Service → Handler → Repository → Entity.
7. **Use BCMath** for all financial and quantity calculations.
8. **Never use floating-point arithmetic** in financial or quantity context.
9. **Wrap all stock mutations** in database transactions with pessimistic locking.
10. **Cross-module communication** must be via contracts/events only — no direct module-to-module calls.

**After making changes:**

11. **Update the module's README.md** — including the Architecture Compliance table.
12. **Update OpenAPI/Swagger docs** for any new or changed endpoints.
13. **Update IMPLEMENTATION_STATUS.md** — add a new row to the Change History table and update the module status table.
14. **Ensure all regression tests pass** before completing the task.

---

## Definition of Done

A module is **complete** only when **all** of the following are true:

- Clean Architecture respected; no duplication
- Tenant isolation enforced; authorization enforced
- Concurrency handled correctly (pessimistic + optimistic locking as appropriate)
- Financial precision validated (BCMath, deterministic, no float)
- Domain events emitted correctly
- API documented (full OpenAPI spec with request/response schemas)
- All six test types pass
- Module README updated
- `IMPLEMENTATION_STATUS.md` updated
- No technical debt introduced

---

## PR Checklist

Before merging any pull request, verify:

- [ ] No business logic in any controller
- [ ] No query builder calls in any controller
- [ ] All new tables include `tenant_id` with global scope applied
- [ ] All new endpoints covered by authorization tests
- [ ] All financial and quantity calculations use BCMath (no float), minimum 4 decimal places
- [ ] Module README updated (with Architecture Compliance table)
- [ ] OpenAPI docs updated
- [ ] `IMPLEMENTATION_STATUS.md` updated
- [ ] No cross-module direct dependency introduced
- [ ] Pharmaceutical compliance mode respected (if applicable)

---

## Build, Test & Development Commands

> **Note:** The codebase is in the planning/documentation phase. No backend or frontend has been scaffolded yet. When scaffolding begins, use these commands:

**Backend (Laravel):**
```bash
# Scaffold (run once)
composer create-project laravel/laravel backend

# Install modular structure
composer require nwidart/laravel-modules

# Run tests
php artisan test

# Run specific test
php artisan test --filter=InventoryTest

# Static analysis (if configured)
./vendor/bin/phpstan analyse

# Code style fix
./vendor/bin/pint
```

**Frontend (React):**
```bash
# Scaffold (run once, use Vite)
npm create vite@latest frontend -- --template react-ts

# Install dependencies
npm install

# Run development server
npm run dev

# Run tests
npm test

# Build for production
npm run build
```

---

## Key Domain Concepts Quick Reference

| Concept | Definition |
|---|---|
| Tenant | Top-level isolation unit; owns all data beneath it |
| Organisation | Operational entity within a tenant |
| Branch | Geographic or operational subdivision of an organisation |
| Location | Physical space within a branch (e.g., warehouse floor, store) |
| Department | Functional unit within a location |
| UOM | Unit of Measure — the base unit for all inventory tracking |
| FEFO | First-Expired, First-Out — mandatory costing strategy in pharmaceutical mode |
| FIFO | First-In, First-Out — default costing strategy for standard inventory |
| LIFO | Last-In, First-Out — optional costing strategy |
| Weighted Average | Average cost costing strategy — optional |
| Ledger-driven | Stock changes only occur via immutable transaction entries, never direct edits |
| Three-way Match | Verification: Purchase Order + Goods Receipt + Vendor Invoice must agree |
| Double-Entry | Every financial transaction has equal debits and credits |
| BCMath | PHP library for arbitrary-precision arithmetic — required for all financial math |
| Global Scope | Laravel Eloquent feature that automatically filters all queries by `tenant_id` |
| module.json | Module manifest file containing name, version, dependencies, and load priority |

---

## Core System Guarantees (Non-Negotiable)

The system must **always** guarantee:

1. **Tenant isolation** — no data leakage between tenants under any circumstances
2. **Financial correctness** — BCMath only; debits = credits; deterministic rounding
3. **Transactional consistency** — all mutations wrapped in DB transactions
4. **Replaceable modules** — each module independently swappable without breaking others
5. **Deterministic calculations** — same input always produces the same output
6. **Audit safety** — immutable logs, versioned records, full traceability
7. **Horizontal scalability** — stateless application layer; no server-local state
8. **Extensibility without refactor debt** — metadata-driven; no hardcoded logic

**Zero regression tolerance. Zero architectural violations. Zero technical debt acceptance.**

---

## Strategic Goal

Build not just an ERP system, but:

- A **configurable ERP framework** — usable across industries
- A **reusable SaaS engine** — multi-tenant, multi-country, multi-currency
- A **domain-driven enterprise core** — clean, bounded, replaceable modules
- A **plugin-ready platform** — marketplace-grade extensibility
- A **long-term maintainable system** — zero architectural debt tolerance

---

## Legacy Documents (Do Not Use as Authoritative Sources)

The following files contain earlier versions of the governance and knowledge base. They are kept for historical reference only. **Always use the current authoritative sources (AGENT.md v4.0 and KB.md v2.0) instead.**

| File | Superseded By |
|---|---|
| `AGENT.old.md` | `AGENT.md` (v4.0) |
| `AGENT.old_01.md` | `AGENT.md` (v4.0) |
| `KNOWLEDGE_BASE.md` | `KB.md` (v2.0) |
| `KNOWLEDGE_BASE_01.md` | `KB.md` (v2.0) |
| `KNOWLEDGE_BASE_02.md` | `KB.md` (v2.0) |

---

## Key References

- Full governance contract: [`AGENT.md`](../AGENT.md)
- Domain knowledge base: [`KB.md`](../KB.md)
- Laravel docs: https://laravel.com/docs/12.x
- React docs: https://react.dev
- OpenAPI/Swagger: https://swagger.io
- Laravel authorization: https://laravel.com/docs/12.x/authorization
- Laravel authentication: https://laravel.com/docs/12.x/authentication

---

## REFERENCES

These references provide guidance on modular design, Laravel best practices, multi-tenancy, ERP/CRM design, and other principles relevant to this repository:

- https://blog.cleancoder.com/atom.xml
- https://en.wikipedia.org/wiki/Modular_design
- https://en.wikipedia.org/wiki/Plug-in_(computing)
- https://laravel.com/blog/building-a-multi-tenant-architecture-platform-to-scale-the-emmys
- https://en.wikipedia.org/wiki/Enterprise_resource_planning
- https://dev.to/rafaelogic/building-a-polymorphic-translatable-model-in-laravel-with-autoloaded-translations-3d99
- https://sevalla.com/blog/building-modular-systems-laravel
- https://github.com/laravel/laravel
- https://laravel.com/docs/12.x/packages
- https://swagger.io
- https://en.wikipedia.org/wiki/SOLID
- https://laravel.com/docs/12.x/filesystem
- https://laravel-news.com/uploading-files-laravel
- https://adminlte.io
- https://tailwindcss.com
- https://dev.to/bhaidar/understanding-database-locking-and-concurrency-in-laravel-a-deep-dive-2k4m
- https://laravel-news.com/managing-data-races-with-pessimistic-locking-in-laravel
- https://dev.to/tegos/pessimistic-optimistic-locking-in-laravel-23dk
- https://dev.to/takeshiyu/handling-decimal-calculations-in-php-84-with-the-new-bcmath-object-api-442j
- https://dev.to/keljtanoski/modular-laravel-3dkf
- https://laravel.com/docs/12.x/processes
- https://laravel.com/docs/12.x/helpers#pipeline
- https://dev.to/preciousaang/multi-guard-authentication-with-laravel-12-1jg3
- https://laravel.com/docs/12.x/authentication
- https://laravel.com/docs/12.x/localization
- https://www.laravelpackage.com
- https://laravel-news.com/building-your-own-laravel-packages
- https://freek.dev/1567-pragmatically-testing-multi-guard-authentication-in-laravel
- https://laravel-news.com/laravel-gates-policies-guards-explained
- https://dev.to/codeanddeploy/how-to-create-a-custom-dynamic-middleware-for-spatie-laravel-permission-2a08
- https://laravel.com/docs/12.x/authorization
- https://en.wikipedia.org/wiki/Inventory
- https://en.wikipedia.org/wiki/Inventory_management_software
- https://en.wikipedia.org/wiki/Inventory_management
- https://en.wikipedia.org/wiki/Inventory_management_(business)
- https://en.wikipedia.org/wiki/Inventory_theory
- https://en.wikipedia.org/wiki/SAP_ERP
- https://en.wikipedia.org/wiki/SAP
- https://en.wikipedia.org/wiki/E-commerce
- https://en.wikipedia.org/wiki/Types_of_e-commerce
- https://en.wikipedia.org/wiki/Headless_commerce
- https://en.wikipedia.org/wiki/Barcode
- https://en.wikipedia.org/wiki/QR_code
- https://en.wikipedia.org/wiki/GS1
- https://en.wikipedia.org/wiki/Warehouse_management_system
- https://en.wikipedia.org/wiki/Domain_inventory_pattern
- https://en.wikipedia.org/wiki/Point_of_sale
- https://www.oracle.com/apac/food-beverage/what-is-pos
- https://en.wikipedia.org/wiki/Gap_analysis
- https://en.wikipedia.org/wiki/Business_process_modeling
- https://en.wikipedia.org/wiki/Business_process
- https://en.wikipedia.org/wiki/Business_Process_Model_and_Notation
- https://en.wikipedia.org/wiki/Process_design
- https://en.wikipedia.org/wiki/Business_process_mapping
- https://en.wikipedia.org/wiki/Workflow_pattern
- https://en.wikipedia.org/wiki/Workflow
- https://en.wikipedia.org/wiki/Workflow_application
- https://www.researchgate.net/publication/279515140_Enterprise_Resource_Planning_ERP_Systems_Design_Trends_and_Deployment
- https://medium.com/@bugfreeai/key-system-design-component-design-an-inventory-system-2e2befe45844
- https://www.cockroachlabs.com/blog/inventory-management-reference-architecture
- https://www.0xkishan.com/blogs/designing-inventory-mgmt-system
- https://quickbooks.intuit.com/r/bookkeeping/complete-guide-to-double-entry-bookkeeping
- https://www.extension.iastate.edu/agdm/wholefarm/pdf/c6-33.pdf
- https://en.wikipedia.org/wiki/Double-entry_bookkeeping
- https://en.wikipedia.org/wiki/Bookkeeping
- https://github.com/DarkaOnLine/L5-Swagger
- https://medium.com/@nelsonisioma1/how-to-document-your-laravel-api-with-swagger-and-php-attributes-1564fc11c305
- https://medium.com/@harryespant/advanced-microservices-architecture-in-laravel-high-level-design-dependency-injection-repository-0e787a944e7f
- https://dev.to/programmerhasan/creating-a-microservice-architecture-with-laravel-apis-3a16
- https://laravel.com/ai/mcp
- https://laravel.com/docs/12.x/mcp
- https://github.com/L5Modular/L5Modular
- https://github.com/nWidart/laravel-modules
- https://github.com/keljtanoski/modular-laravel
- https://laravel.com/docs/12.x
- https://woocommerce.com
- https://developer.wordpress.org/plugins/intro
- https://en.wikipedia.org/wiki/WooCommerce
- https://github.com/woocommerce/woocommerce
- https://woocommerce.github.io/woocommerce-rest-api-docs/#introduction
- https://developer.wordpress.org
- https://github.com/Astrotomic/laravel-translatable
- https://github.com/spatie/laravel-translatable
- https://oneuptime.com/blog/post/2026-02-03-laravel-multi-language/view
- https://github.com/spatie/laravel-translatable/blob/main/docs/introduction.md
- https://dev.to/abstractmusa/modular-monolith-architecture-within-laravel-communication-between-different-modules-a5
- https://oneuptime.com/blog/post/2026-02-02-laravel-database-transactions/view
- https://laravel-news.com/database-transactions
- https://github.com/spatie/laravel-multitenancy
- https://github.com/archtechx/tenancy
- https://sevalla.com/blog/mcp-server-laravel
- https://github.com/laravel/mcp
- https://tailadmin.com
- https://github.com/TailAdmin/tailadmin-free-tailwind-dashboard-template
- https://github.com/ColorlibHQ/AdminLTE
- https://adminlte.io/blog/free-react-templates
- https://madewithreact.com/reactjs-adminlte
- https://github.com/TailAdmin/free-react-tailwind-admin-dashboard?tab=readme-ov-file 
- https://oneuptime.com/blog/post/2026-02-03-laravel-repository-pattern/view 
- https://micro-frontends.org 
- https://en.wikipedia.org/wiki/Micro_frontend 
- https://single-spa.js.org/docs/microfrontends-concept 
- https://nx.dev/docs/technologies/module-federation/concepts/micro-frontend-architecture
- https://semaphore.io/blog/microfrontends
- https://www.geeksforgeeks.org/blogs/what-are-micro-frontends-definition-uses-architecture
- https://microfrontend.dev 
- https://bit.dev/docs/micro-frontends/react-micro-frontends 
- https://medium.com/@nexckycort/from-monolith-to-microfrontends-migrating-a-legacy-react-app-to-a-modern-architecture-bd686aee0ce8
- https://blog.nashtechglobal.com/the-power-of-react-micro-frontend 
- https://github.com/miteshtagadiya/microfrontend-react 
- https://medium.com/@ignatovich.dm/micro-frontend-architectures-in-react-benefits-and-implementation-strategies-5a8bd5f66769 
- https://github.com/nrwl/nx 
- https://github.com/neuland/micro-frontends 
- https://laraveldaily.com/post/traits-laravel-eloquent-examples 
- https://dcblog.dev/enhancing-laravel-applications-with-traits-a-step-by-step-guide 
- https://laravel.com/docs/12.x/migrations 
- https://laravel.com/docs/12.x/eloquent-relationships 
- https://laravel-news.com/effective-eloquent 
- https://dev.to/rocksheep/cleaner-models-with-laravel-eloquent-builders-12h4 
- https://dev.to/abrardev99/pipeline-pattern-in-laravel-278p 
- https://jordandalton.com/articles/laravel-pipelines-transforming-your-code-into-a-flow-of-efficiency 
- https://medium.com/@harrisrafto/streamlining-data-processing-with-laravels-pipeline-pattern-8f939ee68435 
- https://marcelwagner.dev/blog/posts/what-are-laravel-pipelines 
- https://jahidhassan.hashnode.dev/how-i-simplified-my-laravel-filters-using-the-pipeline-pattern-with-real-examples 
- https://laracasts.com/discuss/channels/eloquent/create-a-custom-relationship-method 
- https://stackoverflow.com/questions/39213022/custom-laravel-relations 
- https://api.laravel.com/docs/12.x/index.html 
- https://medium.com/coding-skills/clean-code-101-meaningful-names-and-functions-bf450456d90c 
- https://devopedia.org/naming-conventions 
- https://www.oracle.com/java/technologies/javase/codeconventions-namingconventions.html 
- https://www.freecodecamp.org/news/how-to-write-better-variable-names 
- https://en.wikipedia.org/wiki/Naming_convention_(programming)
- https://www.netsuite.com/portal/resource/articles/inventory-management/what-are-inventory-management-controls.shtml 
- https://www.finaleinventory.com/inventory-management/retail-inventory-management-15-best-practices-for-2024-ecommerce 
- https://modula.us/blog/warehouse-inventory-management 
- https://sell.amazon.com/learn/inventory-management 
- https://www.sap.com/resources/inventory-management 
- https://www.camcode.com/blog/warehouse-operations-best-practices 
- https://www.ascm.org/ascm-insights/8-kpis-for-an-efficient-warehouse 
- https://ascsoftware.com/blog/good-warehousing-practices-in-the-pharmaceutical-industry 
- https://www.buchananlogistics.com/resources/company-news-and-blogs/blogs/utilizing-the-7-cs-systems-of-logistics-and-supply-chain-management 
- https://axacute.com/blog/5-key-warehouse-processes 
- https://www.conger.com/warehouse-5s 
- https://navata.com/cms/1pl-2pl-3pl-4pl-5pl 
- https://www.researchgate.net/publication/329038196_Design_of_Automated_Warehouse_Management_System 
- https://www.researchgate.net/publication/377780666_DESIGNING_AN_INVENTORY_MANAGEMENT_SYSTEM_USING_DATA_MINING_TECHNIQUES 