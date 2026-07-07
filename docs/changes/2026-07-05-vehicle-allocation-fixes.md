# Vehicle Allocation Fixes

## Why

Vehicle allocation audit findings showed runtime replacement failure, stale-write gaps, unsafe concurrent availability checks, loose source references, incomplete audit fields, missing planned cancellation, and under-guided UI lookups.

## What Changed

- Fixed replacement custody confirmation calls to pass the draft custody event row version.
- Required reviewed row versions for allocation create, driver assignment, replacement, and planned allocation cancellation.
- Locked the selected vehicle during availability assertions so concurrent allocation and reservation writes for the same vehicle serialize before conflict checks.
- Locked the employee row before driver assignment conflict checks and blocked driver assignment on non-open allocations.
- Enforced source-field ownership rules in the allocation service so company-owned, owner-supplied, and financed allocations cannot carry unrelated source references.
- Stored finance agreement ids only for financed allocations.
- Populated `activated_by` and `closed_by`, added the missing tenant-scoped `activated_by` foreign key, and preserved the original planned allocation end while storing actual return separately.
- Added planned allocation cancellation through a version-checked backend transition and allocation detail action.
- Filtered source allocation and finance agreement lookups by selected vehicle, period, source agreement kind/status, and finance coverage.
- Replaced generic vehicle selection in allocation and replacement flows with the period-aware rental availability lookup.
- Exposed allocation ownership/source/finance context in the allocation resource and detail page.
- Added regression coverage for the fixed allocation lifecycle contracts.

## Verification

- `npm run typecheck`
- `php artisan test tests\Unit\VehicleRental\RentalEndToEndContractFixTest.php tests\Unit\VehicleRental\VehicleRentalModuleBaselineTest.php`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx resources/js/modules/vehicle-rental/pages/RentalCustodyPage.test.tsx --reporter=dot`
- `php artisan test tests\Unit\VehicleRental tests\Feature\VehicleRental\RentalAgreementCreateTest.php`
- `npm run build`
