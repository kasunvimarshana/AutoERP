Rule: Always inspect existing migrations first. Update the original migration for schema changes instead of creating patch migrations. Create new migrations only for new tables or major new features. Keep schemas DRY, clean, and maintain a single source of truth per table.

---

Rule: Implement module-related seeders inside `app\Modules\*\Infrastructure\Persistence\Eloquent\Seeders` and keep all seeding logic scoped to its respective module.

---

DATABASE SCHEMA EVOLUTION RULE

Database structure, relationships, normalization, constraints, indexes, migrations, and schema design MAY be modified ONLY when necessary to improve:

- Scalability
- Flexibility
- Performance
- Data integrity
- Maintainability
- Long-term architectural stability

------------------------------------------------------------
STRICT RULES
------------------------------------------------------------

- DO NOT modify schema for convenience or minor code changes
- DO NOT change migrations without valid architectural reason
- DO NOT refactor database unless there is a proven need
- DO NOT duplicate tables or relationships unnecessarily

------------------------------------------------------------
WHEN SCHEMA CAN BE CHANGED
------------------------------------------------------------

Only allow schema changes if:

- Current design cannot scale vertically or horizontally
- Data integrity is compromised or at risk
- Performance issues are structurally caused by schema design
- Relationships are incorrectly modeled
- Normalization/denormalization is required for real performance needs
- Business domain requirements cannot be supported otherwise

------------------------------------------------------------
CORE PRINCIPLE

Schema is a CONTRACT, not a flexible implementation detail.

Code MUST adapt to schema.
Schema should NOT constantly change to fit code.

------------------------------------------------------------
FINAL RULE

Any schema modification must be justified by:
- Real architectural need
- Measurable improvement in system quality
- Long-term system stability

GLOBAL ARCHITECTURE RULES (MANDATORY FOR ALL MODULES)

From now on, ALL development MUST strictly follow:

------------------------------------------------------------
1. DESIGN BY CONTRACT (DbC)
------------------------------------------------------------

- Every class/method MUST define clear contracts:
  - Preconditions (input requirements)
  - Postconditions (output guarantees)
  - Invariants (state rules)
- No silent failures
- No undefined behavior
- Validate boundaries at service/repository level

------------------------------------------------------------
2. INTERFACE-DRIVEN DEVELOPMENT (IDD)
------------------------------------------------------------

- ALWAYS depend on interfaces, NOT concrete classes
- Controllers → Service Interfaces only
- Services → Repository Interfaces only
- Never use direct implementations in business/application logic
- Everything MUST be injectable and mockable

------------------------------------------------------------
3. CORE MODULE ENFORCEMENT
------------------------------------------------------------

- MUST follow app/Modules/Core architecture strictly
- Core defines shared contracts, patterns, and abstractions
- No bypassing Core rules

------------------------------------------------------------
4. CLEAN ARCHITECTURE RULE
------------------------------------------------------------

- Strict separation of layers:
  - Domain
  - Application
  - Infrastructure
  - Interface (API / CLI)
- No cross-layer violations

------------------------------------------------------------
5. CODE QUALITY RULES
------------------------------------------------------------

- One class per file
- One responsibility per class
- No god classes
- No mixed concerns
- No tight coupling
- No hidden dependencies
- Prefer simplicity over complexity

------------------------------------------------------------
6. ANTI-PATTERNS (STRICTLY FORBIDDEN)
------------------------------------------------------------

- Direct Eloquent usage outside infrastructure
- Concrete dependency usage in business logic
- God repositories / services
- Over-engineering / unnecessary abstractions
- Mixing multiple responsibilities in one class
- Skipping interfaces

------------------------------------------------------------
FINAL RULE

All modules MUST be:
- Contract-driven (DbC)
- Interface-driven (IDD)
- Clean architecture compliant
- Fully decoupled
- Maintainable and scalable

------------------------------------------------------------

GLOBAL RULE: API MODULE + CORE MODULE DEPENDENCY CONTROL

------------------------------------------------------------
RULE STATEMENT
------------------------------------------------------------

Whenever implementing:
- new feature
- bug fix
- error handling improvement
- performance improvement
- API change

in any module (especially API layer),

