# Fix Vehicle Rental assignment period validation

## Trigger

Two handover failures exposed separate assignment-state problems:

```text
The selected driver already has an overlapping rental assignment.
Active owner-supply assignment must cover the complete customer-use operational period.
```

The linked owner-supply/customer-use driver exclusion was already correct. The remaining driver failure represents another planned or active assignment, but the generic message did not identify it.

The owner-source coverage failure could be created by the system itself: source lookup and planning validation compared calendar dates only, while handover and replacement compared exact timestamps. A customer-use assignment could therefore be planned a few minutes beyond its owner-supply source and become impossible to hand over later.

## Root cause

- Owner-source lookup used `whereDate` for start and end coverage.
- Planning validation reduced assignment periods to calendar dates.
- Operational validation used exact datetimes.
- Driver-overlap validation returned only a generic message for a real unrelated conflict.

## Fix

- Use exact minute-normalized timestamps in owner-source lookup.
- Use one exact period-coverage rule for planning, handover, and replacement.
- Keep planned and active owner-supply assignments eligible for customer planning.
- Keep the existing linked owner/customer driver exclusion unchanged.
- When an unrelated driver conflict exists, include its agreement reference, side, status, and period in the error.

## Existing invalid records

This change does not silently mutate assignment history. A customer-use assignment already planned outside its owner source must be cancelled and recreated within the source period. A genuine unrelated planned assignment must be cancelled, or an active assignment must be returned, before reusing its driver.

## Preserved integrity

- No schema or relationship changes.
- No weakening of driver overlap prevention.
- No automatic period truncation.
- No mutation of active or historical assignments.
- Tenant, organization-unit, vehicle, ownership, agreement, and optimistic-concurrency boundaries remain unchanged.

## Verification

```bash
php -l app/Modules/VehicleRental/Http/Controllers/RentalLookupController.php
php -l app/Modules/VehicleRental/Services/Validation/RentalAssignmentSourceGuard.php
php -l app/Modules/VehicleRental/Services/Validation/RentalAssignmentTimelineGuard.php
php -l tests/Unit/VehicleRental/RentalAssignmentSourceValidationContractTest.php
php artisan test --filter=RentalAssignmentSourceValidationContractTest
php artisan test --filter=VehicleRental
php artisan test
composer test:mysql

npm run typecheck
npm run lint
npm run test
npm run build
```

Paid tools and GitHub Actions are not used.
