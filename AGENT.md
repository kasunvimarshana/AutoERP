# AGENT.md

Enterprise ERP/CRM SaaS Governance & Execution Contract
Version: 4.0 (Consolidated & Authoritative)
Status: Strictly Enforced – No Exceptions
Scope: Entire Repository

---

# SYSTEM MISSION

Build a **production-grade, enterprise, multi-tenant, modular ERP/CRM SaaS platform** using:

* Laravel (LTS only)
* React (LTS only)
* Native framework capabilities first
* No unstable or experimental dependencies

System must be:

* Modular Monolith (plugin-ready)
* Fully metadata-driven
* API-first
* Stateless
* Horizontally scalable
* Vertically scalable
* Financially precise
* Strictly tenant-isolated
* Replaceable per module
* Zero architectural debt tolerance

---

# ERP FOUNDATIONAL PRINCIPLES

An ERP (Enterprise Resource Planning) system integrates:

* Finance
* Inventory
* Sales
* Procurement
* CRM
* Operations
* Human resources (optional extension)

Core ERP characteristics:

* Single source of truth
* Transaction-driven
* Financially reconcilable
* Cross-module traceable
* Audit-safe

---

# CORE ARCHITECTURE (STRICT CLEAN ARCHITECTURE)

## Mandatory Rules

* Controllers contain no business logic.
* Controllers never use query builder.
* No circular dependencies.
* Domain logic isolated from infrastructure.
* Module boundaries strictly respected.
* Cross-module communication via contracts/events only.

## Layering Standard (Per Module)

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

Each module must be independently replaceable.

---

# MULTI-TENANCY (STRICT ISOLATION)

## Hierarchy Model

```
Tenant
 └── Organisation
      └── Branch
           └── Location
                └── Department
```

## Multi-Tenant Models

Three models in common use:

| Model | Description | Trade-offs |
|---|---|---|
| Shared DB, Shared Schema | All tenants in one schema with row-level isolation via tenant_id | Lowest cost, simplest ops; requires strict global scope enforcement |
| Shared DB, Separate Schema | Each tenant gets its own schema within one database | Better isolation; schema migrations per tenant can be complex |
| Separate DB per Tenant | Dedicated database per tenant | Maximum isolation and performance; highest operational overhead |

Recommended default: Shared DB + Strict Row-Level Isolation + Optional DB-per-tenant upgrade path.

## Mandatory Enforcement

* tenant_id on every business table
* Global scope enforcement
* Tenant-scoped cache
* Tenant-scoped queues
* Tenant-scoped storage
* Tenant-scoped configs
* JWT per user × device × organisation
* Stateless authentication
* Tenant resolution via:
  * Subdomain
  * Header
  * JWT claim

Failure to isolate tenants = Critical Violation.

---

# AUTHORIZATION MODEL

Hybrid enforcement:

* RBAC (roles & permissions)
* ABAC (policy-based rules)
* Multi-guard
* Scoped API keys
* Tenant-level feature flags
* Feature-level gating

Rules:

* Policy classes only.
* No permission logic in controllers.
* No hardcoded role checks.

---

# METADATA-DRIVEN CORE

All configurable logic must be:

* Database-driven
* Enum-controlled
* Runtime-resolvable
* Replaceable without deployment

Includes:

* Dynamic forms & custom fields
* Validation rules & conditional visibility
* Computed fields
* Workflow states
* Approval chains
* Pricing rules
* Tax rules
* Notification templates
* UI layout definitions
* Feature toggles

Hardcoded business rules are prohibited.

---

# PRODUCT DOMAIN (ENTERPRISE GRADE)

## Supported Product Types

* Physical (Stockable)
* Consumable
* Service
* Digital
* Bundle (Kit)
* Composite (Manufactured)
* Variant-based

## Key Concepts

* SKU
* UOM (Unit of Measure) with conversion matrix
* Costing method (FIFO / LIFO / Weighted Average)
* Valuation method
* Traceability (Serial / Batch / Lot) – optional
* GS1 compatibility – optional enterprise feature
* Multi-image management (0..n)
* Multi-location pricing
* Multi-currency pricing

## Mandatory Capabilities

* Optional traceability (Serial / Batch / Lot)
* Optional Barcode / QR
* Optional GS1 compatibility
* 0..n images per product
* Base UOM (`uom`) — required (base inventory tracking unit)
* Buying UOM (`buying_uom`) — optional, fallback to base UOM
* Selling UOM (`selling_uom`) — optional, fallback to base UOM
* UOM conversion matrix
* Multi-location pricing
* Multi-currency pricing
* Tiered pricing
* Rule-based pricing engine
* Fully traceable inventory flow

## Financial Rules

* Arbitrary precision decimals only (BCMath or equivalent)
* Minimum 4 decimal places
* Intermediate calculations (further divided or multiplied before final rounding): 8+ decimal places
* Final monetary values: rounded to the currency's standard precision (typically 2 decimal places)
* Floating-point arithmetic strictly forbidden
* Tax inclusive/exclusive support
* Double-entry bookkeeping compatibility

