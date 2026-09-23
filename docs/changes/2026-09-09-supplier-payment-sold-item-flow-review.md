# Supplier payment and sold-item flow review

Date: 2026-09-09

## Request

Reviewed the existing Purchase UI and backend to explain how a supplier payment is created and whether it can be restricted to items that have been sold.

## Findings

- Supplier payments are allocated to posted or partially paid supplier invoices, not to individual item lines.
- The payment screen supports selecting a supplier, allocating monetary amounts across that supplier's outstanding invoices, and splitting the payment total across outbound payment methods.
- A payment allocation cannot exceed the invoice balance and must remain within the current tenant, organization unit, supplier, and currency scope.
- Creating a supplier payment runs payment creation, submission, approval, allocation realization, and Finance posting atomically. Realized allocations update the invoice paid amount and remaining balance.
- Item and quantity selection belongs to supplier-invoice creation. The invoice UI can create an invoice from selected GRN or purchase-order lines and allows a specific `Quantity now` for each included line.
- Therefore, when the business meaning is "pay only for these selected purchased items/quantities," the auditable current flow is to create and post a supplier invoice containing only those lines/quantities, then pay that invoice.
- The current code does not derive supplier-payable quantities from customer sales. There is no consignment or sell-through settlement model linking sold customer invoice lines to supplier-owned receipt quantities. A partial monetary payment against a mixed supplier invoice cannot identify which item was paid.

## Changes

- No application behavior, database schema, permissions, or transactional data was changed.
- Added only this review record to the append-only change history.

