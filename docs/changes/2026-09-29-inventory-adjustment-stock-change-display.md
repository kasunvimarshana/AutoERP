# Inventory adjustment stock change display

## Request

Make the adjustment detail table clearly show whether counted stock increased or decreased compared with system stock.

## Changes

- Rename quantity columns to System stock and Counted stock.
- Show the exact difference as Increased by, Decreased by, or No change, with directional iconography and accessible text labels.
- Format the adjustment date for human reading instead of showing the raw timestamp.

## Verification

- TypeScript typecheck passed (`npm run typecheck`).
- `git diff --check` passed.
