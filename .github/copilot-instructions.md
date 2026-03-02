# Copilot Instructions

## Project Overview

This repository is a **production-grade, enterprise, multi-tenant, modular ERP/CRM SaaS platform** built with:

- **Backend:** Laravel (LTS only)
- **Frontend:** React (LTS only)
- Native framework capabilities preferred; no unstable or experimental dependencies

The platform covers: Inventory Management, Pharmaceutical Inventory, Warehouse Management (WMS), Sales & POS, Accounting & Finance, CRM, Procurement, Workflow Engine, and third-party ERP/CRM integration via open APIs.

The full governance contract is defined in [`AGENT.md`](../AGENT.md). The domain knowledge base is in [`KB.md`](../KB.md). All contributors and AI agents are bound by both.

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

## Core Architecture

### Governing Principles

- SOLID, DRY, KISS
- Explicit domain boundaries; clear separation of concerns
- Deterministic behavior; immutable financial calculations

### Mandatory Application Flow

Every feature must follow this pipeline:

```
Controller → Service → Handler (Pipeline) → Repository → Entity
```

| Layer | Responsibilities |
|---|---|
| **Controller** | Input validation, authorization, response formatting — **no business logic** |
| **Service** | Orchestrates use cases, defines transaction boundaries, calls pipelines |
| **Handler (Pipeline)** | Single-responsibility processing steps, transformations, domain rules, reusable logic units |
| **Repository** | Data access only — no domain logic, always tenant-aware |
| **Entity** | Pure domain model — relationships, attribute casting, no orchestration |

### Module Structure (Per Module)

```
Modules/
 └── {ModuleName}/
     ├── Application/       # Use cases, commands, queries, DTOs, service orchestration
     ├── Domain/            # Entities, value objects, domain events, repository contracts, business rules
     ├── Infrastructure/    # Repository implementations, external service adapters, persistence
     ├── Interfaces/        # HTTP controllers, API resources, form requests, console commands
     ├── module.json
     └── README.md
```

- Each module must be independently replaceable.
- Controllers contain **no business logic** and **no query builder calls**.
- Cross-module communication via contracts and events only — no direct coupling.
- Domain logic must be isolated from infrastructure.
- No circular dependencies.

---

## Multi-Tenancy

### Hierarchy Model

```
Tenant
 └── Organisation
      └── Branch
           └── Location
                └── Department
```

### Mandatory Enforcement

- `tenant_id` on every business table with a global scope enforced.
- Tenant-scoped: cache, queues, storage, configs.
- JWT per user × device × organisation; stateless authentication.
- Tenant resolution via subdomain, header, or JWT claim.
- Unauthorized cross-tenant access is **strictly prohibited**.
- Failure to isolate tenants is a **Critical Violation**.

### Multi-Tenant Database Models

| Model | Description |
|---|---|
| Shared DB, Shared Schema | Row-level isolation via `tenant_id` — lowest cost, requires strict global scope |
| Shared DB, Separate Schema | Per-tenant schema — better isolation, complex migrations |
| Separate DB per Tenant | Maximum isolation — highest operational overhead |

Default: Shared DB + strict row-level isolation, with optional DB-per-tenant upgrade path.

---

## Authorization Model

- Hybrid RBAC (roles & permissions) + ABAC (policy-based attributes).
- Policy classes only — no permission logic in controllers; no hardcoded role checks.
- Multi-guard authentication; scoped API keys; tenant-level feature flags; feature-level gating.
- Dynamic middleware for permission enforcement.
- Tenant-scoped permissions enforced at the repository layer.

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

## Product Domain

### Supported Product Types

Physical (Stockable), Consumable, Service, Digital, Bundle (Kit), Composite (Manufactured), Variant-based.

### Key Concepts

- SKU; UOM (Unit of Measure) with conversion matrix
- Costing method: FIFO / LIFO / Weighted Average
- Optional traceability (Serial / Batch / Lot) — mandatory in pharmaceutical mode
- Optional Barcode / QR / GS1 compatibility
- 0..n images per product
- Multi-location and multi-currency pricing; tiered pricing; rule-based pricing engine

### Financial Rules

- Arbitrary precision decimals only (BCMath) — floating-point arithmetic is **strictly forbidden**.
- Tax inclusive/exclusive support; double-entry bookkeeping compatibility.

