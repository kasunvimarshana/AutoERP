# Vehicle Service atomic job cancellation and reversal

Date: 2026-09-03

## Implemented behavior

- Cancelling a draft, inspected, or in-progress job requires `vehicle_service.jobs.transition`. Cancelling a completed job additionally requires `vehicle_service.jobs.cancel_completed`, registered in the module-owned permission catalogue. Invoiced, partially paid, paid, and already cancelled jobs cannot be cancelled.
- The existing cancel endpoint now requires a non-empty reason and `expected_version`. The status service also requires a version for direct cancellation calls and checks the current locked job status, not the caller's possibly stale model.
- Cancellation reverses every linked posted stock issue through Inventory's existing reversal API, preserving original quantities, costs, source links, and `inventory_movement_id`. Reversals return stock to the original inventory dimensions.
- Vehicle Service delegates non-zero issue journal reversal to Finance using the original issue movement ID. A zero-cost issue deliberately has no finance journal, so only its finance step is skipped. Missing non-zero postings, inconsistent/already reversed movements, or any other reversal failure abort the entire transaction with an explicit error.
- Active linked invoices and payments block cancellation, including partial invoices on jobs that still have `completed` status. Reversed/voided documents are assessed through their actual document states, not just link flags. Cancellation never voids billing documents on the user's behalf.
- Original employee/supervisor commission values are retained. Cancelled job status removes them from payable Employee Commission Report totals and retains them in cancelled audit totals. Regression tests exercise real cancellation for both job-level supervisor and combo-supervisor commissions, including duplicate-row protection.
- The confirmation dialog loads a backend preview of stock quantities/locations, inventory value, commission amount, and document blockers. It requires a reason and explains that issued items must be physically returned and restockable. Errors remain visible within the dialog; conflicts reload the preview without automatically retrying cancellation.
- After successful cancellation, the page refreshes the job, clears cached workforce state, and remounts cached tabs. Stock lines receive structured original-movement state and show `Returned` for reversed issues instead of incorrectly continuing to show `Issued`.

## Ownership, concurrency, and historical data

The cancellation service is called **inside** the existing status-service transaction after its vehicle/job locks and expected-version check. This refines the earlier design record: it avoids an outer cancellation service performing permission checks against stale state or introducing a circular dependency with the status service. Inventory and Finance still own their respective immutable reversals; the status service still owns job/vehicle status and timeline history.

All reversals and the job/vehicle transition commit or roll back together. Job lines and movements are locked in deterministic line order. Repeated/stale cancellations cannot return stock twice. Existing relations were reviewed and reused; no new tables, foreign keys, bidirectional relationships, or schema migrations were needed. Original completion timestamps, line/assignment amounts, and issue references remain historical records.

## Verification

- Focused backend cancellation and Employee Commission Report run: **13 passed, 187 assertions**.
- Full Vehicle Service engine plus Technician Work Report run: **50 passed, 1 failed, 513 assertions**. The failure is the existing `test_tracked_inventory_lines_are_blocked_before_issue`: its fixture creates a batch-tracked item without a batch, while the existing line validator already requires one. Both the test and validation rule were checked in `HEAD`; that unrelated behavior was not changed.
- Frontend Vehicle Service suite: **13 files, 60 tests passed**, including cancellation preview, required reason/version, blockers, and conflict refresh.
- TypeScript check and production build passed. PHP syntax/formatting and changed-file ESLint were checked; `git diff --check` passed.
- Database tests use the configured isolated SQLite in-memory database. These tests cover stale-version and rollback scenarios; they are not a multi-connection MySQL concurrency stress test. No live job was cancelled and no production data was changed.

## Deployment / permission activation

No schema migration is required. Deploy the PHP changes and the rebuilt `public/build` assets together. Run the existing User-owned permission synchronizer as part of deployment:

```sh
php artisan db:seed --class='Modules\User\Database\Seeders\TenantPermissionSeeder' --force --no-interaction
```

This synchronizes the registered permission catalogue for non-archived tenants and the protected Super Admin role; it is not a job-data repair. It was **not run against the user's application database** during this implementation. Refresh the signed-in session after synchronization. Grant `vehicle_service.jobs.cancel_completed` only to intended administrator roles; ordinary users keep only the existing transition permission. Rebuild route/application caches using the project's normal deployment procedure if caching is enabled.

Previously cancelled jobs are not retroactively reversed. Existing missing journals or independently reversed issues must be reconciled through their owning modules before attempting a new cancellation.
