# Vehicle Rental Hook Warning Cleanup

## Context

Followed up after the lessee/lessor agreement fixes to remove the remaining React Hooks lint warnings in vehicle-rental pages.

## Changes

- Deferred immediate effect reset updates in vehicle-rental pages so effects no longer synchronously call state setters.
- Covered reservation loading, agreement/allocation URL-param syncing, ownership lookup reset/loading states, custody allocation loading, replacement ownership lookup, and running-chart allocation loading.
- Kept existing fetch, selection, and submit payload behavior unchanged.

## Verification

- `npm run lint`
- `npm run typecheck`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx resources/js/modules/vehicle-rental/pages/RentalCustodyPage.test.tsx resources/js/app/navigation/navigationUtils.test.ts --reporter=dot`
- `php artisan test tests/Feature/VehicleRental/RentalAgreementCreateTest.php tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/RentalCalculationIntegrityContractTest.php tests/Unit/VehicleRental/VehicleRentalModuleBaselineTest.php`
- `git diff --check`
