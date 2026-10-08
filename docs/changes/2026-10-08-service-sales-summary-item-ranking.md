# Rank stock and combo items in service sales summary

## Request

Show, for a selected date range, how many times each combo and stock item was sold, its sales, direct cost, and profit, with the most sold items first.

## Changes

- Added date-range item summaries for standalone stock items and combo items, ranked by invoiced quantity with sales as a tie-breaker.
- Added per-item job counts, sales, direct cost, profit, and margin. Combo item cost includes its recorded labour/service and stock components; stock component cost is also shown separately.
- Added expandable combo item rows showing aggregated component quantities and costs across the selected period. Kept job-level profit details below the item rankings.

## Verification

- `npm run typecheck` passed.
- PHP syntax check passed for the report service.
- `git diff --check` passed.
- Read-only report service check returned the selected date range's combo totals and item rankings.
