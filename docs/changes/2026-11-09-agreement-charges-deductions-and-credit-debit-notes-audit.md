# Agreement Additional Charges, Deductions, and Credit/Debit Notes Comprehensive Audit & Implementation

**Date:** 2026-11-09  
**Module:** `VehicleRental` (with integration into `Invoice` and `Payment`)  
**Scope:** Agreement Financial Adjustments, Customer Expense Recoveries, Owner Settlement Deductions, Financial Position Summary, and Credit/Debit Note Document Workflows.

---

## 1. Audit Scope & Summary of Issues Identified

An end-to-end audit and cleanup of additional charges, deductions, Credit Notes, Debit Notes, and expense allocations was conducted across the `VehicleRental`, `Invoice`, and `Payment` modules:

1. **Owner Disbursement Report Deduction Leak**:
   - `OwnerDisbursementReportService.php` previously aggregated deductions using `RentalExpense::where('supplier_id', $agreement->supplier_id)`.
   - **Root Cause & Impact:** This aggregated ALL supplier expenses across all vehicles owned by that supplier, causing deduction multiplication across multiple agreements. Furthermore, it completely missed 3rd-party vendor maintenance/fuel expenses (e.g., fuel from IOC or workshop repairs billed by an external vendor) that were specifically allocated as deductions against this owner's agreement.
   - **Fix:** Switched to direct aggregation of `RentalExpenseAllocation` targeted at `$agreement->id` with type `OwnerDeduction`, combined with direct unallocated expenses matching both vehicle and supplier.

2. **Vehicle Profitability Direct Cost & Revenue Distortion**:
   - In `VehicleProfitabilityReportService.php`, owner payout was calculated as base rent + usage rent without deducting owner expense chargebacks (`OwnerDeduction` allocations), leading to double-counting and overstated vehicle costs.
   - Additionally, billable customer expense recoveries (`CustomerRecovery` allocations) were counted as costs in vehicle profitability without crediting customer revenue, artificially depressing margin calculations.
   - **Fix:** Adjusted owner payout to net of owner deductions: `max(0.0, ($ownerBase + $ownerUsage) - $ownerDeductions)`. Credited confirmed customer recovery allocations to customer revenue so recovered fleet expenses balance with 100% accuracy.

3. **Lessee Outstanding Report Source Attribution Inconsistency**:
   - `LesseeOutstandingReportService.php` queried `invoice_sources.source_type` using hardcoded values (`['base_rent', 'excess_mileage', ...]`), which did not match the enum backed values (`BaseChargeSource::Customer->value`, `UsageChargeSource::Customer->value`, and `vehicle_rental_customer_recovery`).
   - **Root Cause & Impact:** Customers with multiple agreements had their outstanding balances lumped together or misattributed to the wrong agreement reference.
   - **Fix:** Extended the subquery to include `BaseChargeSource::Customer->value`, `UsageChargeSource::Customer->value`, and `'vehicle_rental_customer_recovery'`.

4. **Rental Revenue Report Missing Allocated Fleet Recoveries**:
   - In `RentalRevenueReportService.php`, recovered revenue aggregated only direct toll expenses with `customer_agreement_id` set, omitting `RentalExpenseAllocation` records of type `CustomerRecovery`.
   - **Fix:** Included `RentalExpenseAllocation` targeting customer agreements alongside direct unallocated tolls to capture all billable recoveries.

5. **Customer Recovery Billing & Invoice Unlinking Lifecycle**:
   - Previously, confirmed `CustomerRecovery` expense allocations had no mechanism to bill the customer or generate an outbound `Invoice` / `Debit Note`.
   - Furthermore, allocations could not handle invoice voiding/cancellation—if an invoice was cancelled in the `Invoice` module, the allocation remained stuck with an inactive invoice reference.
   - **Fix:** Added `billAllocation()` to `RentalExpenseService.php` to issue outbound sales invoices or Debit Notes (`InvoiceType::Debit` / `InvoiceType::Sales`) for customer recoveries, setting `invoice_id` on the allocation. Added `unlinkCancelledInvoice()` to cleanly unlink the allocation if the linked invoice is cancelled or voided.

6. **Missing Agreement Financial Adjustments (Credit & Debit Notes)**:
   - Agreements lacked a documented, audited pathway to issue authorized financial adjustments (Credit Notes for downtime rebates/goodwill allowances, and Debit Notes for damages/recharges) directly against an agreement.
   - **Fix:** Implemented `RentalChargeDocuments::issueAdjustment()` creating properly scoped `Invoice` records (`InvoiceType::Credit` or `InvoiceType::Debit`) with unique source line IDs and audit trails, without conflicting with single-source quantity constraints.

7. **Missing Agreement Financial Position Overview**:
   - Operators had to inspect disparate panels across billing, usage, deposits, and accounting to understand the customer/owner's true net financial standing.
   - **Fix:** Created `AgreementFinancialSummaryService.php` providing complete financial metrics (Total Invoiced, Total Paid, Total Credit Notes, Total Debit Notes, Balance Due, Unallocated Credit, and Security Deposit standing).

