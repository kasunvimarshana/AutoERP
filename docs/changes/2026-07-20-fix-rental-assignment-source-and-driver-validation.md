# Fix Vehicle Rental assignment source and driver validation

## Request

Correct Vehicle Rental assignment creation failures where a valid owner-supply/customer-use chain was rejected as a driver overlap, and where users could select an owner-supply assignment that did not cover the requested customer-use period.

## Root cause

The driver timeline query treated the linked owner-supply assignment and its dependent customer-use assignment as two unrelated physical driver bookings. The assignment-source lookup also exposed every active owner-supply assignment without considering the selected vehicle or requested period. Replacement creation retained the previous vehicle's source assignment until the user manually changed it.

## Change

- A customer-use driver overlap check excludes only its explicitly linked owner-supply source assignment. Unrelated planned or active assignments for the driver remain blocking.
- The owner-supply source lookup now filters by the selected vehicle and requires complete requested-period coverage.
- Open-ended customer-use assignments can select only open-ended owner-supply sources.
- The assignment and replacement forms pass the selected vehicle and period into the source lookup and clear a selected source whenever those inputs change.
- Replacement starts without carrying the original vehicle's owner-supply source relationship.

## Preserved controls

- Vehicle overlap prevention remains unchanged.
- Driver overlap prevention remains active for every unrelated assignment.
- Owner-supply assignments must still be active, use the same vehicle, and cover the complete customer-use period.
- Company-owned customer-use assignments remain valid without an owner-supply source.
- Ownership, agreement-period, availability, tenant, organization-unit, and optimistic-concurrency checks remain unchanged.
- Backend validation remains authoritative even though the UI now filters invalid source choices.

## Relationship review

No schema relationship was removed or added. The customer-use assignment to owner-supply source relationship remains the correct evidence of an owner-provided vehicle. The only relationship correction is that a replacement vehicle no longer inherits the previous vehicle's source assignment, because that source belongs to a different vehicle timeline.

## Scope

Vehicle Rental assignment validation, source lookup, assignment/replacement form state, focused regression tests, and this append-only record only. Invoice, Payment, Tax, Finance, Reporting, and database schema are unchanged.
