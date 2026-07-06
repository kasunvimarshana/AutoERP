# Vehicle Allocation Custody Status Fix

## Summary
- Fixed the allocation lifecycle blocker seen in `storage/logs/laravel.log`: custody event creation attempted to eager-load a non-existent `attachments` relationship on `RentalCustodyEvent`.
- Removed that invalid relation from `RentalCustodyService::relations()` so custody handover/return events can be created and confirmed, allowing allocation status changes to continue through the intended custody-controlled lifecycle.
- Added a contract assertion to prevent the invalid relation from returning.

## Verification
- `php -l app/Modules/VehicleRental/Services/RentalCustodyService.php`
- `php -l tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php`
- `php artisan test tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/RentalCalculationIntegrityContractTest.php`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalCustodyPage.test.tsx --reporter=dot`
- `npm run typecheck`
