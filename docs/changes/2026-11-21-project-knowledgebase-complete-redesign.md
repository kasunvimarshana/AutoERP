# AutoERP Project Knowledge Base Complete Redesign & Reimplementation

**Date:** 2026-11-21  
**Modules:** Documentation, Core, Architecture, All 31 Business Modules  

### Summary of Changes

1. **Complete Redesign and Reimplementation of `docs/project_knowledgebase.md` from Scratch:**
   - Authored the authoritative, beginner-friendly technical guide and enterprise system specification based on the actual codebase implementation as the single source of truth.
   - Totaling 1,185 lines across 10 structured chapters:
     * **Chapter 1: Executive System Overview & Architectural Axioms:** Core system purpose, dual operating planes (Platform Control Plane vs. Tenant Business Plane), non-negotiable architectural axioms (`AGENTS.md` / `RULES.md`), and system source precedence hierarchy.
     * **Chapter 2: Technology Stack & Prerequisites:** Backend (PHP 8.2+/8.3, Laravel 12, Passport OAuth2, BCMath `DecimalMath`), Frontend (React 19, TypeScript 6, Vite 7, Tailwind CSS 4, React Router DOM 7, Zustand 5, Axios), and system prerequisites.
     * **Chapter 3: Setup, Installation & Local Development:** Step-by-step installation (`composer setup`), environment configuration (`.env`), unified concurrent runner (`npm run dev`), and testing commands (`php artisan test`, `npm test`, `npm run typecheck`, `npm run lint`).
     * **Chapter 4: Platform Foundations & Core Architectural Patterns:** Shared-schema multi-tenancy (`TenantOwnedModel`, `TenantScope`), optimistic concurrency control (`row_version` & `expected_version`), operational ledger immutability (`LogicException` locks on updates/deletes), exact decimal math (`DecimalMath`), temporal causality & dynamic workspace timezone resolution (`ConfigurationKey::WORKSPACE_TIMEZONE`), zero magic values (PHP Backed Enums & Constants), KISS + explicit migrations (canonical 1-table-per-file baseline), and idempotency engine.
     * **Chapter 5: Complete Module Catalog & Domain Responsibilities:** Detailed domain breakdowns for all 31 application modules under `app/Modules/`, cataloging exact responsibilities, key models, service classes, and all 259 canonical domain database tables.
     * **Chapter 6: Database Architecture & Core Schema Relationships:** Database topology (259 domain tables + 5 framework tables), multi-tenant composite foreign key conventions (`['foreign_key_id', 'tenant_id']` with `ON DELETE RESTRICT`), and Mermaid ER diagrams.
     * **Chapter 7: End-to-End Business Workflows:** Detailed execution graphs for Commercial Vehicle Rental (dual agreements, custody, 31-day running chart matrix, debtor billing, owner settlement with 10% WHT ledger `:3305-000` & CDN repair deductions), Automotive Workshop Service (job cards, automatic stock reservations and issues, labor commissions, invoicing), Supply Chain & Fast Purchase (PO &rarr; GRN &rarr; Supplier Invoice &rarr; Payment), Order to Cash (Sales Orders, deliveries, invoicing), and Double-Entry General Ledger (semantic account roles, posting profiles, balanced journals).
     * **Chapter 8: Frontend Architecture & UI/UX Standards:** React 19 SPA architecture, design system component library (`Modal`, `ConfirmDialog`, `Tabs`, `Alert`, `Badge`), controlled lookups & zero raw IDs rule (`LookupSelect`), and speed & clarity UX philosophy.
     * **Chapter 9: Development Practices, Conventions & Maintenance:** Strict PHP typing, Laravel Pint, TypeScript strict typing, ESLint compliance, database migration rules, `/docs/changes` append-only memory rules, and troubleshooting guide.
     * **Chapter 10: Practical Developer Tutorials & Code Examples:** End-to-end tutorials for creating version-checked mutation endpoints, implementing semantic financial posting events, creating controlled React lookup components, and authoring canonical database migrations.

2. **Index Linkage in `docs/README.md`:**
   - Updated `docs/README.md` under Core Technical & Architectural Guides to prominently feature [AutoERP Project Knowledge Base & Technical Developer Guide](file:///d:/project-new/AutoERP/docs/project_knowledgebase.md).

### Verification Record

- Verified `docs/project_knowledgebase.md` markdown anchor syntax and table of contents alignment.
- Verified database table counts: exactly 259 domain tables across 259 canonical migration files + 5 framework tables = 264 total tables.
- Verified module count: exactly 31 modules registered in `bootstrap/providers.php` and present under `app/Modules/`.
- Verified test suite integrity:
  * `php artisan test --filter=ModuleMigrationBaselineTest`: Passed (3 assertions).
  * `vendor/bin/pint --test docs/README.md`: N/A (markdown documentation).
