# AGENT.md
Enterprise ERP/CRM SaaS Governance & Execution Contract
Version: 1.0
Status: Enforced
Scope: Entire Repository

---

# SYSTEM PURPOSE

This repository implements a production-grade, enterprise, multi-tenant, modular ERP/CRM SaaS platform using:

- Laravel (Stable LTS only)
- Vue (Stable LTS only)
- Native framework features only
- No unstable or experimental dependencies

The system must remain:

- Fully modular
- Fully isolated per tenant
- Fully metadata-driven
- Fully API-first
- Fully stateless
- Enterprise secure
- Horizontally scalable
- Vertically scalable
- Replaceable per module

---

# ARCHITECTURE (STRICT CLEAN ARCHITECTURE)

All modules MUST follow strict layering:

Presentation
→ Application
→ Domain
→ Infrastructure

## Layer Responsibilities

### Presentation
- Controllers
- API routes
- Request validation
- Response formatting
- No business logic allowed

### Application
- Use cases
- Services
- Orchestrators
- Transaction management
- Emits domain events

### Domain
- Entities
- Value Objects
- Aggregates
- Enums
- Interfaces (contracts)
- No framework dependency allowed

### Infrastructure
- Repositories
- Database models
- External integrations
- File systems
- Queue adapters

---

# MODULAR PLUGIN SYSTEM (MANDATORY)

Every feature is an isolated module.

## Module Directory Structure

Modules/
 └── {ModuleName}/
     ├── Domain/
     ├── Application/
     ├── Infrastructure/
     ├── Presentation/
     ├── Providers/
     ├── routes.php
     ├── config.php
     ├── module.json
     └── README.md

## Module Rules

- No direct cross-module calls
- Communication only via:
  - Contracts
  - Events
  - API
- No shared state
- No circular dependencies
- Each module must register itself
- Each module must be enable/disable capable

---

# MULTI-TENANCY (STRICT ISOLATION)

## Required Capabilities

- Multi-tenant
- Hierarchical organizations
- Multi-branch
- Multi-location
- Multi-vendor
- Multi-currency
- Multi-language
- Multi-unit
- Multi-user
- Multi-device

## Isolation Enforcement

- Tenant ID mandatory in every table
- Global scope enforced
- Tenant middleware required
- No cross-tenant queries
- Per-request tenant resolution
- JWT per user × device × organization
- Stateless authentication
- Multi-guard support

Failure to enforce tenant isolation is a critical violation.

---

# AUTHORIZATION MODEL

## Required

- RBAC (Role Based Access Control)
- ABAC (Attribute Based Access Control)
- Laravel Policies only
- Middleware enforced
- No permission logic in controllers

---

# METADATA-DRIVEN SYSTEM

The following MUST NOT be hardcoded:

- Pricing rules
- Workflow states
- UI components
- Permissions
- Tax logic
- Calculation strategies

All configurable logic must be:

- Database-driven
- Enum-controlled
- Runtime-resolvable
- Replaceable without code changes

---

# CORE DOMAIN MODULES

Each module must define:

- Entities
- Use cases
- Contracts
- Events
- Dependencies

---

# PRODUCT MODEL

Must support:

- Physical goods
- Services
- Digital products
- Bundles
- Composite products

Features:

- Multiple units
- Buy/sell conversion
- Location-based pricing
- Multi-currency
- Pricing strategies:
  - Flat
  - Percentage
  - Tiered
  - Rule-based

All monetary calculations must use high precision decimal handling.

Floating point arithmetic is forbidden for financial operations.

---

# DATA INTEGRITY & CONCURRENCY

Mandatory:

- Database transactions
- Foreign keys
- Unique constraints
- Idempotent APIs
- Optimistic locking
- Pessimistic locking
- Audit logging
- Version tracking

All write operations must be concurrency-safe.

---

# EVENT-DRIVEN DESIGN

Modules must communicate using:

- Laravel Events
- Queues
- Pipelines
- Jobs

Direct synchronous cross-module logic is forbidden.

---

# SECURITY REQUIREMENTS

- CSRF protection
- XSS prevention
- SQL injection prevention
- Rate limiting
- Token rotation
- Secure password hashing
- Secure file upload validation
- Strict validation on every request

---

# API DESIGN RULES

- RESTful structure
- Versioned APIs
- Consistent response format
- Structured error responses
- Idempotent endpoints
- Pagination enforced
- No hidden logic

---

# TESTING REQUIREMENTS

Each module must include:

- Unit tests
- Feature tests
- Authorization tests
- Tenant isolation tests
- Concurrency tests

No module is complete without test coverage.

---

# CI/CD ENFORCEMENT

All pull requests must:

- Pass linting
- Pass static analysis
- Pass unit tests
- Pass feature tests
- Validate JSON formatting
- Validate YAML workflows
- Validate dependency versions

No direct commits to production branch.

---

# PROHIBITED PRACTICES

- Business logic inside controllers
- Query builder usage in controllers
- Global state usage
- Static service calls across modules
- Hardcoded pricing
- Direct model coupling between modules
- Bypassing policies
- Partial implementations

Violations must be refactored immediately.

---

# DOCUMENTATION REQUIREMENTS

Each module must contain:

- Purpose
- Scope
- Domain boundaries
- Entity definitions
- Use case definitions
- Event list
- Configuration parameters
- Installation instructions

Documentation must match implementation state.

---

# DEFINITION OF DONE

A module is complete only if:

- Architecture boundaries respected
- No duplication exists
- Tenant isolation enforced
- Authorization enforced
- Events emitted correctly
- Concurrency handled
- Tests pass
- Documentation updated
- CI passes
- No technical debt introduced

---

# AUTONOMOUS AGENT RULES

Any AI agent modifying this repository must:

1. Analyze entire affected module before modifying.
2. Refactor existing violations before adding new code.
3. Preserve tenant isolation at all times.
4. Update documentation.
5. Avoid introducing coupling.
6. Maintain clean architecture boundaries.
7. Maintain strict modular independence.

---

# FINAL DIRECTIVE

This system must evolve into a:

