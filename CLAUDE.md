# CLAUDE.md

**Claude AI Agent Guide — KV Enterprise Dynamic SaaS CRM ERP**
Version: 2.0
Last Updated: 2026-02-27
Status: Authoritative — Binding on All Claude Instances

---

## What This Repository Is

A **production-grade, enterprise, multi-tenant, modular ERP/CRM SaaS platform** built with:

- **Backend:** Laravel (LTS only — currently Laravel 12.x)
- **Frontend:** React (LTS only)
- **Architecture:** Modular Monolith (plugin-ready)
- **Database:** MySQL / PostgreSQL — shared DB, row-level tenant isolation
- **Auth:** JWT, stateless, multi-guard
- **Authorization:** RBAC + ABAC (Policy classes only)
- **Financial Precision:** BCMath — arbitrary precision, no floating-point arithmetic
- **API:** RESTful, versioned at `/api/v1`, OpenAPI/Swagger documented

> **Current State (as of 2026-02-27):** All 19 modules are **🔴 Planned** — no backend or frontend code has been scaffolded yet. All existing work is documentation and governance only.

---

## Governing Documents (Read First)

| Document | Purpose | Authority |
|---|---|---|
| [`AGENT.md`](AGENT.md) | Full governance contract — binding rules for all contributors and AI agents | **Primary Authority** |
| [`KB.md`](KB.md) | Complete domain knowledge base — 38 sections covering all ERP/CRM domains | **Authoritative Reference** |
| [`IMPLEMENTATION_STATUS.md`](IMPLEMENTATION_STATUS.md) | Module-by-module implementation progress tracker | **Must be updated after every change** |
| [`README.md`](README.md) | Repository overview and module table | Context |
| [`.github/copilot-instructions.md`](.github/copilot-instructions.md) | GitHub Copilot instructions (mirrors AGENT.md + KB.md) | Context |

**Always read AGENT.md and KB.md before making any changes.** These are binding contracts.

---

## Repository Structure

```
/
├── AGENT.md                        # Governance contract (primary authority)
├── KB.md                           # Domain knowledge base (38 sections)
├── IMPLEMENTATION_STATUS.md        # Module progress tracker
├── README.md                       # Repository overview
├── CLAUDE.md                       # This file — Claude AI agent guide
├── AGENT.old.md                    # Legacy (superseded by AGENT.md v4.0)
├── AGENT.old_01.md                 # Legacy (superseded by AGENT.md v4.0)
├── KNOWLEDGE_BASE.md               # Legacy KB (superseded by KB.md v2.0)
├── KNOWLEDGE_BASE_01.md            # Legacy KB supplement (superseded by KB.md v2.0)
├── KNOWLEDGE_BASE_02.md            # Legacy KB supplement (superseded by KB.md v2.0)
├── .github/
│   └── copilot-instructions.md     # Copilot-specific instructions
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

## Mandatory Application Flow

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

---

## Multi-Tenancy (Critical — Zero Tolerance)

**Hierarchy:**

```
Tenant
 └── Organisation
      └── Branch
           └── Location
                └── Department
```

**Mandatory enforcement rules:**

- Every business table must have `tenant_id` with a **global scope enforced at the Eloquent model level**.
- Tenant-scoped: cache, queues, storage, configs.
- JWT per user × device × organisation; stateless authentication.
- Tenant resolution via: subdomain, header, or JWT claim.
- Unauthorized cross-tenant data access is a **Critical Violation** — zero tolerance.
- Tenant isolation must be enforced at the **repository layer**, not the controller layer.

**Multi-tenant database models:**

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

## Metadata-Driven Architecture

**All** configurable logic must be:
- Database-driven (not hardcoded)
- Enum-controlled
- Runtime-resolvable
- Replaceable without redeployment

This applies to: dynamic forms, custom fields, validation rules, conditional field visibility, computed fields, workflow states, approval chains, pricing rules, tax rules, notification templates, UI layout definitions, and feature toggles.

**Hardcoded business rules are a prohibited practice and must be refactored immediately.**

---

## Frontend Architecture

- Micro-frontend ready; module federation compatible
- Feature-based component architecture
- No business logic duplication from backend; strict API contract adherence
- Dashboards may use Tailwind CSS-based admin templates (e.g., TailAdmin) or Bootstrap-based templates (e.g., AdminLTE)

---

## Performance & Scalability

ERP bottlenecks typically occur in: inventory deduction, accounting posting, reporting aggregation, and workflow engines.

Mitigation strategies:
- Event-driven design; queue processing
- Read replicas; caching abstraction
- Partitioned reporting tables; background reconciliation jobs

The application layer must remain stateless to support horizontal scaling.

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

## What Claude Must Do (Autonomous Agent Execution Rules)

Before making any changes:

1. **Read the entire affected module's README.md** to understand its current state and architecture compliance table.
2. **Read AGENT.md** to confirm applicable governance rules.
3. **Check IMPLEMENTATION_STATUS.md** to understand the current implementation state.
4. **Refactor any violations** before adding new features.

When making changes:

5. **Preserve tenant isolation** at every layer — no exceptions.
6. **Maintain architecture boundaries** — Controller → Service → Handler → Repository → Entity.
7. **Use BCMath** for all financial and quantity calculations.
8. **Never use floating-point arithmetic** in financial or quantity context.
9. **Wrap all stock mutations** in database transactions with pessimistic locking.
10. **Cross-module communication** must be via contracts/events only — no direct module-to-module calls.

After making changes:

11. **Update the module's README.md** — including the Architecture Compliance table.
12. **Update OpenAPI/Swagger docs** for any new or changed endpoints.
13. **Update IMPLEMENTATION_STATUS.md** — add a new row to the Change History table and update the module status table.
14. **Ensure all regression tests pass** before completing the task.

---

## PR / Task Completion Checklist

Before finalizing any change, verify every item:

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

The following are **immediately refactorable** violations. If Claude detects any of these, the violation must be fixed before adding new features:

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

## Security Baseline

**General (mandatory for all modules):**
CSRF protection, XSS prevention, SQL injection prevention, rate limiting, token rotation, Argon2/bcrypt password hashing, strict file upload validation, signed URLs, audit logging, suspicious activity detection, RBAC enforcement, ABAC policy enforcement, tenant isolation enforcement.

**Pharmaceutical-specific (mandatory when pharma compliance mode is enabled):**
Full audit trail of stock mutations, user action logging, tamper-resistant records, expiry override logging, high-risk medication access logging.

---

## API Design Standard

- RESTful, versioned at `/api/v1`
- Every public endpoint must have a full OpenAPI spec (request schema + response schema + error format)
- Standard response envelope required on all responses
- Pagination required on all list endpoints
- Idempotent endpoints — no hidden state changes on re-submission
- Integration capabilities: webhooks, event publishing, third-party connectors, e-commerce sync, payment gateways

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

*This document is the authoritative guide for all Claude AI instances working on this repository. It is binding and must be followed without exception. When in doubt, defer to `AGENT.md` (v4.0) as the primary authority.*
