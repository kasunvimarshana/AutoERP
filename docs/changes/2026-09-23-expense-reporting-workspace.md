# Expense reporting workspace

Date: 2026-09-23

## What changed

- Added a tenant- and organization-scoped Expense Report API with date, search, expense type, payment method, activity, sorting, pagination, and export support.
- Modelled expense postings and reversals as separate dated reporting events. Original postings retain the expense date; reversal amounts are negative events on the reversal date, preserving immutable historical reporting.
- Registered the report in the shared report catalogue and exposed a dedicated `/reports/expenses` page and Reports navigation entry.
- Added an expense composition story, category distribution, daily activity, payment-method breakdown, detailed activity grid, and export controls using the existing AutoERP design system.
- Added an Operating Expenses snapshot to Summary Reports with a plain-language category insight and a direct link to the dedicated report.
- Added backend and frontend coverage for posting/reversal totals, catalogue registration, report interaction, summary rendering, and navigation.

## Why

Users needed a clear view of where operating money went without interpreting raw ledger rows. The event-based design keeps report totals aligned with the finance posting and reversal model while making category and payment-method trends understandable at a glance.

## Verification

- `php artisan test app/Modules/Expense/Tests/ExpensePostingTest.php`
- `php artisan test app/Modules/Reporting/Tests/ReportingFrameworkTest.php --filter=registry_contains`
- `npx vitest run resources/js/modules/reporting/pages/ExpenseReportPage.test.tsx resources/js/modules/reporting/pages/SummaryReportPage.test.tsx resources/js/app/navigation/navigationUtils.test.ts`
- Focused ESLint checks for changed reporting, routing, and navigation files
- `npm run build`
- `git diff --check`

The repository-wide TypeScript check still reports the pre-existing optional `result.meta` error in `VehicleServiceHistoryReportPage.tsx`; no new expense-reporting TypeScript errors were reported.
