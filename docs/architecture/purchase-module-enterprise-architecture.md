# Purchase Module Enterprise Architecture

## Scope

The Purchase module owns the procurement lifecycle from supplier commitment through receipt, supplier billing, payment, and supplier returns. Existing Eloquent models and migrations under `app/Modules/*/Infrastructure/Persistence/Eloquent` remain the source of truth for table names, columns, foreign keys, and persisted states.

VehicleRental is intentionally excluded.

## Folder Structure

```text
app/Modules/Purchase
├── Application
│   ├── DTOs
│   ├── Orchestrators
│   └── UseCases
├── Domain
│   ├── Entities
│   ├── Enums
│   ├── Events
│   ├── Exceptions
│   ├── Repositories
│   └── Services
├── Infrastructure
│   ├── Config
│   ├── Persistence/Eloquent
│   │   ├── Migrations
│   │   ├── Models
│   │   └── Repositories
│   └── Providers
├── Presentation
│   └── Http
│       ├── Controllers
│       ├── Requests
│       └── Resources
└── routes
```

## Domain Model

Core purchase aggregates:

- `PurchaseOrder`: supplier commitment, warehouse destination, currency, status, invoice status, monetary totals, and order lines.
- `GoodsReceipt`: physical receipt of goods against a PO or supplier delivery, with warehouse/location/batch/serial tracking.
- `PurchaseInvoice`: inbound supplier bill using the shared `invoices` table.
- `PurchasePayment`: outbound supplier payment using the shared `payments` and `payment_allocations` tables.
- `PurchaseReturn`: supplier return linked to PO, GRN, invoice, or manual reference.
- `AdvancePayment`: supplier prepayment using `advance_payments` and `advance_payment_allocations`.

The module uses shared services instead of duplicating cross-module logic:

- `Modules\Inventory\Domain\Services\InventoryTransactionService`
- `Modules\Finance\Domain\Services\JournalEntryService`
- `Modules\Sequence\Domain\Services\SequenceService`
- `Modules\Purchase\Domain\Services\TaxCalculationService`

## Service Breakdown

- `PurchaseOrderService`
  - Creates and updates draft POs.
  - Generates PO numbers.
  - Calculates line/header totals.
  - Confirms and cancels with validation.
  - Prevents cancellation once GRNs exist.

- `GoodsReceiptService`
  - Creates GRNs against confirmed POs.
  - Confirms GRNs.
  - Updates PO line `received_qty` and `rejected_qty`.
  - Updates PO status to `confirmed`, `partially_received`, or `received`.
  - Calls Inventory receive operations.

- `PurchaseInvoiceService`
  - Creates inbound supplier invoices.
  - Links source PO/GRN through `invoice_references`.
  - Approves invoices.
  - Updates invoiced quantities.
  - Posts journal entries through Finance.

- `PurchasePaymentService`
  - Creates outbound supplier payments.
  - Allocates payments to invoices.
  - Updates invoice balances and PO paid/balance rollups.
  - Posts payment journal entries.

- `PurchaseReturnService`
  - Creates supplier returns.
  - Approves returns.
  - Issues stock back out of inventory.
  - Posts purchase return accounting entries.

- `PurchaseAdvancePaymentService`
  - Records supplier advances.
  - Allocates advance balances to documents.

- `PurchaseReversalService`
  - Cancels invoices.
  - Voids payments.
  - Maintains idempotent status transitions.

## Core Workflow

### PO -> GRN -> Invoice -> Payment

1. Create PO
   - Status: `draft`.
   - PO number generated through Sequence.
   - Line totals, header discount/tax, grand total, and balance are calculated.

2. Confirm PO
   - Status moves to `confirmed`.
   - Draft-only edits are blocked after confirmation.

3. Create GRN
   - Requires confirmed PO when linked.
   - Supports partial receipts.
   - Captures warehouse, location, batch, serial, UOM, quantity, cost, tax, and discount values.

4. Confirm GRN
   - Status moves to `confirmed`.
   - PO lines receive quantity updates.
   - Inventory receives stock and creates movement/cost-layer records.
   - PO status becomes `partially_received` or `received`.
   - Dispatches `GrnConfirmed`.

5. Create Purchase Invoice
   - Uses `invoices.direction = inbound`.
   - Uses `invoice_references` for PO/GRN linkage.
   - Starts as `draft`.

