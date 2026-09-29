# Rental foundation integration verification — 2026-09-08

## Baseline and purpose

Continue from `worktree-0.0.8` commit `55d3aaff3e4fe7a4d48ee4a82a735810d2c2295f`. Full repository verification exposed integration defects not covered by the earlier focused agreement suites. Correct them in their owning files without changing business rules or restoring removed Rental code.

## Changes and evidence

- The full backend suite rejected all four new migration filenames because the repository requires the `_table.php` suffix. Rename the fresh baseline files accordingly, retaining timestamps, table names, explicit definitions and dependency order.
- The tenant-schema architecture test rejected both agreement history tables because they lacked the required unique `(id, tenant_id)` candidate key. Add those explicit keys; retain the existing agreement/revision uniqueness and tenant-safe agreement/actor foreign keys. No relationship or historical row is removed or repurposed.
- The model tenant-scope test produced a regex compilation warning: double-quoted PHP interpolation corrupted its literal `$table` matcher and skipped model checks. Correct the expression and verify actual inherited `HasTenantScope` traits through autoloaded classes. This covers abstract domain model inheritance without a list of special Rental exceptions or weakened tenant requirements.
- The complete frontend suite showed Rental missing from the Tenant-owned plan editor's module grouping. Add its named module code under Operations. Normalize initial selections, change comparison and confirmation through one revision-aware helper: persisted schema versions before 4 do not preselect retired Rental entries. Explicit selection is required for the fresh feature.
- Add a plan submission regression proving explicit fresh Rental opt-in and extend historical-plan coverage to prove Rental remains unchecked when changing only the plan name.

## Verification

Initial complete backend run reached the default local PHP 128 MB limit during PDF rendering. With a 512 MB process limit, it completed and reproduced two architecture failures and one warning. All are corrected; no application memory configuration was changed.

Final checks on the corrected code:

- Full PHPUnit suite: **678 tests, 7,731 assertions, no failures or warnings**; PHP 8.3/SQLite; peak memory 157 MB.
- Full Vitest suite: **77 files, 287 tests passed**. The initial run also reported one Platform Operators interaction failure; it passed in isolation and in the complete rerun. No Platform Operators code/test was changed; intermittent timing remains possible.
- TypeScript, changed Tenant frontend ESLint, migration/test Pint and production Vite build passed.
- Git whitespace validation passed before publication.

All tools were free/local. Commit uses `[skip ci]`; no GitHub Actions or production deployment was requested or performed by these checks.

## Deployment and remaining scope

These are fresh-baseline migrations, not an upgrade against an inspected production database. If the short-named migration files from the preceding commit were already applied anywhere, do not rerun their renamed equivalents blindly: inspect the actual schema and migration journal and prepare an explicit deployment-specific reconciliation. No production database or migration journal was accessed or changed here.

The complete Vehicle Rental module remains unfinished. Assignments/custody, Running Charts, effective successor terms, independent calculations/source consumption, financial handoffs, deposits, reports and production acceptance are not delivered by this verification batch. Full continuous video/audio review, password-protected backup contents and several financially material policies remain unresolved. Passing repository tests does not establish those business rules or constitute MySQL concurrency, production upgrade or browser/UAT acceptance.
