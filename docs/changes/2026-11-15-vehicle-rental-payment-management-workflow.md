# Vehicle Rental Payment Management Workflow & Receipt Engine

**Date:** 2026-11-15  
**Scope:** `Payment`, `VehicleRental`, `Invoice`  
**Focus:** Enterprise payment lifecycle, multi-invoice allocations, advance payments, owner disbursements, payment receipts & disbursement vouchers, reversals, and financial summary integration.

---

## 1. Problem Statement & Objectives
Previously, the Vehicle Rental module had separate mechanisms for handling security deposits and generating invoices/settlement bills, but lacked an end-to-end payment workflow directly connected to the agreement lifecycle:
1. Users could generate Stage 1 provisional invoices, Stage 2 supplementary/consolidated invoices, and owner settlement bills, but could not record customer payment receipts or owner disbursement vouchers directly against an agreement.
2. There was no direct UI support for multi-invoice payment allocation (paying multiple rental invoices with a single payment) or handling advance payments / overpayments (where payment amount exceeds allocated invoices, resulting in an unapplied advance credit balance).
3. Printable payment receipts and disbursement vouchers (complete with amount-in-words, payment method details, party information, agreement reference, and invoice allocation table) were not exposed.
4. When invoices were in draft, payment allocation failed without auto-posting through the canonical invoice lifecycle.
5. In `InvoiceDetailPage.tsx`, `freshRentalSourceTypes` omitted modern two-stage billing cycle invoices and owner settlement bills, preventing direct settlement actions.

---

## 2. Key Changes & Implementation Details

### Backend
1. **`Modules\Payment\Enums\PaymentSourceType`**:
   - Added canonical enum cases `RentalCustomerPayment`, `RentalOwnerPayment`, and `RentalAgreementAdvance`.
2. **`Modules\Payment\Models\PaymentAllocation`**:
   - Added explicit `public function invoice(): BelongsTo` relationship pointing to `Modules\Invoice\Models\Invoice`, satisfying Eloquent eager loading across payments and receipts.
3. **`Modules\Payment\Services\PaymentReceiptService`**:
   - Created dedicated receipt data compilation service:
     - Formats title dynamically (`OFFICIAL PAYMENT RECEIPT` for inbound, `DISBURSEMENT VOUCHER` for outbound).
     - Resolves party names, phone numbers, and codes (from snapshot or direct party fallback).
     - Converts payment totals to English words via `AmountInWordsService`.
     - Maps tender instrument lines (cash, cheque number & date, bank transfer, card brand).
     - Summarizes invoice allocations with before and after balance tracking.
4. **`Modules\Payment\Http\Controllers\PaymentController` & Routes**:
   - Added `receipt()` and `exportReceipt()` methods rendering `payment.receipt`.
   - Registered endpoints: `GET /api/v1/payments/{payment}/receipt` and `GET /api/v1/payments/{payment}/receipt/export/{format}`.
5. **`resources/views/payment/receipt.blade.php`**:
   - Created high-fidelity Blade template supporting browser print (`window.print()`) and PDF export with clean borders, party details, itemized method lines, and allocation breakdown.
6. **`Modules\VehicleRental\Services\RentalPaymentService`**:
   - Created central rental payment orchestrator:
     - `recordCustomerReceipt`: Validates lines, ensures allocations do not exceed invoice balances, auto-approves/posts draft invoices, creates and posts `Payment`, attaches `PaymentAllocation` records, and preserves unapplied surplus as active advance credit.
     - `recordOwnerDisbursement`: Disburses outbound payments against owner settlement bills or credit notes, ensuring supplier AP consistency.
     - `allocateUnappliedFunds`: Allows applying existing agreement advance balances against open invoices without new cash flow.
     - `reversePayment`: Reverses posted payments via `PaymentReversalService`, restores invoice open balances, and registers GL reversals.
     - `listAgreementPayments`: Queries both direct agreement payments and payments allocated to linked billing cycle invoices.
