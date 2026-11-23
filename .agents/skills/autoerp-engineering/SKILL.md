---
name: autoerp-engineering
description: >-
  Actionable procedures, verification runbooks, migration standards, anti-pattern checklists,
  and change-log protocols for developing, testing, and auditing modules in AutoERP.
  Use whenever running test gates, planning features, writing database migrations, or logging changes.
---

# AutoERP Engineering & Verification Skill

This skill provides comprehensive operational runbooks, architectural invariants, database migration standards, and quality gate verification procedures for AutoERP in strict compliance with the 12 Core Axioms in `AGENTS.md`.

---

## 1. Pre-Task Protocol & Context Check
Before modifying any code or database schema:
1. **Consult Shared Project Memory:** Read recent records in `/docs/changes` to understand recent active work and avoid conflicting changes or regressions.
2. **Verify Module Ownership & Boundaries:** Identify responsible modules (`app/Modules/<ModuleName>/` and `resources/js/modules/<module-name>/`). Never introduce inter-module workarounds or shadow tables.
3. **Evidence Over Guessing:** If business rules or historical records are ambiguous, request clarification. Physical facts (trips, damaged parts, labor hours) may be recorded, but **no monetary transaction may be invented** without explicit business entitlement.
4. **Implementation Plan:** If changes require schema migrations or cross-module interfaces, prepare a structured plan before making code edits.

---

## 2. Complete Quality Gate Verification Matrix
AutoERP enforces a zero-tolerance policy for broken tests, type errors, or linting violations. Always run the appropriate gates before declaring any task complete:

| Quality Gate | Tooling / Command | Target Baseline / Pass Criteria |
|---|---|---|
| **TypeScript Typecheck** | `npm run typecheck` | **0 errors** across all TS/TSX files (strict mode) |
| **ESLint Analysis** | `npm run lint` | **0 errors** across `resources/js/*` |
| **Backend Code Style (Pint)** | `php vendor/bin/pint --test` | **0 PSR-12 violations** |
| **Migration Baseline Integrity**| `php artisan test --filter ModuleMigrationBaselineTest` | **1 passed (3 assertions)**: 1:1 table migration mapping, 0 alteration patches |
| **Historical Retention Integrity** | `php artisan test --filter HistoricalRetentionMigrationTest` | **1 passed (22 assertions)**: 100% `restrictOnDelete()` compliance on audit tables |
| **Frontend Test Suite** | `npm test` or `npx vitest run` | **176 passed / 176 test files (735 tests passed)** |
| **Backend Test Suite** | `php artisan test` | **1,364 passed / 1,364 tests (14,218 assertions)** |
| **Live MySQL Integration** | `composer test:mysql` | Multi-connection transactions & locking verification |

---

## 3. Database Migration & Schema Design Standards
Migrations represent **immutable database schema specifications**, not dynamic application code:
- **One Table Per Migration File:** Every database table must be created in its own dedicated migration file. Never combine multiple table creations.
- **Zero Patch Migrations:** Historical `Schema::table()` alteration patches are forbidden. Additions and alterations are merged directly into the original creation migration.
- **KISS + Explicit over DRY:**
  - Write every column, data type, nullability, and default value explicitly line-by-line.
  - Never use dynamic loops (`foreach`), programmatic column naming, or runtime conditionals.
  - Native database `enum` types (`$table->enum(...)`) are prohibited; use bounded strings (`$table->string('status', 32)`) backed by PHP Backed Enums.
  - Monetary values and exchange rates use `$table->decimal('amount', 20, 6)`.
