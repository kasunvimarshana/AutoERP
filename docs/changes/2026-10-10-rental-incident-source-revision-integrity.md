# Rental incident source-revision integrity (2026-10-10)

## Evidence

The authoritative Rental `VehicleUseService` writes immutable `vehicle_rental_use_history` records for each lifecycle row version. The usage/running-chart financial evidence model already references those historical revisions, so a newly introduced incident record must not silently reinterpret the referenced use after subsequent return/replacement transitions.

## Change and ownership

- The unmerged, Rental-owned incident-creation migration now includes required `vehicle_use_version`, constrained by composite FK (`vehicle_use_id`, `vehicle_use_version`) to `vehicle_rental_use_history` (`vehicle_use_id`, `row_version`).
- `RentalIncidentService::create` takes its revision **server-side from the locked Vehicle Use**, never from the client request.
- The incident model casts the revision explicitly, the API includes it for traceability, and the regression test asserts the captured version.
- This is a single forward-only incident-to-existing-history reference; no inverse relation, duplicate pricing, cross-module responsibility or additional table is introduced.

## Limits

This revision snapshot is **not** automatic incident liability or monetary authorization. PR #123 remains unmerged pending Laravel, database, frontend and production verification; prior change records are unchanged.
