# Stock item profit by price type

## Request

Show purchase-price-based profit amount and margin for stock items in the item Summary tab, with Selling and Service prices calculated independently.

## Changes

- Resolve the current Selling, Service, and Purchase prices in the Item detail query using the existing item price resolution rules for the item's base UOM, tenant currency, and organization unit.
- Expose the resolved Selling price through the Item summary resource and type it alongside the existing resolved Service and Purchase price fields.
- Show Selling and Service price, profit per base unit, and margin in a compact table for stock items only. Profit is price minus purchase price; margin is profit divided by that price. Missing prices are clearly indicated, and a zero price has no percentage margin.

## Verification

- PHP syntax checks passed for the changed Item query service and summary resource.
- Frontend typecheck passed (`npm run typecheck`).
- Production frontend build passed (`npm run build`).
- `git diff --check` passed.
