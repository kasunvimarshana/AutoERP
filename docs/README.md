# AutoERP Documentation Suite & Architecture Index

- **Author:** Kasun Vimarshana
- **Email:** <kasunvmail@gmail.com>
- **Phone:** +94713525253

Welcome to the **AutoERP Documentation Suite**. This directory contains the authoritative technical specifications, architectural designs, user manuals, quality assurance protocols, database standards, and shared project memory for the AutoERP platform.

---

## 1. Primary Entry Points & Canonical References

| Resource | Path | Description |
| --- | --- | --- |
| **Repository Root Overview** | [`README.md`](file:///d:/project-new/AutoERP/README.md) | Ground-up platform architecture, technology stack, setup guide, and module catalog. |
| **Project Knowledge Base & Technical Guide** | [`project_knowledgebase.md`](file:///d:/project-new/AutoERP/docs/project_knowledgebase.md) | Central technical knowledge base, developer onboarding handbook, module encyclopedia, and architecture manual. |
| **Enterprise Knowledge Base** | [`knowledgebase.md`](file:///d:/project-new/AutoERP/docs/knowledgebase.md) | Canonical technical specification, developer onboarding handbook, and domain encyclopedia. |
| **Core Engineering Axioms** | [`AGENTS.md`](file:///d:/project-new/AutoERP/AGENTS.md) / [`RULES.md`](file:///d:/project-new/AutoERP/RULES.md) | The 12 non-negotiable architectural axioms governing backend, frontend, database, concurrency, and UI/UX. |
| **Permanent Project Rules** | [`DURABLE_RULES.md`](file:///d:/project-new/AutoERP/DURABLE_RULES.md) | Architectural invariants, database migration standards, and Markdown documentation rules. |
| **Audit Authority Protocol** | [`AUDIT_AUTHORITY.md`](file:///d:/project-new/AutoERP/docs/AUDIT_AUTHORITY.md) | Authoritative precedence hierarchy for reconciling code, tests, append-only change logs, and business evidence. |
| **Vehicle Rental Technical Guide** | [`modules/vehicle_rental_complete_guide.md`](file:///d:/project-new/AutoERP/docs/modules/vehicle_rental_complete_guide.md) | Complete end-to-end technical specification for commercial fleet leasing, running charts, billing, and settlements. |
| **Vehicle Rental Operator Manual** | [`user-guides/vehicle_rental_user_guide.md`](file:///d:/project-new/AutoERP/docs/user-guides/vehicle_rental_user_guide.md) | Comprehensive, beginner-friendly operator handbook for daily dispatchers, billing clerks, cashiers, and managers. |
| **Database Key Naming Standard** | [`database/migration-key-naming.md`](file:///d:/project-new/AutoERP/docs/database/migration-key-naming.md) | Explicit line-by-line foreign key, unique key, and index naming conventions (`_fk`, `_uk`, `_ix`). |

---

## 2. Directory Structure & Documentation Catalog

```text
docs/
├── README.md                                  # This documentation portal and catalog
├── project_knowledgebase.md                   # Central technical knowledge base & developer guide
├── knowledgebase.md                           # Canonical system specification & developer guide
├── AGENTS.md                                  # The 12 Core Engineering Axioms & Rules
├── RULES.md                                   # Synchronized copy of the 12 Core Axioms
├── DURABLE_RULES.md                           # Permanent project rules and migration standards
├── AUDIT_AUTHORITY.md                         # Precedence hierarchy and evidence governance
│
├── modules/                                   # Domain-specific technical manuals
│   └── vehicle_rental_complete_guide.md       # Canonical Vehicle Rental technical manual
│
├── user-guides/                               # End-user operator manuals
│   └── vehicle_rental_user_guide.md           # Step-by-step visual workflow manual
│
├── architecture/                              # High-level architecture papers & reviews
│   ├── dual-timestamp-and-backdated-crud-a... # Universal dual-timestamp & backdated CRUD specification
│   ├── financial-integrity-foundation.md      # Double-entry ledger and posting profile design
│   ├── autoerp-saas-tenancy-and-pricing-...   # SaaS subscription tiers and tenancy review
│   └── vehicle-service-promotions-...         # Service cards, offers, and promotion designs
│
├── database/                                  # Database architecture & schema policies
│   └── migration-key-naming.md                # Explicit key and index naming standard
│
├── plans/                                     # Implemented feature plans & roadmap specs
│   ├── dashboard-business-overview-final...   # Executive dashboard design & metrics
│   ├── vehicle-service-job-cancellation-...   # Service job cancellation state transitions
│   ├── vehicle-service-workforce-assignm...   # Workforce assignment panel design
│   ├── whatsapp-document-sharing.md           # PDF document sharing via WhatsApp
│   └── whatsapp-manual-number-verificati...   # Customer phone verification workflow
│
├── qa/                                        # Quality assurance & verification suites
│   ├── 2026-09-15-full-system-sample-dat...   # End-to-end sample data execution flow
│   └── 2026-11-14-system-feature-and-tes...   # Feature-by-feature test catalog & matrix
│
├── audits/                                    # Active audit fixtures & historical archives
│   ├── purchase-debit-note-currency-reco...   # SQL test fixture (loaded by PHPUnit)
│   ├── finance-journal-ledger-integrity.sql   # Double-entry GL integrity verification SQL
│   ├── core-integrity-closure-matrix.md       # Platform integrity remediation closure matrix
│   └── historical/                            # Archived historical audits & video reports (2026-07)
│
├── vehicle-rental/                            # Commercial vehicle rental domain research
│   ├── agreements.md                          # Agreement aggregate domain rules
│   ├── operations.md                          # Custody, replacement & dispatch workflows
│   ├── base-billing.md / mileage-billing.md   # Invoicing models and calculation algorithms
│   └── deposits.md / usage-billing.md         # Treasury deposits & running chart usage
│
└── changes/                                   # Shared Project Memory (Append-Only)
    └── YYYY-MM-DD-<description>.md            # Immutable records of all changes & verifications
```

---

## 3. Verified System Baseline & Quality Gates

The AutoERP codebase is continuously verified across strict automated regression gates:

| Quality Gate | Tool / Command | Verified Baseline Status |
| --- | --- | --- |
| **Backend Test Suite** | `php artisan test` | **1,433 tests passed** (15,106 assertions, 0 failures) |
| **Frontend Test Suite** | `npm test` | **177 test files passed** (745 tests passed, 0 failures) |
| **TypeScript Static Check** | `npm run typecheck` | **0 errors** (strict compiler mode) |
| **ESLint Static Analysis** | `npm run lint` | **0 errors**, 0 warnings |
| **Database Migrations** | `php artisan test --filter ModuleMigrationBaselineTest` | **302 module tables** (307 total tables: 5 root + 302 module), 1:1 migration mapping, 0 alteration patches |
| **Historical Data Retention** | `php artisan test --filter HistoricalRetentionMigrationTest` | **100% compliance** (`restrictOnDelete()` on status histories) |

---

## 4. The 12 Non-Negotiable Core Engineering Axioms

All development, bug fixes, refactoring, and AI-agent interactions must adhere strictly to [`AGENTS.md`](file:///d:/project-new/AutoERP/AGENTS.md):

1. **Understand First, Verify Second, Change Third:** Never act on assumptions or guess. Fully understand requirements, module ownership, and data consequences before modifying code.
2. **Root Cause Design over Compatibility Workarounds:** Fix root causes at their source. Do not preserve legacy flaws or build compatibility patches unless explicitly required.
3. **Simplicity, Pragmatism & No Overengineering:** Prefer the simplest clean design that solves the problem. Avoid premature optimization or generic frameworks. No dead code.
4. **Module Ownership & Clear Boundaries:** Each of the 32 backend modules in `app/Modules/*` (paired with 33 frontend modules in `resources/js/modules/*`) owns its models, tables, and logic. Never introduce cross-module workarounds or shadow tables.
5. **Backend Authority & Single Source of Truth:** Core calculations, business logic, state transitions, permissions, and validations reside authoritatively on the backend.
6. **Concurrency, Atomicity & Transaction Immutability:** Write mutations must be atomic, conflict-aware, and version-checked (`expected_version` / `row_version`). Historical operational and financial records are immutable.
7. **Database Architecture & Explicit Migrations:** One table per migration file. Write every column, constraint, foreign key, and index explicitly line-by-line (KISS + Explicit over DRY). Zero dynamic loops or runtime naming.
8. **Code Modularity & File Size Management:** Split files only when there is a clear architectural reason (feature boundary, separation of concerns) to prevent unreadable, oversized files.
9. **Zero Magic Values:** Use PHP Backed Enums (`string`) and TypeScript enums for predefined option sets. Use named constants for shared values.
10. **UI/UX Clarity & Cognitive Efficiency:** Design for speed and task completion. **Never expose raw database IDs or internal primary keys in the UI.** Use controlled lookup selectors (`LookupSelect`, `GenericLookupSelect`). Display transparent calculation formulas and contextual hints.
11. **Security & Predictable Error Handling:** Never weaken validation or expose secrets. Handle errors explicitly and predictably without swallowing exceptions.
12. **Shared Project Memory via `/docs/changes`:** The `/docs/changes` directory is the project's shared append-only memory. Read recent records before starting work, modify only necessary files, and create a new record detailing changes and verification results upon completion. Existing change records are never edited or deleted.

---

## 5. Developer Quickstart & Command Cheatsheet

### Running Local Development

```bash
# Start backend server, queue worker, log tail, and Vite HMR simultaneously
composer dev
```

### Running Automated Test Gates

```bash
# Backend unit, feature, and modular tests (SQLite :memory:)
php artisan test

# Run specific module or baseline tests
php artisan test --filter ModuleMigrationBaselineTest
php artisan test --filter HistoricalRetentionMigrationTest
php artisan test app/Modules/VehicleRental/Tests/

# Frontend component & integration tests (Vitest)
npm test

# TypeScript strict type checking
npm run typecheck

# Code quality and style linting
npm run lint
```

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
- **Repository:** [kasunvimarshana/AutoERP](https://github.com/kasunvimarshana/AutoERP)
