# Add customer-wise outstanding report

## Request

Provide a clear Finance report for customers with unpaid invoice balances, including aging and invoice-level detail.

## Changes

- Added a tenant- and organization-unit-scoped Finance report that groups outstanding customer invoices by customer and currency.
- Added an as-of aging cutoff, customer search, aging-bucket filter, currency-safe summary totals, paginated customer results, and paginated invoice details.
- Added a CSV export using the active report filters and protected customer text fields against spreadsheet formula execution.
- Added the report to Finance Reports and protected its page and endpoints with the existing Finance reports-view permission.
- The as-of date filters invoices issued by that date and determines aging buckets; outstanding amounts use the current invoice balances because the existing invoice balance source does not retain dated balance snapshots.

## Verification

- PHP syntax checks passed for the new Finance enum, request, service, controller, and route file.
- `npm run typecheck` passed.
- `git diff --check` passed.
- Automated tests were not run.
