# Running-chart physical approval review

Date: 2026-07-12

## Evidence

The Daily Running Chart table exposed `Approve physical usage` and `Reject` directly from each submitted summary row. The detailed workspace containing the selected physical usage and its customer/owner commercial facts was opened separately through `Manage facts`.

This allowed an approver to transition physical usage without first opening the evidence workspace. The backend state machine, optimistic row version, physical/commercial separation, and transition validation were already correct; the gap was in the Vehicle Rental presentation workflow.

## Correction

- The summary row now exposes `Review usage and facts` and retains recorder-owned draft submission.
- Submitted physical usage approval and rejection actions were removed from the summary row.
- Approval and rejection are available only after selecting the running-chart entry and opening its detailed workspace.
- The detailed workspace reuses the existing optional transition note, current row version, transition API, and commercial fact editors.
- Approved physical usage reversal remains in the same detailed workspace and still requires a reason.

## Ownership and scope

- Vehicle Rental continues to own physical usage workflow and presentation.
- Commercial revenue/cost facts remain independently reviewed through `RentalUsageFactEditor`.
- No Invoice, Payment, Finance, Tax, schema, API, calculation, or backend state-machine behavior changed.
- No compatibility shim, duplicated transition service, or hardcoded workflow bypass was introduced.

## Verification

- Added a portable frontend contract test proving that summary-row physical approval/rejection calls are absent and selected-usage transitions are present.
- Verified the GitHub commit diff contains only the intended action relocation and label change.
- Run from the latest `worktree-0.0.8` branch:

```bash
npx vitest run resources/js/modules/vehicle-rental/pages/RentalRunningChartReviewContract.test.tsx --reporter=dot --silent
npm run typecheck -- --pretty false
npm run lint
npm run build
npm run test
php artisan test
composer test:mysql
```
