# Business Overview Dashboard Phase 2

Date: 2026-09-15

## Summary

Added the first safe Phase 2 dashboard capabilities: authoritative profitability and compact employee performance. Both reuse Reporting-owned canonical services instead of duplicating business calculations in the dashboard.

## Changes

- Extracted the ledger-backed performance calculation from SummaryReportService into a reusable public contract.
- Added gross profit alongside revenue, cost of sales, other expenses, total expenses, and net profit.
- Added a dashboard employee-performance contract to EmployeeCommissionReportService.
- Ranked the top five employees by effective non-cancelled labour value, then total commission.
- Included completed and assigned jobs, labour hours/value, and earned, pending, cancelled, and effective commission totals.
- Added responsive Profitability Overview and Employee Performance panels to the Business Overview dashboard.
- Added human-readable employee drill-down links that preserve the selected dashboard date range without exposing database IDs.
- Updated the Employee Commission Report to initialize its date and search filters from validated URL context.
- Added the finalized Phase 2 scope in docs/plans/2026-09-15-business-overview-dashboard-phase-2.md.

## Data integrity

- Finance remains the single source of truth for profitability.
- Employee Commission Report remains the single source of truth for employee commission and labour performance.
- Cancelled commission and labour amounts remain historical but do not contribute to effective ranking totals.
- Existing tenant and current organization-unit scoping is preserved.
- Cross-organization-unit consolidation was not approximated because the current authoritative services intentionally scope one organization unit.
- Historical as-of aging was not fabricated from mutable current invoice balances; it requires a dedicated historical balance service.

## Verification

- Focused backend commission/dashboard API test passed: 1 test, 38 assertions.
- Reporting framework regression suite passed: 9 tests, 83 assertions.
- Focused dashboard, Summary Report, and Employee Commission frontend tests passed: 4 tests.
- Focused Employee Commission drill-down suite passed: 2 tests.
- ESLint passed for all changed frontend files.
- Production Vite build passed with 667 modules transformed.
- PHP Pint and PHP syntax checks passed.
- git diff --check passed.
- Full TypeScript verification continues to report only the pre-existing optional result.meta issue in VehicleServiceHistoryReportPage.tsx.