---

## Multi-UOM Design

Each product supports:

- `uom` — base inventory tracking unit (**required**)
- `buying_uom` — purchasing unit (optional; fallback to `uom`)
- `selling_uom` — sales unit (optional; fallback to `uom`)

### Conversion Rules

- `uom_conversions` table: `product_id`, `from_uom`, `to_uom`, `factor`
- Direct path and inverse reciprocal path supported
- Product-specific factors only — no global assumptions, no implicit conversion
- All calculations use BCMath with a minimum of 4 decimal places; use higher precision (8+ decimal places) for intermediate calculations that will be further divided or multiplied before final rounding. Final monetary values must be rounded to the currency's standard precision (typically 2 decimal places). Deterministic and reversible.

---

## Pricing & Discounts

Buying price, selling price, purchase discount, and sales discount may vary by: location, batch, lot, date range, customer tier, or minimum quantity. Supported discount formats: flat (fixed) amount, percentage. All prices and discounts must use BCMath; no floating-point arithmetic.

---

## Inventory Management System (IMS)

Inventory is **ledger-driven**, **transactional**, and **immutable** (historical entries).

- Stock is never edited directly; all changes occur via transactions.
- Reservation precedes deduction; deduction must be atomic.

### Capabilities

Multi-warehouse, multi-bin locations, stock ledger, FIFO / LIFO / Weighted Average, reservations, transfers, adjustments, cycle counting, expiry tracking, damage handling, reorder rules, procurement suggestions, backorders, drop-shipping.

### Concurrency Controls

- Pessimistic locking for stock deduction
- Optimistic locking for updates
- Atomic stock transactions; idempotent stock APIs

---

## Pharmaceutical Compliance Mode

When pharmaceutical compliance mode is enabled:

- Lot tracking and expiry date are **mandatory**
- FEFO (First-Expired, First-Out) is **enforced**
- Serial tracking required where applicable
- Audit trail **cannot be disabled**
- Regulatory reports must be available (FDA / DEA / DSCSA aligned)
- Quarantine workflows for expired or recalled items are enforced
- Expiry override logging and high-risk medication access logging are required

**Compliance is NOT optional.**

---

## Warehouse Management System (WMS)

- Bin-level and location-level real-time tracking
- Automated receiving and intelligent putaway suggestions
- Picking strategies: batch, wave, zone; optimized route generation
- Packing validation; reverse logistics (returns, restocking, damage classification)
- Labor performance tracking; warehouse layout optimization
- Integration with ERP, transportation, and e-commerce platforms

---

## Sales & POS

**Sales flow:** `Quotation → Sales Order → Delivery → Invoice → Payment`

Capabilities: POS terminal mode, offline-first design with local transaction queue and sync reconciliation, draft/hold receipts, split payments, refund handling, backorders, cash drawer tracking, receipt templating, commission engine, rule-based discount engine, tax calculation, loyalty system, gift cards, coupons, e-commerce API compatibility.

---

## Accounting

**Mandatory capabilities:** Double-entry bookkeeping, chart of accounts per tenant, journal entries, auto-posting rules, tax engine, fiscal periods, trial balance, P&L, balance sheet, immutable audit trail.

### Double-Entry Rule

Every transaction must debit one account and credit another; Total Debits = Total Credits at all times.

### Financial Integrity

- No floating-point arithmetic — BCMath only with deterministic rounding.
- Immutable journal entries; accounting must reconcile with inventory valuation.
- Financial integrity **cannot be bypassed**.

---

## CRM

**Pipeline:** `Lead → Opportunity → Proposal → Closed Won / Closed Lost`

Capabilities: leads, opportunities, pipeline stages, activities, campaign tracking & attribution, email integration, SLA tracking & timers, notes & attachments, customer segmentation.

---

## Procurement

**Flow:** `Purchase Request → RFQ → Vendor Selection → Purchase Order → Goods Receipt → Vendor Bill → Payment`

Capabilities: purchase requests, RFQ, vendor comparison, purchase orders, goods receipt, three-way matching (PO / Receipt / Invoice), vendor bills, vendor scoring, price comparison.

---

## Workflow Engine

**State machine model:** `State → Event → Transition → Guard → Action`

