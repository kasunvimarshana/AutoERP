# Consumable usage final plan

Date: 2026-10-29

## Requirement

Stock-tracked shampoo, cleaning liquid, and similar supplies are purchased and paid for through the existing Purchase, GRN, Supplier Invoice, and Supplier Payment flows. Their actual internal usage is not predictable per service job. A user records the quantity used when known. The system must reduce stock, recognize the valuation cost as an operating expense once, and show usage over a selected period. Job-level attribution and automatic job deduction are outside this workflow.

## Existing foundation and gap

- A posted GRN for a stockable consumable creates Inventory stock and valuation; the Purchase lifecycle posts Inventory against GRNI and the supplier invoice/payment settles the liability.
- Inventory movement posting already calculates authoritative issue cost and updates stock balances.
- Finance Profit and Loss already derives expense totals from posted ledger accounts.
- The current Expenses `Add Expense` action posts an immediately paid `Operating Expense / Cash or Bank` journal. It cannot represent consumption of previously purchased stock without double counting.
- Inventory Stock Adjustment records quantity movements but does not create the required consumption expense journal.
- The existing Expense Report reads paid Expense records. It does not represent stock consumption.

## User flow

Add `Consumable Usage` under `Finance > Expenses`, with a focused record form and a history view on the same screen. The form asks for usage date, stockable consumable item, warehouse/location, quantity in a guided unit, and optional reason. Show current available stock and a preview of the estimated cost; posting cost is recalculated authoritatively at submission. No service job, rental, supplier payment, or manually entered cost is required.

History has optional `From` and `To` date filters, defaulting to the current month with an explicit way to view other dates. Show item-wise quantity used and valuation cost in the selected period, alongside a clearly labelled current stock balance. Where useful, show quantity received in that period from existing stock movements, without duplicating GRN data. Keep the selected-period totals separate from current balance and provide drill-down to the original usage, stock movement, and finance journal.

## Ownership and posting

- Expenses owns navigation and the user-facing screen. Inventory owns the consumption documents, validation, stock issue, valuation, reversal, API, and usage query. Finance owns the posting-account resolution and journal. Purchase and Payment flows remain unchanged.
- Post a consumption as one atomic operation: lock and validate scoped available stock, resolve the effective cost using existing Inventory valuation, record an outbound movement, and post `Dr Consumables Expense / Cr Inventory` through the Finance posting interface. Use a configured active consumables expense account for the posting scope; block posting clearly if configuration is missing.
- Use version checks, idempotency, and tenant/organization scope. Posted original records stay immutable; correction uses a linked reversal that restores stock and reverses the journal on its own date.
- Do not use `Add Expense` or Stock Adjustment for this flow. Do not modify the existing vehicle-service stock issue behavior. The same physical usage must be recorded through only one owning flow.

## Reporting

The new period-filtered Consumable Usage history is the operational quantity-and-cost report. Posted consumption journals feed the existing ledger-backed Profit and Loss automatically on the consumption posting date. Keep the existing paid Expense Report's meaning intact; do not silently merge Inventory consumption into its payment-method and expense-type metrics.

## Delivery and verification

1. Add Inventory-owned consumption header/line documents, permissions, scoped lookups, posting, reversal, and finance integration.
2. Add the Expenses navigation and focused form/history UI.
3. Verify GRN to supplier invoice/payment to partial consumption, remaining stock, valuation, P&L, period filters, reversal, retries, and concurrent usage. Verify existing Purchase, Expenses, Inventory, Vehicle Service, and Finance flows do not regress.

This record finalizes a development plan only; no application code, schema, or transactional data was changed.
