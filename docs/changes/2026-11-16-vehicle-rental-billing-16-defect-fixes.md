# Vehicle Rental Billing & Invoicing Subsystem Defect Fixes

**Date:** 2026-11-16  
**Scope:** `VehicleRental` billing, invoicing, owner settlement, document register, and disbursement reporting  
**Focus:** Comprehensive production accounting integrity, robust concurrency handling, and resolution of 16 defect fixes across rental billing cycles and owner settlements.

---

## 1. Problem Statements & Root Causes

1. **Defect 1 (`handover_at` vs `handed_over_at`)**:
   - In `TwoStageInvoicingService`, custody proration referenced `$use->handover_at`, a non-existent property on `VehicleUse` (the schema column is `handed_over_at`), causing fallback to null or improper cycle-wide allocation.
2. **Defect 11 (Supplementary Invoice Cancellation Destroys Cycle)**:
   - When cancelling a supplementary invoice, `RentalBillingDocumentService` unconditionally marked the entire underlying `BillingCycle` as `Cancelled`, stranding the active initial provisional invoice and permanently preventing Stage 2 from being re-finalized.
3. **Defect 12 (Fatal Posting Plan Crash on Over-Deducted Owner Settlements)**:
   - In `OwnerSettlementBillingService`, when maintenance chargebacks equaled or exceeded gross lease payable, net payable dropped to zero or became negative. Emitting an Accounts Payable posting line with `debit = 0` and `credit = 0` triggered a fatal exception in `InvoicePostingPlanService`.
4. **Defect 2 (Owner BillingCycle Double Billing)**:
   - In `OwnerSettlementBillingService`, unbilled owner billing cycles (`BillingCycle` where `owner_agreement_id` is set) were included in gross payable calculations, but their status was never transitioned to `Finalized` nor linked to `final_invoice_id`, leaving them eligible for repeated double-billing.
5. **Defect 6 (Expense Allocation Stranding on Cancellation)**:
   - When an unposted invoice or bill was cancelled, `RentalBillingDocumentService` unlinked `RentalExpenseAllocation::invoice_id`, but left `status` as `Billed`, stranding allocations from ever being re-billed.
6. **Defect 3 (Regex Date Format Mismatch)**:
   - `OwnerSettlementBillingService` parsed period dates using a regex strictly expecting `YYYY-MM-DD`, failing or misinterpreting period strings formatted as `DD/MM/YYYY`.
7. **Defect 4 (Toll Aggregation in Attachment)**:
   - `TwoStageInvoicingService` initialized `$totalTolls = '0.000000'` as a static zero placeholder during running chart attachment rather than aggregating actual approved toll expenses from `RentalExpense`.
8. **Defect 5 (Missing AgreementAdjustment Integration in Owner Settlements)**:
   - Approved `AgreementAdjustment` records on owner agreements (debit notes and credit notes) were omitted during owner settlement AP bill generation.
9. **Defect 7 & 8 (Mode A Credit Notes & Itemization Preservation)**:
   - In `TwoStageInvoicingService`, Mode A finalization did not support negative deltas (downtime rebates exceeding usage), and credit notes collapsed multiple itemized deductions into an opaque single generic line.
10. **Defect 13 (Empty Driver String Match Flaw)**:
    - In `TwoStageInvoicingService`, driver item matching evaluated `str_contains(..., $driverName)` without checking if `$driverName` was empty, causing empty driver strings to match every unassigned item in the cycle.
11. **Defect 14 (Swap Date Double Counting in Custody Proration)**:
    - When consecutive replacement vehicle uses met on the same calendar day (swap day), both uses included the swap day (`diffInDays + 1`), double-counting that day and causing total custody days to exceed the cycle length.
12. **Defect 9 (Blank Driver Name in Document Register)**:
    - `RentalBillingDocumentService` only inspected line-item level driver references, resulting in blank driver names in the document register when charges were attached via running charts.
13. **Defect 10 (AC Differentials & Agreement Driver Rates Unmapped)**:
    - Contractual AC differential rates (`front_ac_rate`, `dual_ac_rate`) and agreed driver daily/monthly rates were not evaluated into discrete billing cycle line items during chart attachment.
14. **Defect 16 (Static Mock Literals in Disbursement Reports)**:
    - `OwnerDisbursementReportService::detailIiRow` contained hardcoded placeholder literals (`total_km = '0.00'`, `non_ac_rate = '0.00'`, etc.) instead of dynamic computations from running charts and agreement terms.

---

## 2. Key Changes Implemented

