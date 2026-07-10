# Vehicle Rental custody and allocation integrity

Date: 2026-07-11

## Context

A current-source Vehicle Rental audit found a contained concurrency and decimal-integrity gap in the custody-driven allocation lifecycle. Custody confirmation version-checked the custody event but not the vehicle allocation that confirmation activates or closes. Activation also persisted the custody start odometer in a second allocation write, and allocation/custody odometer comparisons used binary floating point.

## Changes

- Custody confirmation now requires the loaded allocation row version in addition to the custody-event row version.
- The controller, request, frontend API, custody page, and replacement workflow carry the allocation version end to end.
- Allocation activation now locks and version-checks the allocation, validates the handover odometer, and persists status plus start odometer atomically in one version increment.
- Allocation closure now locks and version-checks the allocation before applying return state and end odometer.
- Allocation and custody odometer values are normalized and compared with the shared exact `DecimalMath` service; binary float comparisons were removed.
- Replacement return and handover confirmations pass the exact old/new allocation versions they loaded.
- Contract and frontend tests were updated to protect the new request and caller behavior.

## Ownership and scope

All business-rule changes remain in the Vehicle Rental module. The shared Core decimal service is consumed without modification. No database migration, compatibility alias, raw account mapping, or unrelated module change was introduced.

## Verification

- PHP syntax checks passed for the modified allocation service, custody service, and Vehicle Rental contract test.
- Published Git blobs were verified against the syntax-checked local source hashes.
- Repository-wide call-site search found allocation activation/closure owned only by `RentalCustodyService` and custody confirmation callers only in the expected Vehicle Rental API/page/controller/test paths.
- The isolated branch diff was reviewed before PR creation.
- The full PHP, TypeScript, lint, build, and Vitest suites must be rerun after merging in a project runtime.
