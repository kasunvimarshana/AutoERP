# Filter owner-agreement vehicles by authoritative ownership

## Trigger

Creating an owner-supply rental assignment could fail after submission with:

```text
The vehicle ownership does not match the selected owner agreement for the assignment period.
```

The backend correctly required the vehicle to be registered to the supplier on the owner agreement for the complete assignment period. The frontend nevertheless used the generic active-vehicle lookup, so it offered company-owned vehicles, customer-owned vehicles, vehicles owned by another supplier, and vehicles whose supplier relationship did not cover the requested period.

## Root cause

Vehicle ownership is authoritative master data owned by the Vehicle module. An owner-supply rental assignment is valid only when one `vehicle_ownerships` record has all of the following:

- the active tenant and selected vehicle;
- owner type `supplier`;
- owner id equal to the owner agreement supplier;
- ownership start at or before assignment start; and
- no ownership end, or an ownership end at or after assignment end.

The Rental backend already enforced this invariant. The defect was allowing an invalid selection to reach submission.

## Changes

- Added an assignment-owned lookup endpoint for owner-agreement vehicles.
- The lookup accepts the owner agreement and explicit-offset assignment period.
- It returns active vehicles only when the Vehicle module ownership history matches the owner-agreement supplier and covers the complete period.
- Owner-supply assignment and owner-supply replacement vehicle controls now use this contextual lookup.
- Changing the owner agreement or assignment period clears a previously selected vehicle so stale eligibility cannot be submitted.
- The empty state directs the user to add or correct the record under Supplier Vehicles.
- Vehicle options show make, model, status, and current odometer.
- The final backend guard remains authoritative and now returns an actionable remediation message.

## Module ownership

- Vehicle remains the single source of truth for legal/operational ownership relationships and their effective dates.
- Vehicle Rental consumes that ownership history but does not create, rewrite, or infer it.
- Rental agreement creation does not silently manufacture a Supplier Vehicle relationship.
- Historical ownership records and rental assignments are not mutated.

## Immediate data correction

For a vehicle that should be supplied under an owner agreement:

1. Open **Supplier Vehicles**.
2. Create or verify the relationship using the same supplier selected on the owner agreement.
3. Select the correct vehicle.
4. Set a truthful relationship type such as `third_party`, `leased`, or `rented`.
5. Set the ownership start on or before the rental assignment start.
6. Leave the end open for an open-ended assignment, or set it on/after the rental assignment end.
7. Reopen the owner agreement vehicle lookup.

## Verification

```bash
php -l app/Modules/VehicleRental/Http/Requests/RentalOwnerVehicleLookupRequest.php
php -l app/Modules/VehicleRental/Http/Controllers/RentalLookupController.php
php -l app/Modules/VehicleRental/Services/Validation/RentalAssignmentSourceGuard.php
php artisan test --filter=RentalCrudInterfaceBoundaryTest
php artisan test --filter=RentalAssignmentSourceValidationContractTest
php artisan test --filter=VehicleRental

npx vitest run resources/js/modules/vehicle-rental/vehicleRentalUxFoundation.test.ts
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
```

GitHub Actions and paid tools are not required or used.