- Fully modular
- Fully pluggable
- Fully configurable
- Fully enterprise-grade
- Fully isolated multi-tenant SaaS ERP/CRM

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
- https://sevalla.com/blog/building-modular-systems-laravel 
- https://www.laravelpackage.com 
- https://laravel-news.com/building-your-own-laravel-packages 
- https://dev.to/rafaelogic/building-a-polymorphic-translatable-model-in-laravel-with-autoloaded-translations-3d99 
- https://freek.dev/1567-pragmatically-testing-multi-guard-authentication-in-laravel 
- https://laravel-news.com/laravel-gates-policies-guards-explained 
- https://dev.to/codeanddeploy/how-to-create-a-custom-dynamic-middleware-for-spatie-laravel-permission-2a08 
- https://laravel.com/docs/12.x/authorization 
- https://en.wikipedia.org/wiki/Inventory 
- https://en.wikipedia.org/wiki/Inventory_management_software 
- https://en.wikipedia.org/wiki/Inventory_management 
- https://en.wikipedia.org/wiki/Inventory_management_(business) 
- https://en.wikipedia.org/wiki/Inventory_theory 
- https://en.wikipedia.org/wiki/Enterprise_resource_planning 
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
- https://dev.to/keljtanoski/modular-laravel-3dkf 
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
- https://laravel.com/docs/12.x/localization 
- https://oneuptime.com/blog/post/2026-02-03-laravel-multi-language/view 
- https://github.com/spatie/laravel-translatable/blob/main/docs/introduction.md 
- https://dev.to/abstractmusa/modular-monolith-architecture-within-laravel-communication-between-different-modules-a5 
- https://oneuptime.com/blog/post/2026-02-02-laravel-database-transactions/view 
- https://laravel-news.com/database-transactions 
- https://github.com/spatie/laravel-multitenancy 
- https://laravel.com/blog/building-a-multi-tenant-architecture-platform-to-scale-the-emmys 
- https://github.com/archtechx/tenancy 
- https://sevalla.com/blog/mcp-server-laravel 
- https://github.com/laravel/mcp 
- https://tailadmin.com 
- https://github.com/TailAdmin/tailadmin-free-tailwind-dashboard-template 
- https://github.com/ColorlibHQ/AdminLTE 
- https://adminlte.io/blog/free-react-templates 
- https://madewithreact.com/reactjs-adminlte 
- https://github.com/TailAdmin/free-react-tailwind-admin-dashboard?tab=readme-ov-file 

No shortcuts.
No architectural violations.
No regression in modular integrity.

This file is binding governance for all contributors and AI agents.

---

# COMPREHENSIVE FEATURES & MODULE CATALOGUE

> Derived from systematic deep analysis of leading open-source and commercial ERP/CRM
>
> Every feature listed below must be implemented as an isolated, tenant-aware,
> policy-guarded Laravel module under `Modules/` following the Clean Architecture
> contract defined above. No feature may be hardcoded; all configuration must be
> database-driven and runtime-resolvable.

---

## PLATFORM / INFRASTRUCTURE MODULES

### Module: Auth
Authentication, authorisation bootstrap, and token lifecycle management.

**Features:**
- Stateless JWT authentication (access token + refresh token pair)
- Refresh token rotation with per-version invalidation (prevents replay attacks)
- Token issuance scoped per user × device × organisation
- Multi-factor authentication (TOTP, email OTP, backup codes)
- Social OAuth 2.0 login (Google, Microsoft, GitHub — pluggable adapters)
- Password strength enforcement (configurable policy: length, complexity, history)
- Secure bcrypt/argon2 password hashing
- Password reset flow (signed time-limited URL, single-use token)
- Email verification on registration
- Rate limiting on all auth endpoints (configurable per tenant)
- Brute-force lockout with exponential back-off
- Multi-guard support (web, api, admin — separate token namespaces)
- Session management: list active sessions, forced remote logout
- Login history with IP, device, timestamp
- Suspicious login detection and alerting
- API key issuance (hashed storage, scoped permissions, rotation, revocation)

### Module: Tenant
Multi-tenancy, organisation hierarchy, and tenant lifecycle.

**Features:**
- Fully isolated single-database multi-tenancy (tenant_id on every table, global scope)
- Tenant registration and self-service onboarding wizard
- Tenant provisioning: default roles, chart of accounts, settings, POS terminal
- Tenant suspension (read-only mode), archival, and permanent deletion
- Hierarchical organisation tree: Tenant → Organisation → Branch → Location → Department
- Per-tenant custom subdomain / custom domain mapping
- Tenant metadata: name, logo, timezone, default currency, locale, fiscal year
- Per-request tenant resolution: subdomain, HTTP header, JWT claim — configurable strategy
- Tenant resource quotas (users, storage GB, API calls/month)
- Usage tracking and overage notifications
- Tenant-level feature flags (enable/disable modules)
- Cross-tenant isolation verification (automated tests)

### Module: User
User identity, roles, and permission management.

**Features:**
- User CRUD with soft-delete and complete audit trail
- User invitation flow (email invite → accept → set password → activate)
- Profile management: avatar upload, contact info, UI preferences, timezone, language
- Role management: system built-in roles + tenant-defined custom roles
- Granular permission management (module, entity, action, field level)
- RBAC: roles → permissions matrix, role inheritance
- ABAC: attribute-based rules evaluated at policy layer (Laravel Policies)
- Dynamic permission assignment without code deploy
- Team / group management (users can belong to multiple teams)
- User status: active, inactive, suspended, pending verification
- Bulk import/export of users (CSV)
- Password policy enforcement per tenant
- Last login tracking and inactive user alerts

### Module: Setting
Metadata-driven, tenant-scoped configuration management.

**Features:**
- All configuration stored in database — nothing hardcoded
- Setting groups: company, finance, inventory, sales, HR, notification, integration
- Setting schema: key, value, type (string/integer/boolean/json/enum), validation rules
- Global settings (platform admin), tenant settings (per-tenant override)
- Setting versioning and change history
- Feature flag management: per-tenant module enable/disable
- Email provider configuration (SMTP, AWS SES, Mailgun, Postmark, SendGrid)
- SMS provider configuration (Twilio, Vonage, AWS SNS)
- Storage driver configuration (local, AWS S3, GCS, Azure Blob)
- Payment gateway configuration (Stripe, PayPal, Razorpay, Braintree)
- Webhook endpoint management (outbound, inbound, signing secrets)
- Localisation settings: language, timezone, date format, number format, currency symbol
- Maintenance mode (per-tenant, with bypass IPs)
- GDPR / data retention policy configuration

