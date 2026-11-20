# Vehicle Rental Module Documentation Audited Corrections & Hardening

**Date:** 2026-11-20  
**Scope:** `docs/modules/vehicle_rental_complete_guide.md`, `docs/VEHICLE_RENTAL_GUIDE.md`  
**Topic:** Critical factual reconciliation between source code and documentation covering domain enums, database schema definitions, HTTP 405 immutability guards, exact permission strings, and report links.

---

## 1. Summary of Changes

1. **Domain Enums Catalog Reconciliation (38 Enums)**:
   - Audited all 38 backed PHP 8.2 enums in `app/Modules/VehicleRental/Enums/`.
   - Corrected all cases and string values in Section 2.5 of [`vehicle_rental_complete_guide.md`](file:///d:/project-new/AutoERP/docs/modules/vehicle_rental_complete_guide.md), removing hallucinations across `AgreementAction`, `AgreementAdjustmentCategory`, `AgreementAdjustmentStatus`, `BaseChargeSource`, `BaseRentPolicy`, `BillingCycleItemCategory` (all 15 cases), `DriverIdentitySource`, `IncidentPermission`, `IncidentStatus`, `IncidentType`, `MileagePolicy`, `ReassignmentFinancialStatus`, `ReassignmentType`, `RentalBasis`, `RentalExpenseAllocationStatus`, `RentalExpenseAllocationType`, `RentalExpenseStatus`, `RentalExpenseType`, `RunningChartAction`, `UsageChargeComponent`, `UsageChargePolicy`, `UsageChargeSource`, `VehicleFinanceAgreementStatus`, `VehicleFinanceInstallmentStatus`, and `VehicleUseAction`.

2. **Database Schema & Table Corrections**:
   - Reconciled column definitions against database migrations:
     - `vehicle_rental_customer_agreements`: Corrected `basis` (not `rental_basis`), `executing_on` (not `executing_date`), added `agreed_on`, `driver_mode`, snapshots, `activated_at`, `closed_at`, `closed_on`.
     - `vehicle_rental_owner_agreements`: Corrected `basis`, `executing_on`, added `agreed_on`, `driver_mode`, vehicle number/registration snapshots, `activated_at`, `closed_at`, `closed_on`.
     - `vehicle_rental_uses`: Removed non-existent columns (`driver_mode`, `driver_employee_id`, and `replaced_by_use_id`), documented `replaces_use_id` / `replacedByUse` relationship, and documented actual columns `starts_at_input`, `ends_at_input`, `customer_agreement_version`, `owner_agreement_version`, `vehicle_label_snapshot`.
     - `vehicle_rental_running_charts`: Removed fabricated columns (`chart_date`, `private_km`, `total_km`), documented actual columns `garage_km`, `commercial_km`, `driver_identity_source`, `driver_employee_id`, snapshots, `corrects_chart_id`, `finalized_at`, `reversed_at`, `vehicle_use_version`.
     - `vehicle_rental_billing_cycles`: Corrected default `billing_mode` to `without_running_chart`, added `customer_base_charge_id`, `owner_base_charge_id`, `total_paid_amount`, `currency_id`, `exchange_rate`, `created_by`, `finalized_by`, `finalized_at`.
     - `vehicle_rental_reassignments`: Documented `previous_owner_agreement_id`, `new_owner_agreement_id`, `user_id`, and exact financial status values (`pending`, `settled`, `none`).
   - Updated database precision to `decimal(20,6)` (`AgreementFields::DECIMAL_PRECISION = 20, DECIMAL_SCALE = 6`).
   - Updated Master ERD to include `vehicle_rental_reservations`, `vehicle_rental_reservation_histories`, and `vehicle_rental_reassignments`.
   - Updated history tables reference to include `vehicle_rental_reservation_histories` and exact column names for adjustment/finance status histories.

3. **HTTP 405 MethodNotAllowed Immutability Protections & API Permissions**:
   - Explicitly documented that all HTTP `DELETE` routes on immutable business records (`/reservations/{id}`, `/finance-agreements/{id}`, `/expenses/{id}`, `/incidents/{id}`, `/{kind}/agreements/{id}`, `/vehicle-uses/{id}`, `/running-charts/{id}`, `/billing-cycles/{id}`, `/adjustments/{id}`) throw `405 Method Not Allowed` by architectural invariant, and documented the non-destructive state transition alternatives (`cancel`, `void`, `reverse`, `close`).
   - Corrected all permission strings to exact keys defined in `RentalAuthorization` and `PaymentPermission`:
     - `vehicle-rental.*` with proper hyphens and pluralization.
     - `payments.view`, `payments.create`, `payments.allocate`, `payments.reverse`, `payments.refund`.
     - `vehicle-rental.reports.view` across analytical reports.

4. **Directory Structure & Codebase Inventory**:
   - Removed non-existent `app/Modules/VehicleRental/Policies/` directory reference from codebase layout.
   - Accurately inventoried 31 Eloquent models, 21 controllers, 44 domain services (including `RentalAuthorization`), and 47 test files (running 344 tests).

5. **Operational Constants & Validations**:
   - Corrected Clock Drift Tolerance from 10 minutes to 2 minutes (`OperationalFields::CLOCK_DRIFT_TOLERANCE_MINUTES = 2`).
   - Corrected internal deployment payload (`OperationalFields::MUTABLE_USE`).
   - Corrected 4-way allocation types (`company_cost`, `customer_recovery`, `owner_deduction`, `employee_reimbursement`).

6. **File Link Hardening in [`docs/VEHICLE_RENTAL_GUIDE.md`](file:///d:/project-new/AutoERP/docs/VEHICLE_RENTAL_GUIDE.md)**:
   - Replaced 6 broken file links with existing paths (`AgreementService.php`, `BaseRentBilling.php`, `UsageChargeBilling.php`, `reports/OwnerDisbursementReportPage.tsx`, `reports/RentalRevenueReportPage.tsx`).
   - Updated table count from 22 to 28 tables and cataloged all 28 tables.

---

## 2. Verification Record

- **Test Suites Verified:**
  - Backend PHPUnit Tests: `php artisan test app/Modules/VehicleRental/Tests/` — 344 passed (2,948 assertions).
  - Frontend Vitest Suites: `npx vitest run resources/js/modules/vehicle-rental/` — 38 passed (181 tests).
  - TypeScript Compiler: `npm run typecheck` — Passed with 0 errors.
- **Link Audits:**
  - `docs/modules/vehicle_rental_complete_guide.md`: All 42 file links verified and confirmed existing on disk.
  - `docs/VEHICLE_RENTAL_GUIDE.md`: All 49 file links verified and confirmed existing on disk.
- **Mermaid Syntax Audits:**
  - All 5 Mermaid blocks (`flowchart TD`, `erDiagram`, 3x `stateDiagram-v2`) verified syntactically clean.
