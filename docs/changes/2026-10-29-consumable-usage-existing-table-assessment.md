# Consumable usage existing-table assessment

Date: 2026-10-29

## Request

Assess whether the planned single-item, manually entered consumable usage can use a current database table instead of adding consumption header and line tables. This assessment revises the schema proposal in the earlier final plan; that record remains unchanged as append-only history.

## Table assessment

- `inventory_movements` already stores a movement number, date, type, direction, tenant and organization scope, item and UOM, warehouse/location/batch, entered and base quantity, authoritative posted unit/total valuation cost, description, creator/poster, lifecycle status, and reversal link. Its posting service locks the stock balance, checks available quantity, values issues, and updates stock atomically. It can serve as the canonical record for one item per usage entry.
- `inventory_adjustments` and `inventory_adjustment_lines` are for stock corrections and recounts. Their posting creates adjustment movements without a consumable expense journal. Reusing them would conflate physical use with correction and change their financial meaning.
- `expenses` stores immediately paid operating expenses and payment-method snapshots. It has no item, quantity, stock-location, or valuation fields. Reusing it for stock usage would misstate a stock issue as a new cash/bank payment and risk double counting.

## Revised recommendation for the confirmed scope

Do not create `inventory_consumptions` and `inventory_consumption_lines` for the current single-item, immediate-post usage workflow. Add a controlled `InternalConsumption` movement type in Inventory and create one posted outbound `inventory_movements` row per usage entry. Use its description for the optional reason. Inventory owns the request validation and orchestrates movement posting with Finance posting in one transaction. Finance needs a distinct consumables operating-expense posting profile/account resolution; the existing inventory-issue profile posts to Cost of Goods Sold and must not be reused unchanged. Use the movement identity as the Finance source and existing idempotency infrastructure for retries. Reversal must reverse both the movement and its Finance journal together.

The Expenses module can own the screen and navigation while Inventory remains the API and stock-data owner. History can filter movement type and movement date, and aggregate quantity and posted cost by item. Existing GRN, supplier-invoice, supplier-payment, paid-expense, stock-adjustment, and service-job flows retain their current meanings.

No new transactional table is required for this scope. A future composite index on tenant, organization unit, movement type, date, and item can be added with one explicit `Schema::table('inventory_movements')` migration if query volume or an execution plan justifies it. A separate consumption document would become justified only if the business later needs multi-item grouped documents, draft/approval workflow, or data that cannot belong to one movement.

## Verification needed during implementation

Prove the one-step post and reversal are atomic and idempotent, protect stock under concurrent use, value cost through Inventory, and reconcile the new Finance journal with Profit and Loss. Verify period-filtered usage totals and regressions in existing movement, adjustment, GRN, payment, and expense flows.

This is a schema and code-path assessment only. No application code, migration, or transactional data was changed.
