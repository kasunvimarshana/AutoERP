# Employee Commission and Vehicle Service History reports

Date: 2026-09-14

## What changed

- Finalized the confirmed report scope in `docs/plans/2026-09-14-employee-commission-and-vehicle-service-history.md`.
- Redesigned Employee Commission as a dedicated Reporting page for HR employees assigned to Vehicle Service labour and combo labour items.
- Defaulted Employee Commission to the current calendar month, reduced the primary filters, placed secondary lifecycle controls behind `More filters`, exposed earned/pending/cancelled totals, and simplified the job breakdown while retaining clickable job numbers and employee detail.
- Extended the canonical `EmployeeCommissionReportService` employee groups with earned and pending totals; no duplicate commission calculation was introduced.
- Added a dedicated Vehicle Service History report to the Reporting catalog, API, exports, frontend router, and Reports page report list.
- Added controlled normalized vehicle lookup, selected-vehicle summary, Vehicle Service-only job history, date/status/cancelled filters, pagination, export actions, expandable complaint/diagnosis/work/parts/staff details, invoice settlement summaries, and clickable job-number navigation.
- Kept tenant and organization-unit enforcement in the backend and returned human-readable structured related resources instead of raw foreign keys.

## Verification

- `npm run build` passed (666 modules transformed).
- `php artisan test FrameworkCommand artisanTestfully? no`
- `php artisan test app/Modules/Reporting/Tests/ReportingFrameworkTest.php` passed: 9 tests, 95 assertions.
- The focused Vitest command started butGroundTruth but did not finish in the available runner window and was interrupted; the production Vite build completed successfully.