ALWAYS evaluate dependency impact on:

app/Modules/Core

------------------------------------------------------------
MANDATORY BEHAVIOR
------------------------------------------------------------

IF the change:
- affects shared logic
- affects contracts/interfaces
- affects DTO patterns
- affects repository/service abstractions
- affects cross-module behavior
- affects architecture consistency

THEN:

- MUST update or extend app/Modules/Core accordingly
- MUST ensure Core remains consistent and minimal
- MUST follow Core contracts and patterns strictly

------------------------------------------------------------
CORE SAFETY RULE
------------------------------------------------------------

- Core is NOT optional
- Core is NOT bypassable
- Core is the architecture foundation

Any change in API that touches shared behavior MUST reflect in Core.

------------------------------------------------------------
ANTI-PATTERN (STRICTLY FORBIDDEN)
------------------------------------------------------------

- Fixing API in isolation while ignoring Core impact
- Duplicating Core logic inside modules
- Bypassing Core contracts
- Creating inconsistent implementations across modules
- Quick fixes that break architecture consistency

------------------------------------------------------------
DECISION RULE

Before implementing any change:

1. Does it affect shared behavior?
   → YES: update Core first or in parallel

2. Does it stay module-only?
   → YES: no Core change required

------------------------------------------------------------
GOAL

Maintain a consistent, scalable, and contract-driven system where:
- Core defines the rules
- Modules implement the rules
- No module violates Core architecture

------------------------------------------------------------

MIGRATION CHANGE CONTROL RULE (STRICT)

------------------------------------------------------------
CORE PRINCIPLE
------------------------------------------------------------

All migrations are part of:
app/Modules/*/Infrastructure/Persistence/Eloquent/Migrations

AND ARE THE SOURCE OF TRUTH FOR DATABASE STRUCTURE.

------------------------------------------------------------
RULE FOR CHANGES
------------------------------------------------------------

1. ADDING NEW STRUCTURES
- Adding new tables → ALLOWED
- Adding new fields/columns → ALLOWED (if required for new features)

2. MODIFYING EXISTING STRUCTURES
- Modifying existing tables/columns → ONLY allowed if absolutely necessary
  (design issue, scalability issue, or critical bug)

3. REMOVING STRUCTURES
- Removing existing tables/columns → ONLY allowed when explicitly required
  and after full impact analysis

------------------------------------------------------------
STRICT RULE (IMPORTANT)

Existing migrations MUST NOT be modified or deleted unless:

- There is a critical design flaw
- There is a scalability limitation
- There is a data integrity issue
- System cannot function correctly without the change

------------------------------------------------------------
FORBIDDEN ACTIONS

- DO NOT remove existing tables for convenience
- DO NOT modify schema to match new code changes
- DO NOT delete columns without strong justification
- DO NOT restructure database casually during feature development

------------------------------------------------------------
FEATURE DEVELOPMENT RULE

- New features MUST adapt to existing schema
- Schema changes are LAST RESORT, not first option
- Prefer extending system instead of modifying existing structure

------------------------------------------------------------
SCALABILITY RULE

Migration changes are ONLY allowed when:
- Vertical scaling is blocked
- Horizontal scaling is impacted
- Data model becomes invalid at scale

------------------------------------------------------------
FINAL RULE

- Additions → allowed
- Changes → restricted
- Deletions → strictly controlled

Migrations must remain stable, predictable, and safe.

------------------------------------------------------------

GLOBAL ARCHITECTURE RULE: SINGLE SOURCE OF TRUTH (MIGRATIONS)

------------------------------------------------------------
CORE PRINCIPLE
------------------------------------------------------------

The ONLY source of truth for system structure is:

app/Modules/*/Infrastructure/Persistence/Eloquent/Migrations

------------------------------------------------------------
RULE STATEMENT
------------------------------------------------------------

All system design decisions MUST be derived from migrations.

Migrations define:
- Database schema
- Table structures
- Relationships
- Constraints
- Data integrity rules
- Core domain boundaries

------------------------------------------------------------
STRICT RULES
------------------------------------------------------------

- Migrations are the PRIMARY source of truth
- Code MUST adapt to migrations
- NEVER modify schema to match code
- NEVER ignore migration structure
- NEVER design domain models without referencing migrations first