### Module: Audit
Immutable audit trail for all data operations.

**Features:**
- Automatic audit log for every create/update/delete across all modules
- Records: actor (user_id), timestamp, IP address, user agent, action, model, record_id
- Field-level change tracking (before/after JSON diff)
- Tenant-scoped audit queries
- Audit log search and filter (by user, model, date range, action)
- Exportable audit reports (PDF, CSV)
- Tamper-evident log storage (append-only, hash chain optional)
- Audit log retention policy with configurable expiry
- Compliance reports (GDPR data access log, SOX trail)

### Module: Notification
Multi-channel notification delivery and preference management.

**Features:**
- Channels: in-app bell, email, SMS, push (FCM/APNs), webhook
- Template-driven notifications (stored in DB, tenant-customizable with variables)
- Notification preference management per user per channel per event type
- In-app notification centre: unread count, mark as read, bulk clear, pagination
- Queue-backed async delivery (Laravel queues)
- Retry logic and delivery status tracking
- Event-triggered automated notifications (configurable trigger → channel → template)
- Scheduled digest notifications (daily/weekly summaries)
- Broadcast notifications (tenant-wide announcements)
- Notification grouping and priority levels

### Module: Media
File storage, management, and access control.

**Features:**
- Multi-driver storage (local, AWS S3, GCS, Azure Blob — configurable per tenant)
- Secure upload with MIME type + extension whitelist validation
- Maximum file size enforcement (configurable per tenant)
- Virus scanning hook (ClamAV adapter, configurable)
- Image processing: resize, thumbnail generation, WebP conversion
- File organisation: folders, tags, categories
- File search (name, tag, uploader, date)
- Per-tenant storage quota enforcement with usage dashboard
- Signed temporary URLs for private file access (configurable TTL)
- File versioning (overwrite creates new version, retains history)
- Bulk upload and download (ZIP)
- File sharing links (public/password-protected, expiry)
- File attached to any entity (polymorphic attachment)

---

## CRM MODULE

### Module: CRM
Customer relationship management, pipeline, and communication tracking.

**Features:**

#### Leads
- Lead capture: web form embed, REST API, email-to-lead parser, CSV import
- Lead fields: name, company, email, phone, source, campaign, description
- Lead source tracking (website, social, referral, advertisement, cold-call)
- Lead scoring engine (configurable rule-based scoring: demographic + behavioural)
- Duplicate lead detection and merge
- Lead assignment (manual, round-robin, territory-based — configurable)
- Lead follow-up reminders and auto-escalation

#### Opportunities
- Lead-to-opportunity conversion with data carry-over
- Opportunity pipeline: configurable stages (DB-driven, not hardcoded)
- Kanban pipeline view with drag-and-drop stage progression
- Expected revenue, probability, and weighted pipeline value
- Win/loss tracking with reason codes (DB-driven reason list)
- Competitor tracking
- Opportunity cloning and templates

#### Contacts & Accounts
- Contact (individual) and Account (company) management
- Contact ↔ Account linkage (many-to-many with role)
- Contact deduplication engine
- Contact timeline: all emails, calls, meetings, notes, orders in one feed
- Tags and segments (user-defined, DB-driven)
- Custom fields on contacts (EAV or JSON column — configurable per tenant)
- Contact merge
- Bulk contact import/export (CSV, vCard)
- GDPR consent management per contact

#### Activities
- Activity types: call, email, meeting, task, note, demo — DB-driven, extensible
- Activity creation linked to any CRM entity
- Activity scheduling with calendar view
- Reminders (in-app, email, push) before activity due date
- Activity completion with outcome logging
- Recurring activity support

#### Email Marketing
- Campaign creation with subject, sender, template, audience segment
- Segment builder: filter contacts by any field/tag combination
- HTML email template designer (stored in DB, tenant-branded)
- Send scheduling and throttling (per-hour rate limit)
- Open/click tracking (pixel + link rewriting)
- Unsubscribe management (1-click, list suppression)
- GDPR compliance: consent check before send
- Campaign analytics: sent, delivered, bounced, opened, clicked, unsubscribed
- A/B split testing on subject lines

#### Sales Pipeline & Automation
- Multiple pipelines per tenant (sales, partnerships, renewals, etc.)
- Stage-based automation: trigger notifications, tasks, emails on stage change
- Rotting deal alerts (no activity for N days — configurable)
- Sales team management (team → members → territories)
- Revenue forecast by period and pipeline stage

---

## SALES MODULE

### Module: Sales
Quotations, orders, customers, and fulfilment.

**Features:**

#### Customers
- Customer master (B2B and B2C types)
- Customer classification: segment, industry, tier, account manager
- Credit limit management (hard/soft limit with override approval)
- Customer price list assignment (per-customer or per-group)
- Customer-level discount rules (flat amount, percentage, by product category)
- Customer payment terms (NET 30, COD, 50% upfront, etc. — DB-driven)
- Customer statement of account (outstanding invoices, payments, balance)
- Customer credit notes and refund management
- Shipping address book (multiple delivery addresses per customer)

#### Price Lists
- Multiple price lists per tenant
- Price list applicability: all customers, customer group, specific customer, date range
- Pricing strategies: flat price, percentage discount off list, tiered (quantity breaks), formula
- Volume/quantity break pricing matrix
- Promotional pricing with start/end date validity
- Price override at order line level (with audit and permission check)
- Price list version history

#### Quotations
- Quotation creation from CRM opportunity or from scratch
- Multi-line items: products, services, comments, sections
- Line-level discount, tax, and unit of measure
- Quotation revision history (v1, v2, v3… with diff)
- PDF generation with tenant-branded letterhead
- Email quotation with share link (public URL, expiry)
- Quotation acceptance/rejection with digital signature hook
- Terms & conditions (tenant-configurable template)
- Quotation expiry date and auto-expiry workflow
- Quotation-to-sales-order conversion (one-click)

