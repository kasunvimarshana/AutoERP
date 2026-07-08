# Lessor and Lessee Agreement End-to-End Fixes

Date: 2026-07-08

## Context

- Followed up on the lessor and lessee agreement end-to-end audit findings.
- Kept fixes scoped to the vehicle-rental owners and the finance/payment owner where money is applied.

## Changes

- Made rental deposit forfeiture require the selected deposit receipt payment, expected payment version, and allocation date, then allocate the deposit payment to the invoice before recording the forfeiture link.
- Aligned billing-period validation with inclusive calculation logic so same-day billing periods are accepted.
- Advanced the agreement aggregate `row_version` after successful allocation creation, user-driven rate version changes, and calculation creation, and returned the updated version where the UI needs to continue safely.
- Added service-level duplicate validation for rate components by component code and vehicle category, including the global nullable-category scope that database uniqueness cannot enforce portably.
- Centralized running-chart event-to-rate-component mapping and derived stored event units from active rate components instead of accepting free-text units from the UI/API.
- Removed the running-chart event unit input and updated deposit, allocation, billing, metadata, and type contracts to match the backend behavior.
- Updated vehicle-rental contract tests to cover the corrected financial, concurrency, billing, rate, and event-unit contracts.

## Verification

- `php -l` on the changed vehicle-rental PHP services and controllers.
- `php artisan test tests/Unit/VehicleRental/RentalAgreementIntegrityContractTest.php tests/Unit/VehicleRental/RentalCalculationIntegrityContractTest.php tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php`
- `php artisan test tests/Feature/VehicleRental tests/Unit/VehicleRental`
- `npm run typecheck -- --pretty false`
- `npm test`
