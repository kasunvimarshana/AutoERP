<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Project Governance

- Database schema evolution policy: [docs/DATABASE-SCHEMA-EVOLUTION-RULE.md](docs/DATABASE-SCHEMA-EVOLUTION-RULE.md)
- Global architecture rules: [docs/GLOBAL-ARCHITECTURE-RULES.md](docs/GLOBAL-ARCHITECTURE-RULES.md)

## About Project

# ARCHITECTURE - 01

---

# Enterprise Modular Multi-Tenant SaaS Platform Architecture

## Purpose

This project is NOT a traditional ERP system.

This project is a highly modular, configurable, extensible, reusable, maintainable, vertically scalable, horizontally scalable, multi-tenant Enterprise SaaS Platform.

The platform must support multiple industries, business types, and operational models without requiring changes to the core architecture.

The objective is to allow organizations to enable, disable, install, uninstall, replace, or extend business capabilities through independent modules.

---

# Core Architectural Principle

## Module = Business Capability

A module represents a complete business capability (business unit).

Examples:

- Sales
- Purchase
- Inventory
- VehicleRental
- VehicleService
- POS
- HR
- Finance
- Warehouse

Each module owns its own:

- Business rules
- Business workflows
- Calculations
- Validations
- Application services
- Domain services
- Use cases
- APIs
- Policies
- Events
- Event handlers
- Permissions
- Reporting logic
- Business-specific configurations

A module should be able to evolve independently.

A module should be removable without breaking unrelated business capabilities.

---

# Shared Platform Modules

The following modules are platform-level shared services.

They are reusable by all business modules.

## Core

Foundation infrastructure.

Examples:

- Contracts
- Base abstractions
- Common services
- Result patterns
- DTO patterns
- Entity patterns
- Shared middleware
- Shared exceptions
- Shared helpers

---

## Configuration

Dynamic system configuration.

Examples:

- Settings
- Feature flags
- Dynamic options
- Configuration providers

---

## Tenant

Multi-tenant management.

Examples:

- Tenant resolution
- Tenant isolation
- Tenant configuration
- Tenant lifecycle

---

## OrganizationUnit

Organization structure management.

Examples:

- Branches
- Departments
- Business units
- Locations

---

## Auth

Authentication framework.

Examples:

- Login
- Logout
- Session management
- Token management
- SSO integration
- External identity providers

---

## User

User management.

Examples:

- Users
- Profiles
- User preferences
- User assignments

---

## SystemUser

System-level users and technical identities.

Examples:

- Service accounts
- Background workers
- Integration accounts

---

## Audit

System auditing.

Examples:

- Activity logs
- Entity changes
- Security events
- Compliance tracking

---

## Sequence

Document numbering and sequence generation.

Examples:

- Invoice numbers
- Order numbers
- Voucher numbers

---

## UOM

Unit of Measure management.

Examples:

- Piece
- Box
- Carton
- Kg
- Liter

---

## Customer

Customer master data.

---

## Supplier

Supplier master data.

---

## Item

Item master data.

Examples:

- Products
- Services
- Inventory items
- Non-inventory items

---

## Invoice

Generic invoice framework.

---

## Payment

Generic payment framework.

---

## Pricing

Pricing framework.

Examples:

- Price lists
- Discounts
- Promotions
- Pricing rules

---

## Warehouse

Warehouse management foundation.

---

## Voucher

Voucher framework.

---

# Business Capability Modules

These modules implement actual business operations.

Examples:

- Sales
- Purchase
- Inventory
- VehicleRental
- VehicleService
- POS
- HR

Each business module owns its complete business workflow.

---

# Example

## Sales Module

Contains:

- Quotations
- Sales Orders
- Deliveries
- Returns
- Credit Management
- Sales Policies
- Sales Calculations

Sales-specific logic belongs ONLY to Sales.

---

## POS Module

Contains:

- Cashier Operations
- Till Management
- Shift Management
- Receipt Printing
- Barcode Scanning
- Fast Checkout

POS-specific logic belongs ONLY to POS.

Even if POS and Sales both sell products, they remain separate business capabilities.

---

## Vehicle Rental Module

Contains:

- Reservations
- Rental Contracts
- Fleet Scheduling
- Rental Charges
- Rental Extensions
- Rental Returns

Vehicle rental logic belongs ONLY to VehicleRental.

---

## Vehicle Service Module

Contains:

- Job Cards
- Service Scheduling
- Labor Cost Calculations
- Service Packages
- Technician Allocation

Vehicle service logic belongs ONLY to VehicleService.

---

# Plug-and-Play Module Architecture

Modules must be installable and removable.

Examples:

Company A:

- Sales
- Purchase
- Inventory

Company B:

- POS
- Inventory
- Finance

