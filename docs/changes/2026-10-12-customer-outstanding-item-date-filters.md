# Add item and invoice date range filters to Customer Outstanding

## Request

Add item filtering, allow an invoice date range, and verify that the report returns matching data.

## Changes

- Added an item lookup filter that matches invoices containing the selected item; displayed amounts remain full invoice balances because balances are tracked per invoice.
- Replaced the single aging date control with invoice date From / To controls. From is optional; To limits invoice dates and sets the aging cutoff.
- Applied item and date filters to the customer summary, invoice details, and CSV export.
- Fixed a grouped-query selection issue that dropped the customer ID from result rows.
- Added Finance report tests for item and date filtering, invoice details, exports, and an omitted start date.

## Verification

- Focused Laravel tests passed (11 assertions).
- PHP syntax checks passed for the report request, controller, service, and test.
- `npm run typecheck` passed.
- `git diff --check` passed.
