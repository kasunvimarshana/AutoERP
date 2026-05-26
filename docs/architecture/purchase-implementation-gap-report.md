# Purchase Implementation Gap Report

Primary source of truth:
- `app/Modules/*/Infrastructure/Persistence/Eloquent/Models`
- `app/Modules/*/Infrastructure/Persistence/Eloquent/Migrations`

Scope reviewed:
- `Purchase`, `Inventory`, `Invoice`, `Finance`, `Payment`, `Supplier`, `Sequence`, `Item`, `UOM`, `Warehouse`

## Decision

Do not implement the complete enterprise procurement lifecycle in one pass.

The current migrations support a strong Purchase backbone, but the runtime layer is mostly generated CRUD. The requested orchestration services do not currently exist as stable application contracts:

- `InventoryTransactionService`
- `JournalEntryService`
- `SequenceService`
- `UomConversionService`
- `TaxCalculationService`

The correct first implementation is **Phase 1: Purchase Order workflow foundation**, because it is mostly supported by current Purchase migrations and can establish enums, DTOs, aggregate services, recalculation, sequence integration, optimistic locking, and transactional writes without forcing premature cross-module posting logic.

## Purchase Schema Capability

### Purchase Orders

Supported by current schema:
- Header table: `purchase_orders`
- Line table: `purchase_order_lines`
- Soft delete on both header and lines
- `row_version` for optimistic concurrency
- Tenant and organization unit scope
- Supplier, warehouse, currency, exchange rate, price list
- `po_number` with tenant uniqueness
- Status field with documented values: `draft`, `confirmed`, `partially_received`, `received`, `cancelled`
- Invoice status field with documented values: `not_invoiced`, `partially_invoiced`, `closed`
- Header discount type/value/amount
- Header tax group and tax amount
- Line discount type/value/amount
- Line tax group and tax amount
- Line totals: gross, net, with tax
- Header totals: subtotal, line tax total, line discount total, discount total, tax total, debit note total, credit note total, grand total
- Payment rollups: `paid_amount`, `balance`
- Line receipt and invoice tracking: `received_qty`, `rejected_qty`, `invoiced_qty`

Needs implementation:
- PO aggregate service
- PO line recalculation
- Header recalculation
- Confirm/cancel transition rules
- Sequence number generation
- Optimistic update checks
- Automatic status update from received/invoiced quantities

### Goods Receipt Notes

Supported by current schema:
- Header table: `grn_headers`
- Line table: `grn_lines`
- Soft delete on both header and lines
- `row_version`
- Link from GRN header to purchase order
- Link from GRN line to purchase order line
- Supplier, warehouse, currency, exchange rate, price list
- `grn_number` with tenant uniqueness
- Status field with documented values: `draft`, `confirmed`
- Invoice status field
- Expected, received, rejected, and invoiced quantities
- Batch, serial, warehouse, and location references on lines
- Same discount/tax/total structure as PO

Needs implementation:
- GRN create-from-PO service
- Partial receipt validation
- Confirm transition
- PO line `received_qty` update
- PO status recomputation
- Inventory receive contract and adapter

### Purchase Returns

Supported by current schema:
- Header table: `purchase_returns`
- Line table: `purchase_return_lines`
- Soft delete on both header and lines
- `row_version`
- Links to original purchase order, GRN, and invoice
- `return_number` with tenant uniqueness
- Status field with documented values: `draft`, `approved`, `closed`, `cancelled`
- Return reason
- Line references to original GRN line and original purchase order line
- Return quantity
- Restocking fee, condition, disposition
- Quality check notes
- Same discount/tax/total structure, plus line restocking total

Needs implementation:
- Return approval workflow
- Stock reversal contract
- Credit/debit note creation through Invoice
- Finance reversal/posting integration

## Related Module Capability

### Invoice

Supported:
- `invoices.direction` supports `inbound` and `outbound`
- `invoice_type` is a free string
- Status supports `draft`, `approved`, `partially_paid`, `paid`, `disputed`, `cancelled`
- `party_type`, `party_id`
- Header discounts, taxes, totals, paid amount, balance
- AP/AR account references
- `journal_entry_id`
- `invoice_references` can link invoices to source documents by `document_type` and `document_id`
- `invoice_lines` support item, UOM, quantity, unit price, discounts, taxes, account

Gaps:
- No purchase-specific invoice service exists.
- Requested invoice types `standard`, `credit_note`, `debit_note` can be stored as strings, but no enum/contract enforces them.
- Invoice lines do not directly reference PO/GRN lines except through `invoice_reference_id` and metadata/reference conventions.

### Payment

Supported:
- `payments.direction` supports outbound payments.
- `payments.party_type`, `party_id` can represent supplier payments.
- `payment_allocations` can allocate payments to document records.
- `advance_payments` supports supplier advances through `type`, `party_type`, `party_id`, `remaining_amount`, and statuses.
- `advance_payment_allocations` supports later allocation to documents.
- `journal_entry_id` exists on payments.

Gaps:
- No purchase payment orchestration service exists.
- No payment posting contract exists.
- No direct enforcement that allocations target inbound purchase invoices.

### Finance

Supported:
- Journal headers and lines exist.
- Journal entries support reference type/id, status, reversal link, posting metadata.
- Journal lines support debit/credit, account, currency, base amounts, tax rate, cost center.
- Tax groups and tax rates exist.
- `tax_rates.is_compound` exists.

Gaps:
- No `JournalEntryService` with purchase accrual/invoice/payment/return methods exists.
- No tax calculation service exists.
- No account role resolver exists for AP, inventory, tax, GR/IR clearing, bank/cash.

