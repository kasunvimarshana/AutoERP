# Management report development reverted

Date: 2026-09-06

## Reason

The latest reporting roadmap implementation was removed at the user's request. This rollback is intentionally limited to that development and does not remove earlier vehicle-service cancellation, stock, commission, finance, or profitability reconciliation work.

## Removed

- Daily Job Operations, Job Billing & Collection, Vehicle Service History, Customer Activity, and Supplier Activity backend report endpoints and services.
- Daily Labour Commission and Labour Incentive Summary route presets.
- The management-workspace report catalogue UI and its related filter/type changes.
- Payment report catalogue adjustments that were introduced as part of the same development.
- Tests added specifically for the removed report workspaces and endpoints.

## Restored

- Existing report routes, report registry, report list UI, operational report filters, employee commission screen, and detailed vehicle-service reporting were restored to their state before the management report roadmap implementation.
- Existing cancellation-aware Vehicle Service profitability behavior and all earlier unrelated functionality were preserved.

## Verification

- `php artisan test app/Modules/Reporting/Tests` — 21 tests passed, 318 assertions.
- `npm test -- resources/js/modules/reporting/pages/OperationalReportPage.test.tsx --run` — 1 test passed.
- `.\\node_modules\\.bin\\tsc --noEmit` — passed.
- `git diff --check` — passed.
