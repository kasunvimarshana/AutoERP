# Vehicle Rental Billing & Invoicing Defect Remediation and Hardening

**Date:** 2026-11-16  
**Scope:** `VehicleRental` billing, invoicing, owner settlement, document register, and disbursement reporting  
**Focus:** Audit of prior defect resolution attempt, fixing critical flaws in custody deduplication, cycle cancellation cleanup, agreement adjustment lifecycle, and toll expense attribution.

---

## 1. Summary of Prior Attempt Deficiencies Found & Resolved

1. **Defect 14 (Consecutive Swap Boundary Logic Broke Concurrent Vehicle Rentals)**:
   - *Issue:* The prior attempt checked `$nextStartDay->lessThanOrEqualTo($uEndDay)`. When two vehicles were rented concurrently over the same calendar period (both starting on Day 1), this condition triggered on the first vehicle, deducting 1 day (counting 29 instead of 30 days).
   - *Fix:* Revised boundary detection in both `previewFinalStage` and `buildStage1Lines` to `($nextStartDay->equalTo($uEndDay) && $nextStartDay->greaterThan($uStartDay)) || ($nextUse->replaces_use_id === $use->id && $nextStartDay->lessThanOrEqualTo($uEndDay) && $nextStartDay->greaterThan($uStartDay))`. This accurately deduplicates meeting swap days without deducting days from concurrent parallel rentals.

2. **Defect 11 (Supplementary Invoice Cancellation Left Orphaned Audit and Billed Totals)**:
   - *Issue:* When cancelling a supplementary invoice, `RentalBillingDocumentService` reset the cycle to `Stage2Attached`, but failed to clear `finalized_at`, `finalized_by`, and did not restore `total_billed_amount` / `tax_amount` back to the initial invoice baseline. Furthermore, Step 3 in `restoreSourcesOnCancellation` filtered out `BillingCycleStatus::Finalized`, preventing proper cycle lookup via invoice sources.
   - *Fix:* Cleared `finalized_at` and `finalized_by`, reset `finalization_mode` to `BillingFinalizationMode::SupplementaryInvoice`, restored `total_billed_amount` and `tax_amount` to the initial invoice amounts (or 0 if unbilled), and removed the restrictive `Finalized` exclusion in Step 3.

3. **Defect 6 & 5 (Stranding Agreement Adjustments and Owner Cycles on Cancellation)**:
   - *Issue:* Cancelling a draft owner settlement bill unlinked `RentalExpenseAllocation`, but left `AgreementAdjustment` records in `status = Applied` and linked to the cancelled bill, permanently stranding them from future settlements. Similarly, unbilled owner cycles finalized by the settlement bill were marked `Cancelled` instead of being restored to `Stage2Attached`/`Draft`.
   - *Fix:* Added full restoration of `AgreementAdjustment` (`invoice_id = null`, `status = Authorized`, `row_version += 1`, and audit history entry) in `restoreSourcesOnCancellation`. Added restoration of owner settlement cycles (`final_invoice_id = null`, `status = Stage2Attached/Draft`, `finalized_at = null`, `finalized_by = null`) so they remain eligible for re-billing.

4. **Defect 10 (AC Differentials & Driver Rate Item Duplication on Re-Attachment)**:
   - *Issue:* In `attachRunningCharts`, existing chart attachments were deleted, but previously generated AC differential items and agreed driver salary items were not purged. Re-attaching charts resulted in doubled AC differential charges.
   - *Fix:* Added idempotent cleanup of previously auto-generated AC differential items and agreed driver salary items before generating new ones. Set `driver_employee_id` on the driver salary item when all attached charts belong to a single driver.

5. **Defect 7 & 8 (Credit Note Source Line Invoiced Total Mismatch)**:
   - *Issue:* In `buildCreditNoteLines`, `invoicedLineTotal` on `InvoiceSourceLineData` was set to the gross `sourceLineTotal` (`$itemAmount`) rather than the net invoiced amount after deducting the usage offset discount (`$itemAmount - $lineDiscount`).
   - *Fix:* Set `invoicedLineTotal` to `$this->math->sub($itemAmount, $lineDiscount, 6)`.

6. **Defect 4 (Toll Expenses Associated with Vehicle Uses Missed)**:
   - *Issue:* In `attachRunningCharts`, only toll expenses with `running_chart_id` were queried. Toll expenses recorded for the vehicle use during the cycle without a specific running chart ID were ignored.
   - *Fix:* Extended toll query to also include approved `RentalExpense` records where `vehicle_use_id` matches the cycle's vehicle uses and `running_chart_id` is null, attributing them to the corresponding use.

7. **Defect 2 & 5 (Concurrency and Optimistic Locking Consistency)**:
   - *Issue:* Updating `BillingCycle`, `RentalExpenseAllocation`, and `AgreementAdjustment` during owner settlement generation did not increment `row_version`.
   - *Fix:* Added `row_version += 1` across all updated models, and populated `finalized_at` and `finalized_by` on finalized owner cycles.

---

## 2. Key Files Modified

- [RentalBillingDocumentService.php](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/RentalBillingDocumentService.php)
- [OwnerSettlementBillingService.php](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/OwnerSettlementBillingService.php)
- [TwoStageInvoicingService.php](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/TwoStageInvoicingService.php)
- [OwnerDisbursementReportService.php](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Services/OwnerDisbursementReportService.php)
- [RentalBillingDocumentTest.php](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Tests/RentalBillingDocumentTest.php)
- [TwoStageInvoicingTest.php](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Tests/TwoStageInvoicingTest.php)
- [OwnerSettlementBillingTest.php](file:///d:/project-new/AutoERP/app/Modules/VehicleRental/Tests/OwnerSettlementBillingTest.php)

---

## 3. Verification Results

- `php artisan test app/Modules/VehicleRental/Tests/RentalBillingDocumentTest.php`: **PASS** (14 passed, 112 assertions)
- `php artisan test app/Modules/VehicleRental/Tests/TwoStageInvoicingTest.php`: **PASS** (28 passed, 174 assertions)
- `php artisan test app/Modules/VehicleRental/Tests/OwnerSettlementBillingTest.php`: **PASS** (7 passed, 29 assertions)
- `php artisan test app/Modules/VehicleRental/Tests/`: **PASS** (300 passed, 2,433 assertions)
- `php artisan test tests/Unit/Architecture`: **PASS** (12 passed, 124 assertions)
- `npm run typecheck`: **PASS** (0 errors)
