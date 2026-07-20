# Fix Vehicle Rental linked-driver handover validation

## Trigger

A planned owner-supply assignment and its directly linked customer-use assignment used the same vehicle, driver, and overlapping rental period. This is one physical rental operation represented from the owner and customer commercial sides.

Owner handover failed with:

```text
The selected driver already has an overlapping rental assignment.
```

Customer handover then failed because the owner-supply source was still planned instead of active, creating a circular workflow block.

## Root cause

Driver-overlap validation already excluded the linked owner-supply source while validating a customer-use assignment. The inverse operation was missing: owner-supply handover still counted its directly linked customer-use assignment as an unrelated driver booking.

## Fix

- Keep excluding the linked owner-supply source from customer-use driver overlap checks.
- During owner-supply handover, exclude only customer-use assignments whose `source_assignment_id` points to that owner-supply assignment.
- Preserve the overlap rejection for every unrelated planned or active assignment using the same driver.

## Preserved integrity

- No schema or relationship changes.
- Vehicle overlap prevention remains unchanged.
- Owner-source status and exact operational-period coverage remain mandatory for customer handover and replacement.
- Unrelated driver overlaps remain blocked.
- Tenant, organization-unit, ownership, availability, agreement-period, and optimistic-concurrency controls remain unchanged.

## Verification

```bash
php -l app/Modules/VehicleRental/Services/Validation/RentalAssignmentTimelineGuard.php
php -l tests/Unit/VehicleRental/RentalAssignmentSourceValidationContractTest.php
php artisan test --filter=RentalAssignmentSourceValidationContractTest
php artisan test --filter=VehicleRental
php artisan test
composer test:mysql
```

Paid tools and GitHub Actions are not used.
