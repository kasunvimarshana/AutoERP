# Rental Allocation Agreement Period Prefill

## Why

Creating a vehicle allocation could fail with `Allocation must be inside the agreement period.` The backend validation was correct, but the allocation page only kept a lookup label for the selected agreement and left the allocation period fully manual, so users could submit dates outside the agreement without any guided constraints.

## What Changed

- Loaded the selected rental agreement details on the allocation page.
- Prefilled the allocation start and end from the agreement period.
- Constrained the allocation datetime inputs to the loaded agreement period.
- Submitted allocation datetimes as ISO values to avoid browser-local datetime ambiguity.
- Cleared dependent source and finance selections when the agreement changes.
- Added a focused frontend behavior test proving the allocation form uses the loaded agreement period and submits ISO datetimes.

## Verification

- `php artisan test tests\Unit\VehicleRental\RentalEndToEndContractFixTest.php`
- `php artisan test tests\Unit\VehicleRental tests\Feature\VehicleRental\RentalAgreementCreateTest.php`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx --reporter=dot`
- `npm run typecheck`
- `npm run build`
- `git diff --check`