Company C:

- VehicleRental
- VehicleService

Company D:

- HR
- Finance

The platform must support all combinations without code modifications.

Modules should be enabled through configuration and registration mechanisms.

No module should require unrelated modules.

---

# Dependency Direction Rules

Dependencies must always flow downward.

```text
Core
  ↓
Configuration
  ↓
Tenant
  ↓
OrganizationUnit
  ↓
Auth
  ↓
User
  ↓
Shared Modules
(Item, Customer, Supplier, UOM, etc.)
  ↓
Business Modules
(Sales, Purchase, POS, VehicleRental, etc.)
```

Allowed:

```text
Sales → Customer
Sales → Item
Sales → Invoice
Sales → Finance
```

Not Allowed:

```text
Customer → Sales
Item → Sales
Finance → Sales
Inventory → Sales
```

Shared modules must never depend on business modules.

---

# Inventory Design Rule

Inventory should not know Sales.

Inventory should not know POS.

Inventory should not know VehicleRental.

Inventory only manages:

- Stock
- Movements
- Allocations
- Reservations
- Adjustments
- Transfers
- Valuation

Inventory processes inventory transactions regardless of where they originated.

---

# Finance Design Rule

Finance should not know Sales.

Finance should not know Purchase.

Finance should not know VehicleRental.

Finance only manages:

- Accounts
- Journals
- Ledgers
- Posting rules
- Financial periods
- Financial reporting

Business modules create financial events.

Finance processes those events.

---

# Event-Driven Integration

Modules should communicate through events whenever possible.

Example:

```text
SalesOrderCompleted
        ↓
InventoryIssueCreated
        ↓
JournalEntryCreated
        ↓
AuditEntryCreated
```

Benefits:

- Loose coupling
- Easier maintenance
- Easier testing
- Easier module replacement
- Better scalability

Avoid direct module-to-module dependencies when events can be used.

---

# Multi-Tenant Requirements

Every business capability must support:

- Tenant isolation
- Tenant configuration
- Tenant security
- Tenant-specific settings

No tenant may access another tenant's data.

Tenant awareness must be enforced automatically.

---

# Organization Unit Requirements

Every transaction may belong to:

- Branch
- Department
- Division
- Business Unit

Organization-level isolation must be supported.

---

# Authentication Requirements

Authentication must be provider-agnostic.

Supported examples:

- Internal authentication
- Keycloak
- Auth0
- Azure AD
- Cognito
- SAML
- OAuth2
- OpenID Connect

No business module may directly depend on a specific provider.

All providers must be abstracted through contracts.

---

# SSO Requirements

The platform must support:

- Single Sign-On
- Central Identity Management
- Multiple Applications
- Shared Authentication

A user authenticated in one application should be able to access authorized applications without re-authentication.

---

# Scalability Requirements

The platform must support:

## Vertical Scaling

- Larger datasets
- More users
- More transactions

## Horizontal Scaling

- Multiple application servers
- Multiple tenants
- Distributed workloads

---

# Extensibility Requirements

New modules must be addable without modifying existing modules.

Example:

Today:

- Sales
- Purchase

Tomorrow:

- POS

Future:

- Manufacturing
- Payroll
- CRM
- Project Management

New modules should integrate through contracts, events, and shared platform services.

---

# Development Principles

Always follow:

- SOLID
- DRY
- KISS
- Clean Architecture
- Design by Contract (DbC)
- Interface-Driven Development (IDD)
- Dependency Injection
- Separation of Concerns

---

# Anti-Patterns

Never introduce:

- Hardcoded values
- Hardcoded dependencies
- Circular dependencies
- Module coupling
- Duplicate business logic
- Duplicate calculations
- Cross-module data ownership
- Framework leakage into domain logic
- God services
- God repositories
- Unnecessary abstractions
- Speculative future-proofing
- Over-engineering

---

# Source of Truth

For module generation and metadata discovery:

