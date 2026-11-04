# Vehicle Rental: Comprehensive Business Operations, Transactions, Financial, and Compliance Reporting Suite

## Context & Motivation
In accordance with user requirements, legacy visual reports, and attachment audits (`pmcre1vehstat_b.pdf`, `pmcre1vehstat_c.pdf`, `pmcrelicence.frx`, `pmdebinv5.frx`, `pmglled.frx`, `pmdeblogsheet2.frx`, `pmcre1logsheet2.frx`), the Vehicle Rental module now provides a comprehensive, production-grade reporting and business intelligence suite covering:
1. **Fleet Utilization & Vehicle Status Report (`pmcre1vehstat_b.pdf`, `_c.pdf`):** Physical asset tracking, custody statuses, planned dispatches, ownership type (company-owned vs owner-leased), period kilometers driven, custody days, and asset utilization rates.
2. **Vehicle Revenue Licence Expire Report (`pmcrelicence.frx`):** Regulatory compliance tracking for fleet revenue licences, owner names, expiration alerts (expired, due within 30 days, valid), and countdown timers.
3. **Lessee's Outstanding Age Analysis (`pmdebinv5.frx`):** Customer receivables aging report grouped by client, rental agreement reference, and individual vehicle with aging days and subtotals.
4. **Rental Revenue & Commercial Billing Summary:** Commercial performance across customer contracts, base rent billing cycles, excess distance charges, overtime, night outs, and toll reimbursements.
5. **Vehicle Owner Disbursement & Payables Ledger (`pmglled.frx`):** Lessor financial settlements, monthly base lease entitlements, usage revenue sharing, toll/fuel deductions, and withholding tax (WHT 10%) ledger statements.
6. **Fleet Vehicle Profitability & Unit Economics Report:** Vehicle-level profit & loss, direct operating costs (owner lease, fuel, maintenance, tolls), gross margins, margin percentages, and per-kilometer unit economics.
7. **Rental Incident & Damage Risk Report:** Risk management register, damage event classifications, liability determinations, and photographic evidence audits.
8. **Running Chart 31-Day Matrix Sheet (`pmdeblogsheet2.frx`, `pmcre1logsheet2.frx`):** Official monthly basis trip sheet report with comparative & cumulative mileage, overtime hours, night outs, expressway toll passes, and driver/owner signatures in both Lessee and Owner modes.

---

## Changes Made

### 1. Backend Architecture (`app/Modules/VehicleRental`)
- **Authorization:**
  - Added `REPORT_VIEW = 'vehicle-rental.reports.view'` and `RentalAuthorization::assertReport()` in `Services/RentalAuthorization.php`.
- **Form Requests:**
  - `Http/Requests/FleetUtilizationReportRequest.php`: Validates date range, ownership, and custody filters.
  - `Http/Requests/VehicleRevenueLicenseReportRequest.php`: Validates expiry date range, status filters, and vehicle selection.
  - `Http/Requests/RentalRevenueReportRequest.php`: Validates commercial billing period, customer, and agreement filters.
  - `Http/Requests/LesseeOutstandingReportRequest.php`: Validates as-of date, customer, agreement, and aging bucket filters.
  - `Http/Requests/OwnerDisbursementReportRequest.php`: Validates settlement period, supplier, and contract filters.
  - `Http/Requests/VehicleProfitabilityReportRequest.php`: Validates evaluation period, ownership type, and vehicle filters.
  - `Http/Requests/RentalIncidentReportRequest.php`: Validates occurrence date range, incident type, status, and vehicle filters.
- **Reporting Services:**
  - `Services/FleetUtilizationReportService.php`: Computes custody periods, active assignments, and running chart distances.
  - `Services/VehicleRevenueLicenseReportService.php`: Evaluates `VehicleDocument` expiration against current date and resolves owner/lessor information.
  - `Services/RentalRevenueReportService.php`: Aggregates customer base charges, excess mileage, overtime, night outs, and pass outlays.
  - `Services/LesseeOutstandingReportService.php`: Queries outstanding invoice balances linked to rental agreements, groups by customer with contact details, and computes aging days.
  - `Services/OwnerDisbursementReportService.php`: Computes owner base pay, usage revenue share, toll/damage chargebacks, and net disbursements.
  - `Services/VehicleProfitabilityReportService.php`: Computes vehicle-level gross margin, margin %, revenue/km, and direct operating cost/km.
  - `Services/RentalIncidentReportService.php`: Summarizes incident severity, fault determinations, and review statuses.
- **Controller & Routes:**
  - `Http/Controllers/RentalReportsController.php`: Dispatches JSON payloads and multi-format exports (print HTML, PDF via Spatie LaravelPdf, and preview HTML).
  - `Routes/api.php`: Registered endpoints under `/api/v1/vehicle-rental/reports/*`.
- **Blade Layouts:**
  - `resources/views/reports/vehicle-rental/fleet-utilization.blade.php`
  - `resources/views/reports/vehicle-rental/revenue-license.blade.php`
  - `resources/views/reports/vehicle-rental/lessee-outstanding.blade.php`
  - `resources/views/reports/vehicle-rental/rental-revenue.blade.php`
  - `resources/views/reports/vehicle-rental/owner-disbursement.blade.php`
  - `resources/views/reports/vehicle-rental/vehicle-profitability.blade.php`
  - `resources/views/reports/vehicle-rental/rental-incidents.blade.php`

### 2. Frontend Architecture (`resources/js/modules/vehicle-rental`)
- **API Client (`rentalReportsApi.ts`):**
  - Strongly typed TypeScript interfaces, query builders, and export URL helpers for all 7 reports.
- **Dedicated Report Pages:**
  - `reports/FleetUtilizationReportPage.tsx`
  - `reports/VehicleRevenueLicenseReportPage.tsx`
  - `reports/RentalRevenueReportPage.tsx`
  - `reports/LesseeOutstandingReportPage.tsx`
  - `reports/OwnerDisbursementReportPage.tsx`
  - `reports/VehicleProfitabilityReportPage.tsx`
  - `reports/RentalIncidentReportPage.tsx`
- **Reports Hub (`RentalReportsPage.tsx`):**
  - Updated with organized cards categorized into Fleet Operations & Compliance, Commercial Revenue & Receivables, and Supplier Payables & Financial Performance.
- **Router (`resources/js/app/router.tsx`):**
  - Lazy-loaded route bindings under `/vehicle-rental/reports/*`.

---

## Verification & Automated Tests
- **Backend Tests:**
  - Created `app/Modules/VehicleRental/Tests/RentalReportsTest.php` covering all 7 report services, HTTP endpoints, JSON structures, KPI computations, and HTML print exports (6 tests, 78 assertions, 0 failures).
  - Executed full VehicleRental suite: 173 tests passed (1,573 assertions, 0 failures).
- **Frontend Verification:**
  - TypeScript type check: Passed (`tsc --noEmit`, 0 errors).
  - Vitest test suite: Passed (`RentalReportsPage.test.tsx` and full repository suite).
