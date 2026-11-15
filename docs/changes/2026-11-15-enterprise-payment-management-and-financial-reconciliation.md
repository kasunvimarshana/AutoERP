# Enterprise Payment Management & Financial Reconciliation Architecture

**Date:** 2026-11-15  
**Scope:** `VehicleRental`, `Payment`, `Invoice`  
**Focus:** Mode B reallocation bounding with surplus credit retention, credit note balance tracking and over-allocation prevention, incremental top-up payment allocations, itemized owner settlement AP bills with statutory WHT (10%), customer/owner payment refunds, and real-time net account balance reconciliation.

---

## 1. Problem Statement & Architecture Objectives

Following the implementation of the Vehicle Rental payment workflow and receipt engine, five critical architectural and financial integrity requirements remained:
1. **Mode B Consolidated Invoicing Reallocation**:
   When finalizing a two-stage billing cycle in Mode B (Consolidated Revision), provisional invoices are credited/voided and their payment allocations are transferred to the final consolidated invoice. If the provisional invoice had multiple payments or if deductions caused the final invoice total to be lower than the payments already made, the previous logic either looped unconstrained or threw balance errors without preserving surplus payment amounts as unapplied advance credits.
2. **Credit Note Balance Tracking & Over-Allocation Prevention**:
   Allocating credit from a Credit Note (`InvoiceType::Credit`) against open debit invoices decreased the open balance of the debit invoice but failed to lock and decrement the available `remaining_amount` on the Credit Note itself. This permitted double-allocation or infinite over-allocation of credit notes without transitioning their lifecycle status to `Paid` or `PartiallyPaid`.
3. **Incremental Top-Up Allocations**:
   When a payment had an active allocation on an invoice and the operator attempted to apply additional unapplied funds to the same invoice, `PaymentAllocationService` rejected the write with `Payment is already allocated to this invoice.` Users were forced to reverse and re-allocate from scratch rather than performing an incremental top-up.
4. **Owner Settlement AP Bill Itemization & Statutory Withholding Tax (WHT)**:
   In `OwnerSettlementBillingService`, statutory WHT (10%) and maintenance deductions were collapsed into a single discount adjustment on Line 1. This violated statutory accounting standards requiring gross rent presentation, explicit itemized withholding tax lines, and itemized fleet maintenance recovery lines while keeping the net bill payable matching `$netPayable`.
5. **Customer & Owner Refund Engine**:
   Agreements with active advance overpayments or unapplied credits had no direct refund mechanism. Operators had no endpoint or UI modal to issue an authentic refund transaction to return unapplied credits to customers or owners.

---

## 2. Key Changes & Implementation Details

### A. Mode B Reallocation Bounding & Multi-Payment Loop
- **File:** `app/Modules/VehicleRental/Services/TwoStageInvoicingService.php` (`voidInitialInvoiceAndReallocate()`)
- Dynamically loads and calculates `$remainingToAllocate = (string) ($finalInvoice->balance?->remaining_amount ?? $finalInvoice->balance_due);`.
- Iterates over all active allocations on the provisional invoice:
  - Reverses each allocation back to its source payment via `PaymentAllocationReversalService::reverse()`.
  - Determines allocation date respecting `$finalInvoice->invoice_date` and `$payment->payment_date`.
  - If `$remainingToAllocate > 0`, allocates `min($alloc->allocated_amount, $remainingToAllocate)` to `$finalInvoice`.
  - Decrements `$remainingToAllocate` by the allocated amount.
  - Any surplus amount from the payments automatically remains on the payment as active unapplied advance credit (`unapplied_amount`).
  - Updates `$cycle->total_paid_amount` with the actual transferred payment total.

