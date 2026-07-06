# Lessor Agreement Workflow

## Context

Implemented a focused lessor agreement workflow without creating a duplicate backend aggregate. The existing `owner_supply` rental agreement kind remains the Vehicle Rental module's source of truth for supplier-side lessor contracts.

## Changes

- Added shared rental agreement presentation constants and labels for customer-rental and lessor agreement UI.
- Added `/vehicle-rental/lessor-agreements`, `/vehicle-rental/lessor-agreements/create`, and `/vehicle-rental/lessor-agreements/:id` routes that reuse the existing agreement pages in lessor mode.
- Filtered the lessor list to supplier-side `owner_supply` agreements and hid the generic agreement-kind selector in that workflow.
- Made the lessor create screen submit an `owner_supply` agreement with a controlled supplier selection and no customer/deposit payload.
- Added lessor agreement entries to the vehicle rental tab navigation, tenant navigation, and route entitlement rules.
- Updated rental payable invoice actions to open the lessor agreement workflow directly.
- Added backend and frontend tests covering supplier-side lessor agreement creation and lessor UI payload/filter behavior.

## Verification

- `php artisan test tests/Feature/VehicleRental/RentalAgreementCreateTest.php`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx --reporter=dot`
- `npx vitest run resources/js/app/navigation/navigationUtils.test.ts --reporter=dot`
- `npm run typecheck`
- `php -l tests/Feature/VehicleRental/RentalAgreementCreateTest.php`
- `git diff --check`