- **Constraint & Index Naming Standard (`<table-alias>_<business-purpose>_<suffix>`):**
  - **Foreign key:** `_fk` (e.g. `vrca_customer_id_fk`)
  - **Unique key:** `_uk` (e.g. `vrca_agreement_number_uk`)
  - **Normal index:** `_ix` (e.g. `vrca_tenant_status_ix`)
  - **Custom / composite primary key:** `_pk` (e.g. `reference_values_tenant_code_pk`)
  - **Check constraint:** `_ck`
  - Maximum identifier length: **60 characters** (leaves safety margin under MySQL's 64-char limit).
  - Every `foreignId()->constrained()` declaration must provide an explicit `indexName: '...'`.
  - Composite referenced indexes must be declared before the foreign keys that depend on them.
- **Audit Retention (`restrictOnDelete`):**
  - Cascading deletions (`cascadeOnDelete()`) on historical operational, financial, or state-history tables (`*_status_histories`) are prohibited.
  - All relationship references from history/audit tables must specify `restrictOnDelete()`.

---

## 4. Financial Core, Arithmetic & Immutability Patterns
- **Exact Arbitrary-Precision Arithmetic:** Never perform floating-point math on financial numbers. All monetary and currency calculations must use `DecimalMath` (PHP BCMath string arithmetic).
- **Public Invoices Create Drafts Only:** Public endpoints submit business inputs only (direction, dates, customer/supplier, line items). All tax calculations, grand totals, line numbers, document sequences, and lifecycle statuses are authoritative server-owned facts.
- **Historical Master Snapshots:** Master party and item identities are copied into immutable header and line snapshots at creation time. Historical document rendering reads frozen snapshots, never mutable master entities.
- **Optimistic Concurrency Control:** Every mutable operational entity carries an unsigned integer `row_version`. API mutations require `expected_version` and atomically assert `WHERE row_version = ?` while incrementing.
- **Explicit Optional Form Defaulting & Field Presence:** In form requests and DTOs, distinguish between omitted fields and explicitly cleared fields (`null`/`""`) using `$this->has('field')`. Partial updates must preserve existing database entity values for omitted fields.
- **Blade Template Sandboxing & Tenant Layout Isolation:** Custom Blade templates and layouts (e.g. invoices, vouchers, printouts) must never include unescaped raw PHP execution or direct arbitrary filesystem includes outside registered tenant views.
- **Token Rotation Grace Window:** Refresh token rotation must provide an idempotent concurrency grace window (30 seconds) to prevent simultaneous API requests from triggering false token revocation.

---

## 5. The 10 Prohibited Architectural Anti-Patterns
The following practices are **strictly prohibited** across AutoERP:
1. **Direct Vehicle-to-Agreement Foreign Keys:** Vehicles must never be bound directly to customer agreements; all assignments must pass through `VehicleUse` to preserve replacement lineage.
2. **Destructive In-Place Edits on Posted Transactions:** Posted invoices, payments, vouchers, or running charts must never be edited in-place or deleted. Corrections must follow governed reversal/credit/reissue workflows.
3. **Inter-Module Workarounds:** Never compensate for missing features in `Invoice`, `Payment`, `Inventory`, or `Finance` by building shadow tables in a business module.
4. **Fictitious Owner Costs:** Never manufacture artificial Owner Agreements or payable vouchers for company-owned vehicles.
5. **Hardcoded Magic Account Numbers:** Never hardcode legacy account codes in business logic; all GL postings must resolve through `Finance` semantic posting profiles.
6. **Exposing Raw Database IDs in the UI:** The user interface must never display raw integer IDs. Relational inputs must use controlled selection components (`LookupSelect`, `GenericLookupSelect`).
7. **Client Timezone Drift & Hardcoded Timezones:** Never rely on browser UTC string slicing (`.toISOString()`) or hardcode a static timezone. All business dates and timestamps must be formatted using `businessDate.ts` against the workspace's dynamically configured civil timezone (`localization.timezone` / application configuration).
8. **Dynamic Migration Code:** Never use dynamic loops, runtime conditionals, or raw SQL statements in migration files.
9. **Omitted Field Overwrites on Partial Updates:** Never assume omitted request fields imply nullification; always verify presence (`$this->has()`) before updating existing entity attributes.
10. **Unchecked Token Revocation on Concurrency:** Never invalidate refresh tokens immediately without an idempotent grace window for concurrent requests.

---

## 6. Audit Precedence Hierarchy & Verification Language
When conducting audits or reviewing historical reports, follow [`AUDIT_AUTHORITY.md`](file:///d:/project-new/AutoERP/docs/AUDIT_AUTHORITY.md):
- **Precedence Order:**
  1. Latest production code and migrations.
  2. Automated behavioral tests executed against the same commit and database engine.
  3. Latest append-only records in `/docs/changes`.
  4. Current architecture and product specifications.
  5. Historical project audit reports.
  6. Legacy-system video audit reports.
- **Finding Classification Labels:**
  - `Current defect`: Reproduced in current active code.
  - `Verification gap`: Current behavior not yet proven in the required environment.
  - `Resolved`: Corrected and linked to a change record and test evidence.
  - `Decision required`: Implementation requires an unapproved business or deployment rule.
  - `Historical evidence`: Describes a legacy system or older commit.
  - `Not applicable`: Valid evidence that does not apply to current scope.
- **Precise Verification Language:**
  - *“Source review passed”*: Source and module boundaries inspected.
  - *“Focused tests passed”*: Named test commands executed on the stated commit.
  - *“Full automated gates passed”*: SQLite, MySQL, Vitest, typecheck, lint, and build verified on the same commit.
  - *“Production ready”*: Full automated gates plus migration rehearsal, backup/restore verification, and UAT.

---

## 7. Post-Task Shared Memory Protocol (`/docs/changes`)
Upon completing and verifying any task:
1. Create a new markdown record in `/docs/changes/YYYY-MM-DD-<feature-slug>.md`.
2. Format structure:
   - **Context & Objectives:** Problem addressed, scope, and rationale.
   - **Modified & Created Files Summary:** Table listing every touched file and its change description.
   - **Verification & Quality Gate Results:** Exact command outputs and pass status for all executed gates.
   - **Author & Maintainer Signature:**
     - Lead Architect & Developer: Kasun Vimarshana
     - Email: kasunvmail@gmail.com
     - Phone: +94713525253
     - Repository: https://github.com/kasunvimarshana/AutoERP
3. Never edit or delete existing change records.

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
- **Repository:** [kasunvimarshana/AutoERP](https://github.com/kasunvimarshana/AutoERP)