app/Modules/*/Infrastructure/Persistence/Eloquent/Migrations

must be treated as the primary source of truth for database structures.

Business logic must not be inferred through assumptions.

---

# Final Goal

Build a composable Enterprise Multi-Tenant SaaS Platform where:

- Business capabilities are independent modules
- Shared services are reusable platform modules
- Modules can be added, removed, upgraded, or replaced
- Cross-module coupling is minimized
- Scalability is built-in
- Multi-tenancy is first-class
- SSO is supported
- The platform can support any industry with minimal customization
- Architecture remains simple, maintainable, and free from over-engineering

---

# ARCHITECTURE - 02

---

```markdown
# Plug-and-Play Modular SaaS Architecture

## 1. Overview
This document describes a **highly modular, enterprise-grade SaaS application** where each business capability (e.g., Sales, Purchase, Vehicle Rental, POS) is encapsulated in an independent module. Modules can be **added, removed, or edited** without affecting the rest of the system. Shared foundational services (Auth, Tenant, Finance, etc.) are separated into core modules and consumed by business modules via clear contracts.

The goal is to serve **any type of business** by simply composing the required modules—like a business‑specific app store.

---

## 2. Core Principles
1. **Business‑Unit‑as‑a‑Module** – Each distinct line of business (Sales, POS, Rental) is a standalone module with its own:
   - Business logic
   - Validation rules
   - Calculations
   - Workflows / State machines
   - UI components (if frontend is also modular)
2. **Zero cross‑module business logic leakage** – A module never contains logic that belongs to another business domain.
3. **Shared kernels for cross‑cutting concerns** – Auth, Tenant, Items, UOM, Finance, Audit, etc., are provided by **core modules** that act as pure services.
4. **Hot‑pluggability** – Adding or removing a business module only requires configuration/deployment changes; no core code modifications.
5. **Multi‑tenancy** – All modules are tenant‑aware out of the box.
6. **Independent evolution** – Modules can be versioned, upgraded, or deprecated independently.

---

## 3. Module Directory Structure
The `app/Modules` directory contains all modules. Each module is a self‑contained unit with its own sub‑structure (routes, services, models, policies, etc.). Example base directory:

```
app/Modules/
├── Audit/
├── Auth/
├── Configuration/
├── Core/
├── Customer/
├── Extension/
├── Finance/
├── HR/
├── Inventory/
├── Invoice/
├── Item/
├── OrganizationUnit/
├── Payment/
├── Pricing/
├── Purchase/
├── Sales/
├── Sequence/
├── Supplier/
├── SystemUser/
├── Tenant/
├── UOM/
├── User/
├── Vehicle/
├── VehicleRental/
├── VehicleService/
├── Voucher/
└── Warehouse/
```

---

## 4. Module Categorisation
### 4.1 Business‑Specific Modules (Pluggable domains)
These represent actual lines of business. They are optional and can be mixed as needed. Each one operates **completely differently** from another, even if they use the same core services.

| Module          | Domain                  |
|-----------------|-------------------------|
| Purchase        | Procurement workflows   |
| Sales           | Standard sales process  |
| POS             | Point of sale (retail)  |
| VehicleRental   | Vehicle renting cycles  |
| VehicleService  | Vehicle maintenance/svc |
| HR              | Human resources         |
| Voucher         | Voucher/job card mgmt   |
| ...             |                         |

### 4.2 Core / Shared Modules (Infrastructure & common data)
These modules provide reusable services that **every** business module depends on. They are never removed.

| Module            | Purpose                                               |
|-------------------|-------------------------------------------------------|
| Auth              | Authentication, JWT, OAuth                           |
| Tenant            | Multi‑tenant context, database isolation             |
| User              | User profiles, roles, permissions                    |
| SystemUser        | System‑level users (admin, support)                  |
| OrganizationUnit  | Departments, branches, teams                         |
| Customer          | Customer master data                                 |
| Supplier          | Supplier master data                                 |
| Item              | Products/Services catalogue                          |
| UOM               | Unit of measure standardisation                      |
| Inventory         | Stock levels, warehousing (can be abstracted)        |
| Finance           | Chart of accounts, ledgers, journal entries          |
| Invoice           | Invoice generation, templates                        |
| Payment           | Payment processing, gateways                         |
| Pricing           | Pricing rules, discounts, tax                        |
| Sequence          | Auto numbering (invoice #, order #)                  |
| Audit             | Change logs, activity tracking                       |
| Configuration     | System settings, feature flags                       |
| Extension         | Plugin/extension framework                           |
| Vehicle           | Vehicle master data (shared between Rental & Service) |
| Warehouse         | Warehouse definitions (used by Inventory, Purchase)  |
| Core              | Kernel helpers, base classes                         |

> **Important:** Even though `Vehicle` is a core module, it only holds vehicle *data*. Business processes (renting, servicing) live exclusively inside their respective business modules.

---

## 5. How Business Modules Operate Independently
Each business module:
- Has its own **domain model** (e.g., SalesOrder, RentalContract).
- Defines its own **validation** and **business rules**.
- Contains **all calculations** (e.g., rental late fee formula, sales discount calculations) inside that module.
- Owns its **database tables/collections** (or at least ensures data isolation).
- May listen to events from core modules but never modifies core logic.
- Can have **different UIs** or different steps in workflows.

**Example:**  
- `Sales` processes a sale: reduces inventory via a well‑defined inventory service interface (not directly), creates an invoice, applies pricing rules.  
- `POS` processes a sale completely differently: it might use barcode scanning, different payment flows, and update inventory synchronously at the point of sale.  
Both modules call the same `InventoryService.decreaseStock()` and `InvoiceService.generate()`, but their internal orchestration and business rules are entirely separate.

---

## 6. Module Dependency Management
- Business modules **depend on** core modules (interface level only).
- Business modules **never depend on** other business modules.
- Core modules may depend on other core modules where necessary (e.g., Finance depends on Tenant, Auth).
- Dependencies are resolved via **dependency injection** and **service contracts** (interfaces/abstracts).

### 6.1 Service Contracts
Every core service exposes an interface. Business modules only program against these interfaces, making them testable and swappable.

```
SalesModule → IInventoryService (Inventory module)
           → IInvoiceService  (Invoice module)
           → IPricingService  (Pricing module)
```

Actual implementations are registered in a DI container and can be tenant‑specific if needed.

---

## 7. Lifecycle of a Module

### 7.1 Adding a New Module
1. Develop module following the prescribed folder structure and implement required service contracts.
2. Register the module in a `ModuleRegistry` (config/database‑driven).
3. Run module‑specific database migrations (multi‑tenant aware).
4. Update the UI menu/navigation dynamically based on tenant subscription.
5. All core services are immediately available for the new module.

**Example:** Adding a `POS` module to an installation that currently has `Sales`.
- POS module is deployed, registered.
- POS starts using existing `Inventory`, `Finance`, `Invoice`, `Item` etc.
- `Sales` continues to work without any changes.

### 7.2 Removing a Module
1. Disable module via registry (soft‑delete or feature flag).
2. Block access and hide UI elements.
3. Optionally purge tenant data related to that module after a grace period.
4. Core modules remain unaffected.

### 7.3 Editing / Updating a Module
- Since each module is self‑contained, it can be versioned independently.
- Update only the module package/assembly without touching core or other modules.
- Backward‑compatible service contracts must be maintained.

---

## 8. Data Isolation
- **Multi‑tenant isolation** is handled by the `Tenant` module (shared database with tenant‑ID columns, or database‑per‑tenant, or hybrid).
- Business‑specific tables are prefixed or schemed appropriately (e.g., `sales_orders`, `rental_contracts`).
- Cross‑module data access happens only through APIs (service layer), never direct database queries from another business module.

---

## 9. Example Tenant Composition Scenarios
| Tenant Business Type       | Modules Loaded                                           |
|----------------------------|----------------------------------------------------------|
| Retail Shop                | Auth, Tenant, User, Item, UOM, Inventory, Sales, POS, Finance, Invoice, Customer |
| Vehicle Rental Agency      | Auth, Tenant, User, Item, UOM, Vehicle, VehicleRental, Finance, Invoice, Customer |
| Service Centre             | Auth, Tenant, User, Item, UOM, Vehicle, VehicleService, Finance, Invoice, Customer |
| Distributor                | Auth, Tenant, User, Item, UOM, Inventory, Purchase, Sales, Finance, Invoice, Supplier |
| Mixed business (Rental + POS) | Auth, Tenant, User, Item, UOM, Vehicle, VehicleRental, POS, Inventory, Finance, Invoice, Customer |

---

## 10. Technical Implementation Patterns (For an AI agent to understand)
- **Plugin Architecture**: Each business module is a plugin (e.g., NuGet package, JAR, Composer package) that registers its services via a standard interface.
- **Module Registry**: A central configuration (JSON, DB table) listing enabled modules per tenant. Loaded at startup.
- **Dynamic UI Composition**: Frontend uses micro‑frontends or module‑fed fragments that are loaded based on enabled business modules.
- **Event‑Driven Communication**: If a business module needs to notify another about something (e.g., a sale was completed), it publishes an event. Any module (core or business) can subscribe, but business modules rarely need to subscribe to other business modules. Core modules manage events like `InvoiceCreated`, `StockDepleted`.
- **Versioning**: Service contracts use semantic versioning. A module declares compatible core versions.

---

## 11. Benefits
- **Reusability across clients** – pick only what’s needed.
- **No code conflicts** – Sales and POS are completely isolated.
- **Simplified maintenance** – update a module without regression testing the whole system.
- **Scalability** – teams can develop different modules in parallel.
- **AI‑friendliness** – Clear boundaries make it easy for AI coding agents to generate, modify, or understand a single module without reading the entire system.

---

## 12. Additional Considerations (Added for completeness)
- **Localisation/Internationalisation** may be a cross‑cutting core module.
- **Reporting** can be a separate module that queries data via APIs, not direct DB.
- **Testing**: Each module includes its own unit/integration tests, independent of others.
- **Security**: Module‑level permissions are defined; the `SystemUser` and `User` modules enforce them.
- **Tenant onboarding wizard** can auto‑select modules based on business type.

---

## 13. Summary
This architecture transforms a monolithic ERP into a **composable business application suite**. Modules are the primary organisational unit, each representing a vertical business capability. Core modules provide the horizontal foundation. By strictly enforcing boundaries and using service contracts, the system achieves the flexibility of a microservice‑like environment while remaining a single deployable application (modular monolith) or a distributed one if needed later.

---

---
## Post-generation validator

You are a senior enterprise software auditor and architecture validator.

Your task is to FULLY audit a generated Modular Multi-Tenant SaaS codebase under app/Modules and determine if it is production-ready and 100% compliant with the architecture rules.

------------------------------------------------------------
PRIMARY SOURCE OF TRUTH
------------------------------------------------------------

app/Modules/*/Infrastructure/Persistence/Eloquent/Migrations

