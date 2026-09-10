# Finalized dual-mode supplier procurement and sell-through settlement requirements

Date: 2026-09-10

Status: Confirmed for future development; implementation has not started

## Purpose

This document is the approved source of truth for a future development that must support both normal supplier purchases and supplier-owned stock that becomes payable only when sold. Future implementation must refer to this document before changing application code or database schema.

This record finalizes the requirement and development direction only. It does not authorize implementation by itself. Development must begin only after the user explicitly requests it.

## Business requirement

The business works with two supplier arrangements:

1. **Normal purchase**: all accepted GRN quantities become company-owned stock and are payable regardless of whether they have been sold.
2. **Consignment / pay when sold**: received quantities remain supplier-owned, unsold quantities may be returned, and only quantities actually sold become eligible for supplier settlement.

Example for consignment:

- receive 10 units of Item A;
- sell 5 units;
- make the supplier settlement and payment for only the sold 5 units;
- retain 5 units as supplier-owned unsold custody stock; and
- report the exact supplier, item, receipt, sale, settlement, supplier invoice, payment, value, and lifecycle status.

The system must support both arrangements concurrently. A supplier may use different arrangements for different items or different purchase documents.

## Terminology

- **Procurement mode** determines ownership and when supplier settlement eligibility arises.
- **Payment terms** determine the due date after an amount has become payable.
- **Settlement** records which sold consignment quantities are being recognized and invoiced by the supplier.
- **Payment** records the later cash, bank, cheque, or other outbound settlement of an existing supplier payable.

Procurement mode and payment terms are independent concepts and must not be represented by one field.

## Procurement modes

Use a code enum with the following values:

- `normal_purchase`
- `consignment`

Do not use uncontrolled strings or boolean flags for this lifecycle.

### Normal purchase

- Accepted stock becomes company-owned when the GRN is posted.
- The full accepted stock value is recognized through Inventory and GRNI.
- The supplier invoice may be created for eligible received quantities.
- The supplier payable is not conditional on customer sales.
- Existing supplier invoice and supplier payment lifecycles remain authoritative.

### Consignment

- Posted receipt records physical custody but does not create company-owned Inventory, GRNI, or Supplier Payable.
- Each quantity remains linked to the supplier, GRN line, item, variant, UOM, warehouse/location, and batch where applicable.
- Only a posted, non-reversed sale stock issue can create supplier settlement eligibility.
- An invoice without an authoritative stock issue is not sufficient evidence that an item was sold.
- Unsold quantities may be returned to the supplier without inventing a supplier payable or a financial purchase return where no payable exists.

## Configuration resolution

The procurement mode must resolve in this order:

1. explicit purchase-order line or direct-GRN line selection;
2. supplier-item mapping override;
3. supplier default; and
4. system default of `normal_purchase`.

The UI must show human-readable controlled choices:

- `Company-owned — payable for received quantity`
- `Supplier-owned — payable when sold`

The resolved mode must be snapshotted on the purchase-order line and GRN line. Later changes to supplier defaults or item mappings must not rewrite historical documents.

Changing a document-line mode after dependent receipt, sale, settlement, invoice, return, or payment activity exists must be prohibited. Incorrect posted history must be corrected through explicit cancellation or reversal workflows.

## Current-system baseline

At the time this requirement was finalized:

- posted stockable GRNs are treated as normal company-owned purchases;
- the full accepted stock value posts to Inventory and GRNI;
- payment terms only calculate due dates;
- supplier credit profiles may allow partial monetary payment but do not model stock ownership or sell-through eligibility;
- supplier payments allocate money to supplier invoices, not to item lines or sold quantities;
- purchase invoice creation can select GRN quantities manually, but this does not prove that those quantities were sold;
- inventory valuation consumption is an accounting-cost mechanism and must not be reused as the authoritative supplier-ownership ledger, especially because weighted-average valuation may pool receipts; and
- generic manual sales invoices do not currently provide the authoritative stock-issue lineage needed for this requirement.

