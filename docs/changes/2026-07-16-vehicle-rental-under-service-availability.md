# Vehicle Rental blocks vehicles under service

## Context

The Vehicle Rental video audit requires rental availability to respect workshop and off-road vehicle state. The Vehicle module already defines `under_service` as a first-class vehicle status, and Vehicle Service accepts that status for active workshop work. Vehicle Rental availability previously excluded inactive, sold, blocked, and scrapped vehicles, but did not exclude vehicles under service.

## Root cause

`RentalAvailabilityService` duplicated the unavailable-status list in both the authoritative allocation assertion and the availability query. `VehicleStatus::UnderService` was absent from both lists, so an under-service vehicle could appear in rental search results and pass allocation availability validation.

## Change

- Added one named blocking-vehicle-status constant owned by `RentalAvailabilityService`.
- Included `VehicleStatus::UnderService` in that canonical set.
- Reused the same set in `assertVehicle()` and `queryAvailable()`.
- Added behavioral coverage proving an under-service vehicle is rejected by authoritative validation and excluded from availability results while an active vehicle remains available.

## Scope and relationships

No schema, model relationship, API payload, status transition, pricing, agreement, allocation, Invoice, Payment, Tax, or Finance relationship changed. The existing module boundary remains intact: Vehicle owns the status vocabulary, Vehicle Service uses the service status, and Vehicle Rental consumes the Vehicle-owned status when deciding rental availability.

## Verification

Run:

```bash
php artisan test --filter=RentalVehicleServiceAvailabilityTest
php artisan test
composer test:mysql
```

The connector environment does not provide a local repository checkout or PHP dependencies, so runtime suite results must be produced from a current checkout before production release.
