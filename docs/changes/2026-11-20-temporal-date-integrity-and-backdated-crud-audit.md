# Systemwide Temporal Causality and Backdated CRUD Audit

**Date:** 2026-11-20  
**Modules:** Invoice, Payment, Expense, Purchase, Vehicle Rental, Accounting & Billing

### Summary of Changes

1. **Core Invoice Domain Date Integrity (`InvoiceValidationService`):**
   - In `InvoiceValidationService::validateHeader`, enforced server-side validation that whenever an invoice carries an explicit `due_date`, the due date cannot be earlier than `invoice_date`. This guarantees across all internal callers (e.g. fast sale, vehicle service quick completion, rental billing cycles, owner settlement bills) that invalid due dates cannot be persisted.
   - Enforced that when both `supply_period_start` and `supply_period_end` are specified, the end date cannot precede the start date.

2. **Vehicle Rental Billing Cycle Date Validation (`BillingCycleRequest`, `TwoStageInvoicingService`):**
   - In `BillingCycleRequest`, added `'after_or_equal:invoice_date'` validation rule to `'due_date'` on billing cycle finalization and generation requests.
   - In `TwoStageInvoicingService`, implemented centralized `resolveInvoiceAndDueDate` helper enforcing `due_date >= invoice_date` across all 4 invoice-issuing methods (`issueStage1Invoice`, `issueStage2SupplementaryInvoice`, `issueStage2ConsolidatedInvoice`, `issueAdjustmentInvoice`).

3. **Payment Reversal Chronological Integrity (`PaymentReversalService`):**
   - In `PaymentReversalService::reverse`, enforced that `reversalDate` cannot be earlier than the payment's `payment_date`. Reversing a payment before it was made violates ledger chronology and distorts cash account histories.

4. **Expense Reversal Chronological Integrity (`ExpenseReversalService`):**
   - In `ExpenseReversalService::reverse`, enforced that `reversalDate` cannot be earlier than the expense's `expense_date`, preventing premature expense reversal journals.

5. **Purchase Procurement & Return Temporal Causality (`GoodsReceiptNoteService`, `PurchaseReturnService`, `PurchaseDebitNoteService`):**
   - In `GoodsReceiptNoteService::create`, verified that when creating a goods receipt against a purchase order, `receivedDate` cannot precede the purchase order's `purchase_order_date`.
   - In `PurchaseReturnService::resolveReferencedSource`, verified that when creating a purchase return referencing a goods receipt note, `returnDate` cannot precede the goods receipt's `received_date`.
   - In `PurchaseDebitNoteService::assertSourceContract`, verified that when creating a debit note for a purchase return, `debitNoteDate` cannot precede the purchase return's `return_date`.

6. **Regression Tests Added:**
   - `InvoiceCalculationServiceTest`: Added tests rejecting due date before invoice date and supply period end before start.
   - `TwoStageInvoicingTest`: Added test verifying billing cycle creation and finalization reject due date before invoice date.
   - `VehicleRentalCrudAuditAndEdgeCasesTest`: Added HTTP endpoint test for `/billing-cycles/{id}/finalize` rejecting invalid due dates.
   - `HistoricalPaymentReversalTest`: Added test verifying payment reversal rejects reversal date preceding payment date.
   - `ExpensePostingTest`: Added test verifying expense reversal rejects reversal date preceding expense date.
   - `PurchaseEngineTest`: Added tests ensuring goods receipt rejects date before order, purchase return rejects date before receipt, and purchase debit note rejects date before return.

### Verification Record

- **PHPUnit Backend Suite:** Executed full test suite (`php artisan test`) with 1221 tests passing (12,836 assertions), 0 failures.
- **PHP Code Style:** Verified with `vendor\bin\pint --test` on all modified files, 100% passed.
- **Frontend Typecheck:** Executed `npm run typecheck` (`tsc --noEmit`), passing with 0 errors.