#### Sales Orders
- Sales order from quotation or direct entry
- Multi-line order with product, qty, UOM, price, discount, tax
- Order status workflow: Draft → Confirmed → Processing → Partially Shipped → Shipped → Invoiced → Closed
- Partial shipment support (ship in multiple deliveries)
- Partial invoicing support (bill by delivery or by schedule)
- Backorder creation for undelivered quantities
- Order modification (add/remove lines) with approval workflow
- Order cancellation with reason code and stock release
- Promised delivery date commitment and tracking
- Sales order PDF and email

#### Deliveries / Shipments
- Automatic delivery order generation on SO confirmation
- Pick → Pack → Ship workflow (configurable per warehouse)
- Carrier selection and shipping cost calculation
- Pluggable carrier adapters (DHL, FedEx, UPS, local — configurable)
- Shipment tracking number recording and status polling
- Proof of delivery (POD) upload
- Returns / RMA (Return Merchandise Authorisation) processing
- Delivery note PDF generation

#### Sales Reports & Analytics
- Sales by: product, product category, customer, sales rep, region, period
- Revenue vs target comparison dashboard
- Gross margin analysis (revenue − COGS)
- Pipeline-to-close conversion funnel
- Sales rep performance leaderboard
- Repeat vs new customer split

---

## PURCHASE MODULE

### Module: Purchase
Vendor management, procurement, and payables.

**Features:**

#### Vendors
- Vendor master: contact info, bank details, tax registration, classification
- Vendor rating and performance metrics (on-time delivery %, quality score)
- Preferred vendor assignment per product
- Vendor-specific payment terms and currency
- Vendor price lists with validity dates
- Vendor classification: local, foreign, preferred, blacklisted

#### Purchase Requisitions
- Internal purchase request (PR) creation by any authorised user
- Line items: product, qty, required-by date, justification
- Multi-level approval workflow (configurable by amount threshold, department)
- Link PR to sales order demand or inventory reorder rule
- PR status: Draft → Pending Approval → Approved → PO Raised → Closed

#### Request for Quotation (RFQ)
- RFQ creation from approved PR or manually
- Dispatch RFQ to multiple vendors simultaneously
- Vendor quotation entry (price, lead time, validity)
- Comparison matrix: side-by-side vendor quote comparison
- Auto-selection criteria: lowest price, fastest delivery, highest rating — configurable
- RFQ-to-PO conversion (selected vendor lines)

#### Purchase Orders
- PO creation from RFQ or direct entry
- Multi-currency PO (rate locked at PO confirmation)
- PO approval workflow (configurable thresholds)
- PO status: Draft → Approved → Sent → Partially Received → Fully Received → Billed → Closed
- Partial receipt support (GRN per partial delivery)
- Partial billing support (bill when received)
- PO amendment: change qty/price with version history and approval
- PO cancellation with reason code
- PO PDF generation and email to vendor

#### Goods Receipt (GRN)
- Goods receipt note creation linked to PO
- Quality inspection checkpoint (pass/fail/conditional)
- Automatic stock increase on confirmed receipt
- Over-delivery and under-delivery discrepancy recording
- Discrepancy resolution: accept, reject, return to vendor
- GRN reference on stock movement record

#### Vendor Bills & Payments
- Vendor bill creation from PO/GRN (auto-populate lines)
- Three-way matching: PO ↔ GRN ↔ Bill (mismatch alerts)
- Bill approval workflow
- Payment scheduling (due date from payment terms)
- Batch payment run: select multiple bills, generate payment file
- Early payment discount calculation (2/10 net 30 type terms)
- Vendor credit notes handling

---

## INVENTORY MODULE

### Module: Inventory
Stock management, warehousing, and movements.

**Features:**

#### Product Catalogue
- Product types: physical goods, services, digital, consumables, bundles/kits, composite
- Product variants via attribute-value matrix (size × colour × material — any combo)
- Variant price and cost per combination
- Product images: multiple images, primary image, gallery, thumbnail auto-generation
- Barcode: EAN-8, EAN-13, UPC-A, Code128, QR — per product or per variant
- Internal reference / SKU
- Product tags and internal notes
- Product status: active, archived, discontinued
- Rich description with multi-language support (DB-stored translations)
- Product documents: spec sheets, MSDS, certificates (linked via Media module)

#### Product Categories
- Unlimited-depth hierarchical category tree
- Inherited default: accounts, tax, UOM per category (overridable per product)
- Category-level pricing rules

#### Units of Measure (UOM)
- UOM categories: weight, volume, length, area, time, count, custom
- Conversion factors between UOMs within a category
- Per-product: purchase UOM, sale UOM, inventory UOM (with auto-conversion)
- UOM enforcement on order lines (prevent mismatched UOM entry)

#### Warehouses & Locations
- Multi-warehouse management (each with address, responsible person)
- Hierarchical storage locations: Warehouse → Zone → Aisle → Rack → Level → Bin
- Location types: receive area, quality control, bulk storage, pick face, output, scrap, transit, virtual
- Virtual locations for accounting (stock input, stock output, valuation)
- Location capacity limits (optional, configurable)

#### Stock Movements
- Receipt movements (incoming from PO, manufacturing, transfer)
- Delivery movements (outgoing to customer, transfer out)
- Internal transfer (between locations/warehouses)
- Inventory adjustments (with reason codes: damage, theft, found, expiry write-off)
- Scrapping with automatic cost write-off journal
- Double-entry stock ledger: every movement has a corresponding counter-entry
- Movement reference linkage (PO number, SO number, MO number)
- Movement reversal (correction of posted movement)

#### Lot & Serial Number Tracking
- Lot-based tracking: batch/lot number, manufacture date, expiry date
- Serial number tracking: unique serial per individual unit
- Lot/serial assignment on receipt, transfer, delivery
- Full upstream/downstream traceability report per lot/serial
- Expiry date alerts (configurable days-before-expiry warning)
- Recall management: block movement for specific lot

#### Stock Valuation
- Costing methods (per product): FIFO, Weighted Average Cost (WAC), Standard Cost
- Automatic cost entry on every stock movement (integration with Accounting module)
- Standard cost variance tracking and journal
- Stock valuation report: by product, by location, at any date
- COGS calculation and automatic journal on delivery
- Inventory period closing

