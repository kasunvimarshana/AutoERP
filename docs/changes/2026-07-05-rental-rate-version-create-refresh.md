# Rental Rate Version Create Refresh

## Why

Creating a new rental agreement with an inline rate version could fail during immediate activation with `expected_version` validation. The draft rate version was created successfully, but the returned Eloquent model did not reload database defaults such as `row_version` before the activation step compared the expected version.

## What Changed

- Refreshed the newly created rental rate version before returning it from `RentalRateVersionService::createDraft()`.
- Added a feature regression proving an agreement can create and immediately activate its first immutable rate version with the persisted row version.
- Extended the existing Vehicle Rental agreement contract test to guard the refreshed create path.

## Verification

- `php -l app/Modules/VehicleRental/Services/RentalRateVersionService.php`
- `php -l tests/Feature/VehicleRental/RentalAgreementCreateTest.php`
- `php artisan test tests\Unit\VehicleRental\RentalAgreementIntegrityContractTest.php tests\Feature\VehicleRental\RentalAgreementCreateTest.php`
