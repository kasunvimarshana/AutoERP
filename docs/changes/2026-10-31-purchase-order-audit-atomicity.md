# Purchase Order lifecycle and audit atomicity — 2026-10-31

## Root cause

The Purchase Order HTTP controller called Purchase-owned service methods to create/update/delete/submit/approve/cancel/close an order, then recorded the immutable Purchase workflow audit event **after the service had committed**. A failure in the required `AuditRecorderInterface` therefore caused a failed API response while retaining a partially unaudited state change.

## Exact correction

`app/Modules/Purchase/Http/Controllers/PurchaseOrderController.php` now runs the Purchase operation and its existing `PurchaseAuditService` event in the **same outer `DB::transaction`** for seven paths: create, update, delete, submit, approve, cancel and close. The same owner-module atomicity treatment has been applied on the integration candidate to debit-note and return/GRN workflow controllers. The existing Purchase business services still own validation, state rules and persistence, and the Audit module still owns recording immutable events.

The shared database-backed `RecordAuditEvent` → `EloquentAuditLogWriter` path is unchanged. HTTP permissions, return formats, existing tax and finance rules, source relations and database schemas are unchanged. Share-link interactions are not financial document mutations and are not part of this patch.

## Regression tests

Added to the existing real Purchase HTTP fixture:

- Creation must leave **zero** Purchase orders and lines if the *Purchase Order Created* event deliberately fails.
- Draft soft deletion must roll back if the *Purchase Order Deleted* event deliberately fails: original row remains nondeleted and draft.

Tests target specific event names to avoid a false-positive caused by unrelated audit writes.

**Execution:** These Laravel regression tests were committed but **could not be run** in the current GitHub-connector-only execution environment. An earlier standalone PHP 8.4.24 syntax smoke covered the analogous four debit-note closure patterns, not these actual source files. Do not present that smoke or the user's earlier 958-test local log as a passing test run for this code. The updated Purchase controller tests require full SQLite and disposable MySQL suite execution at the exact candidate commit before production.

No paid resources, GitHub Actions, production database changes, workflow bypass, or promotion of the authoritative `worktree-0.0.8` branch occurred.
