# Lessor and Lessee Allocation Fixes

## Context

Fixed the allocation issues found in the lessor/lessee vehicle allocation audit. The fixes keep the source of truth in the Vehicle Rental backend while aligning the UI with the corrected API contract.

## Changes

- Made allocation start odometer optional in the allocation UI so confirmed custody receive/handover events can write the actual start odometer when no known odometer is entered.
- Added activation-time protection for owner-supplied customer allocations: the linked owner/source allocation must be active, under an active owner supply agreement, for the same vehicle, and covering the full customer allocation period.
- Blocked returning an owner/source allocation to the owner while any linked customer allocation remains planned or active, including future allocations.
- Bound usage-log `agreement_id` plus `financial_side` filters to the same usage context to prevent mismatched side/agreement results.
- Exposed the usage context allocation in usage-log API responses.
- Updated agreement running-chart tables to display the side-specific allocation, so lessor running-chart rows show the lessor/source allocation while the physical running-chart link still opens the customer usage workspace.
- Updated frontend and backend contract coverage for the corrected allocation and running-chart behavior.

## Verification

- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx --reporter=dot`
- `php artisan test tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/VehicleRentalModuleBaselineTest.php tests/Feature/VehicleRental/RentalAgreementCreateTest.php`
- `php -l app/Modules/VehicleRental/Services/RentalAllocationService.php`
- `php -l app/Modules/VehicleRental/Services/RentalCustodyService.php`
- `php -l app/Modules/VehicleRental/Services/RentalUsageService.php`
- `php -l app/Modules/VehicleRental/Http/Resources/RentalUsageLogResource.php`
- `npm run typecheck`
- `git diff --check`
- `npx eslint resources/js/modules/vehicle-rental/pages/RentalAllocationPage.tsx resources/js/modules/vehicle-rental/pages/RentalAgreementDetailPage.tsx resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx resources/js/modules/vehicle-rental/vehicleRentalTypes.ts` completed with existing React Hooks warnings in `RentalAllocationPage.tsx`.
