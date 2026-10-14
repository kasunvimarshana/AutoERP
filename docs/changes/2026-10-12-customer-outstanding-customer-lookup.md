# Add customer autocomplete to Customer Outstanding

## Request

Show matching customers in a dropdown while searching the Customer Outstanding report.

## Changes

- Replaced free-text customer search with the existing customer autocomplete control and included inactive customers that may still have outstanding balances.
- Added a tenant-validated customer ID filter to the Finance-owned report API, including report rows, invoice details, and CSV export.
- Kept the report's Apply filters and Reset behavior for the selected customer and other filters.

## Verification

- PHP syntax checks passed for the report request, controller, and service.
- `npm run typecheck` passed.
- `git diff --check` passed.
- Automated tests were not run.
