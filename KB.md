# KB.md — Enterprise ERP/CRM SaaS Platform Knowledge Base

Version: 2.0
Scope: Entire Repository
Status: Authoritative Reference — Strictly Enforced

---

# TABLE OF CONTENTS

1. [System Mission & Purpose](#1-system-mission--purpose)
2. [ERP Foundational Principles](#2-erp-foundational-principles)
3. [Core Architecture](#3-core-architecture)
4. [Multi-Tenancy](#4-multi-tenancy)
5. [Authorization Model](#5-authorization-model)
6. [Metadata-Driven Core](#6-metadata-driven-core)
7. [Product Domain](#7-product-domain)
8. [Multi-UOM Design](#8-multi-uom-design)
9. [Pricing & Discounts](#9-pricing--discounts)
10. [Inventory Management System (IMS)](#10-inventory-management-system-ims)
11. [Pharmaceutical Inventory Management](#11-pharmaceutical-inventory-management)
12. [Warehouse Management System (WMS)](#12-warehouse-management-system-wms)
13. [Sales & POS](#13-sales--pos)
14. [Accounting](#14-accounting)
15. [CRM](#15-crm)
16. [Procurement](#16-procurement)
17. [Workflow Engine](#17-workflow-engine)
18. [Customization & Rule Engine](#18-customization--rule-engine)
19. [SaaS Architecture](#19-saas-architecture)
20. [Organizational Hierarchy](#20-organizational-hierarchy)
21. [API Design](#21-api-design)
22. [Frontend Architecture](#22-frontend-architecture)
23. [Security](#23-security)
24. [Data Integrity & Concurrency](#24-data-integrity--concurrency)
25. [Performance & Scalability](#25-performance--scalability)
26. [Audit & Compliance](#26-audit--compliance)
27. [Enterprise Reporting](#27-enterprise-reporting)
28. [Plugin Marketplace](#28-plugin-marketplace)
29. [Reusability Principles](#29-reusability-principles)
30. [Testing Requirements](#30-testing-requirements)
31. [Prohibited Practices](#31-prohibited-practices)
32. [Definition of Done](#32-definition-of-done)
33. [System Guarantees](#33-system-guarantees)
34. [Business Objectives](#34-business-objectives)
35. [Autonomous Agent Execution Rules](#35-autonomous-agent-execution-rules)
36. [Implementation Tracking](#36-implementation-tracking)
37. [Strategic Design Goal](#37-strategic-design-goal)
38. [References](#38-references)

---

# 1. System Mission & Purpose

Build a **production-grade, enterprise, multi-tenant, modular ERP/CRM SaaS platform** using:

- Laravel (LTS only)
- React (LTS only)
- Native framework capabilities first
- No unstable or experimental dependencies

The platform covers:

- Inventory Management
- Pharmaceutical Inventory Management
- Warehouse Management (WMS)
- Sales & Point of Sale (POS)
- Accounting & Finance
- CRM (Customer Relationship Management)
- Procurement
- ERP/CRM Integration
- Human Resources (optional extension)

The system must be:

- Modular Monolith (plugin-ready)
- Fully metadata-driven
- API-first
- Stateless
- Horizontally scalable
- Vertically scalable
- Financially precise
- Strictly tenant-isolated
- Replaceable per module
- Zero architectural debt tolerance

---

# 2. ERP Foundational Principles

An ERP (Enterprise Resource Planning) system integrates:

- Finance
- Inventory
- Sales
- Procurement
- CRM
- Operations
- Human Resources (optional extension)

Core ERP characteristics:

- Single source of truth
- Transaction-driven
- Financially reconcilable
- Cross-module traceable
- Audit-safe

---

# 3. Core Architecture

## 3.1 Governing Principles

- SOLID principles (Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion)
- DRY — no duplication of business logic
- KISS — minimal complexity
- Explicit domain boundaries
- Clear separation of concerns
- Immutable financial calculations
- Deterministic behavior

## 3.2 Mandatory Rules

- Controllers contain no business logic.
- Controllers never use query builder directly.
- No circular dependencies.
- Domain logic isolated from infrastructure.
- Module boundaries strictly respected.
- Cross-module communication via contracts/events only.

## 3.3 Application Flow (Mandatory)

Every feature must follow:

```
Controller → Service → Handler (Pipeline) → Repository → Entity
```

### Layer Responsibilities

**Controller**
- Input validation
- Authorization
- Response formatting
- No business logic

**Service**
- Orchestrates use cases
- Defines transaction boundaries
- Calls handlers/pipelines

**Handler (Pipeline)**
- Single-responsibility processing steps
- Transformations
- Domain rules
- Reusable logic units

**Repository**
- Data access only
- No domain logic
- Tenant-aware queries

**Entity**
- Pure domain model
- Relationships
- Attribute casting
- No orchestration logic

## 3.4 Module Structure (Per Module)

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

# 4. Multi-Tenancy

## 4.1 Hierarchy Model

```
Tenant
 └── Organisation
      └── Branch
           └── Location
                └── Department
```

## 4.2 Multi-Tenant Models

| Model | Description | Trade-offs |
|---|---|---|
| Shared DB, Shared Schema | All tenants in one schema with row-level isolation via `tenant_id` | Lowest cost, simplest ops; requires strict global scope enforcement |
| Shared DB, Separate Schema | Each tenant gets its own schema within one database | Better isolation; schema migrations per tenant can be complex |
| Separate DB per Tenant | Dedicated database per tenant | Maximum isolation and performance; highest operational overhead |

Recommended default: Shared DB + Strict Row-Level Isolation + Optional DB-per-tenant upgrade path.

## 4.3 Mandatory Enforcement

- `tenant_id` on every business table
- Global scope enforcement
- Tenant-scoped cache
- Tenant-scoped queues
- Tenant-scoped storage
- Tenant-scoped configs
- JWT per user × device × organisation
- Stateless authentication
- Tenant resolution via:
  - Subdomain
  - Header
  - JWT claim

Failure to isolate tenants = Critical Violation.

---

# 5. Authorization Model

Hybrid enforcement:

- RBAC (roles & permissions)
- ABAC (policy-based rules)
- Multi-guard
- Scoped API keys
- Tenant-level feature flags
- Feature-level gating

Rules:

- Policy classes only.
- No permission logic in controllers.
- No hardcoded role checks.
- Unauthorized cross-tenant access is strictly prohibited.
- Dynamic middleware for permission enforcement.
- Tenant-scoped permissions enforced at repository layer.

---

# 6. Metadata-Driven Core

All configurable logic must be:

- Database-driven
- Enum-controlled
- Runtime-resolvable
- Replaceable without deployment

Includes:

- Dynamic forms & custom fields
- Validation rules & conditional visibility
- Computed fields
- Workflow states
- Approval chains
- Pricing rules
- Tax rules
- Notification templates
- UI layout definitions
- Feature toggles

Hardcoded business rules are prohibited.

---

# 7. Product Domain

## 7.1 Supported Product Types

- Physical (Stockable)
- Consumable
- Service
- Digital
- Bundle (Kit)
- Composite (Manufactured)
- Variant-based

## 7.2 Key Concepts

- SKU
- UOM (Unit of Measure) with conversion matrix
- Costing method (FIFO / LIFO / Weighted Average)
- Valuation method
- Traceability (Serial / Batch / Lot) — optional by default; mandatory when pharmaceutical compliance mode is enabled
- GS1 compatibility — optional enterprise feature
- Multi-image management (0..n)
- Multi-location pricing
- Multi-currency pricing

## 7.3 Mandatory Capabilities

- Optional traceability (Serial / Batch / Lot; mandatory in pharmaceutical mode)
- Optional Barcode / QR
- Optional GS1 compatibility
- 0..n images per product
- Required base UOM (`uom`)
- Optional buying UOM
- Optional selling UOM
- UOM conversion matrix
- Multi-location pricing
- Multi-currency pricing
- Tiered pricing
- Rule-based pricing engine
- Fully traceable inventory flow

## 7.4 Financial Rules

- Arbitrary precision decimals only (BCMath or equivalent)
- Minimum 4 decimal places
- Intermediate calculations (further divided or multiplied before final rounding): 8+ decimal places
- Final monetary values: rounded to the currency's standard precision (typically 2 decimal places)
- Floating-point arithmetic strictly forbidden
- Tax inclusive/exclusive support
- Double-entry bookkeeping compatibility

---

# 8. Multi-UOM Design

## 8.1 UOM Structure

Each product supports:

- `uom` → Base inventory tracking unit (required)
- `buying_uom` → Purchasing unit (optional; fallback to `uom`)
- `selling_uom` → Sales unit (optional; fallback to `uom`)

## 8.2 UOM Conversions

`uom_conversions` table fields:

- `product_id`
- `from_uom`
- `to_uom`
- `factor`

Example: 1 box = 12 pcs

## 8.3 Conversion Rules

- Direct path: `from_uom` → `to_uom`
- Inverse path: reciprocal calculation (`to_uom` → `from_uom`)
- Product-specific conversion factors
- No global assumptions
- No implicit conversion

## 8.4 Arithmetic Precision

- All calculations use BCMath
- Precision: 4 decimal places minimum
- Intermediate calculations (further divided or multiplied before final rounding): 8+ decimal places
- Final monetary values: rounded to the currency's standard precision (typically 2 decimal places)
- No floating-point arithmetic permitted
- Deterministic and reversible

---

# 9. Pricing & Discounts

Buying price, selling price, purchase discount, and sales discount may vary by:

- Location
- Batch
- Lot
- Date range
- Customer tier
- Minimum quantity

Discount formats:

- Flat (fixed) amount
- Percentage

All prices and discounts must use BCMath; no floating-point arithmetic.

---

# 10. Inventory Management System (IMS)

A modern Inventory Management System serves as a central hub for tracking, organizing, and optimizing company stock across the entire supply chain. It streamlines stock tracking using real-time data, barcode scanning, automation, and multi-channel synchronization to optimize stock levels and reduce operational costs.

## 10.1 Inventory is Ledger-Driven

- Stock is never edited directly.
- All changes occur via transactions.
- Reservation precedes deduction.
- Deduction must be atomic.
- Historical entries are immutable.

## 10.2 Critical Flows

- Purchase Receipt
- Sales Shipment
- Internal Transfer
- Adjustment
- Return

## 10.3 Core Capabilities

### 10.3.1 Real-Time Inventory Tracking
- Instant visibility into current stock quantities and physical locations
- Automatic updates as items move through: receiving → storage → picking → packing → shipping → final sale
- Continuous monitoring across warehouses and stores

### 10.3.2 Automated Reordering & Alerts
- Predefined reorder points
- Automatic purchase order generation
- Low-stock notifications
- Prevention of stockouts and overstocking
- Historical consumption-based forecasting

### 10.3.3 Centralized Order Management
- Consolidates multi-channel sales (e-commerce, POS, marketplaces)
- Unified dashboard with consistent stock counts across platforms
- Full order lifecycle tracking: order received → picking → packing → shipping → delivery

### 10.3.4 Multi-Location & Multi-Channel Management
- Synchronization across multiple warehouses, retail stores, and distribution centers
- Seamless inter-location stock transfers
- Channel consistency (website, POS, marketplaces)

### 10.3.5 Barcode & RFID Scanning
- Digital scanning via Barcode, QR Code, RFID
- Reduces manual entry errors
- Accelerates receiving, picking, packing, and shipping
- GS1-compatible standards support

### 10.3.6 Demand Forecasting
- Historical sales analysis
- Seasonal trend modeling
- Market-based projections
- Optimization of stock levels

### 10.3.7 Traceability (Lot, Batch & Serial Tracking)
- Track by lot number, batch number, or serial number
- Manage expiry dates, product recalls, and quality control
- Critical for pharmaceutical, food & beverage, and high-value electronics

### 10.3.8 Supplier & Vendor Management
- Centralized vendor records
- Lead time and performance tracking
- Product–vendor mapping
- Reordering workflows

### 10.3.9 Inventory Optimization (ABC Analysis)
- Categorization by value and turnover rate:
  - High-value (A): prioritized resource allocation
  - Medium-value (B): balanced management
  - Low-value (C): minimal oversight
- Resource allocation efficiency

### 10.3.10 Cycle Counting
- Partial inventory audits (scheduled subset counting)
- Maintains high accuracy with minimal operational disruption
- Alternative to full-scale audits

### 10.3.11 Reporting & Analytics
- KPIs: inventory turnover, carrying cost, order cycle time, profitability per product, labor efficiency, inventory accuracy
- Data-driven forecasting and strategic planning

### 10.3.12 Third-Party Integrations
- Accounting systems (e.g., QuickBooks)
- E-commerce platforms (e.g., Shopify, WooCommerce)
- ERP systems
- POS systems

### 10.3.13 Cloud & Mobile Accessibility
- Smartphone and tablet support
- Warehouse floor operations
- Remote access and 24/7 secure availability

## 10.4 Additional Capabilities

- Multi-warehouse
- Multi-bin locations
- Stock ledger
- FIFO / LIFO / Weighted Average costing
- Reservations
- Transfers
- Adjustments
- Cycle counting
- Expiry tracking
- Damage handling
- Reorder rules
- Procurement suggestions
- Backorders
- Drop-shipping

## 10.5 Concurrency Controls

- Pessimistic locking for stock deduction
- Optimistic locking for updates
- Atomic stock transactions
- Idempotent stock APIs

---

# 11. Pharmaceutical Inventory Management

A pharmaceutical inventory system extends standard inventory management with regulatory, compliance, and safety-focused functionality.

Primary goals: Compliance, Efficiency, Safety, Traceability.

## 11.1 Core Inventory & Real-Time Control

- Real-time multi-branch visibility
- Barcode & RFID scanning
- Inter-branch transfers

## 11.2 Expiry Control & FEFO

- First-Expired, First-Out (FEFO) strategy
- Expiry date tracking and alerts
- Expired product quarantine workflows
- Waste minimization

## 11.3 Lot & Batch Traceability

- Mandatory traceability
- Full recall management support
- Quality assurance compliance
- Drug serial number tracking

## 11.4 High-Risk Medication Monitoring

- Flag expensive drugs
- Controlled substances tracking
- Low-demand monitoring
- Restricted access controls
- Enhanced security for controlled substances

## 11.5 Automated Reordering & Demand Forecasting

- Threshold-based purchase order generation
- Seasonal consumption analysis
- Drug shortage prevention

## 11.6 Regulatory Compliance & Security

Compliance frameworks:
- FDA
- DEA
- DSCSA (Drug Supply Chain Security Act)

Security:
- Tamper-proof transaction logs
- Full user activity history
- Audit-ready reporting
- Expiry override logging
- High-risk medication access logging

## 11.7 Integration Capabilities

- EHR / EMR systems
- POS systems
- Billing platforms
- ERP systems

## 11.8 Pharmaceutical Compliance Mode

When pharmaceutical compliance mode is enabled:

- Lot tracking is mandatory
- Expiry date is mandatory
- FEFO is enforced
- Serial tracking required where applicable
- Audit trail cannot be disabled
- Regulatory reports must be available (FDA / DEA / DSCSA aligned)
- Quarantine workflows for expired or recalled items are enforced
- Expiry override logging and high-risk medication access logging are required

Compliance is NOT optional.

---

# 12. Warehouse Management System (WMS)

A WMS optimizes warehouse operations through real-time inventory tracking, efficient order fulfillment (picking/packing/shipping), accurate labor management, and integration with ERP and e-commerce platforms to reduce errors and improve efficiency.

## 12.1 Real-Time Inventory Visibility

- Bin-level and location-level tracking
- Movement history logs
- Accurate up-to-the-minute data on stock levels and locations

## 12.2 Receiving & Putaway

- Automated receiving
- Intelligent storage location suggestion based on:
  - Turnover rate
  - Size
  - Space availability

## 12.3 Order Picking & Packing Optimization

- Optimized route generation
- Picking strategies:
  - Batch picking
  - Wave picking
  - Zone picking
- Reduced travel time
- Packing validation

## 12.4 Labor Management

- Productivity tracking
- Skill-based task assignment
- Proximity-based task allocation
- Performance metrics

## 12.5 Warehouse Layout Optimization

- Movement pattern analysis
- Storage reconfiguration for efficiency
- Travel distance reduction

## 12.6 Returns Management (Reverse Logistics)

- Return inspection
- Restocking workflows
- Damage classification
- Credit processing

## 12.7 Integration Capabilities

- ERP systems
- Transportation systems
- E-commerce platforms

## 12.8 Reporting & KPIs

- Inventory accuracy
- Order cycle time
- Labor efficiency
- Expiry risk analysis (pharmaceutical context)

---

# 13. Sales & POS

## 13.1 Sales Flow

```
Quotation → Sales Order → Delivery → Invoice → Payment
```

## 13.2 Capabilities

- POS terminal mode
- Offline-ready sync design
- Draft / Hold receipts
- Split payments
- Refund handling
- Backorders
- Cash drawer tracking
- Receipt templating
- Commission engine
- Rule-based discount engine
- Tax calculation
- Loyalty system
- Gift cards
- Coupons
- E-commerce API compatibility

## 13.3 POS Requirements

- Offline-first design
- Local transaction queue
- Sync reconciliation engine

---

# 14. Accounting

## 14.1 Mandatory Capabilities

- Double-entry bookkeeping
- Chart of accounts per tenant
- Journal entries
- Auto-posting rules
- Tax engine
- Fiscal periods
- Trial balance
- Profit & Loss
- Balance sheet
- Immutable audit trail

## 14.2 Double-Entry Rule

Every transaction must:

- Debit one account
- Credit another account
- Total Debits = Total Credits

## 14.3 Financial Integrity Rules

- No floating-point arithmetic
- Arbitrary precision decimals only (BCMath)
- Minimum 4 decimal places
- Intermediate calculations (further divided or multiplied before final rounding): 8+ decimal places
- Final monetary values: rounded to the currency's standard precision (typically 2 decimal places)
- Deterministic rounding
- Immutable journal entries

Accounting must reconcile with inventory valuation.
Financial integrity cannot be bypassed.

---

# 15. CRM

## 15.1 CRM Pipeline

```
Lead → Opportunity → Proposal → Closed Won / Closed Lost
```

## 15.2 Capabilities

- Leads
- Opportunities
- Pipeline stages
- Activities
- Campaign tracking
- Campaign attribution
- Email integration
- SLA tracking & timers
- Notes & attachments
- Customer segmentation

---

# 16. Procurement

## 16.1 Procurement Flow

```
Purchase Request → RFQ → Vendor Selection → Purchase Order → Goods Receipt → Vendor Bill → Payment
```

## 16.2 Capabilities

- Purchase requests
- RFQ (Request for Quotation)
- Vendor comparison
- Purchase orders
- Goods receipt
- Three-way matching (PO, Receipt, Invoice)
- Vendor bills
- Vendor scoring
- Price comparison logic

---

# 17. Workflow Engine

## 17.1 State Machine Model

```
State → Event → Transition → Guard → Action
```

## 17.2 Must Support

- State machine flows
- Approval chains
- Escalation rules
- SLA enforcement
- Event-based triggers
- Background jobs
- Scheduled tasks

No hardcoded approval logic.

---

# 18. Customization & Rule Engine

Enterprise SaaS ERP must be customizable without redeployment.

## 18.1 Metadata-Driven Schema

- Custom fields
- Validation rules
- Dynamic forms
- Conditional visibility
- Computed fields

## 18.2 Declarative Rule Engine

Rule pattern:

```
IF condition
THEN action
```

Used for:

- Pricing
- Discounts
- Taxes
- Commissions
- Inventory reservation logic

---

# 19. SaaS Architecture

## 19.1 Definition

SaaS (Software-as-a-Service) is a cloud-based architecture where a single application instance serves multiple tenants via the internet, focusing on scalability, cost-efficiency, and centralized management.

## 19.2 Multi-Tenancy Models

### Multi-Tenant (Default)
- Shared infrastructure
- Single application version
- Cost-effective model
- Requires strict tenant data isolation

### Single-Tenant
- Dedicated instance per customer
- Dedicated database
- Higher isolation and customization
- Higher operational cost

### Database Strategies
- Shared database with `tenant_id`
- Schema-per-tenant
- Database-per-tenant

## 19.3 Scalability

- Horizontal scaling
- Stateless services
- Centralized updates and maintenance
- Automated deployment
- Avoid tenant-specific code branches
- Use centralized configuration

## 19.4 Identity & Access Management

- Role-based access control (RBAC)
- Multi-guard authentication
- Policy-based authorization
- Dynamic middleware
- Cross-tenant isolation

## 19.5 Microservices (Optional Extraction)

- Independent service modules (billing, user management, inventory, reporting)
- API-based communication
- Supports microservice extraction if required

---

# 20. Organizational Hierarchy

A nested hierarchical organizational structure where each level is a subset of the level above, forming a layered tree-like model that supports complex, geographically dispersed enterprises.

## 20.1 Hierarchy Levels

```
Tenant
 └── Organisation
      └── Branch
           └── Location
                └── Department
```

Or in enterprise expansion:

```
Company → Division → Region → Warehouse → Department → Sub-unit
```

## 20.2 Rules

- Parent-child relationships enforced
- Recursive querying supported
- Tenant-bound hierarchy
- No circular relationships allowed
- Supports geographically dispersed operations
- Multi-branch operations supported

---

# 21. API Design

- RESTful
- Versioned (`/api/v1`)
- Idempotent endpoints
- Standard response envelope
- Structured error format
- Pagination required
- OpenAPI/Swagger documentation required
- No hidden behavior

## 21.1 Integration Capabilities

- Webhooks
- Event publishing
- Third-party connectors
- E-commerce sync
- Payment gateway support

## 21.2 Documentation Standard

Every public endpoint must:

- Be documented using OpenAPI
- Include request validation schemas
- Include response schemas
- Be versioned

Internal modules must expose clean service contracts.

---

# 22. Frontend Architecture

If React frontend exists:

- Micro-frontend ready
- Module federation compatible
- Feature-based architecture
- No business logic duplication from backend
- Strict API contract adherence

Dashboards may use Tailwind-based admin templates (e.g., TailAdmin, AdminLTE).

---

# 23. Security

## 23.1 Mandatory Controls

- CSRF protection
- XSS prevention
- SQL injection prevention
- Rate limiting
- Token rotation
- Argon2/bcrypt hashing
- Strict file validation
- Signed URLs
- Audit logging
- Suspicious activity detection
- Role-based access control
- Attribute-based policies
- Tenant isolation enforcement

## 23.2 Pharmaceutical-Specific Security

- Full audit trail of stock mutations
- User action logging
- Tamper-resistant records
- Expiry override logging
- High-risk medication access logging

---

# 24. Data Integrity & Concurrency

ERP systems are highly concurrent. The following controls are mandatory:

- DB transactions
- Foreign keys
- Unique constraints
- Optimistic locking
- Pessimistic locking
- Idempotency keys
- Version tracking
- Immutable logs

## 24.1 Stock Transaction Rules

All stock mutations must:

- Execute inside database transactions
- Guarantee atomicity
- Prevent partial writes

## 24.2 Locking Strategy

- Pessimistic locking for stock deduction
- Optimistic locking for general updates
- Deadlock-aware retry mechanisms

All writes must be safe under parallel load.
Stock and accounting must never be inconsistent.

---

# 25. Performance & Scalability

ERP bottlenecks typically occur in:

- Inventory deduction
- Accounting posting
- Reporting aggregation
- Workflow engines

Mitigation strategies:

- Event-driven design
- Queue processing
- Read replicas
- Caching abstraction
- Partitioned reporting tables
- Background reconciliation jobs

---

# 26. Audit & Compliance

ERP must support:

- Immutable logs
- Versioned records
- Traceable financial flows
- Historical state reconstruction
- Regulatory export capability

Audit trail is non-optional.

---

# 27. Enterprise Reporting

Reports must support:

- Aggregated financial statements
- Inventory valuation reports
- Aging reports
- Tax summaries
- Custom report builder
- Export formats (CSV, PDF)

Reports must also be:

- Tenant-scoped
- Filterable
- Exportable
- Auditable

Reports must never break transactional integrity.

---

# 28. Plugin Marketplace

A marketplace-ready ERP requires:

- Module manifest definition (`module.json`)
- Dependency graph validation
- Version compatibility rules
- Sandboxed execution
- Tenant-scoped enablement
- Upgrade migration paths

## 28.1 Extensibility Rules

Modules must:

- Be open for extension
- Closed for modification
- Avoid cross-module direct database access
- Communicate through services or events

Plugin-style architecture is encouraged for all non-core features.

---

# 29. Reusability Principles

Reusable ERP modules must:

- Avoid business-specific assumptions
- Be configuration-driven
- Avoid hardcoded logic
- Expose contracts, not implementations
- Remain independently replaceable

Design for:

- Multi-industry support
- Multi-country tax extension
- Marketplace plugin ecosystem

---

# 30. Testing Requirements

Each module must include:

- Unit tests
- Feature tests
- Authorization tests
- Tenant isolation tests
- Concurrency tests
- Financial precision tests

No module is complete without coverage.

---

# 31. Prohibited Practices

The following are strictly disallowed:

- Business logic in controllers
- Query builder in controllers
- Cross-module tight coupling
- Hardcoded IDs
- Floating-point financial math
- Partial implementations
- TODO without tracking issue
- Hardcoded tenant conditions
- Cross-tenant data access
- Silent exception swallowing
- Implicit UOM conversion
- Duplicate stock deduction logic
- Skipping transactions for inventory mutations
- Direct database queries in services (bypass repository)

Immediate refactor required if detected.

---

# 32. Definition of Done

A module is complete only if:

- Clean Architecture respected
- No duplication
- Tenant isolation enforced
- Authorization enforced
- Concurrency handled
- Financial precision validated
- Events emitted correctly
- API documented
- Tests pass
- Documentation updated
- No technical debt introduced

---

# 33. System Guarantees

The system must guarantee:

- Tenant isolation
- Financial correctness
- Transactional consistency
- Replaceable modules
- Deterministic calculations
- Audit safety
- Horizontal scalability
- Extensibility without refactor debt

---

# 34. Business Objectives

The platform must:

- Reduce operational costs
- Increase stock accuracy
- Prevent stockouts
- Minimize expiry waste
- Ensure pharmaceutical compliance
- Improve warehouse efficiency
- Support ERP-level integration
- Maintain high scalability
- Preserve financial integrity
- Reduce labor costs
- Minimize human error
- Increase profitability
- Improve customer satisfaction
- Enable data-driven decisions
- Ensure regulatory compliance
- Improve patient safety (pharmaceutical context)

---

# 35. Autonomous Agent Execution Rules

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

## 35.1 Compliance Validation Checklist

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

# 36. Implementation Tracking

A continuously maintained `IMPLEMENTATION_STATUS.md` must track:

- Module name
- Status (Planned / In Progress / Complete / Refactor Required)
- Test coverage %
- Violations found
- Refactor actions
- Concurrency compliance
- Tenant compliance verification

---

# 37. Strategic Design Goal

Build not just an ERP.

Build:

- A configurable ERP framework
- A reusable SaaS engine
- A domain-driven enterprise core
- A plugin-ready platform
- A long-term maintainable system

---

# 38. References

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

- Fully modular
- Fully pluggable
- Fully configurable
- Fully tenant-isolated
- Fully enterprise-grade
- Financially precise
- Horizontally scalable
- Vertically scalable
- Audit-safe ERP/CRM SaaS platform

Zero regression tolerance.
Zero architectural violations.
Zero technical debt acceptance.

This contract is binding for all contributors and AI agents.
