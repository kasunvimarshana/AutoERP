# Lessee allocation vehicle search fix

**Date:** 2026-07-18

## Problem

The Lessee Agreement allocation workflow opened correctly, but its available-vehicle lookup could return no vehicles even when eligible vehicles existed. Vehicle Rental duplicated the Vehicle module's organization-unit scope and required an exact organization-unit match, which hid tenant-level vehicles whenever an organization unit was selected. The lookup also waited for two typed characters and the availability search supported fewer identifiers than Vehicle Registry.

## Correction

- Moved the reusable vehicle identifier search into the Vehicle model as the Vehicle-owned source of truth.
- Updated Vehicle Registry and Vehicle Rental availability queries to consume that shared search scope.
- Updated Rental availability to consume the Vehicle-owned tenant and organization-unit scope, including tenant-level vehicles and vehicles assigned to the current organization unit while excluding other units and tenants.
- Made the available-vehicle lookup load eligible vehicles when opened after the allocation period is available, while retaining typed search.
- Added focused backend and frontend regression coverage.

## Relationship review

No database relationship, API route, agreement lifecycle, allocation lifecycle, ownership rule, source-allocation rule, finance rule, reservation rule, or conflict rule changed.

Vehicle remains the owner of vehicle identity and visibility. Vehicle Rental only applies rental-specific status, allocation-conflict, and reservation-conflict rules after consuming the Vehicle-owned scope. Company-owned Lessee allocations still require a covering company ownership record; owner-supplied allocations still require a covering Lessor source allocation; financed allocations still require the applicable finance agreement.

## Verification

```bash
php artisan test tests/Feature/VehicleRental/RentalAvailabilityServiceTest.php
npm run test -- resources/js/modules/vehicle-rental/components/RentalAvailableVehicleLookupSelect.test.tsx
npm run typecheck -- --pretty false
npm run lint
npm run build
php -l app/Modules/Vehicle/Models/Vehicle.php
php -l app/Modules/Vehicle/Services/VehicleQueryService.php
php -l app/Modules/VehicleRental/Services/RentalAvailabilityService.php
git diff --check
git status --short
```