---

# INVENTORY & WAREHOUSE

Inventory is ledger-driven, transactional, and immutable (historical entries).

Key rules:

* Stock is never edited directly.
* All changes via transactions.
* Reservation precedes deduction.
* Deduction must be atomic.

Critical flows:

* Purchase Receipt
* Sales Shipment
* Internal Transfer
* Adjustment
* Return

Must support:

* Multi-warehouse
* Multi-bin locations
* Stock ledger
* FIFO / LIFO / Weighted Average
* Reservations
* Transfers
* Adjustments
* Cycle counting
* Expiry tracking
* Damage handling
* Reorder rules
* Procurement suggestions
* Backorders
* Drop-shipping

## Concurrency

* Pessimistic locking for stock deduction
* Optimistic locking for updates
* Atomic stock transactions
* Idempotent stock APIs

---

# PHARMACEUTICAL COMPLIANCE MODE

When pharmaceutical compliance mode is enabled for a tenant:

* Lot tracking and expiry date are mandatory
* FEFO (First-Expired, First-Out) is enforced
* Serial tracking required where applicable
* Audit trail cannot be disabled
* Regulatory reports must be available (FDA / DEA / DSCSA aligned)
* Quarantine workflows for expired or recalled items are enforced
* Expiry override logging and high-risk medication access logging are required

Compliance is NOT optional.

---

# SALES & POS

## Sales Flow

```
Quotation → Sales Order → Delivery → Invoice → Payment
```

## Capabilities

* POS terminal mode
* Offline-ready sync design
* Draft / Hold receipts
* Split payments
* Refund handling
* Backorders
* Cash drawer tracking
* Receipt templating
* Commission engine
* Rule-based discount engine
* Tax calculation
* Loyalty system
* Gift cards
* Coupons
* E-commerce API compatibility

POS requires:

* Offline-first design
* Local transaction queue
* Sync reconciliation engine

---

# ACCOUNTING

Mandatory:

* Double-entry bookkeeping
* Chart of accounts per tenant
* Journal entries
* Auto-posting rules
* Tax engine
* Fiscal periods
* Trial balance
* Profit & Loss
* Balance sheet
* Immutable audit trail

## Double-Entry Rule

Every transaction must:

* Debit one account
* Credit another account
* Total Debits = Total Credits

## Financial Integrity Rules

* No floating-point arithmetic
* Arbitrary precision decimals only
* Minimum 4 decimal places
* Intermediate calculations (further divided or multiplied before final rounding): 8+ decimal places
* Final monetary values: rounded to the currency's standard precision (typically 2 decimal places)
* Deterministic rounding
* Immutable journal entries

Accounting must reconcile with inventory valuation.
Financial integrity cannot be bypassed.

---

# CRM

## CRM Pipeline

```
Lead → Opportunity → Proposal → Closed Won / Closed Lost
```

## Capabilities

* Leads
* Opportunities
* Pipeline stages
* Activities
* Campaign tracking
* Campaign attribution
* Email integration
* SLA tracking & timers
* Notes & attachments
* Customer segmentation

---

# PROCUREMENT

## Procurement Flow

```
Purchase Request → RFQ → Vendor Selection → Purchase Order → Goods Receipt → Vendor Bill → Payment
```

## Capabilities

* Purchase requests
* RFQ
* Vendor comparison
* Purchase orders
* Goods receipt
* Three-way matching (PO, Receipt, Invoice)
* Vendor bills
* Vendor scoring
* Price comparison logic

---

# WORKFLOW ENGINE

State machine model:

```
State → Event → Transition → Guard → Action
```

Must support:

* State machine flows
* Approval chains
* Escalation rules
* SLA enforcement
* Event-based triggers
* Background jobs
* Scheduled tasks

No hardcoded approval logic.

---

# CUSTOMIZATION & RULE ENGINE

Enterprise SaaS ERP must be customizable without redeployment.

## Metadata-Driven Schema

* Custom fields
* Validation rules
* Dynamic forms
* Conditional visibility
* Computed fields

## Rule Engine

Declarative rule patterns:

```
IF condition
THEN action
```

Used for:

* Pricing
* Discounts
* Taxes
* Commissions
* Inventory reservation logic

---

# API DESIGN

* RESTful
* Versioned (/api/v1)
* Idempotent endpoints
* Standard response envelope
* Structured error format
* Pagination required
* OpenAPI documentation required
* No hidden behavior

Integration capabilities:

* Webhooks
* Event publishing
* Third-party connectors
* E-commerce sync
* Payment gateway support

---

# SECURITY

Mandatory:

* CSRF protection
* XSS prevention
* SQL injection prevention
* Rate limiting
* Token rotation
* Argon2/bcrypt hashing
* Strict file validation
* Signed URLs
* Audit logging
* Suspicious activity detection
* Role-based access control
* Attribute-based policies
* Tenant isolation enforcement

Pharmaceutical-specific:

* Full audit trail of stock mutations
* User action logging
* Tamper-resistant records
* Expiry override logging
* High-risk medication access logging

