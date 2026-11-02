# Multi-Invoice Payment Settlement

## Why

Users needed the ability to settle multiple invoices simultaneously using a single payment (e.g., customer receipts settling several rental invoices, or supplier payments settling multiple vendor bills).

While the backend payment and invoice domain engines supported batch allocations internally, the application lacked:
1. An endpoint query filter to retrieve open / payable invoices (`has_balance_due`) for a given party.
2. A user interface during payment creation (`PaymentEntryPage`) to view, select, and allocate amounts across multiple outstanding invoices.
3. A user interface on existing posted payments (`PaymentDetailPage`) to allocate unapplied balances across multiple outstanding invoices.

## What Changed

- **Backend Query Support**:
  - Updated `Modules\Invoice\Http\Requests\ListInvoiceRequest` to accept `party_type` and `has_balance_due` boolean filters.
  - Updated `Modules\Invoice\Http\Controllers\InvoiceController::index` to filter by `party_type` and constrain to open invoices (`balance_due > 0` and status in `posted`, `partially_paid`) when `has_balance_due` is enabled.

- **Frontend Client & Components**:
  - Updated `resources/js/modules/invoice/invoiceApi.ts` `ListInvoiceParams` to include `party_type`, `direction`, and `has_balance_due`.
  - Created `resources/js/modules/payment/components/InvoiceSettlementSelector.tsx`:
    - Automatically loads open invoices for the selected customer/supplier.
    - Displays invoice number, date, type, total, balance due, amount to settle, and projected remaining balance.
    - Supports individual selection, "Select all", "Deselect all", and "Auto-allocate (FIFO)" distribution.
    - Real-time balance calculations ensuring allocated amounts do not exceed invoice balances or payment total.
  - Enhanced `PaymentEntryPage.tsx`:
    - Integrated `InvoiceSettlementSelector` when a party is selected.
    - Enables "Set payment total to [Allocated Total]" convenience action.
    - Submits `allocations` payload alongside payment methods for atomic multi-invoice settlement.
  - Enhanced `PaymentDetailPage.tsx`:
    - Added an "Allocate Invoices" button to the header and Allocations tab when unapplied balance is available (`can_allocate`).
    - Added a guided modal powered by `InvoiceSettlementSelector` to allocate remaining payment funds across multiple invoices.

- **Automated Tests**:
  - Added `test_single_payment_can_settle_multiple_invoices_api` in `tests/Feature/Api/CoreModulesApiTest.php` covering multi-invoice settlement on both payment creation and post-payment allocation.
  - Added unit test suite `resources/js/modules/payment/components/InvoiceSettlementSelector.test.tsx` verifying invoice rendering, selection, and FIFO auto-allocation.

## Verification

- `php artisan test --filter=CoreModulesApiTest` (8 passed, 89 assertions)
- `php artisan test --filter=Payment` (32 passed, 271 assertions)
- `npx vitest run resources/js/modules/payment --reporter=dot` (2 passed)
- `npm run typecheck`
- `npm run build`
- `git diff --check`