6. Approve Invoice
   - Status moves to `approved`.
   - Invoiced quantities are updated.
   - Finance posts double-entry journal.
   - Dispatches `PurchaseInvoicePosted`.

7. Create Payment
   - Uses `payments.direction = outbound`.
   - Allocates to invoices with `payment_allocations.document_type = INVOICE`.
   - Updates invoice paid amount and balance.
   - Updates linked PO aggregate paid amount and balance.
   - Posts payment journal.
   - Dispatches `PurchasePaymentProcessed`.

## Event Flow

| Event | Producer | Main Side Effects |
| --- | --- | --- |
| `PurchaseOrderCreated` | PO create | audit/integration hooks |
| `PurchaseOrderConfirmed` | PO confirm | downstream procurement hooks |
| `GrnConfirmed` | GRN confirm | inventory already updated transactionally; listener-ready |
| `PurchaseInvoicePosted` | invoice approve | finance already posted transactionally; listener-ready |
| `PurchasePaymentProcessed` | payment create | balances updated transactionally; listener-ready |
| `PurchaseReturnProcessed` | return approve | stock and finance updated transactionally; listener-ready |

Current implementation performs critical stock/finance effects inside the same database transaction for consistency. Events are still dispatched to support future async audit, notification, analytics, or external integration listeners.

## Accounting Rules

Journal postings use the shared Finance service and are balanced before insertion.

Typical postings:

- GRN accrual, when enabled:
  - Dr Inventory or Expense
  - Cr GRNI clearing

- Purchase invoice:
  - Dr Inventory, Expense, or GRNI clearing
  - Dr Input tax
  - Cr Accounts payable

- Outbound payment:
  - Dr Accounts payable
  - Cr Cash or Bank

- Purchase return:
  - Dr Accounts payable
  - Cr Inventory or Expense

Account resolution should prefer supplier/item/tax configured accounts where available, then safe configured fallback accounts.

## Inventory Rules

Inventory integration writes:

- `stock_movements`
- `stock_levels`
- `inventory_cost_layers`

GRN confirmation increases stock. Purchase return approval decreases stock. The shared inventory service prevents negative stock and supports warehouse, location, batch, serial, condition, and UOM fields that exist in the migrations.

Valuation support:

- FIFO-compatible receipts via cost layers.
- Weighted-average-compatible stock levels through updated unit cost snapshots.

## Business Rules

- PO can be edited only while `draft`.
- PO can be cancelled only before receipt and only if no GRN exists.
- GRN can be confirmed once; repeated confirmation is idempotent.
- GRN quantities cannot exceed ordered quantities.
- Purchase invoice approval is transactional and posts accounting entries.
- Payment allocation cannot create inconsistent invoice balances.
- Purchase return approval reverses inventory and posts accounting impact.
- Advance payment allocation cannot exceed `remaining_amount`.
- Write operations affecting multiple aggregates run inside `DB::transaction`.
- Critical updates increment `row_version`.

## API Surface

Purchase routes are under `api/purchase`:

- `purchase-orders`
- `purchase-orders/{id}/confirm`
- `purchase-orders/{id}/cancel`
- `grn-headers`
- `grn-headers/{id}/confirm`
- `purchase-invoices`
- `purchase-invoices/{id}/approve`
- `purchase-payments`
- `purchase-returns`
- `purchase-returns/{id}/approve`
- `advance-payments`
- `advance-payments/{id}/allocate`

## Testing Strategy

Recommended critical-path feature test:

1. Seed tenant, organization unit, user, supplier, warehouse, item, UOM, tax group, accounts, payment method.
2. Create PO with one stockable line.
3. Confirm PO.
4. Create and confirm partial GRN.
5. Assert stock movement, stock level, and cost layer exist.
6. Create and approve inbound invoice.
7. Assert journal entry is balanced.
8. Create outbound payment allocation.
9. Assert invoice and PO balances are updated.
10. Create and approve return.
11. Assert stock decreases and return journal exists.

## Production Notes

- Keep module boundaries strict: controllers call use cases/orchestrators; use cases call domain services; domain services call repositories or shared cross-module services.
- Do not access Eloquent models directly from controllers.
- Use events for extensibility, but keep stock and finance mutations in the originating transaction where financial consistency is required.
- Add idempotency keys to externally retried payment/invoice APIs.
- Prefer configured account mappings over fallback accounts before production deployment.