7. **`Modules\VehicleRental\Http\Controllers\RentalPaymentController` & `RentalPaymentRequest`**:
   - Implemented tenant-scoped controller with strict input validation using `BuildsPaymentLines` for multi-tender lines.
   - Registered routes under `/api/v1/vehicle-rental/{kind}/agreements/{agreement}/payments`:
     - `GET /` (list)
     - `POST /` (record payment/disbursement)
     - `POST /{payment}/allocate` (allocate advance)
     - `POST /{payment}/reverse` (reverse)
8. **`AgreementFinancialSummaryService`**:
   - Injected `RentalPaymentService` to embed `'payments'` directly in the financial summary API response.

### Frontend
1. **`resources/js/modules/vehicle-rental/agreementApi.ts`**:
   - Added interfaces `AgreementPayment`, `AgreementPaymentMethod`, `AgreementPaymentAllocation`, `RecordRentalPaymentPayload`, `AllocateRentalPaymentPayload`, `ReverseRentalPaymentPayload`.
   - Added API client functions `listAgreementPayments`, `recordAgreementPayment`, `allocateAgreementPayment`, `reverseAgreementPayment`, `getPaymentReceiptPrintUrl`.
2. **`resources/js/modules/vehicle-rental/AgreementPaymentModal.tsx`**:
   - Interactive modal supporting:
     - Recording customer receipts or owner disbursements.
     - Multi-invoice table with "Pay Full", "Pay All", and individual allocation inputs.
     - Multi-tender instruments via `PaymentLineTable` (Cash with change calculation, Bank Transfer, Cheque, Card).
     - Live advance credit calculation when tender amount > allocated total.
     - Post-submission receipt printing prompt (`🖨️ Print Receipt / Voucher`).
     - "Allocate Advance" mode when allocating existing unapplied balances.
3. **`resources/js/modules/vehicle-rental/AgreementFinancialSummaryPanel.tsx`**:
   - Added top actions: `+ Receive Payment / Advance` (customer) and `+ Disburse Payment` (owner).
   - Added "Actions" column in Documents Table with quick `Receive Payment` / `Pay Bill` buttons.
   - Added comprehensive **Recorded Payments & Receipts** audit table showing Payment #, Date, Type, Method & Reference, Status, Total, Allocated, and Unapplied Advance amounts, with buttons for "Allocate", "Receipt", and "Reverse".
   - Integrated confirmation dialog for payment reversals.
4. **`resources/js/modules/invoice/pages/InvoiceDetailPage.tsx`**:
   - Updated `freshRentalSourceTypes` to include `vehicle_rental_billing_cycle`, `vehicle_rental_billing_cycle_supplementary`, `vehicle_rental_billing_cycle_consolidated`, `vehicle_rental_billing_cycle_credit_note`, and `vehicle_rental_owner_settlement`.

---

## 3. Automated Tests & Verification
1. **`Modules\Payment\Tests\PaymentReceiptTest`**:
   - Verified compilation of inbound customer receipt voucher and outbound supplier disbursement voucher.
   - Verified Blade HTML view generation, amount-in-words, and party resolution.
2. **`Modules\VehicleRental\Tests\RentalPaymentTest`**:
   - `test_record_customer_payment_against_invoice`: Verified full settlement, invoice balance reduction, and posting.
   - `test_multi_invoice_allocation_and_advance_overpayment`: Verified multi-invoice split, surplus advance credit tracking, and subsequent unapplied allocation.
   - `test_owner_disbursement_against_bill`: Verified outbound disbursement against owner bills.
   - `test_payment_reversal_restores_invoice_balance`: Verified payment reversal and invoice balance restoration.
   - `test_payments_embedded_in_financial_summary_and_api`: Verified payments array in financial summary response.
3. **Full Regression Test Suites**:
   - `php artisan test app/Modules/VehicleRental/Tests`: 269 passed (2,220 assertions).
   - `php artisan test app/Modules/Payment/Tests`: 40 passed (191 assertions).
   - `npm run typecheck`: 0 errors.
   - `npx vitest run resources/js/modules/vehicle-rental`: 32 test files passed, 144 tests passed.
   - `npm run build`: Production assets compiled successfully in 8.36s.
