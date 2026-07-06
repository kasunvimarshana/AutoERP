# Lessor and Lessee Allocation Audit Findings

## Context

Audited the vehicle allocation lifecycle around the new lessor (`owner_supply`) and lessee (`customer_rental`) agreement workflows. No runtime code was changed in this pass; this record captures the end-to-end gaps found for follow-up implementation.

## Findings

1. Allocation creation UI stores a default start odometer of `0` for lessor and lessee allocations.
   - `RentalAllocationPage` defaults `startOdometer` to `0`, requires the field, and always submits it.
   - Custody activation only copies the real custody-event odometer when `start_odometer` is null.
   - Impact: normal allocation creation can permanently keep an inaccurate `0` allocation start odometer instead of the confirmed handover/receive odometer.

2. Owner source return only blocks customer allocations active at the return instant.
   - The owner return guard checks linked customer allocations where the customer allocation period covers the return timestamp.
   - Future planned or active lessee allocations that depend on the same lessor/source allocation are not blocked.
   - Impact: a lessor/source allocation can be returned to the owner while future lessee allocations still depend on it.

3. Customer handover checks that the owner source was received, but not that it is still active.
   - Customer handover allows activation when a previous owner-to-company custody event exists.
   - It does not reject a source allocation that was later returned.
   - Impact: a lessee allocation can become active against a returned lessor/source allocation, and running-chart cost context then fails later.

4. Usage-log list filters do not bind `agreement_id` and `financial_side` to the same usage context.
   - The usage query applies separate `whereHas` clauses for agreement and side.
   - Impact: API callers can request a mismatched side/agreement combination and still receive logs where one context matches the agreement and another context matches the side.

5. Lessor running-chart rows do not expose the payable context allocation.
   - The usage log resource exposes the physical customer allocation, while the cost context owns the lessor/source allocation id.
   - Impact: lessor agreement running-chart UI cannot clearly show which lessor allocation generated the payable context when a lessor agreement has multiple supplied allocations.

## Verification

- Read recent `/docs/changes` records before auditing.
- Reviewed allocation, custody, agreement, usage, lookup, resource, route, migration, and frontend agreement/allocation/running-chart code.
- Checked `storage/logs/laravel.log`; the recent tail showed tenant activation context errors, not rental allocation lifecycle errors.
- `php artisan test tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/VehicleRentalModuleBaselineTest.php`
