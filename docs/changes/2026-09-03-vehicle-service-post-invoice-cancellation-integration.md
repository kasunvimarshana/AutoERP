# Post-invoice cancellation integration; cancelled jobs remain terminal

Date: 2026-09-03

## Request and resulting workflow

Completed the accounting-reversal integration identified in the preceding gap record. The user explicitly required cancelled jobs to remain visibly cancelled, not to be restored or reopened.

The supported sequence is now:

1. Reverse linked posted receipts using the Payment module's existing reversal action and permissions, where payments exist.
2. Reverse posted invoices (or cancel/void eligible unposted invoices) using the Invoice module's existing actions and permissions.
3. When the invoice restoration callback finds no active linked invoices or payments, Vehicle Service returns an invoiced, partially-paid, or paid job to `completed` and records why, who, and when. This is billing reconciliation **before cancellation**, not reopening a cancelled job.
4. An administrator with the existing completed-job cancellation permission can then cancel the job. The previously implemented stock/issue-journal reversal and commission exclusion apply. The final status remains `cancelled`.

Payment reversal is an accounting correction, not evidence of a physical cash refund. This change does not initiate cash refunds, reinterpret an active refunded receipt as reversed, or bypass Payment/Invoice settlement protection. Existing active refunds and other settlements remain governed by those modules. A refund-only customer-return/credit-note workflow is not added by this change.

## Implementation and ownership

- Added a shared Vehicle Service billing-protection service used by both cancellation and post-reversal reconciliation. It checks actual document state rather than link flags, including all linked invoices/payments. Write paths use current locking reads; preview remains read-only.
- Extended the existing Invoice source-restoration context with actor and reason metadata. Invoice reversal now invokes source restoration after storing its final reversed document/balance state, inside the same transaction. Void/cancel callbacks also receive actor/reason. This removes the need for source owners to guess an invoice's future state.
- Vehicle Service's existing invoice-restoration handler updates its link and asks its status service to reconcile the related job. No Payment module code or financial authorization rules were changed.
- The status service only reconciles billing statuses. Draft, inspected, in-progress, completed, and especially cancelled jobs are left unchanged. Repeated notifications cannot reopen a cancelled job, increment its version, add duplicate job history, or return its stock twice.
- The reconciliation locks the job, increments its normal row version through the model save, and appends status history. It does not overwrite original completion timestamps, employee commissions, line values/statuses, or vehicle state. This billing-only operation does not need a vehicle-status transition.
- Invoice reversal and source/job reconciliation roll back together on a handler failure. Transaction retries are bounded for retryable database concurrency failures; stale job actions are still rejected by expected-version checks.
- Added permission-gated billing-cancellation guidance on the job page with links to its Payments and Invoice tabs. It does not expose a direct cancellation button on billed jobs or a restore/reopen action on cancelled jobs.

Existing ownership and relationships were reviewed and reused. No tables, migrations, permission definitions, or generic event frameworks were added in this follow-up. The existing completed-job cancellation permission setup from the earlier implementation is still required.

## Verification

- Focused cancellation and billing-reversal tests: **13 passed, 124 assertions**. Includes fully and partially paid end-to-end reversals, multiple invoices, original completion-time preservation, stale versions, elevated cancellation permission, callback rollback, and cancelled-job permanence.
- Invoice engine, manual invoices, invoice settlements, and payment allocation reversals: **19 passed, 66 assertions**.
- Purchase invoice source-restoration regression: **1 passed, 7 assertions**.
- Full Vehicle Service engine plus reporting regression: **54 passed, 1 existing failure, 560 assertions**. The unchanged failure is `test_tracked_inventory_lines_are_blocked_before_issue`, whose fixture omits the batch required by the existing validator. It was already documented in the earlier implementation and remains outside this change.
- Existing frontend Vehicle Service suite: **60 tests passed**; new cancellation-access/guidance suite: **6 tests passed**.
- TypeScript, changed-page ESLint, PHP formatting, production build, and `git diff --check` passed.
- Backend tests ran against isolated SQLite in-memory databases. Multi-connection MySQL stress testing was not performed. No real job, invoice, payment, or application permission data was changed.

## Deployment and existing data

Deploy the PHP changes and rebuilt frontend assets together using normal cache refresh procedures. No schema migration or additional permission seed is introduced here.

This integrates future Invoice reversal/void/cancel callbacks. It does not retroactively update older jobs whose invoices had already been reversed before this deployment, and it never automatically restores cancelled jobs.
