# Purchase Order Detail Report

## Request

Make the existing Detailed Purchase Report readable like the R6 reference: group supplier, PO and item lines, show PO and supplier totals on the report, and remove columns unrelated to this view.

## Changes

- Kept the existing `purchase/detailed` route and renamed its catalog entry to Purchase Order Detail Report. Added a focused page with supplier sections, complete PO blocks, five item columns, PO subtotals, supplier totals and currency-specific report totals. PO numbers remain optional links to their detail pages.
- Paginate by purchase order so an order's items stay together. Date and supplier/status filters select orders; item and text searches find matching orders but retain every line in those orders.
- Calculate GRN quantity and base cost from posted, accepted GRN lines linked to each PO line. Draft and reversed receipts are excluded. Supplier returns remain in receipt history. PO amount comes from the order header and is separate from GRN base cost, which excludes discounts, tax and charges.
- Show quantity subtotals only when all lines in a PO, supplier/currency group or report/currency group share one UOM. Keep monetary totals separate by currency. Restrict joins and aggregations to the current tenant and organization unit.
- Added a Purchase-specific grouped print/PDF view; CSV and Excel use the same ordered supplier/PO/item/subtotal rows. Removed the obsolete purchase branch from the shared operational report page.

## Verification

- `DetailedPurchaseReportTest`: 1 passed, 29 assertions, covering complete PO pagination, item search, date filtering, posted versus draft/reversed receipts, mixed UOM, currency totals, HTML, CSV and a generated PDF.
- Purchase report page and remaining operational report page tests: 2 passed.
- TypeScript typecheck, targeted ESLint, PHP Pint for the report service/test, Vite production build, route registration and `git diff --check` passed.
