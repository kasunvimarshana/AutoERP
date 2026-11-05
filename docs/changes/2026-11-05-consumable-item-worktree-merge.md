# Consumable item merge into worktree integration branch

Date: 2026-11-05

## Change

- Created `codex/merge-consumable-item-worktree-0.0.8` from the latest `origin/worktree-0.0.8` and merged `origin/consumable-item` into it.
- Both branches independently added the Promotion module. Kept the target branch's newer Promotion, Vehicle Service, and UI implementations when resolving conflicts because they include later ownership, audit, authorization, and concurrency behavior. No application code was changed relative to the target branch.
- Preserved the source branch's previously committed change records, architecture documents, and presentation artifacts.
- Excluded the source branch's two `Schema::table()` migrations that make Promotion end dates nullable. The target branch's original table-creation migrations already define those columns as nullable, so the patch migrations are redundant and their rollback would incorrectly make nullable data non-nullable.

## Verification

- `git diff --check` and `git diff --cached --check` passed; no unmerged paths remain.
- Three focused Vehicle Service Promotion tests passed (13 assertions): posted count and reversal, card benefit and expiry, and vehicle transfer progress.
- The configured local MySQL database was reachable. `php artisan migrate:status` showed all migrations, including Promotion migrations, as already run. No local migration was needed or executed.