### B. Credit Note Balance Tracking & Over-Allocation Prevention
- **File:** `app/Modules/Invoice/Services/InvoiceBalanceUpdater.php` (`allocateCredit()`)
- When allocating credit from a Credit Note (`$creditSourceType === 'credit_note'` or `'invoice'`):
  - Queries and locks the Credit Note invoice: `Invoice::query()->where('tenant_id', $lockedInvoice->tenant_id)->lockForUpdate()->find($creditSourceId)`.
  - When verified as `InvoiceType::Credit`:
    - Locks its `InvoiceBalance` record.
    - Asserts requested allocation `$amount <= $creditBalance->remaining_amount`, throwing `InvalidArgumentException` on over-allocation attempts.
    - Increments `credit_allocated_amount` by `$amount`.
    - Invokes `syncBalance()`, updating `remaining_amount` and transitioning status to `InvoiceStatus::Paid` (when remaining is 0) or `InvoiceStatus::PartiallyPaid`.
- Updated `AgreementFinancialSummaryService` to correctly reflect unallocated credit note balance.

### C. Incremental Top-Up Allocations & Reversal Conservation
- **Files:**
  - `app/Modules/Payment/Services/PaymentAllocationService.php` (`allocateOne()`)
  - `app/Modules/Payment/Services/PaymentAllocationFinanceService.php` (`postIncremental()`, `reverse()`)
- When an active `PaymentAllocation` exists for the requested `(payment_id, invoice_id)` pair:
  - Validates that the requested incremental amount does not exceed available unapplied funds or the invoice's remaining balance.
  - Rejects pending allocations explicitly before mutation to preserve clean boundaries.
  - Increments `$existingAllocation->allocated_amount` by `$allocatedAmount`.
  - Updates `$existingAllocation->invoice_balance_after`.
  - Adjusts the payment's `unapplied_amount` and `allocated_amount`.
  - Settles the invoice balance via `InvoiceSettlementService::applyPaymentAllocation()`.
  - Posts an incremental GL journal voucher via `PaymentAllocationFinanceService::postIncremental()` with an idempotent top-up sequence index (`payment_allocation_topup_{index}`).
- **Finance Reversal Integrity**:
  - In `PaymentAllocationFinanceService::reverse()`, systematically iterates through `top_up_posting_references` and `fx_top_up_posting_references`, executing `reverseSource()` for each incremental top-up and FX entry. This ensures that reversing an allocation that had incremental top-ups completely balances the General Ledger with sub-ledgers.

### D. Itemized Owner Settlement AP Bill Lines & Statutory Withholding Tax (WHT)
- **File:** `app/Modules/VehicleRental/Services/OwnerSettlementBillingService.php`
- Replaced collapsed discount adjustments with full multi-line itemization:
  - **Line 1:** Gross Vehicle Lease Rent (`LineType::Charge`, `unitPrice: $grossPayable`, `lineTotal: $grossPayable`).
  - **Line 2 (if WHT > 0):** Statutory Withholding Tax Deduction (`AdjustmentType::Withholding`, amount: `$whtAmount`, explicit description displaying deduction amount).
  - **Line 3 (if maintenance > 0):** Fleet Maintenance Recovery (`AdjustmentType::Discount`, amount: `$maintenanceDeductions`, explicit description displaying deduction amount).
- Configured explicit double-entry `InvoicePostingPlanData`:
  - Debits `RentalExpense` with `$grossPayable`.
  - Credits `WithholdingPayable` with `$whtAmount`.
  - Credits `RentalExpense` (recovery) with `$maintenanceDeductions`.
  - Credits `Payable` with `$netPayable`.
- Net AP Bill balance due matches `$netPayable` exactly.

### E. Customer & Owner Refund Engine & Optimistic Locking
- **Backend:**
  - `app/Modules/VehicleRental/Services/RentalPaymentService.php`:
    - Added `refundPayment()` strictly authorizing against `PaymentPermission::PAYMENTS_REFUND` (closing privilege escalation risks), validating refund amount against unapplied balance and delegating to `PaymentRefundWorkflowService::refund()`. Auto-submits, approves, and posts the resulting outbound/inbound refund payment and ledger entry.
    - Updated `listAgreementPayments()` to include linked refund payments (`source_type = payment_refund`, `original_payment_id = payment.id`) and expose `row_version` for client concurrency checking.
  - `app/Modules/VehicleRental/Http/Controllers/RentalPaymentController.php`: Added `refund()` action with tenant-scoped validation and optimistic version tracking.
  - `app/Modules/VehicleRental/Routes/api.php`: Registered `POST {kind}/agreements/{agreement}/payments/{payment}/refund`.
  - `app/Modules/VehicleRental/Services/AgreementFinancialSummaryService.php`: Excluded credit notes from `total_credit_allocated` to eliminate credit double-counting, accurately aggregated unapplied advance funds across posted payments, and computed `net_account_balance`.
