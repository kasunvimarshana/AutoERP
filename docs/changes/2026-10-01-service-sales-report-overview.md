# Service sales report overview and item usage

## Request

Reshape the new Service Job Sales Summary so users can quickly understand item usage, sales, and collections from an overview, and see the main item metrics together in one table.

## Changes

- Add total service jobs to the report summary and show job count per item, counting invoiced jobs for the selected invoice-date period.
- Replace the basic summary view with a period/filter area, four KPI totals, highlights for the most units sold and highest sales, and a ranked per-item table with jobs, quantity, sales, collected amount, and sales share.
- Clarify that sales use invoice date, collections use payment date, and collections are allocated proportionally to invoice item line totals.

## Verification

- Pending PHP syntax, TypeScript, and diff checks.