---

# DATA INTEGRITY & CONCURRENCY

ERP systems are highly concurrent. Mandatory controls:

* DB transactions
* Foreign keys
* Unique constraints
* Optimistic locking
* Pessimistic locking
* Idempotency keys
* Version tracking
* Immutable logs

All writes must be safe under parallel load.
Stock and accounting must never be inconsistent.

---

# PERFORMANCE & SCALABILITY

ERP bottlenecks typically occur in:

* Inventory deduction
* Accounting posting
* Reporting aggregation
* Workflow engines

Mitigation strategies:

* Event-driven design
* Queue processing
* Read replicas
* Caching abstraction
* Partitioned reporting tables
* Background reconciliation jobs

---

# AUDIT & COMPLIANCE

ERP must support:

* Immutable logs
* Versioned records
* Traceable financial flows
* Historical state reconstruction
* Regulatory export capability

Audit trail is non-optional.

---

# ENTERPRISE REPORTING

Reports must support:

* Aggregated financial statements
* Inventory valuation reports
* Aging reports
* Tax summaries
* Custom report builder
* Export formats (CSV, PDF)

Reports must never break transactional integrity.

---

# PLUGIN MARKETPLACE

A marketplace-ready ERP requires:

* Module manifest definition (module.json)
* Dependency graph validation
* Version compatibility rules
* Sandboxed execution
* Tenant-scoped enablement
* Upgrade migration paths

---

# REUSABILITY PRINCIPLES

Reusable ERP modules must:

* Avoid business-specific assumptions
* Be configuration-driven
* Avoid hardcoded logic
* Expose contracts, not implementations
* Remain independently replaceable

Design for:

* Multi-industry support
* Multi-country tax extension
* Marketplace plugin ecosystem

---

# TESTING REQUIREMENTS

Each module must include:

* Unit tests
* Feature tests
* Authorization tests
* Tenant isolation tests
* Concurrency tests
* Financial precision tests

No module is complete without coverage.

---

# PROHIBITED PRACTICES

* Business logic in controllers
* Query builder in controllers
* Cross-module tight coupling or direct database access between modules
* Hardcoded IDs, tenant conditions, or business rules
* Floating-point financial math
* Partial implementations
* TODO without tracking issue
* Silent exception swallowing
* Implicit UOM conversion
* Duplicate stock deduction logic
* Skipping transactions for inventory mutations
* Cross-tenant data access

Immediate refactor required if detected.

---

# IMPLEMENTATION TRACKING

A continuously maintained:

IMPLEMENTATION_STATUS.md

Must track:

* Module
* Status (Planned / In Progress / Complete / Refactor Required)
* Test coverage %
* Violations found
* Refactor actions
* Concurrency compliance
* Tenant compliance verification

---

# AUTONOMOUS AGENT EXECUTION RULES

Any AI agent must:

1. Analyze entire affected module before changes.
2. Refactor violations before adding features.
3. Preserve tenant isolation.
4. Maintain architecture boundaries.
5. Update module README.
6. Update OpenAPI docs.
7. Update IMPLEMENTATION_STATUS.md.
8. Avoid introducing coupling.
9. Ensure regression tests pass.

## Autonomous Agent Compliance Validation

All pull requests must be verified against this checklist before merge:

- [ ] No business logic present in any controller
- [ ] No query builder calls in any controller
- [ ] All new tables include `tenant_id` with global scope applied
- [ ] All new endpoints covered by authorization tests
- [ ] All financial and quantity calculations use BCMath (no float), minimum 4 decimal places
- [ ] Module README updated
- [ ] OpenAPI docs updated
- [ ] IMPLEMENTATION_STATUS.md updated
- [ ] No cross-module direct dependency introduced
- [ ] Pharmaceutical compliance mode respected (if applicable)

---

# DEFINITION OF DONE

A module is complete only if:

* Clean Architecture respected
* No duplication
* Tenant isolation enforced
* Authorization enforced
* Concurrency handled
* Financial precision validated
* Events emitted correctly
* API documented
* Tests pass
* Documentation updated
* No technical debt introduced

---

# CORE SYSTEM GUARANTEES

The system must guarantee:

* Tenant isolation
* Financial correctness
* Transactional consistency
* Replaceable modules
* Deterministic calculations
* Audit safety
* Horizontal scalability
* Extensibility without refactor debt

---

# STRATEGIC DESIGN GOAL

Build not just an ERP.

Build:

* A configurable ERP framework
* A reusable SaaS engine
* A domain-driven enterprise core
* A plugin-ready platform
* A long-term maintainable system

---

# REFERENCES

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

---

# FINAL DIRECTIVE

This system must evolve into a:

* Fully modular
* Fully pluggable
* Fully configurable
* Fully tenant-isolated
* Fully enterprise-grade
* Financially precise
* Horizontally scalable
* Vertically scalable
* Audit-safe ERP/CRM SaaS platform

Zero regression tolerance.
Zero architectural violations.
Zero technical debt acceptance.

This contract is binding for all contributors and AI agents.