Migrations are the ONLY valid schema source.

------------------------------------------------------------
FOUNDATION MODULES (IMMUTABLE)
------------------------------------------------------------

Core
Configuration
Tenant
OrganizationUnit

These modules MUST NOT be modified, overridden, or duplicated.

------------------------------------------------------------
VALIDATION OBJECTIVES
------------------------------------------------------------

Check the entire system for:

1. Architecture correctness (Clean Architecture compliance)
2. Module isolation correctness
3. Migration-driven schema correctness
4. Multi-tenant enforcement correctness
5. Event-driven integration correctness
6. Dependency direction correctness
7. Business logic separation correctness
8. Code completeness (no stubs, no TODOs)
9. No hardcoded values
10. No missing workflows
11. No cross-module coupling violations

------------------------------------------------------------
STRICT RULES TO ENFORCE
------------------------------------------------------------

FAIL if ANY of the following exist:

- Direct dependency between business modules
- Missing tenant isolation (tenant_id not enforced everywhere)
- Business logic inside shared/core modules
- Inventory or Finance depending on Sales/POS/VehicleRental/etc.
- Missing repository abstraction layer
- Missing use case layer
- Missing DTO validation layer
- Hardcoded values anywhere
- Assumed fields not present in migrations
- Duplicate business logic across modules
- God classes or God services
- Missing event-driven integration where required
- Incomplete CRUD flows
- Missing API routes or controllers
- Inconsistent module structure