## Required database design

The implementation must keep one table per migration and use explicit Laravel migration definitions, foreign keys, indexes, and constraints. Existing records must default to `normal_purchase`; no historical record may be silently converted to consignment.

Exact implementation names may be adjusted only when required by an already-established owning-module convention, while preserving the responsibilities and data integrity defined below.

### Existing table changes

#### `suppliers`

Add:

- `default_procurement_mode`

Purpose: controlled supplier-level default. Existing suppliers resolve to `normal_purchase`.

#### `supplier_item_mappings`

Add:

- nullable `procurement_mode_override`

Purpose: override the supplier default for a specific human-selected item/variant mapping.

#### `purchase_order_lines`

Add:

- non-null `procurement_mode`

Purpose: immutable commercial-term snapshot resolved when the line is created or explicitly selected by the user.

#### `goods_receipt_note_lines`

Add:

- non-null `procurement_mode`

Purpose: immutable receipt and ownership snapshot, including direct GRNs without purchase orders.

The existing line unit price and currency context provide the initial agreed supplier-cost basis. Any later authorized price change must be recorded as a separate revision or adjustment and must not silently rewrite a posted receipt snapshot.

### Consignment custody subledger

The Inventory module owns physical quantity and custody integrity. Supplier settlement rules remain owned by Purchase.

#### `inventory_custody_lots`

One immutable receipt-origin lot per supplier-owned GRN line and batch allocation where applicable. Required data includes:

- tenant and organization unit;
- supplier;
- GRN and GRN line;
- item, variant, and base/entered UOM snapshots;
- warehouse, location, and batch where applicable;
- original accepted quantity;
- agreed supplier unit-cost and currency snapshot;
- status and row version; and
- created/posted actor and timestamps.

The lot identifies ownership and commercial source independently of the configured inventory valuation method.

#### `inventory_custody_movements`

Append-only custody events with explicit reversal relationships. Supported event meanings include:

- receipt;
- reservation/allocation where required by the sale flow;
- sale issue;
- customer-return restoration;
- supplier return;
- transfer; and
- reversal.

Each event must retain quantity, UOM conversion, custody lot, warehouse/location, business source and source line, event date, actor, status, and reversal reference.

#### `inventory_custody_balances`

Current transactional projection per custody lot and stock dimension. It must expose at least:

- quantity on hand;
- reserved/allocated quantity where applicable;
- available quantity;
- returned quantity; and
- row version.

The balance is updated atomically from immutable custody movements. It must never be accepted as historical evidence without its movement ledger.

Supplier-owned quantity must not be mixed into company-owned inventory value or weighted-average cost. Availability services may present combined physical availability only when ownership remains visible and the sale allocation preserves the chosen ownership source.

### Authoritative sale-to-stock linkage

The Sales-owning workflow must create a posted stock issue for every stockable item sale. This applies to any supported source, including general item sales and Vehicle Service part consumption where it represents a customer sale.

Required persisted linkage must identify:

- sale document and sale line;
- item, variant, UOM, warehouse/location, and quantity;
- company-owned inventory movement or supplier-owned custody movement;
- ownership/custody lot when consignment stock is used;
- issue date and actor; and
- reversal/cancellation status.

A mixed sale line may consume more than one stock source, but every allocation must be explicit and quantity-balanced. The UI must show the selected ownership source in human-readable form. No report or settlement service may infer sold supplier quantity from invoice quantity alone.

### Supplier settlement tables

#### `supplier_settlements`

Header fields must include:

- row version;
- tenant and organization unit;
- settlement number and date/period;
- supplier and currency;
- lifecycle status;
- generated supplier invoice reference when created;
- notes and external supplier reference;
- created, approved, cancelled, and invoiced actor/timestamps as applicable.

Initial lifecycle:

- `draft`
- `approved`
- `invoiced`
- `cancelled`

