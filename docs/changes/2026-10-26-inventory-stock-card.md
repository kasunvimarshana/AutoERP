# Inventory Stock Card

## Request

Add the R2-style Stock Card inside Inventory, with the GRN number opening its GRN detail page.

## Changes

- Added an Inventory submenu page and Inventory-owned API protected by `inventory.stock.view`. Users select an item, warehouse, optional location, and date range through controlled inputs.
- Compute opening and running balances from posted inventory movements in posting order. Reversed originals and their reversing entries remain visible, and opening stock is separate from period IN/OUT totals. Historical quantities are converted to the item's current base UOM using applied revision records.
- Resolve source document numbers in batches. GRNs, sales, service jobs, and purchase returns link to their existing detail pages; other movement sources display their document number without an unusable link.
- Added CSV, Excel XML, and PDF exports from the Inventory module. No database schema or historical movement records were changed.

## Verification

- `php artisan test app/Modules/Inventory/Tests/StockCardTest.php` passed: four tests covering balances across locations, reversals, GRN link, and base UOM conversion.
- TypeScript typecheck, targeted ESLint, PHP Pint, production Vite build, view registration, route registration, and `git diff --check` passed. A PDF render returned HTTP 200 with a `%PDF` signature.
