You are working on my modular business management application.

The most critical part of this application is:

app\Modules\Document

This Document module is the CORE / HEART of the entire application. It must be implemented 100% correctly, cleanly, and production-ready.

MAIN GOAL
Reimplement app\Modules\Document from the beginning as a:

- Fully dynamic
- Fully customizable
- Reusable
- Extensible
- Maintainable
- Plug-and-play
- Business-logic-free template engine

VERY IMPORTANT:
The Document module MUST NOT contain any business logic.

Do not hard-code invoice logic.
Do not hard-code purchase logic.
Do not hard-code sales logic.
Do not hard-code vehicle service logic.
Do not hard-code vehicle rental logic.
Do not assume any fixed document type.

This module must only provide a generic dynamic document/template engine that other modules can use.

Business modules must define their own business rules outside the Document module.

The Document module should only handle:
- Document definitions
- Dynamic templates
- Dynamic fields
- Layout/configuration
- Versions
- Attachments
- Comments
- Activities
- Events
- History
- Permissions
- Relations/references
- Generic document records
- Generic document values
- Generic lifecycle/status support if needed

Before implementing, carefully study the current project structure.

Reference these modules to understand coding standards, file structure, naming style, migrations, services, repositories, actions, DTOs, controllers, requests, resources, enums, policies, events, providers, and general architecture:

- Core module
- Configuration module
- Tenant module
- Any other well-structured existing module

Then reimplement Document module following the same standards.

DATABASE / MIGRATION REQUIREMENTS

Review the old/current Document module migrations carefully.

I am not 100% sure whether the current feature list is complete, so you must double-check everything.

Old module may have features like:
- permissions
- activities
- comments
- attachments
- document versions
- events
- history
- relations
- dynamic fields
- template definitions

Do not remove these features accidentally.

If a feature exists in the old Document module and is still generic/template-engine related, preserve it.

If a feature contains business logic, remove the business logic but preserve the generic capability.

Example:
Wrong: invoice approval logic inside Document module.
Correct: generic document status / workflow support, while invoice approval rules stay in invoice/sales/purchase module.

All database tables must be reviewed and fixed if needed.

If tables, fields, indexes, foreign keys, tenant references, audit fields, or relations are missing, add them.

If the schema is wrong, fix it.

If previous refactor changes damaged the module, ignore those broken changes and rebuild correctly.

The final database design must be:
- relational
- clean
- normalized
- multi-tenant ready
- maintainable
- future extendable
- no JSON dependency if avoidable
- no business-specific assumptions

Use proper migrations.
Prefer one table per migration file if that is the project standard.

FEATURE REQUIREMENTS

The Document module should support a generic template engine concept.

It should allow other modules to define document templates dynamically.

Possible generic concepts:

1. Document Type / Definition
A generic definition of a document type/template.
Example: sales invoice, purchase order, service report, rental agreement — but these names are examples only. Document module should not know their business rules.

2. Template
A reusable structure/layout for documents.

3. Template Fields
Dynamic fields that can be configured per template.
Fields should support common data types such as:
- text
- number
- decimal
- boolean
- date
- datetime
- select/options
- reference
- file/attachment
- calculated/display-only if needed, but calculation rules must not become business logic inside this module

4. Document Record
A created document instance based on a template/definition.

5. Document Values
Dynamic values for each document record.

6. Versioning
Documents/templates should support version history where required.

7. Attachments
Generic attachment support.

8. Comments
Generic comments/discussion support.

9. Activities / Events / History
Generic activity log/history/event tracking support.

10. Permissions
Generic access control/permission hooks.
Do not hard-code business permission rules.
Integrate with the project’s existing permission system if available.

11. Relations
Generic ability to relate a document to external module records using safe references.
The Document module may store generic references, but it must not know business rules of those modules.

CRITICAL RULE: NO BUSINESS LOGIC

This must be highlighted everywhere.

The Document module is a template engine only.

Business logic must live in business modules.

Examples:

Purchase module decides purchase document rules.
Sales module decides sales document rules.
VehicleService module decides service document rules.
VehicleRental module decides rental document rules.

Document module only stores and renders/configures generic templates and document data.

CODE QUALITY REQUIREMENTS

The implementation must be:

- readable
- maintainable
- clean
- consistent with project standards
- not over-engineered
- simple where possible
- extensible where necessary
- production-ready

Avoid unnecessary abstractions.
Avoid unnecessary complexity.
Do not build a massive framework inside the module.
Build a clean, practical, flexible template engine.

REVIEW REQUIREMENTS

Before coding:
1. Review existing Document module completely.
2. Review old migrations and existing tables.
3. Identify all generic features.
4. Identify all business-specific logic.
5. Decide what to preserve, what to remove, and what to redesign.
6. Review Core, Configuration, and Tenant modules for project standards.

During implementation:
1. Rebuild Document module properly.
2. Fix database issues.
3. Preserve generic features.
4. Remove all business logic.
5. Align folder/file/code style with project standards.
6. Keep the module plug-and-play.

After implementation:
1. Review the complete module again.
2. Confirm no business logic remains.
3. Confirm all important old features are preserved.
4. Confirm migrations are correct.
5. Confirm module is multi-tenant ready.
6. Confirm other modules can use this Document module without modifying its core.
7. Confirm the code is readable and maintainable.
8. Confirm no over-engineering was introduced.

CROSS-MODULE CHECK

If, while refactoring Document module, you find the same structural mistake or inconsistency in other modules, check those modules too.

If a fix is needed to keep the system consistent and plug-and-play, apply the fix carefully.

But do not move business logic into Document module.

FINAL EXPECTED RESULT

I need a complete reimplementation of app\Modules\Document as the application’s core template engine.

The final module must be:

- 100% production-ready
- fully dynamic
- fully customizable
- reusable
- extensible
- maintainable
- multi-tenant ready
- plug-and-play
- business-logic-free
- aligned with my project coding standards
- database schema reviewed and fixed
- old generic features preserved
- future enhancement friendly
- not over-engineered

Treat this module as the heart of the entire application.
Do not rush it.
Double-check everything.
Implement it carefully from end to end.

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
