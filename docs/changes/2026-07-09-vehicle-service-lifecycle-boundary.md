# Vehicle Service lifecycle boundary

## Context

Vehicle Service used one job `status` to represent operational progress, billing progress, and payment progress. That mixed lifecycle dimensions and made states like completed + partially billed + unpaid impossible to represent truthfully.

## Change

- Added separate Vehicle Service lifecycle enums for operational, billing, and payment state.
- Replaced the service job table's single status source of truth with explicit `operational_status`, `billing_status`, and `payment_status` columns in the owning creation migration.
- Removed the old mixed `VehicleServiceJobStatus` enum so new code cannot keep depending on the flawed shared lifecycle concept.
- Added lifecycle dimension tracking to Vehicle Service status history.
- Updated Vehicle Service model casts, job resources, list filters, operational transitions, invoice billing updates, and payment settlement updates.
- Updated main Vehicle Service frontend job list/detail/summary/invoice/history views to display separate lifecycle dimensions.
- Updated Reporting request contracts, Vehicle Service detailed report, technician work report, employee commission report, and reporting frontend filters/tables to use the split lifecycle fields instead of `job_status`.
- Updated Reporting tests to assert split lifecycle fields and to seed billing/payment status explicitly when test fixtures bypass service-layer invoice/payment sync.
- Added focused Vehicle Service lifecycle boundary tests for initial state/history, operational completion, partial/full billing, payment sync, and invalid operational transitions.
- Replaced `VehicleServiceEngineTest` single-status assertions/usages with split lifecycle assertions and expected-version-aware helpers.

## Verification

- Compared the branch against `vehicle-service-version-hardening-20260709`.
- Manually traced the affected Vehicle Service and Reporting source paths for removed `status`/`job_status` ownership.
- Spot-checked the updated Reporting test source after replacement.
- Fetched back `VehicleServiceEngineTest` after replacement and verified it imports the split lifecycle enums instead of the removed mixed enum.
- No runtime Laravel/MySQL, TypeScript, or production-like UAT suite was available in this connector session.

## Open gate before merge

- Full runtime checks must pass before merge: PHP syntax/static analysis, migrations, backend tests, frontend typecheck, and production-like UAT.
