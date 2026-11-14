# Vehicle Rental Module: Comprehensive Financial Workflow, Attribution, and Audit Completion

**Date:** 2026-11-15  
**Scope:** `app/Modules/VehicleRental/`, `resources/js/modules/vehicle-rental/`

## Context & Objectives
An end-to-end audit and implementation of 23 critical items identified across the Vehicle Rental module was conducted. The objectives included resolving financial inconsistencies in two-stage invoicing, enabling robust physical custody base-rent proration, ensuring proper accounting reconciliation with credit/debit notes and security deposit allocations, hardening driver and vehicle reassignment boundaries, and ensuring reporting accuracy across fleet profitability and owner disbursements.

## Changes Made

### 1. Two-Stage Invoicing & Reconciliation Hardening (`TwoStageInvoicingService.php`)
- **Fix Undefined `$chartIds`:** Replaced `$chartIds` evaluation with `$cycle->charts->isNotEmpty()` so `has_running_charts` accurately indicates chart presence.
- **Mode A Free Base Rent Resolution:** When `initial_invoice_id` is null (`issue_initial_invoice = false`), Stage 2 bills the full `net_subtotal` instead of incorrectly subtracting `base_rent_amount`.
- **Mode A Negative Delta (Downtime / Rebates):** If `$deltaAmount < 0` in Mode A, generate an authentic Credit Note `Invoice` for `abs($deltaAmount)`.
- **Mode B Double Billing & Reallocation Resolution:** Transition Stage 1 posted invoice balances using authentic Outbound Credit Note `Invoice` creation and `InvoiceBalanceUpdater::allocateCredit()`. Ensure consolidated invoice auto-approval and posting with guard checks so transitions do not attempt invalid `Posted -> Approved` operations.
- **Physical Custody Base Rent Proration:** Derived custody days strictly from `VehicleUse` handover and return boundaries (`handover_at`, `returned_at`), avoiding trip-log timestamp misattribution. Proportional excess KM and toll charges are attributed across vehicles so the sum of vehicle attributions reconciles with `cycle->net_subtotal`.
- **Tax & Total Amount Synchronization:** Synchronized `tax_amount` and `total_billed_amount` on `BillingCycle` directly from generated invoices.
- **Cycle Overlap Prevention:** Added database validation to prevent overlapping billing cycles for the same customer or owner agreement.
- **Attachment Filtering:** Disallowed attaching `Draft` or `Reversed` running charts to billing cycles, enforcing `RunningChartStatus::Finalized`.

### 2. Ad-hoc Item Categories & Form Validation (`BillingCycleItemCategory.php`, `BillingCycleRequest.php`, `BillingCycleWizardModal.tsx`)
- Added missing categories to `BillingCycleItemCategory` enum (`excess_km`, `overtime`, `night_out`, `toll`, `fuel`, `downtime_rebate`, `deduction`, `damage_recharge`, `goodwill_discount`).
- Enforced `Rule::enum(BillingCycleItemCategory::class)` in `BillingCycleRequest`.
- Aligned category selection dropdown options in `BillingCycleWizardModal.tsx`.
- Required explicit `expected_version` integer in `BillingCycleRequest`.

### 3. Financial Summary & Target Adjustments (`AgreementFinancialSummaryService.php`, `RentalChargeDocuments.php`, `AgreementFinancialSummaryPanel.tsx`)
- Excluded `Void`, `Cancelled`, and `Reversed` invoices from `$totalInvoiced`, `$totalCreditNotes`, and `$totalDebitNotes`.
- Expanded owner agreement financial summary to match AP bills with source type `vehicle_rental_owner_settlement` and reference pattern `[REF]-SETTLE-%`.
- Added `target_invoice_id` selector in `AgreementFinancialSummaryPanel` for credit and debit notes.
- Added deposit payment tracking and an interactive "Allocate Security Deposit" dialog for applying held customer deposits to unpaid rental invoices via `PaymentAllocationService`.

### 4. Owner Settlement Billing Guard (`OwnerSettlementBillingService.php`)
- Added duplicate settlement month detection preventing multiple active AP bills for the same agreement and settlement month.

### 5. Fleet Profitability & Disbursement Reporting (`RentalRevenueReportService.php`, `VehicleProfitabilityReportService.php`, `OwnerDisbursementReportService.php`)
- Fixed SQL query crash in `RentalRevenueReportService` (`customer_agreement_id` instead of invalid `agreement_id`, and corrected cycle reference/item column access).
- Included `BillingCycle` revenue, overtime, tolls, and owner AP disbursements in both `VehicleProfitabilityReportService` and `OwnerDisbursementReportService`.

### 6. Fleet & Driver Reassignment Protection (`VehicleDriverReassignmentService.php`, `VehicleDriverReassignmentRequest.php`, `RunningChartService.php`, `vehicleUseApi.ts`)
- Disallowed reassigning drivers or reversing running charts that belong to finalized billing cycles.
- Bounded unearned rent deductions during vehicle replacement to active cycle/use periods rather than multi-year contract ends.
- Accepted field aliases (`new_vehicle_id`, `starts_at`, `handover_odometer`) in `VehicleDriverReassignmentRequest`.
- Routed `replaceVehicleUse` in `vehicleUseApi.ts` directly to `VehicleDriverReassignmentController::replaceVehicle`.

### 7. Verification & Tests
- Full PHPUnit suite passes: 255 tests, 2150 assertions (`php artisan test app/Modules/VehicleRental/Tests`).
- Full Vitest suite passes: 32 test files, 144 tests (`npx vitest run resources/js/modules/vehicle-rental`).
- Typecheck clean: `npm run typecheck` (`tsc --noEmit`).
- Production build clean: `npm run build` (`vite build`).