### Inventory

Supported:
- Stock movements support IN/OUT direction, item/variant/batch/serial/location/warehouse, reference morphs, UOM, quantities, unit cost, total cost, balances.
- Stock levels support item/variant/warehouse/location/batch/serial/UOM, on-hand/reserved, unit cost, condition.
- Inventory cost layers support receipt cost layers with quantity remaining and reference morphs.

Gaps:
- No `InventoryTransactionService` contract exists.
- No receive/issue/reverse orchestration exists.
- Cost layer depletion/reversal behavior is not implemented.

### Sequence

Supported:
- `sequences` table has tenant/org/document type/period uniqueness.
- `next_number`, prefix, suffix, padding, period type/value exist.
- Repository can find sequence by scope.
- Domain entity exposes `nextNumber()`.

Gaps:
- No atomic `SequenceService::next()` exists.
- Repository does not expose lock-and-increment semantics.

### Supplier, Item, UOM, Warehouse

Supported:
- Supplier has AP account, currency, status, and payment terms.
- Item has purchase UOM, tax group, stockable flags, valuation method, standard/cost price, inventory account, purchase return account, price variance account.
- UOM conversion table exists with item-specific conversion support.
- Warehouse and warehouse locations exist.

Gaps:
- No supplier business repository methods beyond generic CRUD.
- No item account resolver service.
- No UOM conversion calculation service.
- No warehouse availability/validation service.

## Required Runtime Contracts Before Full Lifecycle

These contracts should be introduced before implementing GRN confirmation, invoice posting, payments, and returns:

| Contract | Suggested Module | Minimum Methods |
|---|---|---|
| `SequenceNumberServiceInterface` | `Sequence` | `next(int $tenantId, ?int $organizationUnitId, string $documentType, ?DateTimeInterface $date = null): string` |
| `TaxCalculationServiceInterface` | `Finance` | `calculateGroup(int $taxGroupId, string $taxableAmount, ?DateTimeInterface $date = null): TaxCalculationResult` |
| `JournalEntryPostingServiceInterface` | `Finance` | `createPurchaseAccrual`, `createPurchaseInvoice`, `createPaymentEntry`, `createReturnEntry`, `reverse` |
| `InventoryTransactionServiceInterface` | `Inventory` | `receive`, `issue`, `reverse` |
| `UomConversionServiceInterface` | `UOM` | `convert(int $tenantId, ?int $itemId, int $fromUomId, int $toUomId, string $quantity): string` |

Do not create concrete cross-module dependencies inside Purchase services until these interfaces exist and are bound by their owning modules.

## Recommended Phase 1

Implement only the Purchase Order workflow foundation:

1. Purchase enums:
   - `PurchaseOrderStatus`
   - `PurchaseInvoiceStatus`
   - `DiscountType`

2. Purchase DTOs:
   - `CreatePurchaseOrderData`
   - `UpdatePurchaseOrderData`
   - `PurchaseOrderLineData`
   - `ConfirmPurchaseOrderData`
   - `CancelPurchaseOrderData`

3. Domain/application services:
   - `PurchaseDocumentCalculator`
   - `PurchaseOrderWorkflowService`

4. Repository improvements:
   - Load PO with lines.
   - Replace PO lines transactionally.
   - Update with expected `row_version`.
   - Recalculate and persist header totals.

5. Controller endpoints:
   - `POST /api/purchase/purchase-orders`
   - `PUT /api/purchase/purchase-orders/{purchaseOrder}`
   - `PATCH /api/purchase/purchase-orders/{purchaseOrder}/confirm`
   - `PATCH /api/purchase/purchase-orders/{purchaseOrder}/cancel`
   - Keep existing CRUD endpoints compatible where practical.

6. Events:
   - `PurchaseOrderCreated`
   - `PurchaseOrderConfirmed`

## Phase 1 Non-Goals

Do not implement these yet:
- GRN confirmation stock posting
- Purchase invoice posting
- Payment allocation
- Advance payment allocation
- Purchase return stock/finance impact
- Journal reversal

These require stable cross-module interfaces first.

## Phase 2

Implement GRN create-from-PO and confirm:
- Add `GoodsReceiptWorkflowService`.
- Generate `grn_number`.
- Validate quantities against PO open quantities.
- Update PO line `received_qty`.
- Recompute PO status.
- Dispatch `GrnConfirmed`.
- Call `InventoryTransactionServiceInterface::receive()` only after the Inventory contract exists.

## Phase 3

Implement purchase invoices:
- Create inbound invoices from GRN or PO.
- Create invoice references using `document_type` and `document_id`.
- Update PO/GRN invoiced quantities.
- Post journal entries through Finance contract.
- Dispatch `PurchaseInvoicePosted`.

## Phase 4

Implement payments and advances:
- Create outbound supplier payments.
- Allocate to inbound purchase invoices.
- Update invoice paid/balance.
- Update PO aggregate paid/balance.
- Record supplier advance payments and allocations.
- Post finance entries.

## Phase 5

Implement purchase returns and reversals:
- Create return from GRN, PO, invoice, or manual source.
- Approve returns.
- Issue/reverse inventory.
- Create credit/debit note.
- Reverse journal entries.
- Dispatch `PurchaseReturnProcessed`.

## Implementation Rule

Phase 1 can proceed without schema changes.

Phases 2-5 should not proceed until the owning modules expose the required interfaces, or the task explicitly includes creating those module-owned interfaces first.