Payment state must be derived from invoice/payment balances and must not be duplicated as an independently editable settlement status.

#### `supplier_settlement_lines`

Each line must retain:

- settlement;
- authoritative sale stock/custody issue allocation;
- custody lot and GRN line;
- item, variant, and UOM snapshots;
- sold quantity available before settlement;
- settlement quantity;
- agreed supplier unit amount;
- line base amount;
- tax/adjustment allocation snapshots where applicable;
- final payable line amount;
- source sale number/date and receipt number/date snapshots; and
- status/reversal fields.

The sum of active settlement quantities against a sale/custody issue must never exceed the non-reversed eligible sold quantity. Approval must lock and revalidate all source quantities.

#### Item-level supplier payment allocation

Add a Purchase-owned item-level allocation record linked to the canonical Payment module allocation. The final table name should follow the module convention, with the responsibility represented by `supplier_payment_line_allocations`.

Required data includes:

- row version;
- tenant and organization unit;
- canonical payment allocation;
- supplier invoice line;
- supplier settlement line where applicable;
- allocated amount;
- allocation date and method;
- status;
- realized/reversed actor and timestamps; and
- explicit reversal reference.

For a normal supplier invoice, this record links payment value to invoice items. For a consignment invoice, it additionally reaches the exact sold quantity, sale, custody lot, and GRN.

Settlement quantity is authoritative. A partial monetary payment must show an exact paid value and partial status; the system must not invent a paid quantity by dividing money unless the user explicitly selected a quantity and the complete line-value allocation is valid. A fully paid line may report its settlement quantity as fully paid.

## Core transaction and concurrency rules

All writes must be transactional, version-checked, and conflict-aware.

The implementation must:

- lock supplier, PO/GRN line, custody lot/balance, sale issue allocation, settlement line, invoice balance, and payment allocation records in a stable order;
- revalidate eligibility after locks are acquired;
- prevent the same sold quantity from being settled twice;
- prevent the same invoice-line value from being paid twice;
- enforce tenant, organization-unit, supplier, item, currency, source, and lifecycle scope on the backend;
- use unique active-identity constraints where a reversal-aware relationship requires one active record;
- use idempotency/correlation keys for document creation and Finance posting;
- reject stale row versions with a reload/conflict response;
- keep original quantities, rates, values, allocations, and postings immutable; and
- correct posted history using separate reversal, credit, debit-note, or adjustment records.

## Accounting requirements

### Normal purchase

At posted GRN:

- Debit Inventory
- Credit GRNI

At posted supplier invoice:

- Debit GRNI for the recognized stock basis
- Debit recoverable input tax/other applicable roles
- Credit Supplier Payable

At item sale/issue:

- Debit Cost of Goods Sold
- Credit Inventory

At supplier payment:

- Debit Supplier Payable
- Credit Cash/Bank or the configured payment account

### Consignment

At custody receipt:

- no company-owned Inventory, GRNI, Supplier Payable, revenue, or expense posting;
- record physical supplier-owned custody only.

At posted customer sale stock issue:

- recognize the related revenue through the authoritative customer-sale posting;
- Debit Cost of Goods Sold using the agreed supplier-cost basis for the sold custody quantity;
- Credit a dedicated Consignment Settlement Accrual liability.

At posted supplier settlement invoice:

- Debit Consignment Settlement Accrual for the previously accrued sold-item basis;
- Debit recoverable input tax/other applicable roles when valid;
- Credit Supplier Payable for the supplier invoice total.

At supplier payment:

- Debit Supplier Payable
- Credit Cash/Bank or the configured payment account.

Supplier payment is not a second expense and must not reduce revenue. Profit must reflect revenue minus COGS and other income-statement expenses. Paying early or late affects cash and outstanding liabilities, not the gross profit already recognized for the sale.

## Return, cancellation, and reversal requirements

### Unsold consignment return