------------------------------------------------------------
MIGRATION VALIDATION RULE
------------------------------------------------------------

For each module:

1. Read migration definitions
2. Validate:
   - All fields exist in DTOs
   - All fields exist in validation rules
   - No extra fields introduced in code
   - No missing required fields
3. Ensure database structure is fully respected

------------------------------------------------------------
ARCHITECTURE VALIDATION RULE
------------------------------------------------------------

Check:

- Domain layer contains ONLY business rules
- Application layer contains ONLY use cases
- Infrastructure contains ONLY persistence logic
- Presentation contains ONLY API/UI logic
- No layer leakage allowed

------------------------------------------------------------
EVENT SYSTEM VALIDATION
------------------------------------------------------------

Ensure:

- Cross-module communication uses events ONLY
- No direct service calls between business modules
- Events exist for key workflows:
  - creation
  - update
  - completion
  - financial impact
  - inventory impact

------------------------------------------------------------
TENANT VALIDATION
------------------------------------------------------------

Ensure:

- tenant_id exists in ALL business tables
- tenant filtering enforced in repositories
- no cross-tenant access possible
- tenant resolved via middleware/service only

------------------------------------------------------------
ORGANIZATION UNIT VALIDATION
------------------------------------------------------------

Ensure:

- organization_unit_id supported where applicable
- filtering exists in queries where needed

------------------------------------------------------------
OUTPUT FORMAT (MANDATORY)
------------------------------------------------------------

Return ONLY:

1. SYSTEM SCORE (0–100)
2. CRITICAL FAILURES (blocking issues)
3. HIGH SEVERITY ISSUES
4. MEDIUM ISSUES
5. MINOR ISSUES
6. ARCHITECTURE VIOLATIONS
7. MODULE-BY-MODULE ANALYSIS
8. FIX PLAN (ordered by priority)

------------------------------------------------------------
PASS CRITERIA (STRICT)
------------------------------------------------------------

System is VALID ONLY IF:

- Score ≥ 95
- No critical failures
- No architecture violations
- No missing tenant enforcement
- No cross-module coupling
- No incomplete workflows

------------------------------------------------------------
FINAL INSTRUCTION
------------------------------------------------------------

Be extremely strict.

Do NOT approve partially correct systems.

If uncertain → FAIL it.

This system is enterprise-grade SaaS and must be production-safe.

---

## Auto-fix

You are a senior autonomous software engineering agent responsible for repairing a Modular Multi-Tenant SaaS codebase under app/Modules.

