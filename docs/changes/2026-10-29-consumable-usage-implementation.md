# Consumable usage implementation

Date: 2026-10-29

## Request

Add a manual, single-item Consumable Usage workflow under Finance → Expenses. Stockable consumables continue through the existing GRN, supplier invoice, and supplier payment flows. Actual usage must reduce stock and recognize inventory valuation as an operating expense without a job link or a second cash payment. Show usage and cost by optional date range.

## Changes

- Inventory now records each usage as a posted outbound `inventory_movements` row with the `internal_consumption` movement type. No new transactional table or migration was needed.
- The Inventory service validates an active stockable consumable, uses the existing locked stock posting and valuation services, and posts Finance in the same database transaction. It uses an idempotency key to prevent duplicate submissions. A zero-valued issue reduces stock without creating a zero-value journal.
- Finance has a dedicated `internal_consumption` profile mapping Operating Expense debit and Inventory credit. The Finance seeder provisions it for the default organization unit when run. Existing organization units need this profile and the two active account-role assignments configured before posting usage; a missing profile fails the transaction without changing stock.
- Reversal uses the existing inventory reversal movement and Finance source reversal together in one transaction. The original movement and journal remain in history.
- The Inventory API provides a consumable-only item lookup, exact usage post/reversal routes, availability, period-filtered history, and posted item-wise quantity/cost totals. The end date uses a next-day exclusive bound because SQLite stores the movement date as a timestamp even though the column is declared as a date.
- Finance → Expenses now has a Consumable Usage screen with item, warehouse, optional stock dimensions, actual quantity, date, notes, current availability, period history, totals, and reversal with a required reason.
- Added granular view, post, and reverse permissions. The screen's item lookup uses the usage view permission, so an Item-view permission is not additionally required.

## Verification

- `npm run typecheck` passed.
- PHP syntax checks and `git diff --check` passed.
- Consumable usage integration test passed for valued stock issue, idempotent retry, period summary, stock restoration, and Finance reversal.
- Inventory permission boundary and Finance seeder tests passed.
- Existing GRN inventory regression test was run separately.
