# Hide unavailable stock in Vehicle Service job-line lookup

## Request

Keep inventory items with no available stock out of the Vehicle Service job-line item search while leaving service, labour, and package choices unchanged. Explain empty results so users can distinguish unavailable stock from an unknown item.

## Changes

- Filtered untracked stock items by the tenant and organization-unit scoped sum of available stock before pagination.
- Kept batch and lot options only when their scoped aggregate available quantity is positive, including when balances span multiple locations.
- Added a Vehicle Service lookup response flag when matching stock items exist but none are available. The dropdown shows an unavailable-stock message for that case and a separate no-match message for genuinely unknown searches.
- Removed this stock-sensitive lookup's recent-results cache so an item that has since run out of stock is not shown from a previous search. Existing selected job lines and backend reservation validation remain intact.
- No schema or relationship changes were required.

## Verification

- Vehicle Service lookup tests passed for unavailable stock, subsequent receipt and reservation, unchanged non-stock item types, and batch availability.
- Inventory batch service-option test passed.
- Vehicle Service item field and shared lookup component tests passed (15 tests total).
- TypeScript typecheck, targeted ESLint, PHP syntax checks, and `git diff --check` passed.
