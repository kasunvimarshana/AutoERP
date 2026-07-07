# Vehicle Allocation Follow-up Audit Findings

## Why

Vehicle allocation was reviewed again for lifecycle correctness, conflict safety, source ownership integrity, and user guidance. This record extends the earlier rental allocation audit without modifying previous change records.

## Findings

1. Replacement completion currently calls custody confirmation with the wrong method signature. `RentalReplacementService` passes only the custody event and user id, while `RentalCustodyService::confirm()` requires the custody event, expected row version, and user id.
2. Vehicle availability checks are not safe under concurrent allocation creation. `RentalAvailabilityService::assertVehicle()` checks for conflicts with `exists()` but does not lock the vehicle or the vehicle allocation timeline before inserting a new allocation.
3. Allocation create, driver assignment, and replacement submission are not version-checked at the API contract. These writes lock rows internally, but the request payloads do not carry the row versions the user reviewed.
4. Allocation source fields are not enforced as a strict mutually exclusive contract. The request accepts ownership, source allocation, and finance agreement references together, and the service can persist a finance agreement id even when the selected source type is not financed.
5. Allocation activation and closure audit fields are incomplete. The table has `activated_by` and `closed_by`, but the service only sets timestamps and `updated_by`; `activated_by` also lacks the tenant-scoped foreign key present on the other user audit fields.
6. Planned allocation cancellation is not exposed. `cancelled` is a valid allocation status and agreement closure tells users to close or cancel active/planned allocations, but the allocation routes expose no cancellation transition.
7. Driver assignment can be added to allocations that are no longer open. `assignDriver()` locks the allocation and validates the assignment period, but it does not require the allocation status to be planned or active.
8. Driver assignment conflict checks are also not safe under concurrent inserts. The conflict query locks matching rows if they exist, but it does not lock the absence of a conflicting assignment for the same employee and period.
9. Replacement driver carry-forward copies every driver assignment already loaded on the old allocation. It does not filter to current planned/active assignments, so historical completed assignments can be recreated on the replacement allocation.
10. Allocation and replacement vehicle selection uses the generic active vehicle lookup instead of the period-aware rental availability endpoint. Users can select vehicles that the backend later rejects.
11. Source allocation and finance agreement lookup controls are under-filtered. They do not constrain options by selected vehicle, selected period, source agreement kind, source status, or active finance coverage.
12. Allocation detail only shows the source type, not the concrete source allocation, finance agreement, or ownership context in the main detail view. The API has some related summaries available, but the UI does not surface enough source context for review.

## Verification

- Read recent `/docs/changes` records before review.
- Reviewed allocation, availability, replacement, custody, request, route, migration, resource, API, lookup, and allocation UI files.
- No runtime behavior was changed in this audit pass.