- Create a supplier custody return linked to the original lot.
- Reduce custody quantity through an immutable movement.
- Do not create a payable, debit note, or cash entry when no supplier financial amount was recognized.

### Customer return before settlement invoice

- Reverse the customer sale and stock/custody issue through owning-module reversal workflows.
- Reverse the related COGS and Consignment Settlement Accrual.
- Restore supplier-owned custody quantity.
- Remove eligibility through reversal state; never delete the original eligible event.

### Customer return after supplier settlement invoice or payment

- Do not rewrite the original sale, settlement, supplier invoice, or payment.
- Restore physical custody through a return/reversal event where the item is returned.
- Create a supplier debit note or explicit supplier credit/recovery allocation against a later settlement according to the existing Purchase credit lifecycle.
- Preserve the original paid history and show the recovery separately in reports.

### Normal purchase return

- Continue using the existing Purchase Return and supplier debit-note lifecycle.
- Do not route normal company-owned returns through the consignment custody-return flow.

## UI requirements

All relationships must use controlled human-readable selectors. Raw database identifiers must never be shown or manually entered.

### Supplier setup

Add a default procurement-mode control with a concise explanation of ownership and payment impact.

### Supplier item mapping

Add an optional override control showing both the supplier default and the effective value.

### Purchase order and GRN lines

Show the effective mode as one of:

- `Company-owned — payable for received quantity`
- `Supplier-owned — payable when sold`

Allow an authorized explicit selection before posting and show a confirmation when it differs from the supplier/item default. Lock the value after dependent activity.

### Supplier Settlements workspace

Create `Purchase > Supplier Settlements` with:

- supplier and period filters;
- counts/values for sold, already settled, ready to settle, returned, and reversed quantities;
- item, variant, GRN, sale, sold date, available settlement quantity, agreed rate, and payable value;
- quick selection of eligible rows/quantities;
- preview with before/after quantities and amounts;
- draft creation, approval, invoice creation, cancellation/reversal, and payment handoff based on permissions.

Only posted, non-reversed, not-fully-settled sale issue allocations may appear as eligible.

### Payment handoff

Reuse the canonical Payment module for cash/bank/cheque execution. The Purchase integration must also record item-level allocations.

Before creation, show:

- supplier and currency;
- supplier invoice and settlement;
- human-readable item lines;
- settlement quantities;
- line payable value, previously paid value, payment allocation, and balance after payment;
- payment-method totals and any unapplied amount.

## Reports

### Supplier Sell-through and Settlement

Filters:

- supplier;
- item/variant;
- GRN/receipt period;
- sale period;
- settlement period;
- procurement mode; and
- lifecycle/payment status.

Columns/totals:

- received quantity/value;
- sold quantity/value;
- eligible quantity/value;
- settled quantity/value;
- invoiced value;
- paid value;
- returned/reversed quantity/value;
- unsold custody quantity; and
- unsettled/unpaid balances.

### Supplier Payment by Item

Show:

- payment number/date/method/status;
- supplier and currency;
- supplier invoice and settlement;
- item/variant/UOM;
- GRN and sale reference;
- settlement quantity;
- line payable amount;
- allocated/paid amount;
- remaining line amount; and
- reversal/recovery references.

### Stock Ownership

Show company-owned and supplier-owned quantities separately by item, warehouse/location, batch, supplier, and age. Combined availability may be shown only with a visible ownership breakdown.

### Profit and Loss, Payables, and Cash Flow

- The ledger-backed P&L remains the source of truth for revenue, COGS, expenses, and net profit.
- Supplier payment must not be deducted from revenue or counted again as an expense.
- Accounts Payable reports show supplier invoice liabilities and settlement status.
- Cash-flow/payment reports show actual supplier cash outflows.
- Operational sell-through reports must reconcile to Finance postings but must not replace ledger-backed financial statements.

## Permissions and audit

Introduce narrowly scoped permissions for:

