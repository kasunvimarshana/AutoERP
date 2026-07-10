# Vehicle Rental Currency Default UX Fix

## Context

Vehicle Rental money-entry screens needed consistent currency loading. Agreement creation already had a local defaulting path, but reservations, rental expenses, and vehicle finance agreements still required users to manually choose currency even when the tenant base currency or selected party/agreement currency was known.

## Changes

- Added a Vehicle Rental frontend currency-default hook that loads tenant base currency from rental metadata, applies party or agreement defaults while untouched, and preserves manual user changes.
- Updated agreement create/edit, reservation create, rental expense create, and vehicle finance create flows to use the shared defaulting behavior.
- Extended rental agreement lookup options with the agreement currency so expense recoveries/deductions can default from the selected target agreement without locking the field.
- Added focused frontend coverage for tenant defaults, party/agreement defaults, reservation conversion currency preservation, and manual overrides.

## Verification

- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx resources/js/modules/vehicle-rental/pages/RentalExpensePage.test.tsx resources/js/modules/vehicle-rental/pages/RentalReservationCreatePage.test.tsx resources/js/modules/vehicle-rental/pages/VehicleFinancePage.test.tsx --reporter=dot`
- `npm run typecheck -- --pretty false`
- `npm run lint`
- `npm run build`
- `php artisan test tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/VehicleFinanceProductionReadinessContractTest.php`
- `git diff --check`