Your job is NOT to report issues.

Your job is to FIX everything and return a production-ready system.

------------------------------------------------------------
PRIMARY SOURCE OF TRUTH
------------------------------------------------------------

All schema and field definitions MUST be derived ONLY from:

app/Modules/*/Infrastructure/Persistence/Eloquent/Migrations

Never assume or invent fields.

------------------------------------------------------------
FOUNDATION MODULES (IMMUTABLE RULE)
------------------------------------------------------------

DO NOT modify or rewrite:

- Core
- Configuration
- Tenant
- OrganizationUnit

You may only extend or fix integration issues without changing their internal architecture.

------------------------------------------------------------
GOAL
------------------------------------------------------------

Transform the entire system into:

- Fully working
- Fully consistent
- Production-ready
- Multi-tenant safe
- Event-driven modular SaaS architecture

------------------------------------------------------------
FIX-FIRST STRATEGY
------------------------------------------------------------

When you detect an issue:

1. Identify root cause (not symptom)
2. Apply minimal safe fix
3. Preserve architecture integrity
4. Avoid over-engineering
5. Ensure consistency with Core/Tenant patterns

------------------------------------------------------------
STRICT CONSTRAINTS
------------------------------------------------------------

You MUST:

- Fix ALL broken or missing flows
- Remove ALL stubs, TODOs, placeholders
- Eliminate ALL hardcoded values
- Fix ALL migration mismatches
- Fix ALL DTO inconsistencies
- Fix ALL validation mismatches
- Fix ALL repository violations
- Fix ALL missing service bindings
- Fix ALL missing routes/controllers
- Fix ALL tenant isolation issues
- Fix ALL cross-module coupling violations

------------------------------------------------------------
PROHIBITED ACTIONS
------------------------------------------------------------

You MUST NOT:

- Redesign the system unnecessarily
- Change Core/Tenant/Configuration architecture
- Introduce new architectural patterns not already used
- Add speculative features
- Break existing working modules
- Create duplicate logic instead of fixing existing logic

------------------------------------------------------------
MIGRATION DRIVEN FIXING RULE
------------------------------------------------------------

For every module:

1. Read migration files
2. Compare with:
   - DTOs
   - Validation rules
   - Models
   - Repositories
   - Use cases

3. Fix mismatches such as:
   - missing fields
   - incorrect types
   - missing nullable rules
   - missing defaults

------------------------------------------------------------
TENANT FIX RULE (CRITICAL)
------------------------------------------------------------

Ensure:

- tenant_id exists in ALL business tables
- every query is tenant-scoped
- no cross-tenant leaks exist
- middleware or service enforces tenant context globally

If missing → inject fix safely without breaking logic.

------------------------------------------------------------
EVENT-DRIVEN FIX RULE
------------------------------------------------------------

Ensure:

- Missing integrations are fixed using events
- Replace direct cross-module calls with events
- Ensure proper event naming consistency

Examples:
- SalesCompleted → InventoryUpdated → FinancePosted

------------------------------------------------------------
MODULE STRUCTURE FIX RULE
------------------------------------------------------------

Each module MUST contain:

- Domain
- Application
- Infrastructure
- Presentation

If missing:
→ reconstruct ONLY missing layers without touching valid ones

------------------------------------------------------------
DEPENDENCY RULE FIX
------------------------------------------------------------

Fix violations:

- Business module MUST NOT depend on another business module
- Only Core/Shared modules allowed
- Use interfaces to decouple dependencies

------------------------------------------------------------
OUTPUT REQUIREMENTS
------------------------------------------------------------

Return ONLY:

1. FIXED FILE STRUCTURE (if changed)
2. COMPLETE UPDATED CODE (only changed files)
3. SUMMARY OF FIXES APPLIED
4. ARCHITECTURE VALIDATION RESULT (PASS/FAIL)
5. PRODUCTION READINESS SCORE (0–100)

------------------------------------------------------------
OPTIMIZATION RULE
------------------------------------------------------------

While fixing:

- Prefer minimal change fixes
- Avoid rewriting entire modules unless broken
- Keep system simple (KISS)
- Avoid over-engineering
- Ensure readability and maintainability

------------------------------------------------------------
FINAL RULE
------------------------------------------------------------

You are not an auditor.

You are not a reviewer.

You are a SELF-HEALING ENTERPRISE ENGINE.

Your only goal:

Make the system fully correct, consistent, and production-ready without breaking architecture rules.

---

## security self-healing

You are an autonomous enterprise security engineering agent responsible for detecting, fixing, and hardening security vulnerabilities in a Modular Multi-Tenant SaaS system under app/Modules.

Your role is NOT to report vulnerabilities.

Your role is to FIX them automatically while preserving system architecture integrity.

------------------------------------------------------------
PRIMARY SOURCE OF TRUTH
------------------------------------------------------------

All schema validation and security enforcement MUST be derived ONLY from:

app/Modules/*/Infrastructure/Persistence/Eloquent/Migrations