- viewing supplier custody stock;
- selecting/overriding procurement mode;
- viewing, creating, approving, invoicing, cancelling, and reversing supplier settlements;
- creating supplier settlement payments; and
- viewing/exporting sell-through and item-payment reports.

Every material action must record actor, timestamp, before/after lifecycle state, row version, source document, and reason where cancellation/reversal occurs.

## Implementation sequence

1. Re-read this requirement and the latest `/docs/changes` records.
2. Audit the active customer-sale sources and identify which currently create authoritative stock issues.
3. Add procurement-mode enums, supplier defaults, item overrides, and PO/GRN snapshots.
4. Add the consignment custody lot, movement, and balance foundation.
5. Make stock availability and sale allocation ownership-aware without mixing supplier-owned quantity into company inventory valuation.
6. Complete the Sales-owned stock-issue linkage for every in-scope item-sale source.
7. Add consignment COGS and settlement-accrual Finance postings and their reversals.
8. Implement supplier settlement lifecycle, quantity eligibility, and invoice generation.
9. Add item-level payment allocations while retaining the canonical Payment module for money movement.
10. Implement unsold supplier returns, customer-return reversals, post-payment recovery/debit-note handling, and cancellation constraints.
11. Build the supplier/item/PO/GRN controls and Supplier Settlements workspace.
12. Build the sell-through, item-payment, stock-ownership, payable, and reconciliation reports.
13. Verify permissions, tenant/organization scope, concurrency, idempotency, immutable history, and performance.
14. Run the complete relevant backend and frontend regression suites.
15. Add a new completion record to `/docs/changes`; never modify this finalized requirement record.

## Required verification scenarios

At minimum, automated tests must prove:

- existing suppliers and historical lines default safely to normal purchase;
- one supplier can use normal and consignment items concurrently;
- a PO/GRN snapshot does not change when supplier defaults are edited;
- normal GRN accounting remains unchanged;
- consignment GRN affects custody quantity but not company Inventory, GRNI, Payable, or P&L;
- a customer invoice without a posted stock issue is not settlement-eligible;
- selling 5 of 10 consignment units makes exactly 5 eligible;
- repeated or concurrent requests cannot settle more than 5;
- selecting 3 of 5 eligible units leaves 2 eligible;
- settlement approval and supplier invoice generation are idempotent;
- payment allocation cannot exceed the invoice or item-line balance;
- a payment can report the exact supplier invoice and item lines it covered;
- partial monetary payment reports paid value without inventing paid quantity;
- unsold supplier return creates no payable or purchase-return finance entry;
- customer return before settlement reverses eligibility and accrual correctly;
- customer return after payment preserves history and creates a separate supplier recovery/debit-note trail;
- payment reversal restores payable/item-line balances without deleting history;
- P&L equals revenue minus COGS/expenses and does not count supplier payment as another expense;
- Payables and Cash/Bank reconcile before and after supplier payment;
- company-owned and supplier-owned quantities do not mix in valuation; and
- all operations reject cross-tenant, cross-organization, cross-supplier, stale-version, invalid-currency, and unauthorized requests.

## Explicit non-goals

- Do not implement the feature merely by adding item columns to the existing payment form.
- Do not infer supplier settlement from customer invoice quantity without posted stock issue evidence.
- Do not use inventory valuation consumptions as the supplier ownership source of truth.
- Do not treat partial payment as proof of a calculated paid quantity.
- Do not post consignment receipt value into normal company Inventory or GRNI.
- Do not rewrite historical receipts, sales, settlements, invoices, payments, or returns.
- Do not preserve the current normal-purchase-only assumption with compatibility workarounds inside unrelated modules.
- Do not start implementation without a separate explicit user instruction.

## Development authorization state

The requirement and plan are confirmed and saved for future reference. No development, migration execution, application-code change, UI change, report change, permission change, or transactional-data change is authorized by this confirmation. The future implementation starts only when the user explicitly asks to develop this finalized requirement.

