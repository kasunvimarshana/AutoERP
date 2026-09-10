# Dual supplier settlement mode review

Date: 2026-09-09

## Clarification

The business needs both supplier arrangements:

- normal procurement, where all accepted GRN quantities become company-owned stock and are payable regardless of whether they have been sold; and
- pay-on-sale/consignment procurement, where unsold quantities can remain supplier-owned and only sold quantities become eligible for supplier settlement.

## Current behavior

- The current system implements only normal procurement accounting for posted stockable GRNs.
- Posting a GRN records the full accepted stock value as Inventory debit and Goods Received Not Invoiced liability credit.
- Posting the supplier invoice clears the relevant GRNI amount and creates the supplier payable.
- Supplier payment terms currently determine only a due date (`due_on_receipt`, `net_7`, `net_15`, `net_30`, or an explicit due date).
- Supplier credit profiles can permit partial monetary payments, but they do not define stock ownership, sell-through eligibility, or item-level payment allocation.
- A partial supplier invoice for sold quantities can imitate cash timing, but it does not establish authoritative sale-to-supplier ownership lineage or a correct consignment accounting/reporting flow.

## Recommended configuration and ownership

- Introduce explicit settlement-basis values owned by Purchase, such as `on_receipt` and `on_sale`.
- Store a default settlement basis for the supplier and allow a supplier-item override because one supplier may provide different items under different arrangements.
- Snapshot the resolved settlement basis on each purchase-order and GRN line. Historical documents must never change when supplier defaults are edited later.
- Display the resolved basis as a controlled, human-readable field on PO and GRN lines; do not expose raw identifiers.
- Normal lines continue through the current inventory, GRNI, supplier-invoice, payable, and payment flow.
- On-sale lines require separate supplier-owned stock custody, sale-to-receipt quantity allocation, supplier settlement documents, item-level payment allocation, and reversal-aware reports.
- Returning unsold on-sale stock must reverse custody quantity without inventing a payable or financial purchase return when no payable was recognized.

## Changes

- No application behavior, schema, transactional records, or permissions were changed.
- Added only this review record to the append-only change history.