### A. Two-Stage Invoicing Service (`TwoStageInvoicingService.php`)
- **Defect 1**: Updated custody calculations in `previewFinalStage` and `buildStage1Lines` to use `$use->handed_over_at ?? $use->starts_at`.
- **Defect 14**: Added consecutive use boundary detection (`$nextStartDay->lessThanOrEqualTo($uEndDay)`), applying `$uStartDay->diffInDays($uEndDay)` on swap boundaries so the swap day is counted exactly once.
- **Defect 4**: Aggregated toll expenses from `RentalExpense` (`type = Toll`) attached to running charts, populating `tolls_amount` on `BillingCycleChart` and `BillingCycle`, and creating discrete itemized toll lines.
- **Defect 10**: Computed AC differential additions against baseline rates (`AirConditioningMode::Front` and `AirConditioningMode::Dual`) per chart commercial kilometers, and generated discrete `BillingCycleItem` additions for agreed `driver_rate`.
- **Defect 13**: Added guard `!empty(trim((string) $driverName))` before substring matching in driver preview attribution.
- **Defect 7 & 8**: Implemented authentic credit note generation when `deltaAmount < 0` in Mode A (and when `initial_invoice_id === null` with negative net), itemizing each deduction with explicit `[Offset by usage: ...]` discount notes while preserving individual deduction descriptions.

### B. Owner Settlement Billing Service (`OwnerSettlementBillingService.php`)
- **Defect 12**: Implemented deduction capping:
  ```php
  $maxDeductionsCapacity = max('0.000000', $this->math->sub($grossPayable, $whtAmount, 6));
  ```
  Guarded the Accounts Payable posting line to only emit when `$netPayable > 0`, preventing zero-amount lines from crashing `InvoicePostingPlanService`.
- **Defect 2**: Finalized unbilled owner cycles upon settlement bill generation:
  ```php
  $cycle->final_invoice_id = $invoice->id;
  $cycle->status = BillingCycleStatus::Finalized;
  $cycle->save();
  ```
- **Defect 5**: Integrated authorized `AgreementAdjustment` records, applying DebitNotes to gross additions and CreditNotes to deductions, and transitioning them to `Applied` with full audit history upon bill creation.
- **Defect 3**: Updated regex to `'/(\d{4}-\d{2}-\d{2}|\d{2}\/\d{2}\/\d{4})\s*(?:-|To)\s*(\d{4}-\d{2}-\d{2}|\d{2}\/\d{2}\/\d{4})/i'` and safely parsed both `YYYY-MM-DD` and `DD/MM/YYYY` formats with Carbon.

### C. Rental Billing Document Service (`RentalBillingDocumentService.php`)
- **Defect 11**: In `restoreSourcesOnCancellation`, when cancelling an invoice where `$cycle->supplementary_invoice_id === $invoice->id`:
  ```php
  $cycle->supplementary_invoice_id = null;
  $cycle->status = BillingCycleStatus::Stage2Attached;
  ```
  preventing destruction of the cycle and keeping the Stage 1 invoice active so Stage 2 can be finalized again. Step 3 was also updated to respect `$handledCycleIds`.
- **Defect 6**: When unlinking `RentalExpenseAllocation` on document cancellation, reset `status = RentalExpenseAllocationStatus::Confirmed` so allocations are eligible for future settlement.
- **Defect 9**: In document register queries, added driver fallback to `$cycle->charts->pluck('driver_name_snapshot')->filter()->unique()->join(', ')` and vehicle registration snapshot when item-level references are null.

### D. Owner Disbursement Report Service (`OwnerDisbursementReportService.php`)
- **Defect 16**: Replaced static mock literals in `detailIiRow` with dynamically computed values from running charts, mileage charges, and agreement terms.

---

## 3. Verification Record

- `php artisan test app/Modules/VehicleRental/Tests/TwoStageInvoicingTest.php`: **PASS** (26 passed, 166 assertions)
  - `test_custody_proration_swap_date_not_double_counted` (Defect 14)
  - `test_custody_proration_uses_handed_over_at_when_present` (Defect 1)
  - `test_attach_running_charts_aggregates_tolls_from_rental_expense` (Defect 4)
  - `test_attach_running_charts_computes_ac_differentials_and_driver_rate` (Defect 10)
  - `test_mode_a_credit_note_itemized_deductions_preserved` (Defects 7 & 8)
  - `test_preview_driver_empty_name_does_not_match_all_items` (Defect 13)
- `php artisan test app/Modules/VehicleRental/Tests/RentalBillingDocumentTest.php`: **PASS** (13 passed, 94 assertions)
  - `test_cancel_draft_supplementary_invoice_reverts_cycle_to_stage2_attached_without_destroying_cycle` (Defect 11)
  - `test_cancel_document_resets_rental_expense_allocation_status_to_confirmed` (Defect 6)
  - `test_document_register_resolves_driver_from_cycle_charts_when_items_driver_empty` (Defect 9)
- `php artisan test app/Modules/VehicleRental/Tests/OwnerSettlementBillingTest.php`: **PASS** (7 passed, 29 assertions)
  - `test_owner_settlement_posting_plan_balanced_when_maintenance_equals_or_exceeds_gross` (Defect 12)
  - `test_owner_settlement_finalizes_unbilled_owner_billing_cycles` (Defect 2)
  - `test_owner_settlement_integrates_agreement_adjustments` (Defect 5)
  - `test_owner_settlement_date_format_mismatch_prevention` (Defect 3)
- `php artisan test app/Modules/VehicleRental/Tests/RentalReportsTest.php`: **PASS** (7 passed, 119 assertions) (Defect 16)
- `php artisan test tests/Unit/Architecture`: **PASS** (12 passed, 124 assertions)
- `npm run typecheck`: **PASS** (0 errors)
