# Item Wise Ageing Report

## Request

Align the existing Inventory Aging Report with the R5 Item Wise Ageing reference. Weighted-average stock should remain one pooled row.

## Changes

- Renamed the report to Item Wise Ageing Report and show current open stock with positive remaining quantity.
- Added source document number and available detail link, UTC transaction timestamp, cost price, received quantity, remaining stock, days, warehouse, and location. Removed Batch and Remaining Value from this report and stopped summing quantities across different items/UOMs.
- Added controlled warehouse and location lookups under reporting view permission, alongside item search and optional posting-date filters. Filter IDs remain internal and are omitted from export headings.
- Show weighted-average stock as one pooled row with its pooled received quantity and current average cost, without implying that one source document represents the whole pool.
- Moved source-document resolution into an Inventory-owned service shared by the Stock Card and Ageing report. Generic reporting now supports batch row preparation and document links while exports retain plain document labels.
- Kept Item Age consistent between the Valuation and Ageing reports by basing it on the first source movement posting date in UTC.

## Verification

- `InventoryAgingReportTest` and `StockCardTest`: 6 passed, 42 assertions. The report tests cover FIFO document links, location/date filtering, CSV export, pooled weighted-average rows, and age consistency.
- PHP Pint, TypeScript typecheck, targeted ESLint, Vite production build, route registration, and `git diff --check` passed.
- `ReportingFrameworkTest`: 8 passed, 1 existing failure in the unrelated PDF export expectation test for other report families. The same failure was recorded before this change.
