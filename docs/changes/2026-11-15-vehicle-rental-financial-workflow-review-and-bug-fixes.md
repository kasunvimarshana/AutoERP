# Vehicle Rental Module: Review, Edge-Case Hardening, and Financial Bug Fixes

**Date:** 2026-11-15  
**Scope:** `app/Modules/VehicleRental/`, `resources/js/modules/vehicle-rental/`

## Context & Objectives
Conducted a deep review and break-and-fix audit on the Vehicle Rental module implementation. Identified and resolved edge-case bugs across HTTP request validation, two-stage invoicing, multi-vehicle attribution, deposit allocation, vehicle replacement history, and profitability reporting.

## Key Defects Found & Resolved

1. **Reassignments Index HTTP Validation Failure (`VehicleDriverReassignmentRequest.php`):**
   - *Problem:* `reason` was required across all HTTP verbs, causing `GET /api/v1/vehicle-rental/reassignments` to fail with HTTP 422 Unprocessable Entity.
   - *Fix:* Made `reason` required only on write operations (`POST`, `PUT`, `PATCH`).

2. **Vehicle Use Replacement Notes Fallback (`VehicleUseEditor.tsx`):**
   - *Problem:* When replacing a vehicle with an empty notes field, the frontend omitted `reason`, triggering an API validation error.
   - *Fix:* Added fallback `notes.trim() || 'Operational vehicle replacement'`.

3. **Credit Note Balance Extinguishment against Target Invoice (`RentalChargeDocuments.php`):**
   - *Problem:* When an adjustment credit note was issued targeting a specific invoice (`target_invoice_id`), the credit note invoice was created but the target invoice AR balance remained unaffected.
   - *Fix:* Auto-posted the credit note invoice and called `InvoiceBalanceUpdater::allocateCredit()` to extinguish the target invoice balance in Accounts Receivable, linking `invoice_id` on `AgreementAdjustment`.

4. **Security Deposit Auto-Posting on Allocation (`DepositReceipts.php`):**
   - *Problem:* Security deposit receipts created via `receive()` start in `Draft` and `NotPosted` state. `PaymentAllocationService::allocate()` rejected allocation with `InvalidArgumentException: Only approved and posted payments can be allocated.`
   - *Fix:* Auto-submitted, auto-approved, and posted the deposit payment using `PaymentDocumentLifecycleService` and `PaymentPostingService` to establish the `PaymentUnappliedBalance` before allocating.

5. **Multi-Vehicle Attribution & Proportional Usage Charges (`TwoStageInvoicingService.php`):**
   - *Problem:* Non-running chart previews were not grouping attributions by vehicle, and excess KM / tolls were omitted. Driver-specific expense items (driver allowances) were mistakenly attributed to vehicles when `vehicle_id` was null.
   - *Fix:* Grouped non-running chart preview attributions by vehicle, attributed excess KM & tolls proportionally across vehicles, and ensured unassigned items only attribute when a single vehicle exists. Also fixed custody proration boundary when use starts after cycle end or ends before cycle start.

6. **Vehicle Replacement History & Audit Attribution (`VehicleDriverReassignmentService.php`):**
   - *Problem:* Driver reassignment on running charts was not recording `occurred_at`, and unearned owner rent adjustments created during vehicle replacement did not log an `AgreementAdjustmentHistory` record.
   - *Fix:* Set `occurred_at` on chart driver reassignments and recorded adjustment history when unearned owner rent is adjusted.

7. **Profitability & Revenue Report Accuracy (`VehicleProfitabilityReportService.php`, `RentalRevenueReportService.php`):**
   - *Problem:* Multi-vehicle billing cycles were attributing 100% of cycle base rent and usage charges to each vehicle, causing multi-vehicle revenue double-counting in fleet profitability reports.
   - *Fix:* Pro-rated customer billing cycle revenue across active vehicles in the cycle based on their running chart proportions or custody days. In `RentalRevenueReportService`, loaded actual invoice numbers rather than defaulting to cycle references.

## Verification
- **PHPUnit Full Test Suite:** `php artisan test app/Modules/VehicleRental/Tests` — 264 passed, 2,185 assertions.
- **Vitest Full Test Suite:** `npx vitest run resources/js/modules/vehicle-rental` — 32 test files, 144 passed.
- **TypeScript Typecheck:** `npm run typecheck` — 0 errors.
- **Production Asset Build:** `npm run build` — 759 modules transformed, built in 8.50s.