------------------------------------------------------------
DEVELOPMENT FLOW (MANDATORY)

1. Read migrations first
2. Derive domain model from schema
3. Design architecture based on schema
4. Implement code based on derived design
5. Validate consistency with migrations

------------------------------------------------------------
FEATURE DEVELOPMENT RULE

- New features MUST adapt to existing migrations
- Schema changes are LAST RESORT only
- Prefer extending existing structure over modifying it
- Do NOT introduce schema changes for convenience

------------------------------------------------------------
ARCHITECTURE RULE

- Migrations define structure
- Core defines architecture rules
- Modules implement business logic

------------------------------------------------------------
ANTI-PATTERNS (STRICTLY FORBIDDEN)

- Designing models without checking migrations
- Changing schema to match code
- Ignoring existing relationships
- Duplicating structure outside migration source
- Guessing database structure from code

------------------------------------------------------------
FINAL RULE

If migrations and code disagree:
→ MIGRATIONS WIN ALWAYS

Migrations are the single source of truth for the entire system.

------------------------------------------------------------

GLOBAL SIMPLICITY RULE (KISS / ANTI-OVER-ENGINEERING)

------------------------------------------------------------
CORE PRINCIPLE
------------------------------------------------------------

All implementations across the codebase MUST prefer the simplest design that satisfies the current requirement.

------------------------------------------------------------
MANDATORY RULES
------------------------------------------------------------

- Apply KISS at all times
- Prefer direct, readable, minimal solutions over layered or speculative designs
- Introduce interfaces, services, DTOs, policies, middleware, abstractions, or supporting layers ONLY when there is a clear, immediate, and justified need
- Reuse existing Core, Configuration, Tenant, User, and shared module patterns before adding new structures
- Extend existing contracts and flows when they already solve the problem correctly
- Keep files, methods, and dependencies as small and explicit as practical
- Optimize for maintainability, clarity, and local reasoning

------------------------------------------------------------
STRICTLY FORBIDDEN
------------------------------------------------------------

- Speculative future-proofing without an immediate requirement
- Creating new abstraction layers for symmetry or aesthetics alone
- Adding interfaces where only one stable implementation exists and no injection boundary is actually needed
- Splitting simple logic across multiple classes without a concrete architectural reason
- Duplicating rules already implemented in Core or shared modules
- Introducing patterns, wrappers, or indirection that make the code harder to follow without measurable benefit

------------------------------------------------------------
DECISION RULE
------------------------------------------------------------

Before adding any new abstraction, ask:

1. Does an existing Core or module pattern already solve this?
  → YES: reuse it

2. Is the new layer required right now for a real boundary, contract, test seam, or cross-module reuse?
  → NO: do not add it

3. Does the change make the code simpler to understand and maintain?
  → NO: reject it

------------------------------------------------------------
FINAL RULE
------------------------------------------------------------

Prefer the minimum architecture necessary for the current requirement.

Simplicity is a hard rule, not an optional preference.

