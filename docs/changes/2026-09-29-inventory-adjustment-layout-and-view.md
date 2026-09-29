# Inventory adjustment layout and view

## Request

Make the stock adjustment form easier to scan by separating item, warehouse, and location from the remaining fields, and let users inspect a saved adjustment.

## Changes

- Place Item, Warehouse, and Location together on the first form row and the adjustment fields on the next row.
- Add a View action that opens the selected adjustment in a detail modal with its human-readable header relationships and item quantities.
- Reuse the adjustment list response, which already includes warehouse, location, and item line relationships; no API endpoint or schema change was needed.

## Verification

- TypeScript typecheck passed (`npm run typecheck`).
- `git diff --check` passed.
