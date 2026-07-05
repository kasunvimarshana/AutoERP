# Rental Allocation Audit Findings

## Why

Vehicle allocation was reviewed for lifecycle correctness, concurrency safety, source ownership integrity, and UI guidance after the recent agreement-period, owner-supply ownership, and custody event fixes.

## Findings

1. Vehicle replacement currently calls custody confirmation with the wrong signature. `RentalReplacementService` passes only the event and user id, while `RentalCustodyService::confirm()` requires the event, expected row version, and user id. The replacement flow can fail at runtime before it completes the old return and new handover transaction.
2. Allocation overlap checks are not concurrency-safe. `RentalAvailabilityService::assertVehicle()` checks conflicting allocations with `exists()` but does not lock the target vehicle or candidate allocation timeline. Concurrent allocation creates for the same vehicle can both pass the availability check.
3. Allocation and replacement writes are not fully version-checked. Allocation create, driver assignment, and replacement submission do not require the loaded agreement/allocation row version in their requests or frontend API helpers, unlike other rental transition flows.
4. Allocation source fields are not enforced as mutually exclusive backend contract rules. The request accepts nullable `source_allocation_id` and `vehicle_finance_agreement_id` for every source type, while the service stores `vehicle_finance_agreement_id` even when the allocation is not financed.
5. Allocation activation and closure audit fields are incomplete. The table has `activated_by` and `closed_by`, but activation/closure only set timestamps and `updated_by`; `activated_by` also has no tenant-scoped foreign key.
6. Planned allocation cancellation is not exposed even though `cancelled` is a valid allocation status and agreement completion/cancellation tells users to close or cancel planned/active allocations. A planned allocation without custody handover has no clean guided cancellation path.
7. Allocation source lookup controls are guided but under-filtered. Owner-source allocations and finance agreements are searched broadly instead of being constrained by selected vehicle, period, source status, and source agreement kind, so users can pick invalid records that the backend later rejects.

## Verification

- Read recent `/docs/changes` records before review.
- Reviewed VehicleRental allocation, replacement, custody, availability, request, API, and UI files.
- No code behavior was changed in this audit pass.