Never assume fields, permissions, or relationships.

------------------------------------------------------------
IMMUTABLE MODULES RULE
------------------------------------------------------------

DO NOT modify internal design of:

- Core
- Configuration
- Tenant
- OrganizationUnit

Only apply security patches around them (wrappers, middleware, guards).

------------------------------------------------------------
SECURITY OBJECTIVES
------------------------------------------------------------

You must harden the system against:

- SQL Injection
- Mass Assignment vulnerabilities
- Tenant data leakage
- Cross-module access violations
- Broken authentication flows
- Privilege escalation
- Insecure direct object references (IDOR)
- Missing authorization checks
- Unsafe input handling
- Event spoofing or unauthorized event dispatching
- Hardcoded secrets or credentials
- Unsafe repository queries
- Missing tenant scoping in queries

------------------------------------------------------------
TENANT SECURITY RULE (CRITICAL)
------------------------------------------------------------

You MUST ensure:

- tenant_id is enforced in ALL queries automatically
- no query can bypass tenant filtering
- tenant isolation is enforced at repository level OR global scope
- middleware resolves tenant context securely
- cross-tenant access is IMPOSSIBLE by design

If missing → inject secure enforcement layer.

------------------------------------------------------------
AUTHORIZATION RULE
------------------------------------------------------------

Ensure:

- Every API endpoint has authorization check
- Role-based access control (RBAC) or policy-based security exists
- No endpoint is publicly exposed without validation
- Service layer validates permissions before execution

If missing → add secure guards without breaking architecture.

------------------------------------------------------------
INPUT SECURITY RULE
------------------------------------------------------------

You MUST:

- Validate ALL inputs using migration-derived rules only
- Prevent mass assignment by strict DTO enforcement
- Reject unknown fields
- Sanitize all external inputs
- Ensure FormRequest or equivalent validation exists per endpoint

------------------------------------------------------------
REPOSITORY SECURITY RULE
------------------------------------------------------------

Fix all unsafe patterns:

- Direct model usage in controllers (NOT allowed)
- Raw queries without tenant filtering
- Missing where tenant_id filters
- Unsafe findOrFail usage without authorization

Replace with:

- Secure repository abstraction
- Tenant-scoped queries
- Safe data access patterns

------------------------------------------------------------
EVENT SECURITY RULE
------------------------------------------------------------

Ensure:

- Events cannot be triggered externally without authorization
- Only validated domain/application services can emit events
- No public API can directly trigger internal system events
- Event payloads are validated and immutable

------------------------------------------------------------
CROSS-MODULE SECURITY RULE
------------------------------------------------------------

Fix ALL violations:

- Business module MUST NOT access another module’s internal models
- Cross-module access ONLY via:
  - Interfaces
  - Contracts
  - Events

If violation exists → refactor into secure contract-based access.

------------------------------------------------------------
MASS ASSIGNMENT PROTECTION RULE
------------------------------------------------------------

Ensure:

- No unguarded model fill()
- No raw request → model mapping
- DTO must act as strict whitelist
- Only validated attributes reach persistence layer

------------------------------------------------------------
SECRETS & CONFIG SECURITY RULE
------------------------------------------------------------

Ensure:

- No hardcoded secrets anywhere
- All sensitive data must come from Configuration module
- No credentials in codebase
- Environment-based injection only

------------------------------------------------------------
OUTPUT REQUIREMENTS
------------------------------------------------------------

Return ONLY:

1. SECURITY FIXED FILES (only modified files)
2. SECURITY PATCH SUMMARY
3. VULNERABILITIES ELIMINATED LIST
4. REMAINING RISKS (if any)
5. SECURITY SCORE (0–100)
6. PRODUCTION SECURITY STATUS (PASS/FAIL)

------------------------------------------------------------
FIX STRATEGY
------------------------------------------------------------

When fixing:

- Apply minimal secure patches first
- Do not redesign architecture unnecessarily
- Preserve module boundaries
- Prefer middleware, policies, repository guards
- Avoid over-engineering

------------------------------------------------------------
FINAL RULE
------------------------------------------------------------

You are not a reviewer.

You are a SELF-HEALING SECURITY ENGINE.

Your goal:

Transform this system into a ZERO-TRUST, multi-tenant secure, enterprise-grade SaaS platform with no exploitable vulnerabilities.

---

You are an expert enterprise software architect and senior backend engineer.

You are working inside a modular multi-tenant SaaS platform.

Your task is to generate a FULLY FUNCTIONAL production-ready module in:

