# Invoice print discount, quantity, and signature layout

Date: 2026-09-19

## Why

The supplied Service Invoice PDF showed the company name in mixed case in its header, three-decimal quantities with UOM text, no separate line and bill discount values, a Credit amount row, and signature labels close to the payment table.

## What changed

- Uppercased only the compact invoice header name when the supplier is the issuing organization. The legal party snapshot and Supplier's Name field retain their original casing.
- Formatted printed invoice quantities to two decimal places and omitted UOM text from the Quantity column. Stored quantities and UOM snapshots remain unchanged.
- Added Line discount and Bill discount rows to focused Service Invoice totals, including zero values. The line value comes from persisted invoice-line discounts; the bill value comes from persisted decreasing discount adjustments allocated to that invoice.
- Removed the Credit amount row from invoice print layouts. Credit allocations still affect the authoritative balance due, and the Mode of Payment field is unchanged.
- Increased the A5 signature-table top spacing so Prepared by, Authorized by, and Customer signature sit farther below the payment table.
- Loaded print relations inside the invoice's tenant context so discount adjustments are available consistently in signed/public print paths.

## Data and schema impact

No migration, historical record edit, or calculation change was required. All changes are to print presentation and preparation from existing persisted invoice values.

## Verification

- Invoice print and legal snapshot suites: 12 passed, 140 assertions.
- Focused Service Invoice PDF rendered and visually reviewed as one A5 portrait page with the requested fields and spacing.
- PHP Pint, PHP syntax checks, and `git diff --check` passed.
