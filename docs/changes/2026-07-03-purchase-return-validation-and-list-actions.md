# Purchase return validation mapping and list action guard

Date: 2026-07-03

## Problem

The referenced purchase return editor displayed all returnable GRN lines but submitted only the selected lines. Backend validation errors use submitted payload indexes such as `lines.0.returned_quantity`, so when an earlier visible line was skipped the error could appear on the wrong line or only in the generic error alert.

The purchase return list also allowed approve, post, and cancel actions directly from row buttons without confirmation, and the status filter accepted free-text status values even though statuses are a fixed set.

## Correction

Mapped backend line validation errors from submitted payload indexes back to the selected visible GRN line. Checked lines are now submitted even when the entered quantity is invalid, allowing the backend to return precise field errors instead of the frontend silently dropping the row. The line editor now shows row-level issues in the summary table and mobile details, while the drawer field receives the correct error.

Added confirmation, busy-state guarding, and per-action loading to purchase return list row actions. Replaced the free-text status filter with a fixed status selector.

## Verification

- `git diff --check`
- `npx vitest run resources/js/modules/purchase/PurchaseSourceCreateFlows.test.tsx --reporter=dot --silent=true`
- `npm run typecheck`
- `npm run build`
- `php artisan test app/Modules/Purchase/Tests/PurchaseOrderApiTest.php --filter partial_grn_return --stop-on-failure`

Note: the first Vitest attempt was blocked by a transient PowerShell startup failure reporting the paging file was too small. The shell recovered and the checks above passed afterward.
