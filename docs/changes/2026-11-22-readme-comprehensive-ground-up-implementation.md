# Ground-Up Design and Implementation of Central `README.md`

**Date:** 2026-11-22  
**Directives:** `/compact /learn /plan /boost /goal`  
**Scope:** Root Project Documentation and Developer Technical Knowledge Base  

---

### Executive Summary

In response to the directive to design and implement `README.md` from scratch as a comprehensive, accurate, and beginner-friendly technical guide for the entire AutoERP platform, a ground-up specification was created directly from the audited codebase implementation.

Previously, no `README.md` existed in the repository root. This overhaul establishes `README.md` as the single authoritative onboarding guide and central technical knowledge base, adhering strictly to the 12 Core Engineering Axioms (`AGENTS.md` / `RULES.md`).

---

### Key Documentation Deliverables Implemented in `README.md`

1. **Executive Overview & Core Capabilities:**
   - Positioned AutoERP as an enterprise Modular Monolith combining commercial vehicle rental operations, workshop maintenance, supply chain procurement, inventory warehousing with FIFO/WAC valuation, canonical invoicing, treasury management, double-entry GL accounting, statutory taxation, and executive business reporting.

2. **The 12 Core Engineering Axioms:**
   - Formally documented all 12 non-negotiable architectural axioms governing development across backend, frontend, database, concurrency, and audit memory.

3. **System Architecture & Visual Diagrams:**
   - Included clean Mermaid flowchart diagrams illustrating the layers: React 19 SPA, HTTP Gateway & Tenancy Middleware (`current.user`, `current.tenant`, `current.organization-unit:required`), Cross-Cutting Core Foundation (`DecimalMath`, `Idempotency`, `Sequence`, `PrivateObject`, `Audit`), and the 31 autonomous domain modules.

4. **Audited Technology Stack & Environment Requirements:**
   - PHP 8.2+ (strict types), Laravel 12.x, Passport 13.x, BCMath `DecimalMath`.
   - React 19.2+, TypeScript 6.0+ (strict mode), Tailwind CSS 4.0+, React Router 7.x, Zustand 5.x, Vite 7.x.
   - MySQL 8.0+ / SQLite, 266 canonical tables, SHA-256 tamper-evident private object storage.

5. **Beginner-Friendly Quick Start & Step-by-Step Installation:**
   - Documented the one-command automated provisioning workflow (`composer setup`).
   - Documented explicit step-by-step setup commands and environment configuration (`.env`).
   - Detailed the local development workflow (`composer dev`) spawning HTTP server, queue worker, log tailing, and Vite HMR via `concurrently`.

6. **Complete Domain Module Catalog (31 Modules):**
   - Detailed matrix of all 31 modules in `app/Modules/*`, listing category, primary Eloquent models, and core public responsibilities.

7. **Database Architecture & Schema Governance:**
   - Documented the Single-Table Baseline Migration Principle (266 canonical tables, zero patches, zero raw SQL).
   - Documented KISS + Explicit over DRY rules, key naming standards (`_fk`, `_uk`, `_ix`), and immutable status histories enforcing `restrictOnDelete()`.
   - Documented the full topological seeder hierarchy across 18 seeder steps.

8. **Cross-Cutting Foundations & Security:**
   - Dual-plane multi-tenancy (`TenantOwnedModel` with automatic query scoping).
   - OAuth 2.0 Passport authentication with secure, HttpOnly refresh cookies and granular RBAC.
   - Request Idempotency engine (`Idempotency-Key` header with SHA-256 fingerprinting).
   - Atomic Sequence generation for monotonic document numbering.
   - Tamper-evident private object storage with SHA-256 integrity verification.

9. **Key Business Workflows & Deep Dives:**
   - Commercial Vehicle Rental Operating Engine: Dual contracts (`CustomerAgreement` vs. `OwnerAgreement`), successor lineage architecture, `VehicleUse` custody state machine, atomic vehicle replacement (`VehicleUseService::replace()`), 31-day running charts, calendar-day billing proration, monthly owner settlements with 10% WHT ledger (`:3305-000`), and 4-way fleet expense matrix.
   - Automotive Workshop Service: Repair job cards, inspection checklists, technician commissions, parts issues, and mechanical custody blockers.
   - Supply Chain & Fast Purchase: Procurement track, single-screen Fast Purchase, 4-dimension inventory valuation (FIFO/WAC).
   - Financial Core: Canonical `Invoice`, `Payment` treasury allocations, `Finance` double-entry GL with dynamic semantic posting profiles, and statutory tax snapshots.
   - Enterprise Reporting Suite: Comprehensive catalog of all 12 operational and financial reports with orientation and FRX legacy lineage.

10. **Engineering Practices & Coding Standards:**
    - Concrete PHP examples for strict typing, thin controllers, PHP Backed Enums, Optimistic Concurrency Control (`expected_version` / `row_version`), BCMath arbitrary-precision arithmetic (`DecimalMath`), deny-by-default mass assignment (`$guarded = ['*']`), and pessimistic locking.
    - Frontend standards: TypeScript strict mode, zero raw database IDs in UI, controlled lookups (`LookupSelect`, `GenericLookupSelect`), calculation transparency hints, civil business timezone (`Asia/Colombo` with `businessDate.ts`), and progressive disclosure.

11. **Testing, Maintenance & Custom CLI Commands:**
    - Testing suite commands table (`php artisan test`, `composer test:mysql`, `npm test`, `npm run typecheck`, `npm run lint`, `vendor/bin/pint --test`, architecture tests).
    - Custom Artisan CLI commands directory.
    - Operational troubleshooting guide for common scenarios (OCC 409 conflicts, idempotency collisions, date shifts, tenant access).

---

### Verification Summary

| Gate | Tool / Command | Result |
|---|---|---|
| Documentation Baseline | Inspection of `README.md` | Created with 953 lines of comprehensive technical content |
| Migration Baseline Integrity | `php artisan test --filter ModuleMigrationBaselineTest` | Passed (266 canonical tables, 0 alterations, 0 raw SQL) |
| Frontend Typecheck | `npm run typecheck` | Passed (0 errors, `tsc --noEmit`) |
