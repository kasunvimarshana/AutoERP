# Vehicle Rental Statement of Accounts & Invoice Listing Reports Implementation

## Context & Motivation
Following an end-to-end deep audit of legacy Visual FoxPro domain attachments and printouts:
- **Attachment 1, 2, 7 (`pmcre1vehstat_b.pdf` / `pmcre1vehstat_b.frx`):** `Statement of Accounts - Vehicle Wise (Detail)` providing single-vehicle owner statements with Balance B/F, rental payables, overtime breakdown, toll/pass reimbursements, PRB bank voucher/cheque settlements, CDN repair deduction notes, and closing balance over paid.
- **Attachment 3, 8 (`pmcre1vehstat_c.pdf` / `pmcre1vehstat_c.frx`):** `Statement of Accounts - Vehicle Wise (Detail II)` displaying operational hire matrix with client/hirer names, hire start/finish dates, included contract KM, rate per KM, hire amount, normal/double/triple overtime hours, driver salary/bata, other charges, VAT, and balance over paid.
- **Attachment 4 (`prndebinv3_s.pdf` / `pmdebinv3.frx`):** `Lessee's All Invoice Listing (Summary)` detailing invoice number, invoice date, lessee details & billing period, vehicle number, rental charges, excess KM charges, VAT, and grand totals.
- **Attachment 10 (`pmglled.frx`):** General ledger account `:3305-000 WHT` tracking 10% statutory tax withheld on vehicle owner gross earnings.

The vehicle rental reporting engine was upgraded to provide dedicated, pixel-perfect digital screens, printable sheets, and PDF export templates for all of these business workflows while respecting module boundaries and avoiding magic values.

---

## Changes Implemented

### 1. Owner Disbursement & Statement of Accounts Engine
- **Service (`OwnerDisbursementReportService.php`):**
  - Added support for `statement_detail_1` (`Statement of Accounts - Vehicle Wise (Detail)`) and `statement_detail_2` (`Statement of Accounts - Vehicle Wise (Detail II)`).
  - Implemented automated calculation of `balance_bf` (Balance Brought Forward) by aggregating prior base payables, usage earnings, payments/settlements, repair deductions, and withholding tax prior to the report period start.
  - Implemented single-contract focus filtering by `owner_agreement_id` or `vehicle_id`.
  - Added operational hire breakdown (`detail_ii_row`) with client name, hire period, included KM, excess rate, hire amount, N/O, D/O, T/O overtime hours split, driver salary/bata, other charges, and VAT.
  - Linked outbound disbursements/settlements (cheque/bank vouchers) and repair chargeback allocations (debit notes) to calculate `balance_closing` with contextual label (`Balance Over Paid` or `Balance Payable`).
- **Request & Controller (`OwnerDisbursementReportRequest.php`, `RentalReportsController.php`):**
  - Permitted `report_type` values: `summary`, `wht_ledger`, `statement_detail_1`, `statement_detail_2`.
  - Added `vehicle_id` filter parameter support.
  - Configured portrait orientation for Detail I and Detail II exports while retaining landscape for multi-agreement payables summaries.
- **Print & PDF Template (`owner-disbursement.blade.php`):**
  - Added dedicated Blade template sections for `statement_detail_1` and `statement_detail_2` matching the legacy Softskills printout layout, column widths, double-underline closing balances, and driver badges.
- **Frontend UI (`OwnerDisbursementReportPage.tsx`, `rentalReportsApi.ts`):**
  - Added 4 tab views:
    1. *Owner Settlements Summary* (Fleet payables matrix & KPIs)
    2. *Statement of Accounts (Detail I)* (Vehicle-wise statement with payables, settlements, repair deductions, and closing balance)
    3. *Statement of Accounts (Detail II)* (Vehicle-wise operational matrix with client details and overtime hours split)
    4. *Withholding Tax (WHT) Ledger* (Account Code `:3305-000 WHT`)
  - Integrated controlled contract/vehicle focus selector to inspect statements for specific fleet vehicles.

---

### 2. Commercial Revenue & All Invoice Listing
- **Service (`RentalRevenueReportService.php`):**
  - Added `report_type` parameter supporting `summary` (Commercial revenue by agreement) and `invoice_listing` (`Lessee's All Invoice Listing (Summary)`).
  - Implemented `buildInvoiceListing` to query formal invoices from `invoices` and `invoice_lines` or synthesize billing cycle records from customer agreement base and usage charges.
  - Returns detailed invoice register with invoice number, invoice date, lessee details with billing period, vehicle number, rental charges, excess KM charges, 18% VAT, and total invoice amounts.
- **Request & Controller (`RentalRevenueReportRequest.php`, `RentalReportsController.php`):**
  - Permitted `report_type` values: `summary`, `invoice_listing`.
- **Print & PDF Template (`rental-revenue.blade.php`):**
  - Added print/export layout for `Lessee's All Invoice Listing (Summary)` with column alignment matching FoxPro `prndebinv3_s.pdf`.
- **Frontend UI (`RentalRevenueReportPage.tsx`, `rentalReportsApi.ts`):**
  - Added tabs: *Commercial Revenue Summary (By Agreement)* and *Lessee's All Invoice Listing (Summary)*.
  - Added KPI metrics for Invoices Count, Rental Charges, Excess KM Charges, VAT, and Grand Total Billed.

---

## Verification & Quality Gates
1. **PHPUnit Test Suite:**
   - `php artisan test app/Modules/VehicleRental/Tests/RentalReportsTest.php`: **7 passed (119 assertions)**.
   - Total Vehicle Rental tests: **234 passed (1,986 assertions)**.
2. **Vitest Frontend Test Suite:**
   - `npx vitest run resources/js/modules/vehicle-rental`: **29 passed (138 tests)**.
3. **Static Analysis & Linting:**
   - `npm run typecheck`: **0 errors**.
   - `npm run lint`: **0 errors**.
   - `npm run build`: **Compiled successfully in 9.13s**.
