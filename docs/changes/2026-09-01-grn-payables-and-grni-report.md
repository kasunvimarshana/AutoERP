# GRN payables and GRNI report

## Purpose

Added a focused purchase report for posted goods receipts so finance and purchasing users can see uninvoiced receipt exposure, supplier-invoice settlement, return credits, and the corresponding GRNI ledger position in one place.

## Changes

- Registered the `purchase/grn-payables` report and added dedicated API and export routes.
- Added a tenant- and organization-unit-scoped query that reconciles each posted GRN to active purchase-invoice links, invoice balances, posted purchase returns/debit-note credits, and GRNI ledger entries.
- Allocates a shared invoice's current settlement and balance across its linked source documents in proportion to each source invoice total, preventing duplicated accounts-payable balances.
- Keeps draft/approved invoice links visible as pending amounts while only posted/partially-paid/paid invoices contribute to finalized AP settlement and outstanding balances.
- Added summary totals, invoice-workflow totals, a top-supplier exposure breakdown, detailed sortable rows, filters, pagination, and HTML/PDF/XLSX/CSV exports.
- Added a dedicated React page at `/reports/purchase/grn-payables`, linked it from Reports and the navigation menu, and documented the calculation/date-scope basis in the UI.
- Added backend reconciliation coverage and frontend rendering/filter coverage.

## Calculation basis

- Expected uninvoiced amount: GRN total less all active linked invoice-source totals, floored at zero.
- Projected exposure: expected uninvoiced amount plus allocated finalized invoice outstanding less posted open return credit.
- Accounting liability: current GRNI ledger balance plus allocated finalized invoice outstanding.
- The report date range filters GRN receipt dates; related invoice, settlement, credit, and GRNI figures represent their current state.

## Verification

- `php artisan test app/Modules/Reporting/Tests/GrnPayablesReportServiceTest.php` passed: 1 test, 28 assertions.
- `php artisan test app/Modules/Reporting/Tests/ReportingFrameworkTest.php` passed: 9 tests, 83 assertions, including PDF and Excel export paths.
- `npm exec vitest run resources/js/modules/reporting/pages/GrnPayablesReportPage.test.tsx -- --reporter=dot` passed: 1 test.
- Focused ESLint and Laravel Pint checks passed.
- `npm run typecheck --if-present` passed.
- `npm run build` passed with 661 modules transformed.

No database schema changes were required.