#### Reorder Rules
- Min/max reorder rules: reorder point, min qty, max qty per product per location
- Make-to-Order (MTO) rules: link demand to purchase/production
- Lead-time-aware: calculate earliest delivery date
- Automatic procurement suggestion (PO or MO) on scheduler run
- Safety stock calculation (statistical or manual)

#### Physical Inventory / Cycle Count
- Full physical inventory: freeze inventory → print count sheets → enter counts → post adjustments
- Cycle count scheduling: ABC classification (A=daily, B=weekly, C=monthly)
- Count sheet generation (PDF or mobile-optimised list)
- Count import (CSV upload)
- Variance review and approval before posting
- Recount for discrepant items

#### Barcode & QR Operations
- Barcode scan for: stock receipt, transfer, pick, ship, adjustment
- QR code generation for: products, lots, locations, pallets
- GS1-128 and GS1 DataMatrix support
- Mobile barcode scanner support (camera-based or Bluetooth scanner)
- Scan-to-validate operations (enforce scanning before posting)

---

## POINT OF SALE (POS) MODULE

### Module: POS
Touch-based point-of-sale for retail, restaurant, and service counters.

**Features:**

#### Session Management
- POS session open/close with opening and closing cash count
- Cash drawer management: opening float, petty cash, denomination breakdown
- Cashier assignment: PIN or password per cashier
- Multiple POS terminals per location (named, with IP/device binding)
- Session Z-report: daily sales summary (gross, discounts, taxes, payments by method)
- X-report: intra-day sales summary without closing session
- Session reconciliation: expected vs actual cash

#### Sales Interface
- Touch-optimised product grid with category tabs
- Product search (name, SKU, barcode)
- Barcode scanner integration (USB HID, Bluetooth, camera)
- Customer search and quick registration (name, phone, email)
- Customer loyalty balance display at order time
- Product price lookup and manual price override (permission-gated)
- Quantity entry (keypad and +/− buttons)
- Line-level discount entry (percentage or amount)
- Order notes and special instructions
- Product image display on POS grid (configurable)
- Favourites / quick-access product grid
- Hold and recall orders (park order, resume later)

#### Payment Processing
- Payment methods: cash, credit/debit card, bank transfer, cheque, credit note, gift card, loyalty points, store credit
- Split payment: multiple methods in one transaction
- Change calculation for cash payments
- Integrated card terminal adapters (Stripe Terminal, Adyen, SumUp — pluggable)
- Offline payment queue: transactions queued when offline, synced on reconnect
- Tip management (fixed amounts or percentage)
- Foreign currency acceptance with exchange rate

#### Receipts
- Configurable receipt templates (tenant-branded: logo, address, footer message)
- Thermal printer support (ESC/POS protocol)
- Browser-print PDF receipt
- Email receipt to customer
- QR code on receipt (links to digital receipt URL)
- Receipt reprint (last transaction or by order number)

#### Discounts & Promotions
- POS-specific discount rules (independent of price list)
- Coupon / voucher code redemption (single-use, multi-use, expiry date)
- Bundle / combo offers (buy X + Y, get Z free)
- Time-limited promotions (happy hour, daily specials)
- Automatic discount on qualifying cart (no code required)

#### Loyalty Program
- Points accrual rules: per currency unit spent, per product, per category
- Points redemption rules: points-to-discount value conversion
- Customer loyalty tier management (Bronze/Silver/Gold — configurable thresholds)
- Tier-based benefits (extra discount %, priority service, birthday bonus)
- Loyalty card (physical barcode / digital QR code)
- Points balance enquiry at POS

#### Inventory Integration
- Real-time stock deduction on completed sale
- Out-of-stock warning at product selection
- Low-stock alert badge on POS product grid
- Stock reservation on order hold

---

## ACCOUNTING MODULE

### Module: Accounting
Double-entry bookkeeping, financial statements, and tax management.

**Features:**

#### Chart of Accounts
- Hierarchical account tree: Asset, Liability, Equity, Revenue, Expense (GAAP/IFRS compliant structure)
- Account types: current asset, fixed asset, current liability, long-term liability, equity, income, COGS, expense, other
- Account tags (for grouping in financial reports)
- Tenant-configurable chart of accounts (from template or custom)
- Account-level: currency, active/inactive, tax mapping
- Restricted accounts (prevent manual journal entry)

#### Journals & Entries
- Double-entry bookkeeping enforced (debit total = credit total on every entry)
- Manual journal entry creation (with narration, reference, date)
- Journal entry approval workflow
- Automatic journals from: sales invoices, purchase bills, stock movements, payroll
- Recurring journal entries (daily/weekly/monthly with auto-post)
- Adjustment entries and reversals (auto-create reverse entry on next period)
- Journal lock by period (prevent backdating after period close)
- Journal reference numbering (sequential, per-journal configurable prefix)
- Bulk journal import (CSV/Excel)

#### Accounts Receivable (AR)
- Customer invoice creation: manual or auto-generated from sales order/delivery
- Invoice line items: product/service, qty, price, discount, tax
- Credit note creation linked to original invoice
- Customer payment recording and allocation (full/partial, against specific invoice)
- AR ageing report (current, 1–30, 31–60, 61–90, 90+ days)
- Dunning sequence: configurable reminder emails at day 7, 14, 30 overdue
- Customer statement of account (PDF, email)
- Advance payment handling (prepayments, deposits)
- Invoice PDF with tenant branding and QR/barcode reference

#### Accounts Payable (AP)
- Vendor bill management: create, approve, pay
- Three-way matching enforcement (PO ↔ GRN ↔ Bill)
- Vendor credit note handling and allocation
- Payment scheduling (due date calculation from terms)
- Batch payment run (select bills → generate bank transfer file: BACS/ACH/SEPA)
- AP ageing report
- Withholding tax on vendor payments
- Payment status: scheduled, paid, partially paid, overdue

#### Bank & Cash Management
- Bank account register (multiple accounts per tenant)
- Bank statement import (CSV, OFX/QFX, MT940/CAMT.053)
- Automated bank reconciliation: rule-based matching (amount + reference + date)
- Manual reconciliation with match/unmatch
- Outstanding items report (unmatched transactions)
- Cash journal (petty cash management)
- Interbank transfer accounting

