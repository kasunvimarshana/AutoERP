# AGENT.md

Enterprise ERP/CRM SaaS Governance & Execution Contract
Version: 2.0
Status: Strictly Enforced
Scope: Entire Repository

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

# SYSTEM MISSION

Build a **production-grade, enterprise, multi-tenant, modular ERP/CRM SaaS platform** using:

* Laravel (LTS only)
* React (LTS only)
* Native framework capabilities first
* No unstable dependencies

System must be:

* Modular monolith (plugin-ready)
* Fully metadata-driven
* API-first
* Stateless
* Horizontally scalable
* Vertically scalable
* Financially precise
* Tenant-isolated
* Replaceable per module
* Zero architectural debt tolerance

---

# ARCHITECTURE (STRICT CLEAN ARCHITECTURE)

Rules:

* Controllers contain no business logic.
* No query builder usage in controllers.
* No circular dependencies.

---

# MODULAR PLUGIN SYSTEM (MANDATORY)

Each feature = isolated module under:

```
Modules/
 └── {ModuleName}/
     ├── module.json
     ├── README.md
```

Rules:

* Module replaceable without breaking others.

---

# MULTI-TENANCY (STRICT ISOLATION)

Model:

Tenant
→ Organisation
→ Branch
→ Location
→ Department

Mandatory:

* tenant_id in every table
* Global scope enforcement
* JWT per user × device × organisation
* Stateless authentication
* Tenant resolution via:

  * Subdomain
  * Header
  * JWT claim

---

# AUTHORIZATION MODEL

Hybrid enforcement:

* RBAC (roles, permissions)
* ABAC (policy-based attributes)
* Policy classes only
* No permission logic inside controllers

Must support:

* Multi-guard
* Scoped API keys
* Feature-level gating
* Tenant-level feature flags

---

# METADATA-DRIVEN CORE

All configurable logic must be:

* Database-driven
* Enum-controlled
* Runtime-resolvable
* Replaceable without deployment

Includes:

* Form definitions
* Workflow states
* Approval chains
* Pricing rules
* Tax logic
* Notification templates
* UI layout metadata

No hardcoded business rules.

---

# PRODUCT DOMAIN (ENTERPRISE GRADE)

Supported Types:

* Physical
* Service
* Digital
* Bundle
* Composite
* Variant-based

Features:

* Optional traceability (serial / batch / lot)
* Optional barcode / QR
* Optional GS1 compatibility
* Multiple images (0..n)
* Optional base UOM
* Optional buying UOM
* Optional selling UOM
* UOM conversion matrix
* Location-based pricing
* Multi-currency pricing
* Tiered pricing
* Rule-based pricing engine

Financial Rules:

* Decimal high precision (BCMath / arbitrary precision)
* No floating point arithmetic
* Tax inclusive/exclusive support
* Double-entry bookkeeping compatibility

---

# INVENTORY & WAREHOUSE

Derived from ERP industry standards.

Capabilities:

* Multi-warehouse
* Multi-location bins
* Stock ledger
* FIFO / LIFO / Weighted Average
* Reservation system
* Stock transfers
* Stock adjustments
* Cycle counting
* Expiry tracking
* Damage handling
* Reorder rules
* Procurement suggestions
* Backorders
* Drop-shipping support

Concurrency:

* Pessimistic locking for stock deduction
* Optimistic locking for updates
* Atomic stock transactions

---

# SALES & POS

Features:

* POS terminal mode
* Offline-ready sync-ready design
* Draft / Hold receipts
* Split payments
* Refund handling
* Cash drawer tracking
* Receipt templating
* Commission tracking
* Discount engine (rule-driven)
* Loyalty system
* Gift cards
* Coupons
* E-commerce API compatibility

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
* P&L
* Balance sheet
* Audit trail immutable logs

Financial integrity cannot be bypassed.

---

# CRM

* Leads
* Opportunities
* Pipeline stages
* Activities
* Campaign tracking
* Email integration
* Notes & attachments
* SLA tracking
* Customer segmentation

---

# PROCUREMENT

* Purchase requests
* RFQ
* Vendor comparison
* Purchase orders
* Goods receipt
* Vendor bills
* Vendor performance scoring

---

# WORKFLOW ENGINE

Must support:

* State machine driven flows
* Approval chains
* Escalation rules
* Event-based triggers
* Background jobs
* Scheduled tasks

No hardcoded approval logic.

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

---

# SECURITY

Mandatory:

* CSRF protection
* XSS prevention
* SQL injection prevention
* Rate limiting
* Token rotation
* Secure hashing (argon2/bcrypt)
* Strict file validation
* Signed URLs
* Audit logging
* Suspicious activity alerts

---

# DATA INTEGRITY & CONCURRENCY

Mandatory:

* Database transactions
* Foreign keys
* Unique constraints
* Optimistic locking
* Pessimistic locking
* Idempotency keys
* Version tracking
* Immutable audit logs

All write operations must be safe under parallel load.

---

# PROHIBITED PRACTICES

* Business logic in controllers
* Query builder in controllers
* Cross-module tight coupling
* Hardcoded IDs
* Floating-point financial math
* Partial implementation
* TODO without tracking issue

Immediate refactor required if detected.

---

# DEFINITION OF DONE

A module is complete only if:

* Clean Architecture respected
* No duplication
* Tenant isolation enforced
* Authorization enforced
* Concurrency handled
* Events emitted correctly
* API documented
* Tests pass
* Documentation updated
* No technical debt introduced

---

# AUTONOMOUS AGENT EXECUTION RULES

Any AI agent must:

1. Analyze entire affected module before change.
2. Refactor violations before new features.
3. Preserve tenant isolation.
4. Maintain architectural boundaries.
5. Update module README.
6. Update OpenAPI docs.
7. Update implementation tracking report.
8. Avoid introducing coupling.
9. Ensure regression tests pass.

No shortcuts.
No hidden changes.

---

# IMPLEMENTATION TRACKING REQUIREMENT

A continuously updated:

IMPLEMENTATION_STATUS.md

Must track:

* Module name
* Status (Planned / In Progress / Complete / Refactor Required)
* Test coverage %
* Violations found
* Refactor actions
* Concurrency compliance
* Tenant compliance verification

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

No regression in modular integrity.
No architectural compromise.
No technical debt tolerance.

This contract is binding for all contributors and AI agents.
