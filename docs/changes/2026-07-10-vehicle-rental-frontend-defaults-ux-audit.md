# Vehicle Rental Frontend Defaults UX Audit

## Context

Vehicle Rental create flows had several frontend-only option lists and inconsistent defaults. Some screens required users to choose safe repeatable values every time, while others exposed unsupported or duplicate options.

## Changes

- Added rental metadata defaults and public option sets in backend configuration and metadata responses.
- Added a shared frontend rental metadata helper and reused it from the currency default hook.
- Updated reservation, agreement, allocation, custody, running chart, expense, replacement, and finance screens to use metadata-backed editable defaults where safe.
- Kept relationship records user-selected, preserved reservation/agreement/allocation-derived authoritative values, and left business-specific period dates blank where required.
- Removed unsupported expense and finance UI options, made custody odometer blank and required, and removed the duplicate blank running chart event option.
- Made customer and supplier lookup labels safe when code is missing and passed required state through to the generic lookup control.

## Verification

- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx resources/js/modules/vehicle-rental/pages/RentalReservationCreatePage.test.tsx resources/js/modules/vehicle-rental/pages/RentalExpensePage.test.tsx resources/js/modules/vehicle-rental/pages/VehicleFinancePage.test.tsx resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx resources/js/modules/vehicle-rental/pages/RentalAllocationDetailPage.test.tsx resources/js/modules/vehicle-rental/pages/RentalCustodyPage.test.tsx resources/js/modules/vehicle-rental/pages/RentalRunningChartPage.test.tsx resources/js/modules/supplier/components/RelationshipLookupSelect.test.tsx --reporter=dot`
- `php artisan test tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php`
- `npm run typecheck -- --pretty false`
- `npm run lint`
- `npm run build`
- `git diff --check`