#### Tax Management
- Tax types: VAT, GST, Sales Tax, Service Tax, Withholding Tax, Reverse Charge, Compound Tax
- Tax groups (apply multiple taxes as a group)
- Fiscal positions: map default taxes for specific customer/vendor locations
- Tax-inclusive and tax-exclusive pricing (configurable per price list)
- Multi-jurisdiction tax support (country + state/province level)
- Tax period closing and return preparation
- Tax reports: VAT return (EU), GST BAS (Australia), Sales Tax (US)
- Tax audit trail

#### Financial Statements
- Profit & Loss (Income Statement): by period, by cost centre, with comparison
- Balance Sheet: as at any date, with prior period comparison
- Cash Flow Statement (indirect method)
- Trial Balance: by account, by date range
- General Ledger: full transaction detail per account
- Statement of Changes in Equity
- Consolidated statements across branches (optional)
- Custom financial report builder (drag-and-drop rows/columns)
- Export: PDF, Excel, CSV

#### Budgeting
- Budget creation by account, cost centre, and period
- Budget templates (copy prior year actuals as budget)
- Budget approval workflow
- Budget vs actual variance analysis (real-time)
- Departmental / cost-centre budget allocation
- Budget alert on overspend (configurable threshold)

#### Fixed Assets
- Asset master: category, acquisition date, cost, depreciation method, useful life
- Depreciation methods: straight-line, declining balance, units of production
- Automatic depreciation journal posting (scheduled job)
- Asset revaluation and impairment write-down
- Asset disposal: sale or write-off with gain/loss journal
- Asset register report

#### Expense Management
- Employee expense claim submission
- Expense categories with per-diems and mileage rates (DB-driven)
- Expense policy enforcement (max amount per category)
- Mileage tracking (km/miles × rate)
- Receipt attachment (via Media module)
- Multi-level approval workflow
- Expense-to-vendor-bill automatic posting
- Expense report (by employee, by category, by period)

#### Multi-Currency
- Currency master with ISO codes and symbols
- Exchange rate management: manual entry or external API (ECB, Open Exchange Rates)
- Unrealised FX gain/loss revaluation (period-end job)
- Realised FX gain/loss on settlement
- Multi-currency reports with functional currency translation
- Currency rounding rules per currency

#### Payroll Integration
- Payroll period management (weekly, bi-weekly, monthly)
- Salary components: basic, allowances (HRA, transport), deductions (PF, tax), net pay
- Payslip generation (PDF)
- Payroll journal posting to accounting (debit: salary expense, credit: payables/bank)
- Payroll cost-centre allocation

---

## HR MODULE

### Module: HR
Human resource management, payroll, and workforce planning.

**Features:**

#### Employee Management
- Employee master: personal info (name, DOB, gender, nationality, marital status)
- Contact details: address, personal email, personal phone, emergency contact
- Employment details: join date, designation, department, grade, employment type
- Employment types: full-time, part-time, contractor, intern, probation
- Employee status: active, on-leave, probation, suspended, terminated
- Employee lifecycle management: hire, transfer, promote, demotion, resign, terminate
- Employee documents: contracts, ID copies, certificates, performance reviews (via Media)
- Organisation hierarchy view (org chart)

#### Departments & Positions
- Department tree structure (unlimited depth)
- Job title / position master with grade bands
- Headcount plan vs actual per department
- Position vacancy tracking

#### Attendance & Time Tracking
- Clock-in / clock-out (manual, biometric adapter, mobile GPS)
- Attendance sheet: daily status per employee (present, absent, half-day, late, on-leave)
- Late arrival and early departure tracking
- Overtime calculation (configurable rules: daily OT after 8h, weekly after 40h)
- Shift management: shift types, shift patterns, employee shift assignments
- Roster planning (drag-and-drop schedule board)
- Integration with payroll (attendance feeds into pay calculation)

#### Leave Management
- Leave types: annual, sick, maternity, paternity, emergency, unpaid, compensatory — DB-driven, tenant-configurable
- Leave balance accrual rules: per month, per year, on anniversary — configurable
- Leave request and multi-level approval workflow
- Public holiday calendar (per-country, per-tenant override)
- Leave encashment calculation
- Leave balance carry-forward rules (max carry-forward, expiry)
- Leave entitlement proration (for mid-year joiners)
- Leave calendar view (team and individual)

#### Payroll
- Payroll run for a period (close attendance, calculate salaries, review, approve, post)
- Salary structure templates: components, formulae (configurable, not hardcoded)
- Statutory deductions: income tax, social security, provident fund — country-configurable
- Configurable pay-grade salary tables
- Payslip generation (PDF per employee)
- Bank transfer file generation (CSV/BACS/ACH)
- Payroll cost-centre allocation
- Year-end payroll reports (P60, Form 16 — pluggable per jurisdiction)

#### Recruitment
- Job requisition creation and approval
- Job posting: internal notice board + external career portal API
- Application intake (web form or API)
- Application tracking kanban: applied → screening → interview → offer → hired
- Interview scheduling (calendar event creation, interviewer notification)
- Interview feedback form (structured scoring)
- Offer letter generation (from DB template)
- Onboarding checklist (tasks assigned to HR, IT, manager, new joiner)

#### Performance Management
- KPI / OKR definition (goal-setting per employee per period)
- Performance review cycles (annual, bi-annual, quarterly — configurable)
- 360-degree feedback (peer, subordinate, manager, self-assessment)
- Competency framework (skill matrix)
- Training and development tracking
- Performance improvement plan (PIP) management

---

## PROJECT MODULE

### Module: Project
Project and task management, time tracking, and billing.

**Features:**

#### Projects
- Project creation: name, description, client, project manager, team, start/end date
- Project templates (reusable blueprints with pre-set tasks and milestones)
- Project status: not started, in progress, on hold, completed, cancelled
- Project health indicators: on-track, at-risk, off-track
- Project-level billing types: time & material, fixed price, milestone-based, retainer
- Project budget definition and tracking (budget vs actuals)
- Project colour coding and tags

