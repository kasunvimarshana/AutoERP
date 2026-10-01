# Vehicle Service Sales Summary report

## Request

Show service job item sales and collected payments by item for today, this week, this month, or a custom date range, with an optional item filter.

## Changes

- Add a Reporting endpoint and page for Service Job Sales Summary, with item lookup and date presets/custom dates.
- Aggregate invoiced item quantities and sales using invoice dates; aggregate collected payments using payment dates.
- Allocate invoice-level net totals and collections proportionally across the invoice's service item lines, and exclude combo child lines to avoid double counting.
- Add a navigation entry under Reports. No schema changes were required.

## Verification

- PHP syntax checks passed for the new request, controller, and service.
- TypeScript typecheck passed (`npm run typecheck`).
- `git diff --check` passed.
