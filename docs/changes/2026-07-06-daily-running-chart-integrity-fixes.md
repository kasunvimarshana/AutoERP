# Daily Running Chart Integrity Fixes

## Summary
- Capped editable commercial billable/payable kilometres at the physical net operational kilometres recorded on the running chart, preventing garage or internal distance from being billed later through commercial fact edits.
- Returned refreshed usage fact resources with their usage log and rate-version context after fact updates and transitions, keeping the API relationship payload structured.
- Exposed agreement rental mode on allocation summaries and made the Daily Running Chart page require a valid active driver assignment for with-driver allocations.
- Added contract assertions for the net-distance cap, rate-version context loading, rental mode payload, and with-driver UI requirement.

## Verification
- `php -l app/Modules/VehicleRental/Services/RentalUsageFactService.php`
- `php -l app/Modules/VehicleRental/Http/Resources/RentalAllocationResource.php`
- `php -l tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php`
- `php artisan test tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/RentalCalculationIntegrityContractTest.php`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx resources/js/modules/vehicle-rental/pages/RentalCustodyPage.test.tsx --reporter=dot`
- `npm run typecheck`
