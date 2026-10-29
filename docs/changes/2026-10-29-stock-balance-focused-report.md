# Focused Stock Balance report

## Why

The generic stock balance report exposed batch-level data across many columns and made current, sellable, damaged, and expired quantities hard to compare. Its generic status filter did not represent the inventory balance data.

## Changes

- Replaced the generic `inventory.stock-balance` implementation with an item-level report grouped by variant, unit, warehouse, and location.
- Added prominent Current stock, Available to sell, Damaged, and Expired columns, with stock-level guidance based on available quantity and the item's reorder level.
- Added an expandable batch breakdown for reserved, allocated, returned, in-transit, damaged, quarantine, expired, and scrapped quantities.
- Added guided warehouse and location filters, item search, zero-balance filtering, pagination, and the existing export formats.
- Kept the report in the All Reports catalog at `/reports/inventory.stock-balance`. No schema change was needed.

## Verification

- `StockBalanceReportTest`: grouped totals, filtering, tenant scoping, detail rows, and PDF export passed.
- `StockBalanceReportPage.test.tsx`: main columns and expansion passed.
- TypeScript typecheck, targeted ESLint, Pint, and route registration checks passed.

The shared PDF framework test still fails at the unrelated `vehicle-service.employee-commissions` case; the Stock Balance-specific PDF assertion passes.
