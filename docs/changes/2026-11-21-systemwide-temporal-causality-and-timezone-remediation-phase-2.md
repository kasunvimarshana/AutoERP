# Systemwide Temporal Causality and Timezone Remediation (Phase 2)

**Date:** 2026-11-21  
**Modules:** Inventory, Invoice, Payment, Expense, Purchase, Reporting, Vehicle Rental

### Summary of Changes

1. **Frontend Date Formatting & Timezone Fixes:**
   - In `resources/js/shared/utils/formatDate.ts`: Refactored `formatDate` to delegate to `formatBusinessDate` for civil date strings (`YYYY-MM-DD`), preventing ECMAScript UTC-to-local midnight date shifting in off-UTC browser environments, while preserving existing behavior for full ISO timestamps and invalid dates.
   - In `resources/js/shared/components/ReversalDialog.tsx`: Replaced local date generation with `businessDateInputValue()` so default document reversal dates reflect the workspace business calendar.
   - In reporting screens (`StockCardPage.tsx`, `PaymentMethodWiseSummaryReportPage.tsx`, `SummaryReportPage.tsx`, `ExpenseReportPage.tsx`, `VehicleServiceSalesSummaryReportPage.tsx`): Updated month/week date range initializers to resolve from `businessDateInputValue()` and civil calendar parsing instead of raw browser-local `new Date()`.

2. **Backend Dynamic Timezone Resolution:**
   - In `app/Modules/Reporting/Services/ReportCatalog.php`: In `InventoryValuationLayer` aging (`ageDaysColumn`), dynamically resolved the tenant workspace timezone via `ConfigurationKey::WORKSPACE_TIMEZONE` using `ConfigurationResolverInterface` instead of hardcoding `now('UTC')`.
   - In `app/Modules/Inventory/Services/StockCardService.php`: Injected `ConfigurationResolverInterface` and resolved `ConfigurationKey::WORKSPACE_TIMEZONE` for the tenant before falling back to `config('app.timezone')`.
   - In `app/Modules/Reporting/Services/CustomerOutstandingAsOfBalances.php`: Replaced `now()->toDateString()` with `$request->reportToday()` in historical coverage assertions to ensure workspace calendar boundaries are respected.
   - In `app/Modules/VehicleRental/Services/AgreementService.php` and `app/Modules/VehicleRental/Services/BaseRentPreview.php`: Replaced hardcoded `'UTC'` calendar logic with the dynamic calendar timezone resolved from the tenant context (`$this->calendar->timezone($context)`).

3. **Temporal Causality & Validations:**
   - In `app/Modules/Purchase/Http/Requests/StoreGoodsReceiptNoteRequest.php`: Added `'after_or_equal:lines.*.batch_allocations.*.manufacture_date'` validation rule to `'lines.*.batch_allocations.*.expiry_date'`.
   - In `app/Modules/Inventory/Services/BatchTrackingService.php`: Enforced that in `create()`, if both `manufacture_date` and `expiry_date` are present, `expiry_date` cannot precede `manufacture_date`.
   - In `app/Modules/Invoice/Services/InvoiceReversalService.php`: Enforced that `$reversalDate` cannot precede `$invoice->invoice_date->toDateString()` in addition to `$postingDate`.
   - In `app/Modules/Payment/Services/PaymentAllocationReversalService.php`: Enforced that `$reversalDate` cannot precede `$payment->payment_date->toDateString()`.
   - In `app/Modules/Expense/Services/ExpensePostingService.php`: Injected `ConfigurationResolverInterface` and enforced that `expense_date` cannot be in the future relative to the workspace today.
   - In `app/Modules/Invoice/Http/Requests/StoreInvoiceRequest.php` and `app/Modules/Payment/Http/Requests/StorePaymentRequest.php` (as well as `BuildsPaymentAllocations`): Updated raw `'date'` rules to strict `'date_format:Y-m-d'`.

4. **Tests & Verification:**
   - Added unit and feature tests covering all new constraints and dynamic timezone resolutions:
     * `InventoryBatchManagementTest`: Rejection of batch expiry before manufacture date.
     * `PurchaseEngineTest`: Rejection of batch allocation expiry before manufacture date on goods receipt note requests.
     * `ManualInvoiceServiceTest`: Rejection of invoice reversal before invoice date, and rejection of non-`Y-m-d` date formats on store invoice requests.
     * `PaymentAllocationReversalServiceTest`: Rejection of allocation reversal before payment date.
     * `StorePaymentRequestIdempotencyTest`: Rejection of non-`Y-m-d` date formats on store payment requests.
     * `ExpensePostingTest`: Rejection of future expense date relative to workspace today.
     * `StockCardTest`: Dynamic workspace timezone resolution when omitted in request filters.
     * `InventoryAgingReportTest`: Dynamic age days calculation under non-UTC workspace timezone.
     * `formatDate.test.ts`: Added frontend tests verifying civil date formatting without timezone shifts.

### Verification Record

- **Backend Test Suite:** 658 tests across all modified modules passed (5,179 assertions), 0 failures.
- **Frontend Test Suite:** 167 test files passed (683 tests), 0 failures.
- **TypeScript Typecheck:** `npm run typecheck` passed with 0 errors.
- **PHP Code Style:** Verified with `vendor/bin/pint --test` on all modified files, 100% passed.
