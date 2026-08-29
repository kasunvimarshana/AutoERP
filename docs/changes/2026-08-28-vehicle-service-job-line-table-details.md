# Vehicle service job-line table details

Date: 2026-08-28

## Why

The Job Lines table needed the compact product context and totals used by the reference order-entry workflow. Stored six-decimal quantities were also visually noisy for everyday workshop entry.

## Changes

- Presented the item name as the primary label and added the human-readable item code below it.
- Added organization-aware available stock to inventory line responses by reusing the Item module's existing stock-availability projection.
- Displayed inventory metadata as `code | In stock: quantity UOM` while retaining pending/issued workflow status.
- Added a table footer for total top-level quantity, billable line discount, and billable line subtotal. Included combo children are excluded to prevent double counting.
- Formatted quantities with at least one decimal place in this UI, so `1.000000` appears as `1.0` without changing stored precision or hiding meaningful additional decimals.
- Extended the shared table and quantity display components with optional footer and minimum-decimal presentation support.

## Verification

- Vehicle Service line-editor tests passed: 8 tests.
- Vehicle Service line-list API test passed: 1 test, 10 assertions.
- TypeScript typecheck passed.
- Focused ESLint checks passed.
- PHP syntax checks passed.
- Laravel Pint completed for the changed PHP files.
