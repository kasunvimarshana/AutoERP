# Rental allocation active-allocation production fixes

## Context

Follow-up fixes for the active vehicle allocation audit. The work focused on making allocation activation, custody confirmation, replacement custody, and running-chart selection production-safe without moving responsibilities out of the Vehicle Rental module.

## Changes

- Blocked vehicle allocation activation unless the owning rental agreement is active.
- Blocked daily running-chart usage unless both the customer allocation agreement and linked owner supply agreement are active.
- Made custody activation events always pass through `RentalAllocationService::activate()`, so stale draft custody confirmations cannot write odometer data onto cancelled, returned, replaced, or otherwise invalid allocations.
- Restricted the public custody request to normal custody event types and prohibited public `replacement_id` input.
- Added service-level replacement custody ownership checks so replacement return/handover events must reference the matching replacement workflow and allocation.
- Refreshed custody allocation details after create/confirm actions so subsequent submissions use the latest allocation `row_version`.
- Replaced the running-chart fixed first-page allocation dropdown with the searchable/paginated rental allocation lookup filtered to active customer rental allocations, while directly loading URL-selected allocation details.
- Added contract coverage for the active-agreement, replacement custody, stale custody version, and running-chart lookup contracts.

## Verification

- `php artisan test tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/VehicleRentalModuleBaselineTest.php`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalCustodyPage.test.tsx --reporter=dot`
- `npm run typecheck`