#### Tasks
- Task creation: title, description, assignee(s), priority (critical/high/medium/low), due date
- Views: kanban board, list, calendar, Gantt
- Sub-tasks and checklists (progress bars)
- Task dependencies (finish-to-start, start-to-start, etc.)
- Task labels, tags, and custom fields (DB-driven)
- File attachments per task (via Media module)
- Task comments and @mentions
- Task watchers (subscribe to updates)
- Recurring tasks (daily/weekly/monthly)
- Task time estimate vs actual tracking

#### Gantt & Scheduling
- Interactive Gantt chart with drag-and-drop task scheduling
- Resource levelling (detect over-allocation)
- Critical path highlighting
- Baseline comparison (original plan vs current plan)
- Milestone markers on Gantt

#### Time Tracking
- Manual timesheet entry (project, task, date, hours, description)
- Timer-based tracking (start/stop clock)
- Timesheet approval workflow (employee → manager → billing)
- Billable vs non-billable hour classification
- Integration with invoicing (auto-generate invoice from approved billable hours)
- Weekly timesheet view per employee

#### Milestones & Deliverables
- Milestone definition (name, due date, billing amount if milestone-billed)
- Milestone completion sign-off
- Client portal view for milestone approval
- Milestone-based invoice trigger

#### Project Reports
- Burn-down and burn-up charts
- Budget vs actual cost
- Resource utilisation per team member
- Project profitability (revenue − labour cost − expenses)
- Overdue task list
- Time log summary by project, task, employee

---

## MANUFACTURING MODULE

### Module: Manufacturing
Production planning, BOM, work orders, and MRP.

**Features:**

#### Bill of Materials (BOM)
- Multi-level BOM (sub-assemblies within assemblies)
- BOM versioning with effectivity dates
- Phantom (virtual) assemblies (explode sub-assembly at MO creation)
- Yield factors and scrap percentage per component
- By-product definition
- BOM cost roll-up (calculated standard cost from components + labour)
- BOM comparison across versions

#### Manufacturing Orders (MO)
- MO creation: from sales demand (MTO), from stock replenishment, manual
- MO status: Draft → Confirmed → In Progress → Done → Cancelled
- Component availability check and reservation on confirmation
- Finished goods posting to stock on completion
- Over/under production recording
- MO splitting and merging
- MO lot/serial number assignment for finished product

#### Work Orders & Work Centres
- Work centre master: name, capacity (units/hour), efficiency %, cost per hour
- Operation routing: sequence of work centres per BOM
- Work order time tracking (planned vs actual)
- Work order status: waiting, in progress, done
- Work centre loading calendar
- Scrap and rework recording at work order level

#### Production Planning
- MRP (Material Requirements Planning) run: demand → BOM explosion → procurement suggestions
- Capacity requirements planning (CRP)
- Production schedule board (timeline view of MOs by work centre)
- Make-to-stock (MTS) replenishment rules
- Make-to-order (MTO) demand-linked MOs

---

## E-COMMERCE MODULE

### Module: Ecommerce
Headless storefront API, cart, checkout, and order management.

**Features:**

#### Headless Storefront API
- Product catalogue API: list, search (full-text + filters), facets (category, price range, attributes)
- Product detail: variants, images, pricing (customer-aware, price-list resolved), stock status
- Related products and cross-sell/up-sell
- Category tree navigation API

#### Cart & Checkout API
- Session-based cart (guest) and user-based cart (authenticated)
- Add/remove/update cart lines
- Cart price recalculation (discounts, promotions, tax)
- Checkout: address entry, shipping method selection, payment method selection
- Order placement with idempotency key
- Order confirmation with reference number

#### Order Management
- E-commerce order intake and automatic SO creation in Sales module (via event)
- Order status API for storefront polling
- Outbound order status webhook (paid, shipped, delivered, cancelled)
- Order cancellation and automated refund initiation
- Digital goods: download link generation on payment confirmation

#### Payment Gateway Integration
- Pluggable gateway adapters: Stripe, PayPal, Razorpay, Braintree
- PCI-DSS compliant: no raw card data stored (tokenisation only)
- Webhook handler: verify signature, idempotent event processing
- Refund initiation via gateway API

#### Shipping Integration
- Pluggable shipping adapters: DHL, FedEx, UPS, local courier
- Real-time shipping rate calculation at checkout
- Shipping label generation
- Tracking number storage and polling
- Delivery status webhook to order record

---

## REPORTING & ANALYTICS MODULE

### Module: Reporting
Configurable dashboards, custom report builder, and standard reports.

**Features:**

#### Dashboards
- Role-based, configurable widget dashboards (drag-and-drop layout)
- Widget types: KPI card, bar chart, line chart, pie chart, funnel, data table, text
- Available KPIs: revenue (today/MTD/YTD), orders, outstanding AR/AP, stock value, open tickets, leads in pipeline
- Date range selector (today, this week, this month, custom range)
- Drill-down from widget to underlying report
- Real-time refresh via polling (configurable interval)
- Dashboard sharing and public embed link

#### Custom Report Builder
- Drag-and-drop report builder: select data source, fields, filters, group-by, sort, aggregates
- Calculated fields (formula-based)
- Scheduled report delivery: email PDF/CSV on cron schedule
- Export formats: PDF, CSV, Excel (.xlsx), JSON
- Saved reports library (per user and shared)

#### Standard Reports Library
- **Sales:** sales by product, customer, rep, category, period, region; margin analysis
- **Purchase:** PO by vendor, product, period; purchase spend analysis
- **Inventory:** stock on hand, stock valuation, stock movement history, expiry report, reorder report
- **Accounting:** P&L, Balance Sheet, Trial Balance, General Ledger, AR ageing, AP ageing, Cash Flow
- **HR:** headcount by department, attendance summary, leave balance, payroll register
- **POS:** Z-report, cashier sales summary, payment method breakdown, top products
- **CRM:** pipeline by stage, conversion rate, lead source analysis, activity completion rate
- **Project:** project status overview, resource utilisation, billable hours, project profitability

---

## WORKFLOW ENGINE MODULE

### Module: Workflow
Configurable state machines and approval chains for all document types.

**Features:**
- Configurable workflow states per document type (defined in DB, not hardcoded)
- State machine with guard conditions (who can transition, under what conditions)
- Action triggers on state transition (emit event → notify, create task, post journal, send email)
- Approval chains: sequential (A then B then C), parallel (A and B simultaneously), any-of (A or B)
- Escalation rules: auto-escalate if no action within N hours (configurable SLA timer)
- Conditional branching (if amount > 10000, route to CFO)
- Workflow history per document (who did what, when)
- Visual workflow designer (drag-and-drop state diagram)
- Workflow templates (reusable across document types)