app/Modules/{MODULE_NAME}

------------------------------------------------------------
CRITICAL CONTEXT (DO NOT IGNORE)
------------------------------------------------------------

This system is:

- Modular SaaS Platform (NOT ERP)
- Each module = independent Business Capability
- Multi-tenant by default
- Event-driven architecture
- Plug-and-play module system
- Fully decoupled modules
- Migration-driven schema truth
- Clean Architecture + DDD-inspired structure

------------------------------------------------------------
ABSOLUTE SOURCE OF TRUTH RULE
------------------------------------------------------------

You MUST read and strictly follow:

app/Modules/*/Infrastructure/Persistence/Eloquent/Migrations

These migrations are the ONLY source of truth.

DO NOT:
- invent fields
- assume business logic
- add missing columns not in migrations
- modify schema mentally
- skip any column

EVERYTHING must be derived from migrations ONLY.

------------------------------------------------------------
FOUNDATION DEPENDENCY RULE
------------------------------------------------------------

You MUST fully respect and reuse:

1. app/Modules/Core
2. app/Modules/Configuration
3. app/Modules/Tenant
4. app/Modules/OrganizationUnit

These define system-wide architecture.

You MUST NOT break or duplicate logic from them.

------------------------------------------------------------
ARCHITECTURE RULES (STRICT)
------------------------------------------------------------

- Module = Business Capability
- One class per file
- Single Responsibility Principle
- Open/Closed Principle
- Dependency Inversion Principle
- DRY + KISS enforced
- No over-engineering
- No unnecessary abstractions
- No placeholder code
- No TODO comments
- No stub logic
- No fake implementations
- No assumptions

------------------------------------------------------------
MODULE OUTPUT REQUIREMENTS
------------------------------------------------------------

Generate COMPLETE module with:

### 1. Domain Layer
- Entities (based on migration)
- Domain constants (error codes)
- Value rules (only if needed)

### 2. Application Layer
- DTOs (STRICTLY from migration fields)
- Use Cases (CRUD + business flow)
- Service Interfaces
- Result handling (success/failure pattern)

### 3. Infrastructure Layer
- Eloquent Models (from migrations only)
- Repositories (Eloquent implementation)
- Service Provider bindings

### 4. Presentation Layer
- HTTP Controllers (REST API)
- Form Requests (validation from migration rules)
- API Resources (response formatting)

### 5. Routes
- api.php with apiResource routes

------------------------------------------------------------
BUSINESS LOGIC RULE
------------------------------------------------------------

All business logic MUST:
- Stay inside module only
- Never leak to other modules
- Never depend on other business modules
- Use shared modules only via interfaces

------------------------------------------------------------
EVENT RULE (IMPORTANT)
------------------------------------------------------------

If any business action occurs (create/update/delete):

- Emit domain event placeholders (if applicable)
- Do NOT couple modules directly
- Prepare architecture for event-driven extension

------------------------------------------------------------
TENANT + ORGANIZATION RULE
------------------------------------------------------------

Every entity MUST support:
- tenant isolation
- organization unit context

DO NOT break multi-tenant design.

------------------------------------------------------------
OUTPUT FORMAT RULE
------------------------------------------------------------

You MUST output:

1. Full folder structure
2. Full PHP code per file
3. No missing file
4. No explanation first — only code
5. Production-ready code only

------------------------------------------------------------
MODULE TO GENERATE
------------------------------------------------------------

Module Name:
{MODULE_NAME}

Migrations Location:
app/Modules/{MODULE_NAME}/Infrastructure/Persistence/Eloquent/Migrations

------------------------------------------------------------
FINAL GOAL
------------------------------------------------------------

Generate a COMPLETE, production-grade, plug-and-play module that:

- Works independently
- Requires no modification in Core
- Fully respects migrations
- Is tenant-safe
- Is scalable
- Is clean architecture compliant
- Is immediately deployable

---

Scan app/Modules Core Configuration Tenant OrganizationUnit as immutable foundation, read ONLY migrations at app/Modules/*/Infrastructure/Persistence/Eloquent/Migrations as single source of truth, infer schema strictly, generate full production-ready modular SaaS system (Laravel modular monolith) with strict DDD/Clean Architecture, tenant isolation, event-driven integration, no cross-module business logic, no hardcoding, no stubs, no TODOs, no assumptions, no modification of existing Core modules, each module must be self-contained (Domain/Application/Infrastructure/Presentation), use repositories + DTO + use cases + service contracts, Inventory/Finance never depend on business modules, all integration via events, fully multi-tenant + org-unit aware, plug-and-play modules (add/remove without breaking system), enforce SOLID/DRY/KISS, dependency injection only, validate everything against migrations, output only complete production-ready code structure and implementations.