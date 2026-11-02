# VehicleRental Module Request Validation, Query Decoupling, and Feature Hardening

## Overview
- Fixed HTTP FormRequest validation bug where Store request classes were applied to `index()` and `show()` query endpoints, causing GET requests to fail with HTTP 422.
- Resolved misplaced request class in `VehicleFinanceController::activate()` which erroneously required `invoice_date` from `CreateVehicleFinancePayableRequest`.
- Resolved misplaced request class in `VehicleFinanceController::refreshDueStatuses()` which erroneously required store payload rules.
- Created dedicated, decoupled FormRequest classes:
  - `RentalReservationQueryRequest` for reservations listing, filtering, and retrieval.
  - `VehicleFinanceQueryRequest` for finance agreement listing, filtering, and retrieval.
  - `ActivateVehicleFinanceAgreementRequest` for finance agreement activation verifying optimistic concurrency version.
  - `RefreshVehicleFinanceDueStatusesRequest` for refreshing installment overdue statuses.
  - `RentalExpenseQueryRequest` for expense listing, filtering, and retrieval.
  - `RentalDashboardRequest` for operational dashboard retrieval.
- Expanded `RentalDashboardService` and `RentalDashboardPage` with real-time tracking for `pending_expenses` and `active_vehicle_incidents`.
- Elevated test coverage with authenticated HTTP end-to-end controller feature tests for all reservation, vehicle finance, expense, and dashboard API endpoints.

## Changes

### 1. HTTP Request Architecture
- Added `app/Modules/VehicleRental/Http/Requests/RentalReservationQueryRequest.php` validating query parameters (`status`, `customer_id`, `requested_vehicle_id`, `requested_vehicle_category_id`, `date_from`, `date_to`, `search`, `per_page`, `page`).
- Added `app/Modules/VehicleRental/Http/Requests/VehicleFinanceQueryRequest.php` validating query parameters (`status`, `supplier_id`, `vehicle_id`, `search`, `per_page`, `page`).
- Added `app/Modules/VehicleRental/Http/Requests/RentalExpenseQueryRequest.php` validating query parameters (`expense_type`, `status`, `vehicle_id`, `customer_agreement_id`, `search`, `per_page`, `page`).
- Added `app/Modules/VehicleRental/Http/Requests/ActivateVehicleFinanceAgreementRequest.php` validating `expected_version`.
- Added `app/Modules/VehicleRental/Http/Requests/RefreshVehicleFinanceDueStatusesRequest.php` validating optional `as_at` date.
- Added `app/Modules/VehicleRental/Http/Requests/RentalDashboardRequest.php` providing tenant and organization context for dashboard metrics.

### 2. HTTP Controllers
- `app/Modules/VehicleRental/Http/Controllers/RentalReservationController.php`: Updated `index()` and `show()` to use `RentalReservationQueryRequest`.
- `app/Modules/VehicleRental/Http/Controllers/VehicleFinanceController.php`:
  - Updated `index()` and `show()` to use `VehicleFinanceQueryRequest`.
  - Updated `activate()` to use `ActivateVehicleFinanceAgreementRequest`.
  - Updated `refreshDueStatuses()` to use `RefreshVehicleFinanceDueStatusesRequest`.
- `app/Modules/VehicleRental/Http/Controllers/RentalExpenseController.php`: Updated `index()` and `show()` to use `RentalExpenseQueryRequest`.
- `app/Modules/VehicleRental/Http/Controllers/RentalDashboardController.php`: Updated `show()` to use `RentalDashboardRequest`.

### 3. Dashboard Metrics & UI
- `app/Modules/VehicleRental/Services/RentalDashboardService.php`: Added queries for `pending_expenses` and `active_vehicle_incidents`.
- `resources/js/modules/vehicle-rental/rentalDashboardApi.ts`: Added `pending_expenses` and `active_vehicle_incidents` to `RentalDashboardSummary`.
- `resources/js/modules/vehicle-rental/RentalDashboardPage.tsx`: Added KPI Summary Cards for Pending Expenses and Active Incidents linking directly to their respective operational registers.
- `resources/js/modules/vehicle-rental/RentalDashboardPage.test.tsx`: Updated mock fixtures to assert new metrics.

### 4. Automated Testing & Verification
- `app/Modules/VehicleRental/Tests/BuildsRentalFixture.php`: Added shared `loginFixture()` and `grant()` helpers for authenticated API feature testing.
- `app/Modules/VehicleRental/Tests/RentalReservationTest.php`: Added `test_reservation_api_endpoints()`.
- `app/Modules/VehicleRental/Tests/VehicleFinanceTest.php`: Added `test_vehicle_finance_api_endpoints()`.
- `app/Modules/VehicleRental/Tests/RentalExpenseTest.php`: Added `test_rental_expense_api_endpoints()`.
- `app/Modules/VehicleRental/Tests/RentalDashboardTest.php`: Added `test_dashboard_api_endpoint()`.
- Executed full PHPUnit test suite for VehicleRental: 162 passed (1,447 assertions).
- Executed full Vitest test suite: 134 test files passed (483 tests).
- Verified TypeScript compilation (`npm run typecheck`): 0 errors.
- Verified ESLint rules (`npm run lint`): 0 errors, 0 warnings.
