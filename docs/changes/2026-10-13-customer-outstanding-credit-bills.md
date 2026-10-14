# Show customer credit bills in Customer Outstanding

## Request

Show unpaid credit bills by selected customer and invoice date range, including older Vehicle Service bills and their source job number.

## Changes

- Corrected the Finance report to match invoices stored with the canonical `customer` party type. This fixes the query mismatch that excluded invoices created through Vehicle Service.
- Removed the item filter and limited the customer lookup and report to customers with credit enabled.
- Added confirmed credit settlement mode to Vehicle Service invoice links. Confirming a bill on credit stores the choice with a locked, version-checked write; direct payment records its mode when a payment is created.
- Included older unmarked Vehicle Service invoices for currently credit-enabled customers as historical estimates. The report marks inferred and confirmed credit separately because the old credit choice was not persisted.
- Included the linked Vehicle Service job number in invoice detail rows and retained invoice date range filtering for the report and CSV export.

## Verification

- Focused Customer Outstanding report tests passed (2 tests, 13 assertions).
- PHP syntax checks passed for modified backend files.
- `npm run typecheck` passed.
- `git diff --check` passed.
