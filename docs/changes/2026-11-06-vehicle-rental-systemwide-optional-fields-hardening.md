# Vehicle Rental: System-Wide Optional Fields & Null Normalization Hardening

**Date:** 2026-11-06  
**Module:** `VehicleRental`  
**Scope:** Backend (`app/Modules/VehicleRental/`), Frontend (`resources/js/modules/vehicle-rental/`), Tests

## Context & Purpose
Users often submit forms and filters with optional fields left blank or unselected. In HTTP requests (both GET query parameters and POST/PUT/PATCH JSON payloads), blank inputs are sent as empty strings `""` or omitted entirely.

In Laravel and PHP 8 strict types:
1. Validation rules such as `integer`, `numeric`, `date_format:Y-m-d`, and `in:...` fail with 422 Unprocessable Entity when receiving empty strings `""`, even if declared `nullable`.
2. Mathematical utilities like `DecimalMath::canonical()` and `DecimalMath::add()` throw `InvalidArgumentException` on empty string inputs `""`.
3. Updating records with `$data['field'] ?? $record->field` makes it impossible for users to clear optional fields once populated, as empty strings fall back or fail validation.
4. Report query endpoints and register filter endpoints failed with 422 validation errors when users cleared dropdowns or left search boxes empty.

A complete audit and hardening pass was conducted across all Vehicle Rental sub-domains:
- Rental Reservations (Create, Update, Transition, Convert, Query)
- Rental Expenses & Allocations (Create, Transition, Allocations, Query)
- Vehicle Finance Agreements & Installments (Create, Due Status Refresh, Query)
- Rental Incidents (Create with/without running chart, Query)
- Rental Charge Documents (Base rent and usage billing due dates)
- Vehicle Availability Search (Filter by category, end date)
- Reports (Fleet Utilization, Revenue Licenses, Rental Revenue, Lessee Outstanding, Owner Disbursements, Vehicle Profitability, Incidents Summary)

---

## Changes Made

### 1. Backend Form Requests & Parameter Normalization
Added `prepareForValidation()` hooks to convert empty strings, whitespace-only strings, or unselected values to `null` or appropriate defaults before validation rules execute:
- **`StoreRentalReservationRequest.php`** & **`UpdateRentalReservationRequest.php`**:
  - `requested_vehicle_id`, `requested_vehicle_category_id`, `estimated_amount`, `estimated_deposit_amount`, `source`, `remarks` are normalized to `null`.
- **`ConvertRentalReservationRequest.php`**:
  - `reference`, `terms`, `notes` normalized to `null`.
- **`StoreRentalExpenseRequest.php`**:
  - `expense_number`, `customer_agreement_id`, `vehicle_use_id`, `running_chart_id`, `supplier_id`, `employee_id`, `tax_group_id`, `tax_amount`, `reference_number`, `description` normalized to `null`.
- **`RentalExpenseTransitionRequest.php`**:
  - Deep normalization of `allocations.*` optional fields (`tax_amount`, `markup_amount`, `withholding_amount`, `target_customer_agreement_id`, `target_owner_agreement_id`, `target_vehicle_use_id`, `customer_id`, `supplier_id`, `employee_id`, `tax_group_id`).
- **`StoreVehicleFinanceAgreementRequest.php`**:
  - `agreement_number`, `initial_deposit_amount`, `residual_value`, `interest_method`, `payment_term_days`, `tax_group_id`, `remarks`, and `schedule.*` optional fields normalized to `null`.
- **`RefreshVehicleFinanceDueStatusesRequest.php`**:
  - `as_at` normalized to `null`.
- **`DepositReceiptRequest.php`**:
  - `reference_number` and `notes` normalized to `null`.
- **Report & Query Requests**:
  - `FleetUtilizationReportRequest.php`: `ownership_type`, `custody_status`, `vehicle_id`.
  - `LesseeOutstandingReportRequest.php`: `as_of_date`, `customer_id`, `agreement_id`, `aging_bucket`.
  - `OwnerDisbursementReportRequest.php`: `supplier_id`, `owner_agreement_id`, `wht_rate`, `report_type`.
  - `RentalIncidentReportRequest.php`: `incident_type`, `status`, `vehicle_id`.
  - `RentalRevenueReportRequest.php`: `customer_id`, `agreement_id`.
  - `RunningChartReportRequest.php`: `vehicle_use_id`, `sheet_mode`.
  - `VehicleProfitabilityReportRequest.php`: `ownership_type`, `vehicle_id`.
  - `VehicleRevenueLicenseReportRequest.php`: `start_date`, `end_date`, `status`, `vehicle_id`.
  - `RentalExpenseQueryRequest.php`, `RentalReservationQueryRequest.php`, `VehicleFinanceQueryRequest.php`: Query filter parameters normalized.
  - `VehicleAvailabilityQueryRequest.php`: `ends_at` and `category_id` normalized.

