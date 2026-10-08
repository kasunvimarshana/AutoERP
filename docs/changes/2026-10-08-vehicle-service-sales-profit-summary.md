# Show service job item usage and profit in sales summary

## Request

Extend the Vehicle Service Sales Summary so users can see stock and combo usage, component costs, and profit by service job.

## Changes

- Reworked the report response around invoiced service jobs and added revenue, stock cost, commission, net profit, and margin totals.
- Added separate stock-item and combo summaries. Combo detail includes each labour/service or stock component; stock components contribute to inventory cost without being counted as separate sales.
- Added expandable job rows with sales items, combo contents, direct costs, commissions, and job profit or loss. Item filtering returns matching jobs with their complete breakdown.
- Prorated line costs and commissions to invoiced quantities for partially invoiced jobs, and used posted stock issue costs where available.
- Excluded combo parent line cost from the existing service profitability calculation so the parent and its component costs are not both counted.

## Verification

- `npm run typecheck` passed.
- PHP syntax checks passed for the changed reporting services.
- `git diff --check` passed.
- Automated tests were not run.