- **Frontend:**
  - `resources/js/modules/vehicle-rental/agreementApi.ts`: Added `RefundRentalPaymentPayload` interface, `refundAgreementPayment()`, exposed `row_version` on `AgreementPayment`, and updated `AgreementFinancialSummary['summary']`.
  - `resources/js/modules/vehicle-rental/AgreementFinancialSummaryPanel.tsx`:
    - Added 7th key metric card: `Net Account Standing` (displaying open receivables minus unapplied advance credits and credit notes).
    - Enabled `Refund` action on posted payments with active `unapplied_amount > 0` across all valid agreement statuses, including closed agreements (permitting deposit/advance returns upon agreement closure).
    - Added controlled `Payment Refund Modal` with amount validation against unapplied credit, asynchronous disbursement method selector with auto-selection, refund date, reason, and optimistic concurrency version tracking.

---

## 3. Verification Record

- **Mode B Multi-Payment Bounded Reallocation:**
  - `php artisan test --filter=test_two_stage_invoicing_mode_b_reallocates_multi_payments_bounded_with_surplus_retention` -> PASSED (11 assertions).
  - `php artisan test --filter=test_two_stage_invoicing_mode_b_reallocates_existing_payments` -> PASSED (14 assertions).
- **Credit Note Balance Tracking & Over-Allocation Prevention:**
  - `php artisan test --filter=test_credit_note_allocation_tracks_remaining_balance_and_prevents_overallocation` -> PASSED (12 assertions).
  - `php artisan test app/Modules/VehicleRental/Tests/AgreementFinancialAdjustmentsTest.php` -> 5 passed (35 assertions, including zero unallocated credit verification).
  - `php artisan test app/Modules/Invoice/Tests/InvoiceBalancePrecisionTest.php` -> PASSED (4 assertions).
- **Incremental Top-Up Allocations & Full GL Reversal:**
  - `php artisan test app/Modules/Payment/Tests/PaymentAllocationReversalServiceTest.php` -> 2 passed (28 assertions, verifying top-up allocation and subsequent reversal of base and top-up Finance journals).
  - `php artisan test app/Modules/Payment/Tests` -> 41 passed (208 assertions).
- **Owner Settlement AP Bill Itemization:**
  - `php artisan test app/Modules/VehicleRental/Tests/OwnerSettlementBillingTest.php` -> 3 passed (17 assertions).
- **Customer & Owner Refund Engine:**
  - `php artisan test --filter=test_refund_customer_advance_payment` -> PASSED.
  - `php artisan test --filter=test_refund_customer_payment_via_api_and_validation` -> PASSED.
  - `php artisan test --filter=test_refund_requires_payments_refund_permission` -> PASSED (verifying 403 when lacking `payments.refund`).
  - `php artisan test --filter=test_refund_owner_disbursement` -> PASSED (verifying inbound owner refund auto-approval and posting).
  - `php artisan test app/Modules/VehicleRental/Tests/RentalPaymentTest.php` -> 9 passed (67 assertions).
- **Frontend & TypeScript:**
  - `npm run typecheck` -> PASSED (0 errors).
  - `npx vitest run resources/js/modules/vehicle-rental/AgreementFinancialSummaryPanel.test.tsx` -> 5 passed (5).
  - `npx vitest run resources/js/modules/vehicle-rental` -> 32 test files passed (146 passed).
  - `npm run build` -> PASSED (Production Vite build succeeded in 8.12s).