### 2. Backend Services & Calculation Safety
- **`RentalReservationService.php`**:
  - `create()`: Normalized `requested_vehicle_id`, `requested_vehicle_category_id`, `estimated_amount`, `estimated_deposit_amount`, `source`, `remarks`. Defaulted empty monetary fields to `'0.000000'`.
  - `update()`: Supported clearing optional fields when `null` or `""` is explicitly submitted (`array_key_exists`), rather than falling back to old values.
  - `convertToAgreement()`: Filtered empty/null inputs, preserved default terms cleanly, and auto-generated reference when empty.
- **`RentalExpenseService.php`**:
  - `create()`: Guarded `DecimalMath` against `""` in `tax_amount` (defaulting to `'0.000000'`); safely cast optional entity IDs; auto-generated `expense_number` when empty.
  - `createAllocations()`: Handled empty string / null `tax_amount`, `markup_amount`, `withholding_amount` safely with `'0.000000'`.
- **`VehicleFinanceService.php`**:
  - `create()`: Normalized `initial_deposit_amount` and `residual_value` before `DecimalMath` operations; auto-generated `agreement_number` when blank; safely defaulted optional fields.
  - `generateSchedule()`: Normalized `principal_due`, `interest_due`, `fee_due`, `tax_due` to prevent `DecimalMath` exception on empty strings.
- **`RentalIncidentService.php`**:
  - Pre-normalized `running_chart_id`, `evidence_reference`, and `description` so empty strings `""` become `null` prior to validation.
- **`RentalChargeDocuments.php`**:
  - `documentInput()`: Pre-normalized `due_date` so empty string `""` becomes `null` prior to `date_format:Y-m-d` validation.
- **`OwnerSourceService.php`**:
  - `list()`: Pre-normalized `ends_at`, `customer_agreement_id`, and `search` before validation.

### 3. Frontend Form Submission Ergonomics
- **`ReservationEditor.tsx`**:
  - Trimmed `estimated_amount`, `estimated_deposit_amount`, `source`, `remarks`, falling back to `'0.00'` or `null` before sending payloads.
- **`RentalExpenseEditor.tsx`**:
  - Trimmed `net_amount`, `tax_amount`, `reference_number`, `description`, falling back to `'0.00'` or `null`.
- **`VehicleFinanceEditor.tsx`**:
  - Trimmed `principal_amount`, `depositAmount`, `residualValue`, `annualRate`, `termDays`, `remarks`, falling back cleanly.

---

## Verification & Test Results
- **PHPUnit:**
  - `php artisan test --filter=VehicleRental` $\rightarrow$ 213 passed (1,852 assertions) across 34 test suites.
  - Added explicit test coverage for omitted/empty optional fields in:
    - `RentalReservationTest`: `test_reservation_handles_empty_string_optional_fields_and_clearing`, `test_reservation_http_endpoints_accept_empty_optional_fields_and_query_filters`.
    - `RentalExpenseTest`: `test_expense_handles_empty_optional_fields_and_query_filters`.
    - `VehicleFinanceTest`: `test_vehicle_finance_handles_empty_optional_fields_and_query_filters`.
    - `RentalIncidentServiceTest`: `test_incident_creation_with_empty_running_chart_and_empty_optional_strings`.
    - `RentalReportsTest`: `test_reports_accept_empty_optional_query_filters`.
    - `VehicleAvailabilityQueryTest`: `test_available_vehicles_query_with_empty_ends_at_and_category_id`.
- **Vitest:**
  - `npx vitest run resources/js/modules/vehicle-rental` $\rightarrow$ 28 suites passed (121 tests).
- **TypeScript:**
  - `npm run typecheck` $\rightarrow$ Passed with 0 errors.
- **ESLint:**
  - `npm run lint` $\rightarrow$ Passed with 0 errors.
- **Vite Build:**
  - `npm run build` $\rightarrow$ Built in 8.82s without errors.