8. **UI/UX Ergonomics & Controlled Relational Selection**:
   - In `AgreementsPage.tsx`, added the "Financial Position & Notes" tab embedding `AgreementFinancialSummaryPanel.tsx`.
   - In `RentalExpensesPage.tsx`, added actions to bill customer recoveries directly with interactive date, rate, and Debit Note options, and unlink cancelled invoices.
   - Designed human-readable presentation with status badges, currency formatting, and zero exposed raw IDs.

---

## 2. Changes Implemented

### Backend (`app/Modules/VehicleRental/`)
1. **`Services/OwnerDisbursementReportService.php`**:
   - Aggregated owner deductions using `RentalExpenseAllocation` targeted directly to the agreement (`allocation_type = 'owner_deduction'`), plus direct unallocated matching expenses.
2. **`Services/VehicleProfitabilityReportService.php`**:
   - Factored owner deductions into owner payout (`max(0.0, ($ownerBase + $ownerUsage) - $ownerDeductions)`).
   - Credited customer recovery allocations into customer rental revenue.
3. **`Services/LesseeOutstandingReportService.php`**:
   - Added `BaseChargeSource::Customer->value`, `UsageChargeSource::Customer->value`, and `'vehicle_rental_customer_recovery'` to invoice source queries.
4. **`Services/RentalRevenueReportService.php`**:
   - Added customer recovery allocations to revenue aggregation.
5. **`Services/RentalExpenseService.php`**:
   - Injected `TaxedSourceInvoiceFactory` and `InvoiceCreationService`.
   - Added `billAllocation(RentalExpenseAllocation $allocation, AgreementContext $context, array $input): Invoice`.
   - Added `unlinkCancelledInvoice(RentalExpenseAllocation $allocation, AgreementContext $context): RentalExpenseAllocation`.
6. **`Http/Requests/RentalExpenseBillAllocationRequest.php`**:
   - Created tenant-scoped validation request for billing allocations.
7. **`Http/Controllers/RentalExpenseController.php` & `Routes/api.php`**:
   - Added endpoints `POST expenses/allocations/{allocation}/bill` and `POST expenses/allocations/{allocation}/unlink-invoice`.
8. **`Services/RentalChargeDocuments.php`**:
   - Added `issueAdjustment(AgreementKind $kind, AgreementContext $context, Agreement $agreement, string $adjustmentType, string $amount, string $reason, array $data): Invoice`.
   - Added `adjustmentInput(array $input): array`.
9. **`Services/AgreementFinancialSummaryService.php`**:
   - Aggregates base charges, usage charges, expense allocations, linked financial invoices/notes, and security deposits.
10. **`Http/Controllers/AgreementController.php` & `Routes/api.php`**:
    - Added `GET {kind}/agreements/{agreement}/financial-summary` and `POST {kind}/agreements/{agreement}/financial-adjustments`.
11. **`Tests/RentalExpenseTest.php` & `Tests/AgreementFinancialAdjustmentsTest.php`**:
    - Added comprehensive automated test coverage for billing allocations, unlinking cancelled invoices, financial summary aggregation, and API endpoints.

### Frontend (`resources/js/modules/vehicle-rental/`)
1. **`rentalExpenseApi.ts`**:
   - Extended `ExpenseAllocation` interface with `row_version`, `status`, `invoice_id`, and `invoice` (`id`, `invoice_number`, `status`).
   - Added `BillAllocationPayload`, `billRentalExpenseAllocation()`, and `unlinkRentalExpenseAllocationInvoice()`.
2. **`RentalExpensesPage.tsx`**:
   - Added "Billing Status" column to the allocations table with invoice link/badges.
   - Added "Bill Customer" interactive modal to bill customer recovery allocations.
   - Added "Unlink" action when a linked invoice is cancelled/void.
3. **`agreementApi.ts`**:
   - Added `AgreementFinancialSummary` interface, `FinancialAdjustmentPayload` interface, `getAgreementFinancialSummary()`, and `createAgreementFinancialAdjustment()`.
4. **`AgreementFinancialSummaryPanel.tsx`**:
   - Created financial summary dashboard displaying key metrics cards (Total Invoiced, Payments/Paid, Credit Notes, Debit Notes, Balance Due, Unallocated Credit), security deposit status, linked financial documents table, allocated expenses table, and interactive "+ Issue Credit / Debit Note" form.
5. **`AgreementsPage.tsx`**:
   - Added "Financial Position & Notes" review tab rendering `AgreementFinancialSummaryPanel`.
6. **`AgreementFinancialSummaryPanel.test.tsx` & `RentalExpensesPage.test.tsx`**:
   - Added unit and interaction tests for panel rendering, adjustment creation, permission guards, and allocation billing.

---

## 3. Verification & Testing

1. **Backend Tests**:
   - Ran `php artisan test app/Modules/VehicleRental/Tests/RentalExpenseTest.php` -> 8 passed (52 assertions).
   - Ran `php artisan test app/Modules/VehicleRental/Tests/AgreementFinancialAdjustmentsTest.php` -> 3 passed (21 assertions).
   - Ran full module test suite `php artisan test app/Modules/VehicleRental/Tests` -> 225 passed (1913 assertions).
2. **Frontend Tests & Typechecks**:
   - Ran `npx vitest run resources/js/modules/vehicle-rental` -> 29 test files passed (128 passed tests).
   - Ran `npm run typecheck` -> 0 errors.
   - Ran `npm run lint` -> 0 errors, 0 warnings.
