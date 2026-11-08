# Service sales item discounts and job links

Date: 2026-11-08

## Request

Show item discounts in Stock and Combo performance, expand each ranked item to its related service jobs, and make the panel totals easier to read.

## Changes

- Reporting now reads posted invoice-line discount snapshots and allocates the invoice's job discount share to each billed source line. Stock and Combo item totals, their job breakdowns, and exports show the discount separately from existing after-discount invoice sales. Profit remains based on the existing sales amount.
- Ranked items now include job numbers and per-job invoiced quantity, discount, and invoice sales. The UI expands an item to linked jobs; Combo components remain available in the same expanded area. Raw job IDs are used only in links.
- Renamed Qty sold to Qty invoiced, clarified that invoice sales may include taxes and charges, and strengthened the Stock and Combo panel summary text.
- Fixed an existing quantity double count: the report previously added invoiced quantity when processing source lines and again when assigning stock cost. Cost assignment now adds only cost, so the displayed invoiced quantity is accurate.
- Added Discount to Stock, Combo, and full report exports. No database schema or historical transaction data changed.

## Verification

- Focused Reporting test covers stock and combo discounts, job links, invoice-date filtering, and the stock quantity correction.
- Focused UI and export tests, TypeScript typecheck, Pint, and diff whitespace checks passed.