Must support: state machine flows, approval chains, escalation rules, SLA enforcement, event-based triggers, background jobs, scheduled tasks. **No hardcoded approval logic.**

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

## API Design

- RESTful, versioned at `/api/v1`
- Standard response envelope, structured error format, pagination required
- OpenAPI/Swagger documentation required for all public endpoints (request & response schemas)
- Idempotent endpoints; no hidden behavior
- Integration: webhooks, event publishing, third-party connectors, e-commerce sync, payment gateway support

---

## Security

Mandatory: CSRF protection, XSS prevention, SQL injection prevention, rate limiting, token rotation, Argon2/bcrypt hashing, strict file validation, signed URLs, audit logging, suspicious activity detection, role-based access control, attribute-based policies, tenant isolation enforcement.

Pharmaceutical-specific: full audit trail of stock mutations, user action logging, tamper-resistant records, expiry override logging, high-risk medication access logging.

---

## Data Integrity & Concurrency

Mandatory controls: DB transactions, foreign keys, unique constraints, optimistic locking, pessimistic locking, idempotency keys, version tracking, immutable logs.

- All stock mutations must execute inside database transactions with guaranteed atomicity.
- Deadlock-aware retry mechanisms required.
- All writes must be safe under parallel load; stock and accounting must **never** be inconsistent.

---

## Performance & Scalability

ERP bottlenecks occur in: inventory deduction, accounting posting, reporting aggregation, and workflow engines.

Mitigation strategies: event-driven design, queue processing, read replicas, caching abstraction, partitioned reporting tables, background reconciliation jobs. Stateless at application level; supports horizontal scaling.

---

## Audit & Compliance

Mandatory: immutable logs, versioned records, traceable financial flows, historical state reconstruction, regulatory export capability. Audit trail is **non-optional**.

---

## Enterprise Reporting

Reports must support: aggregated financial statements, inventory valuation reports, aging reports, tax summaries, custom report builder, export formats (CSV, PDF). Reports must be tenant-scoped, filterable, exportable, auditable, and must **never break transactional integrity**.

---

## Plugin Marketplace

A marketplace-ready ERP requires: module manifest (`module.json`), dependency graph validation, version compatibility rules, sandboxed execution, tenant-scoped enablement, and upgrade migration paths.

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

- Unit tests
- Feature tests
- Authorization tests
- Tenant isolation tests
- Concurrency tests
- Financial precision tests

No module is complete without adequate test coverage.

---

## Prohibited Practices

The following are **immediately refactorable** violations:

- Business logic in controllers
- Query builder calls in controllers
- Cross-module tight coupling or direct database access between modules
- Hardcoded IDs, tenant conditions, or business rules
- Floating-point arithmetic in any financial or quantity calculation
- Partial implementations or TODOs without a tracking issue
- Silent exception swallowing
- Implicit UOM conversion
- Duplicate stock deduction logic
- Skipping transactions for inventory mutations
- Cross-tenant data access

---

## Autonomous Agent Execution Rules

Any AI agent working on this repository must:

1. Analyze the entire affected module before making any changes.
2. Refactor violations before adding new features.
3. Preserve tenant isolation at all times.
4. Maintain architecture boundaries strictly.
5. Update the module README after changes.
6. Update OpenAPI docs after changes.
7. Update `IMPLEMENTATION_STATUS.md` after changes.
8. Avoid introducing coupling between modules.
9. Ensure all regression tests pass before completing work.

---

## Definition of Done

A module is **complete** only if:

- Clean Architecture respected; no duplication
- Tenant isolation enforced; authorization enforced
- Concurrency handled; financial precision validated
- Events emitted correctly; API documented (OpenAPI)
- All tests pass; documentation updated
- `IMPLEMENTATION_STATUS.md` updated; no technical debt introduced

---

## PR Checklist

Before merging any pull request, verify:

- [ ] No business logic in any controller
- [ ] No query builder calls in any controller
- [ ] All new tables include `tenant_id` with global scope applied
- [ ] All new endpoints covered by authorization tests
- [ ] All financial and quantity calculations use BCMath (no float), minimum 4 decimal places
- [ ] Module README updated
- [ ] OpenAPI docs updated
- [ ] `IMPLEMENTATION_STATUS.md` updated
- [ ] No cross-module direct dependency introduced
- [ ] Pharmaceutical compliance mode respected (if applicable)

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