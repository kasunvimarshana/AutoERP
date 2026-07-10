# Driver Assignment Datetime Serialization Fix

## Context

Assigning a driver from a vehicle allocation detail page could fail with `Driver assignment must be inside the vehicle allocation period.` The allocation period was rendered into `datetime-local` fields, but the driver assignment request sent those local datetime strings back without converting them to the same ISO instant format used by allocation and replacement flows.

## Changes

- Converted driver assignment `assigned_from` and `assigned_to` values to ISO instants before calling the vehicle rental API.
- Kept the backend allocation-period validation unchanged as the source of truth.
- Added a focused allocation detail page regression test for the driver assignment payload.
- Updated the vehicle rental contract test to require ISO serialization for driver assignment dates.

## Verification

- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx resources/js/modules/vehicle-rental/pages/RentalAllocationDetailPage.test.tsx --reporter=dot`
- `php artisan test tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php`
- `npm run typecheck -- --pretty false`