---

## INTEGRATION / API MODULE

### Module: Integration
External integrations, REST API, webhooks, and OAuth.

**Features:**
- Full public REST API (versioned at /api/v1/, documented via OpenAPI 3.0 / Swagger UI)
- Consistent JSON response envelope: `{ data, meta, errors, status }`
- Idempotency key support on all write endpoints (X-Idempotency-Key header)
- Pagination on all list endpoints (cursor-based and offset-based)
- Outbound webhook delivery: configurable events, endpoint URL, signing secret, retry with exponential back-off
- Webhook delivery log (attempt, status code, response body, retry count)
- Inbound webhook endpoint: signature verification, idempotent processing
- OAuth 2.0 client credentials grant for M2M integrations
- API key management: scoped permissions, expiry, rotation, revocation
- All API calls logged with: user/key, endpoint, method, response code, duration
- Rate limiting per API key and per tenant (configurable)
- SDK-friendly: HATEOAS links, consistent error codes
- Third-party integrations (pluggable adapters): Slack, Teams, Zapier webhook, QuickBooks, Xero

---

## LOCALISATION MODULE

### Module: Localisation
Multi-language, multi-timezone, multi-currency display, and regional compliance.

**Features:**
- Multi-language UI: all user-visible strings translatable, translations stored in DB + lang files
- RTL language support (Arabic, Hebrew, Urdu)
- Per-tenant language packs (upload custom translations)
- Per-user locale preference (overrides tenant default)
- Multi-timezone: all datetimes stored in UTC, displayed in user/tenant timezone
- Date and time format localisation (DD/MM/YYYY, MM/DD/YYYY, ISO 8601)
- Number format localisation (1,234.56 vs 1.234,56 vs 1 234,56)
- Multi-currency display with configurable decimal places per currency
- Live exchange rate widget
- Unit system: metric or imperial (configurable per tenant)
- Country-specific: tax logic, statutory deductions, public holidays — pluggable country modules

---

## COMMUNICATION MODULE

### Module: Communication
Internal messaging, email inbox, and multi-channel communication.

**Features:**
- Internal team messaging: threaded conversations, @mentions, reactions
- File sharing in messages (via Media module)
- Channel / group chat creation
- Direct messages between users
- Email inbox integration: IMAP polling, parse inbound emails, route to CRM/Help Desk
- Outbound email composition from any record (customer, vendor, employee)
- Email thread tracking (link email replies to original CRM record)
- SMS send from any record (via configured SMS provider)
- Communication log per customer/vendor/employee
- Scheduled / queued message sending

---

## HELP DESK / SUPPORT MODULE

### Module: HelpDesk
Customer support ticketing, SLA management, and knowledge base.

**Features:**

#### Tickets
- Ticket creation: customer portal, inbound email, REST API, manual by agent
- Ticket fields: subject, description, category, priority (low/medium/high/urgent), channel
- File attachments (via Media module)
- Ticket assignment: manual, round-robin, skill-based routing — configurable
- Escalation rules (auto-reassign if SLA breached)
- Internal notes (visible to agents only) vs customer replies
- Ticket status workflow: New → Open → Pending → Resolved → Closed (configurable)
- Ticket merge (combine duplicate tickets)
- Ticket re-open policy (within N days of closing)
- Bulk actions (assign, close, tag multiple tickets)

#### SLA Management
- SLA policies: first response time, resolution time per priority
- SLA timers (pause on pending-customer status)
- SLA breach alerts to supervisor
- SLA compliance report

#### Knowledge Base
- Article creation with rich text editor
- Article categories and tags
- Article visibility: public, agents-only, customers-only
- Article search (full-text)
- Article helpful/not-helpful rating
- Suggested articles during ticket creation
- Knowledge base portal (public-facing)

#### Satisfaction & Analytics
- CSAT survey sent on ticket close (configurable)
- CSAT score tracking per agent and overall
- Ticket volume, resolution time, first-contact resolution rate dashboards
- Agent performance reports

---

## ASSET MANAGEMENT MODULE

### Module: AssetManagement
IT asset, equipment, and fixed asset tracking.

**Features:**
- Asset register: IT assets, equipment, furniture, vehicles, intangibles
- Asset master: category, purchase date, purchase cost, vendor, location, assigned-to
- Asset assignment to employee or physical location
- Asset transfer (reassign to different employee/location with audit)
- Maintenance scheduling: preventive maintenance tasks with recurrence
- Maintenance work order creation and completion recording
- Asset depreciation tracking (linked to Accounting fixed asset module)
- Asset disposal (sale, write-off, donation) with approval
- QR/barcode label generation per asset
- Asset audit / physical verification
- Asset utilisation and warranty expiry reports

---

## MODULE DEPENDENCY MAP

All module dependencies must be satisfied through domain Contracts and Events — never direct class coupling.

```
Auth           ← Tenant, User
Tenant         ← (none)
User           ← Tenant
Setting        ← Tenant
Audit          ← Tenant, User
Notification   ← Tenant, User
Media          ← Tenant, User
CRM            ← Tenant, User, Setting, Notification
Sales          ← CRM, Product, Inventory, Accounting, Notification
Purchase       ← Product, Inventory, Accounting, Notification
Inventory      ← Product, Tenant, Setting
Product        ← Tenant, Setting, Media
POS            ← Product, Inventory, Accounting, CRM, Notification
Accounting     ← Tenant, Setting, Notification
HR             ← Tenant, User, Setting, Notification, Media
Project        ← Tenant, User, Accounting, Notification
Manufacturing  ← Product, Inventory, Setting
Ecommerce      ← Product, Sales, Inventory, Accounting
Reporting      ← all modules (read-only Contracts only)
Workflow       ← Tenant, Setting, Notification
Integration    ← Auth, Tenant
Localisation   ← Tenant, Setting
Communication  ← Tenant, User, Media
HelpDesk       ← Tenant, User, CRM, Notification, Media
AssetManagement← Tenant, User, Accounting, Media
```
