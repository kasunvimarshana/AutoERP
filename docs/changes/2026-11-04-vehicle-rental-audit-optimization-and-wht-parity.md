# Vehicle Rental: Deep Audit, Ergonomic UI Cleanup, and Legacy Report Parity (WHT Ledger & Lessee Outstanding)

## Context & Motivation
Following a comprehensive end-to-end deep audit of the Vehicle Rental module and visual review of legacy report designer printouts (`pmglled.frx`, `pmdeblogsheet2.frx`, `pmcrellogsheet2.frx`, `pmdebinv5.frx`, `pmcrelicence.frx`), this change set implements:
1. **Withholding Tax (WHT) Ledger Statements (`pmglled.frx`):**
   - Statutory 10% Withholding Tax deduction on lessor disbursements/settlements with vehicle registration and owner code lineage.
   - Dual-view reporting in Owner Disbursement: "Owner Settlement Summary" and the exact "Account Code :3305-000 WHT" ledger statement matching `pmglled.frx`.
2. **Lessee Outstanding Age Analysis Enhancement (`pmdebinv5.frx`):**
   - Joined `invoice_document_snapshots` to extract exact `supply_period_start` and `supply_period_end` formatted as `d/m/Y To d/m/Y` under agreement/period column.
   - Eager-loaded `vehicleUses.vehicle` on customer agreements to resolve physical vehicle registration numbers directly on each invoice line.
3. **Ergonomic UI/UX Refactoring (`AgreementsPage.tsx`):**
   - Eliminated stacked, repetitive toggle buttons ("View deposits", "View assigned vehicles", "View history") in the agreement review panel.
   - Replaced them with a clean, responsive tabbed interface (`Billing & Rate Estimates`, `Assigned Vehicles & Handover`, `Security Deposits`, `Audit History`).
   - Automatically focuses the vehicle assignment tab when opened via dispatch link parameters.

---

## Changes Made

### 1. Backend (`app/Modules/VehicleRental`)
- **`Services/OwnerDisbursementReportService.php`:**
  - Added support for `wht_rate` (default 10%) and `report_type` (`summary` vs `wht_ledger`).
  - Calculated `gross_payable`, `wht_amount`, and `net_payable` with `DecimalMath`.
  - Generated WHT ledger entries with transaction date, reference (`CDN...`), description (`WHT 10% {OWNER_NAME} {OWNER_CODE} {VEHICLE_NO}`), debit, credit, and running balance.
- **`Http/Requests/OwnerDisbursementReportRequest.php`:**
  - Added validation for `wht_rate` (`numeric`, `min:0`, `max:100`) and `report_type` (`in:summary,wht_ledger`).
- **`Http/Controllers/RentalReportsController.php`:**
  - Routed `wht_rate` and `report_type` parameters into `OwnerDisbursementReportService` for JSON and export endpoints.
- **`Services/LesseeOutstandingReportService.php`:**
  - Eager-loaded `vehicleUses.vehicle` on customer agreements.
  - Left-joined `invoice_document_snapshots` to resolve exact billing supply periods (`supply_period_start` and `supply_period_end`).
  - Resolved `vehicle_no` from primary assigned vehicle use.
- **`resources/views/reports/vehicle-rental/owner-disbursement.blade.php`:**
  - Added dual rendering: standard owner settlement summary with gross pay, deductions, WHT, and net pay; plus the exact `pmglled.frx` WHT Ledger statement (`Account Code :3305-000 WHT`).
- **`Tests/RentalReportsTest.php`:**
  - Added assertions for `total_gross_payable`, `total_wht_amount`, `wht_ledger` structure, and the `:3305-000 WHT` export preview.

### 2. Frontend (`resources/js/modules/vehicle-rental`)
- **`rentalReportsApi.ts`:**
  - Updated `OwnerDisbursementReportParams`, `OwnerDisbursementReportData`, and added `WhtLedgerEntry` interface.
  - Updated export URL builder to pass `wht_rate` and `report_type`.
- **`reports/OwnerDisbursementReportPage.tsx`:**
  - Added WHT % input field in filter parameter form.
  - Added mode toggle tabs between "Owner Settlements & Disbursement Summary" and "Withholding Tax (WHT) Ledger Account (:3305-000 WHT)".
  - Updated KPI cards and tables to display Gross Pay, WHT Withheld, and Net Payable.
- **`AgreementsPage.tsx`:**
  - Streamlined agreement review section by replacing multiple stacked toggle buttons with clean tabs (`Billing & Rate Estimates`, `Assigned Vehicles & Handover`, `Security Deposits`, `Audit History`).
  - Automatically activates the vehicles tab when landing from vehicle availability dispatch.

---

## Verification
- **Automated Tests:**
  - Backend PHPUnit: 173 tests passed (1,585 assertions, 0 failures).
  - Frontend Vitest: 28 test files passed (102 tests, 0 failures).
  - TypeScript: `tsc --noEmit` passed with 0 errors.
  - ESLint: `npm run lint` passed with 0 errors and 0 warnings.
