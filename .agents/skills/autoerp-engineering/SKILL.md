---
name: autoerp-engineering
description: >-
  Actionable procedures, verification runbooks, migration standards, and change-log
  protocols for developing, testing, and auditing modules in AutoERP. Use whenever
  running test gates, planning features, writing database migrations, or logging changes.
---

# AutoERP Engineering & Verification Skill

This skill provides step-by-step procedures for building and verifying features in AutoERP in strict compliance with the 12 Core Axioms in `AGENTS.md`.

## 1. Pre-Task Protocol
1. Read recent entries in `/docs/changes` to understand current work and avoid regressions.
2. Identify module ownership (`app/Modules/<ModuleName>/` and `resources/js/modules/<module-name>/`).
3. If changes require database schema updates or significant architecture, prepare an implementation plan and seek approval.

## 2. Quality Gate Verification Recipes
Always run the appropriate quality gates before declaring any task complete:

| Gate | Command | Expected Output |
|---|---|---|
| **TypeScript Typecheck** | `npm run typecheck` | 0 errors |
| **ESLint Analysis** | `npm run lint` | 0 errors |
| **Migration Baseline** | `php artisan test --filter ModuleMigrationBaselineTest` | 1 passed (3 assertions) |
| **Historical Retention** | `php artisan test --filter HistoricalRetentionMigrationTest` | 1 passed (22 assertions) |
| **Frontend Test Suite** | `npx vitest run` | 176 passed / 176 files |
| **Backend Test Suite** | `php artisan test` | 1,364 passed / 1,364 tests |

## 3. Database Migration Standards
- **One table per migration file**: Never modify or create multiple tables in a single migration.
- **Explicit line-by-line definitions (KISS + Explicit over DRY)**:
  - Write every column, index, and foreign key explicitly.
  - Foreign key naming: `<table>_<column>_fk`
  - Unique key naming: `<table>_<column>_uk`
  - Index naming: `<table>_<column>_ix`
  - Use `restrictOnDelete()` for all historical ledger and status history relationships.

## 4. Post-Task Shared Memory Protocol
After completing and verifying work:
1. Create a new markdown record in `/docs/changes/YYYY-MM-DD-<feature-slug>.md`.
2. Include:
   - Context & Objectives
   - Modified/Created Files Summary
   - Exact Verification Results (with command outputs)
   - Author & Maintainer signature:
     - Lead Architect & Developer: Kasun Vimarshana
     - Email: kasunvmail@gmail.com
     - Phone: +94713525253
3. Never edit or delete existing change records.

---

## Author & Maintainer

- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
- **Repository:** [kasunvimarshana/AutoERP](https://github.com/kasunvimarshana/AutoERP)
