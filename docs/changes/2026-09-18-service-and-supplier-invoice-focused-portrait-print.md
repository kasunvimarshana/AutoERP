# Service and supplier invoice focused portrait print

## Summary

- Applied a focused print presentation to Service Invoices and Purchase-owned supplier invoices without changing other invoice types.
- Changed configured compact A5 output for those invoice types from landscape to portrait while preserving the configured paper size.
- Replaced the duplicate Reference and Description columns with one human-readable Item Name column; quantity, unit price, and amount remain visible.
- Removed the separate Total Value of Supply and Tax Amount rows from the focused presentation and retained Total Amount including VAT.
- Made Credit and Balance due values visible even when they are zero; Paid remains visible when non-zero.
- Populated Mode of Payment from immutable payment-method snapshots on active payment allocations, with the invoice snapshot or Credit used when no realized payment method exists.

## Architecture

- Added an Invoice-owned payment-method reader contract and a Payment-owned implementation so Invoice printing does not query Payment tables directly.
- Payment lookup runs inside the explicitly supplied tenant context and remains organization-unit scoped, including signed/public print flows.
- Historical payment method names are read from payment-line snapshots rather than mutable payment-method master data.

## Verification

- Complete Invoice and Payment module suites passed: 53 tests, 304 assertions.
- The focused A5 Service Invoice PDF regression renders as one portrait page.
- Service and Purchase invoice regressions cover focused columns, totals, Credit, Balance due, and resolved payment method values.
- Laravel Pint check passed for every changed PHP file.
- `git diff --check` passed.
