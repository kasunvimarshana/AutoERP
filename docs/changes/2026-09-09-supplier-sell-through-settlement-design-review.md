# Supplier sell-through settlement design review

Date: 2026-09-09

## Clarified requirement

The required business flow is to receive a supplier quantity, sell only part of it to customers, pay the supplier only for the sold quantity, and later report exactly which items and quantities each supplier payment covered.

Example: receive 10 units of Item A, sell 5 units, and make a supplier payment for only those 5 units.

## Current-system assessment

- The current supplier-payment flow allocates money to supplier invoices. It does not allocate payments to item lines or sold stock quantities.
- Purchase receipt lines are traceable to inventory receipt movements and optional batches.
- Inventory valuation consumptions connect stock issues to valuation layers, but this is an accounting-cost mechanism rather than a supplier sell-through ownership ledger. In particular, weighted-average valuation can pool receipts, so it must not be treated as authoritative supplier ownership lineage.
- Generic manual sales invoices post financial revenue but do not create the stock-issue lineage required to prove which received units were sold.
- A partial payment against an invoice containing multiple items cannot answer which item or quantity was paid.

## Required foundation

- Model an explicit supplier settlement basis such as normal procurement versus pay-on-sale, snapshotted on the receiving transaction.
- Make every posted customer sale issue inventory through an owning Sales workflow and retain an immutable sale-line-to-stock-issue relationship.
- Maintain an explicit allocation from each sold stock quantity to its supplier receipt/settlement lot independently of the configured inventory valuation method.
- Create supplier settlement documents from eligible sold-but-unsettled quantities. Settlement lines must retain the item, variant, UOM, receipt, sale source, quantity, supplier unit amount, tax/adjustment basis, and total snapshots.
- Generate/post the supplier invoice from approved settlement lines, then use the existing Payment module for the cash/bank/cheque transaction.
- Record immutable item-level payment allocations from the payment allocation to supplier settlement lines so partial payments and reversals remain reportable without guessing.
- Protect every quantity and amount transition with transactions, row locks, version checks, uniqueness constraints, and reversal records rather than editing history.

## Recommended UI

1. `Purchase > Supplier Settlements` lists suppliers with sold, already settled, and ready-to-pay quantities and values.
2. `Create Settlement` selects a supplier and period, then shows only eligible sold-but-unsettled item rows with human-readable sale and receipt references.
3. The user selects quantities, previews totals, and creates/approves the settlement and supplier invoice.
4. `Pay Settlement` presents only those settlement lines and controlled payment methods, followed by a before/after confirmation.
5. `Supplier Sell-through & Payment Report` supports supplier, item, sale date, settlement date, payment date, and status filters and shows received, sold, eligible, settled, paid, reversed, and remaining quantities/values.

## Changes

- No application behavior, database schema, transactional records, or permissions were changed.
- Added only this design-review record to the append-only change history.

