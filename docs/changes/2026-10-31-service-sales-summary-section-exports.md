# Separate stock and combo performance exports

Date: 2026-10-31

## Request

Add separate Stock Item Performance and Combo Item Performance exports while retaining the full Service Job Sales Summary export.

## Changes

- Added a compact Export menu to each performance section with Excel, PDF, and CSV choices. Both menus use the applied date/item filters and existing export permission checks, and disappear with the results while loading.
- Added a validated export-section enum to the existing Reporting endpoint. Stock exports contain only ranked stock items; combo exports contain only ranked combos and their component breakdown. Full exports retain their existing content.
- Section files have distinct names, titles, and focused columns for item, quantity, jobs, sales, cost, and profit. Component rows carry costs without duplicating sales/profit. Export amounts and ordering come from the same report result as the screen.
- Reused the existing export infrastructure and shared performance-row mapping, with the configured row limit applied separately to the selected output. No schema, relationship, transactional data, or business calculation changes were needed.

## Verification

- PHP: 5 tests passed, 109 assertions, covering section isolation, component breakdown, decimal values, section validation, separate row limits, PDF/Excel/CSV generation, existing full exports, and route permission middleware.
- Frontend: report-page test and two compact-export tests passed, covering applied filters, format choices, request parameters, loading, and permission visibility.
- TypeScript typecheck passed.
- Targeted ESLint passed with zero errors; the existing report loading-effect warning remains.
- Pint and `git diff --check` passed.
