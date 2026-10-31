# Overall expense report design proposal

Date: 2026-10-30

## Request

Explain why the Expense Report shows no data and propose a report that gives users a complete view of expenses, including expense type summaries.

## Findings

- The current Expense Report reads posted and reversed rows from `expenses` only. It excludes Inventory Consumable Usage by design.
- In the local AUTOERP organization unit, there are no posted `expenses` rows and one posted `internal_consumption` inventory movement dated 2026-10-30. The zero result on the current report is consistent with its source query.
- Consumable Usage has an item and Finance posting but no Expense Type relationship. Supplier payment for purchased stock is settlement, while actual usage is the expense event.

## Proposed design

- Present a ledger-reconciled overall expense total for the selected period in base currency, with a source breakdown for paid expenses, consumable usage, and other expense accounts that exist in Profit and Loss.
- Show expense type summaries for paid expenses using their saved type snapshots. Show consumables separately by item and quantity rather than inventing an Expense Type association.
- Provide a combined dated activity table with source, readable description, type or item, amount, and reversal status, linked to the owning record when available.
- Keep payment-method analysis specific to paid expenses. Date reversals on their reversal date so the period total reconciles to Finance.
- Default to the current month and retain date and source filters; put detailed filters and transaction rows below the headline summary.

This is a design proposal only. No application behavior or transactional data was changed.