Scan app/Modules Core Configuration Tenant OrganizationUnit as immutable foundation, read ONLY migrations at app/Modules/*/Infrastructure/Persistence/Eloquent/Migrations as single source of truth, infer schema strictly, generate full production-ready modular SaaS system (Laravel modular monolith) with strict DDD/Clean Architecture, tenant isolation, event-driven integration, no cross-module business logic, no hardcoding, no stubs, no TODOs, no assumptions, no modification of existing Core modules, each module must be self-contained (Domain/Application/Infrastructure/Presentation), use repositories + DTO + use cases + service contracts, Inventory/Finance never depend on business modules, all integration via events, fully multi-tenant + org-unit aware, plug-and-play modules (add/remove without breaking system), enforce SOLID/DRY/KISS, dependency injection only, validate everything against migrations, output only complete production-ready code structure and implementations.

---

Before creating any new migration, table, service, model, event, contract, helper, or business logic, ALWAYS first check the existing implementation to avoid duplicates, overlapping responsibilities, inconsistent structures, and architectural conflicts. Prefer extending and improving the existing foundation instead of creating parallel or duplicate implementations unless a full replacement is intentionally required.

---

IMPORTANT FUTURE DESIGN THINKING REQUIREMENT

For all future module implementations, do not design only for the simplest happy-path case.

Before finalizing any database schema, backend service, API contract, or frontend UI, think deeply about real-world business edge cases and cross-module role/context issues.

Always consider:

- Can the same person/company act in multiple roles?
- Can a supplier also be a customer?
- Can a customer also become a supplier/provider?
- Can vehicle owner, service customer, billing customer, and payer be different?
- Can rental customer and vehicle provider be different?
- Can payer and invoice customer be different?
- Can payee and supplier/provider be different?
- Can an entity be internal, external, company-owned, customer-owned, supplier-owned, provider-owned, leased, or financed?
- Should this be modeled as a role/context instead of a fixed hardcoded table reference?
- Will this design support future modules without schema redesign?
- Are we accidentally blocking a valid real business scenario?
- Are we creating duplicate identities without traceability?
- Are we mixing ownership, billing, payment, and operational responsibility incorrectly?

Do not blindly hardcode one entity as one permanent role.

Use flexible, traceable, future-safe designs where needed.

Prefer:
- generic party/business-partner concepts if available
- role-based relationships
- source references
- ownership history
- billing/payer/payee separation
- service/provider/customer role separation
- clean cross-role linking

Avoid:
- assuming Customer and Supplier are mutually exclusive forever
- assuming vehicle owner is always service customer
- assuming billing customer is always payer
- assuming rental customer is always vehicle owner
- assuming provider/payee is always supplier only
- creating frontend workarounds for backend domain issues
- over-engineering huge abstractions when a simple traceable role-link is enough

When a design decision has multiple possible approaches, choose the simplest robust design that supports real business cases and future expansion.

If you discover a similar edge case while implementing any module, do not ignore it. Stop, analyze the domain impact, fix the design properly within the current scope, and mention it in the final report.

---

CORE MODULES ARE THE HEART OF THE APPLICATION

Core/shared modules are the foundation of this ERP/SaaS platform.

Treat these modules as the most critical modules in the system:

- Document
- Finance
- Inventory
- Payment
- Item
- UOM
- Pricing
- Sequence
- Customer
- Supplier
- HR
- Vehicle
- Tenant
- Configuration
- Audit
- User/Auth

These core modules must be designed deeply, carefully, and generically.

They must be:

- reusable
- plug-and-play
- extensible
- maintainable
- tenant-safe
- future-module-safe
- business-module-agnostic
- service-oriented
- cleanly bounded

CRITICAL RULE

Core modules must NOT contain business-module-specific workflow logic.

No Purchase-specific workflow logic inside core modules.
No Sales-specific workflow logic inside core modules.
No VehicleService-specific workflow logic inside core modules.
No VehicleRental-specific workflow logic inside core modules.
No Voucher-specific workflow logic inside unrelated core modules.

Core modules provide generic reusable capabilities.

Business modules orchestrate business workflows.

Examples:

Document module:
- allowed:
  - document definitions
  - templates
  - rendering
  - versioning
  - attachments
  - comments
  - workflow/status engine
- not allowed:
  - purchase invoice calculation
  - sales tax calculation
  - service invoice business rules
  - rental billing logic

Finance module:
- allowed:
  - chart of accounts
  - journals
  - AP/AR
  - tax engine
  - fiscal periods
  - bank/reconciliation
  - posting preview
- not allowed:
  - purchase workflow
  - sales workflow
  - vehicle service job logic
  - vehicle rental agreement logic

Inventory module:
- allowed:
  - stock levels
  - stock movements
  - reservations
  - transfers
  - adjustments
  - valuation
  - batches/serials
  - traceability
- not allowed:
  - purchase GRN workflow
  - sales delivery workflow
  - vehicle service job workflow
  - rental agreement workflow

Payment module:
- allowed:
  - payments
  - receipts
  - allocations
  - advances
  - refunds
  - write-offs
  - payment methods
  - payer/payee context
- not allowed:
  - purchase invoice workflow
  - sales invoice workflow
  - service job workflow
  - rental billing workflow

Pricing module:
- allowed:
  - price lists
  - pricing rules
  - discounts
  - tiers
  - resolve/preview price
- not allowed:
  - sales order workflow
  - purchase order workflow
  - rental agreement workflow
  - service job workflow

UOM module:
- allowed:
  - units
  - conversions
  - compatibility
  - conversion preview
- not allowed:
  - purchase quantity workflow
  - sales delivery workflow
  - rental billing workflow

Item module:
- allowed:
  - item definitions
  - item types
  - attributes
  - variants
  - combo definitions
  - stock/service/labour/non-inventory classification
- not allowed:
  - purchase order workflow
  - sales invoice workflow
  - service job workflow

Customer / Supplier / HR / Vehicle:
- allowed:
  - master data
  - roles/context
  - contacts
  - addresses
  - optional user access
  - validation/lookup services
- not allowed:
  - Purchase/Sales/VehicleService/VehicleRental workflow logic

BUSINESS MODULES SHOULD ORCHESTRATE

Business modules such as:

- Purchase
- Sales
- VehicleService
- VehicleRental
- Voucher

should orchestrate workflows by calling core module services.

Example:

Purchase invoice posting should:
- validate supplier through Supplier service
- validate items through Item service
- convert quantities through UOM service
- resolve price through Pricing service
- generate document through Document service
- receive/return stock through Inventory service
- post AP/journal through Finance service
- settle payment through Payment service

But Document, Inventory, Finance, Payment, Pricing, UOM should not know Purchase-specific workflow internally.

STRICT DEPENDENCY DIRECTION

Allowed dependency direction:

Business Module → Core Module

Allowed:
- Purchase → Document
- Purchase → Finance
- Purchase → Inventory
- Purchase → Payment
- Sales → Document
- Sales → Finance
- Sales → Inventory
- Sales → Payment
- VehicleService → Document/Finance/Inventory/Payment
- VehicleRental → Document/Finance/Payment
- Voucher → Finance/Payment/Document

Not allowed:
- Document → Purchase
- Finance → Sales
- Inventory → VehicleService
- Payment → VehicleRental
- Pricing → Purchase workflow
- UOM → Sales workflow

Core modules may depend on lower-level shared infrastructure only when needed:

- Tenant
- Configuration
- Sequence
- Audit
- User/Auth
- shared contracts/interfaces

CORE MODULE DESIGN CHECKLIST

For every core module, deeply review:

1. Is this module generic?
2. Is this module reusable by future business modules?
3. Does it contain business-specific workflow logic?
4. Does it directly reference Purchase/Sales/VehicleService/VehicleRental tables unnecessarily?
5. Does it use generic source references where needed?
6. Does it expose clean services/contracts?
7. Is it tenant-safe?
8. Is it organization-unit-aware where required?
9. Are calculations owned by backend, not frontend?
10. Is the database schema future-safe?
11. Are there hardcoded assumptions that block future modules?
12. Are APIs generic enough without becoming vague?
13. Are table/field names meaningful?
14. Are status/workflow/history/audit handled cleanly?
15. Are direct DB calls or cross-module table mutations creating coupling?
16. Are seeders/settings clean and module-specific?
17. Are frontend types/components not leaking business-specific assumptions into core UI?

GENERIC SOURCE REFERENCE RULE

Core modules that need to reference business records should use generic source reference fields:

- source_module
- source_type
- source_id
- source_reference
- source_context

Use this especially in:

- Document
- Finance
- Payment
- Inventory
- Voucher
- Audit
- Workflow/status/history

Avoid hardcoding only fields like:

- purchase_invoice_id
- sales_invoice_id
- service_invoice_id
- rental_invoice_id

unless it is a business-module-specific wrapper table.

CORE MODULE FINAL REQUIREMENT

Before moving forward, review all core modules deeply and fix any contamination or coupling.

If business logic is found inside a core module:

1. Remove it from the core module.
2. Move it to the correct business module.
3. Keep only generic reusable service behavior inside the core module.
4. Update APIs/resources/frontend types accordingly.
5. Add generic source references where needed.
6. Update tests/build.

This is mandatory.

The goal is:

Core modules = reusable heart of the system.
Business modules = workflow orchestration.

Do not allow core modules to become hidden business modules.

