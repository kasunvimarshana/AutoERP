# Lessor Agreement, Allocation, Running Chart End-to-End Fixes

Date: 2026-07-07

## Context

Fixed the remaining findings recorded in `2026-07-07-lessor-allocation-running-chart-audit-findings.md`.

## Changes

- Enforced active driver assignments in the running-chart backend path.
  - `RentalUsageService` now requires the locked driver assignment row to be `active` before usage can be created.
  - This keeps planned, completed, or cancelled assignments out of operational usage and driver overlap checks.

- Aligned running-chart event capture with rate configuration and calculation.
  - Added the missing `pass` rate component code.
  - Calculation now maps billable event quantities through `RentalUsageEventType` enum values instead of raw event strings.
  - The agreement create UI now separates core rates from event/recovery rates and exposes the event rate components users can later record in the running chart.

- Made the deposit agreement-kind invariant explicit in schema.
  - `rental_deposit_requirements` now stores an enum `agreement_kind` limited to `customer_rental`.
  - The deposit requirement agreement foreign key now includes agreement kind and customer, tying deposits to customer-rental agreements at the database boundary.
  - The deposit model and agreement service now persist/cast the agreement kind explicitly.

## Verification

- `php -l` on modified Vehicle Rental PHP files.
- `php artisan test tests/Unit/VehicleRental/RentalAgreementIntegrityContractTest.php tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/RentalCalculationIntegrityContractTest.php tests/Feature/VehicleRental/RentalAgreementCreateTest.php`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx resources/js/modules/vehicle-rental/pages/RentalRunningChartPage.test.tsx --reporter=dot`
- `npm run typecheck`
- `npm run lint`
- `git diff --check`

Result: focused backend tests passed with 18 tests and 355 assertions; available frontend agreement-page tests passed with 7 tests.
