# Vehicle Rental: Comprehensive Audit of Legacy Domain Attachments and Running Chart Replacement Vehicle Reconciliation

**Date:** 2026-11-10  
**Modules:** `VehicleRental`  
**Scope:** Deep end-to-end technical audit and reconciliation across all 10 user-provided domain attachments (Owner Statement of Accounts Detail I & II, Lessee Invoice Listings, Lessee/Owner Monthly Running Charts, Replacement Vehicle logs, Withholding Tax (WHT) Ledger :3305-000, Lessee Aging Analysis, and Vehicle Revenue License Expiry reports). Enhanced Running Chart sheet generation with automated replacement vehicle tracking badges.

---

## 1. Audit Findings & Alignment Matrix

### Attachment 1 & 2: Statement of Accounts - Vehicle Wise (Detail)
- **Source Artifacts:** AT Tours & Transport (Pvt) Ltd owner statement printouts (`CAB-0242` and `CAT-8971`).
- **Domain Structure:**
  - Balance B/F (Brought Forward): `260,320.00`
  - Rental & Other Payable: Monthly base hire (`327,500.00`), Normal Overtime (`31.50 Hrs` @ `1,500.00` = `47,250.00`), Highway Toll / Pass fees (`1,900.00`). Total payable: `376,650.00`.
  - Settlements: Payment voucher `PRB7001132` = `(376,650.00)`.
  - Closing balance payable: `260,320.00`.
- **AutoERP Mapping:** Fully covered in `OwnerDisbursementReportService.php` and `OwnerDisbursementReportPage.tsx`, computing base pay, overtime, toll outlays, and payment reconciliations.

### Attachment 3: Statement of Accounts - Vehicle Wise (Detail II)
- **Source Artifacts:** Expanded monthly operational breakdown for `CAB-0242` with lessee details (`UNILEVER SRI LANKA LTD`).
- **Domain Structure:** Columns for Hire Start/Finish, Total KM (`2,500`), Rate (`161.00`), Hire Amount, Overtime split (Normal/Double/Triple OT), Driver Salary/BATA, Other Charges (`1,900.00`), and Net Payable.
- **AutoERP Mapping:** Covered by `OwnerDisbursementReportService.php` and `OwnerUsageCharge` engine.

### Attachment 4: Lessee's All Invoice Listing (Summary)
- **Source Artifacts:** `INTEGRATED RENT A CAR SYSTEM 2023/2024` sales invoice register.
- **Domain Structure:** Grouped invoice listings tracking Customer, Billing Period, Rental Charge, Excess KM Charge, Vehicle Registration, and Gross Amount (including 18% VAT).
- **AutoERP Mapping:** Fully implemented in `RentalRevenueReportService.php` and `RentalRevenueReportPage.tsx`.

### Attachment 5 & 6: Running Chart for Lessee & Physical Replacement Logs
- **Source Artifacts:** Visual FoxPro `pmdeblogsheet2.frx` report for `UNILEVER SRI LANKA LTD` (`CAB-0242`) and physical printout for `CBC-9180`.
- **Key Domain Invariants:**
  - 30/31-day operational log showing Start/Finish Odometer, Comparative & Cumulative KM, ON/OFF times, OT Hours and Type (`N` / `D`), Night-outs, and Pass fees.
  - **Replacement Vehicle Tracking:** When a temporary vehicle replaces the contract vehicle, the Remark column explicitly flags `For Repl. [Vehicle]` (e.g. `For Repl. CAP-1302`).
- **AutoERP Enhancement:**
  - In `RunningChartReportService.php`, detected when a day's vehicle assignment stems from a replacement vehicle use (`replaces_use_id !== null` or vehicle ID differs from primary contract vehicle).
  - Automatically prepends `For Repl. {vehicle_label}` to the remarks column.
  - Emits `is_replacement_day: true` for the row.
  - In `RentalRunningChartReportPage.tsx` and printable Blade template `running-chart-sheet.blade.php`, highlighted replacement days in amber (`#fef9c3`) with dedicated badge formatting.

### Attachment 7 & 8: Statement of Accounts with Maintenance & Repair Deductions
- **Source Artifacts:** `CBC-9180` statement for `GUNATHILAK H M S K GUNATHILAKE`.
- **Key Domain Invariants:**
  - Demonstrates direct chargebacks against owner payables: `Deduction for Repair` via Debit Note `CDN7000294` (`13,100.00`).
- **AutoERP Mapping:** Directly handled in `RentalExpenseAllocation` with `allocation_type = OwnerDeduction` and incorporated in `OwnerDisbursementReportService::generate()` under deductions.

### Attachment 9: Live AutoERP Running Chart Sheet
- **Source Artifacts:** Live AutoERP screen for `TOKYO SUPERMIX (PVT) LTD` (`CAG 4686`).
- **Reconciliation:** Confirms live UI parity with `For Repl. KI 1685` highlighted replacement vehicle rows, OT hours, and contract allowance KPIs.

### Attachment 10: Legacy FoxPro Reports Suite
- **Source Artifacts:**
  - `pmglled.frx`: Withholding Tax (WHT 10%) Ledger Account `:3305-000 WHT`.
  - `pmdebinv5.frx`: Lessee's Outstanding Age Analysis (debtor aging by customer).
  - `pmcrelicence.frx`: Vehicle Revenue Licence Expire Report.
- **AutoERP Mapping:**
  - WHT Ledger: Dedicated view tab in `OwnerDisbursementReportPage.tsx` with `:3305-000 WHT` account code.
  - Lessee Age Analysis: Implemented in `LesseeOutstandingReportService.php` and `LesseeOutstandingReportPage.tsx`.
  - Revenue License Expiry: Implemented in `VehicleRevenueLicenseReportService.php` and `VehicleRevenueLicenseReportPage.tsx`.

---

## 2. Verification & Quality Gates

- **Backend PHPUnit Tests:**
  - `php artisan test app/Modules/VehicleRental/Tests`: **234 passed (1,986 assertions)**.
- **Frontend Vitest Tests:**
  - `npx vitest run resources/js/modules/vehicle-rental`: **29 passed (29 files, 138 tests)**.
- **TypeScript Typecheck:**
  - `npm run typecheck`: **0 errors**.
- **ESLint:**
  - `npm run lint`: **0 errors**.
- **Production Asset Build:**
  - `npm run build`: **Built successfully (all assets compiled and bundled in 9.12s)**.
