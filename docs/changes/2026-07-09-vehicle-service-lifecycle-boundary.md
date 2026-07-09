# Vehicle Service lifecycle boundary

## Context

Vehicle Service used one job `status` to represent operational progress, billing progress, and payment progress. That mixed lifecycle dimensions and made states like completed + partially billed + unpaid impossible to represent truthfully.

## Change

- Added separate Vehicle Service lifecycle enums for operational, billing, and payment state.
- Replaced the service job table's single status source of truth with explicit `operational_status`, `billing_status`, and `payment_status` columns in the owning creation migration.
- Removed the old mixed `VehicleServiceJobStatus` enum so new code cannot keep depending on the flawed shared lifecycle concept.
- Added lifecycle dimension tracking to Vehicle Service status history.
- Updated Vehicle Service model casts, job resources, list filters, operational transitions, invoice billing updates, and payment settlement updates.
- Updated invoiceability to depend on actual remaining billable source quantities instead of trusting a potentially stale billing lifecycle after invoice cancellation.
- Updated main Vehicle Service frontend job list/detail/summary/invoice/history views to display separate lifecycle dimensions.
- Added a shared frontend `VehicleServiceLifecycleStatus` union and removed unsafe `as never` casts from the status history tab.
- Updated Reporting request contracts, Vehicle Service detailed report, technician work report, employee commission report, and reporting frontend filters/tables to use the split lifecycle fields instead of `job_status`.
- Updated Reporting tests to assert split lifecycle fields and to seed billing/payment status explicitly when test fixtures bypass service-layer invoice/payment sync.
- Added focused Vehicle Service lifecycle boundary tests for initial state/history, operational completion, partial/full billing, payment sync, and invalid operational transitions.
- Replaced `VehicleServiceEngineTest` single-status assertions/usages with split lifecycle assertions and expected-version-aware helpers.
- Wrapped the service engine tenant-isolation scenario in the selected tenant execution context so it validates cross-scope rejection inside the trusted tenant boundary.

## Verification

- Compared the branch against `vehicle-service-version-hardening-20260709`.
- Manually traced the affected Vehicle Service and Reporting source paths for removed `status`/`job_status` ownership.
- Spot-checked the updated Reporting test source after replacement.
- Fetched back `VehicleServiceEngineTest` after replacement and verified it imports the split lifecycle enums instead of the removed mixed enum.
- Fetched back the tenant-isolation engine test after patching and verified it now runs inside the selected tenant execution context.
- Fetched back the frontend lifecycle status type cleanup and verified status history no longer uses unsafe casts.
- Source-reviewed Vehicle Service status, invoice, payment, model, resource, controller, request, query, validation, and migration files for lifecycle contract consistency.
- Found and fixed a source invoiceability edge case where stale billing lifecycle could block replacement invoicing after invoice cancellation.
- No runtime Laravel/MySQL, TypeScript, or production-like UAT suite was available in this connector session.

## Open gate before merge

- Full runtime checks must pass before merge: PHP syntax/static analysis, migrations, backend tests, frontend typecheck, and production-like UAT.
