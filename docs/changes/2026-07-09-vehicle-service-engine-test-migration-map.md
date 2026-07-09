# Vehicle Service engine test migration

## Why this exists

`VehicleServiceEngineTest` was a large legacy-style integration test file that referenced the removed single job lifecycle concept. It has now been migrated without adding production compatibility shims such as a `status` accessor or a `VehicleServiceStatusService::change()` wrapper.

## Completed source-truth updates

- Replaced the old mixed `VehicleServiceJobStatus` dependency with explicit lifecycle enums.
- Replaced workshop flow helper calls with `changeOperational(...)` using `VehicleServiceOperationalStatus`.
- Replaced invoice-result expectations with `billing_status` assertions.
- Replaced payment/lifecycle expectations with explicit `payment_status` assertions in the focused lifecycle boundary tests.
- Replaced resource assertions for `status` / `status_label` with `operational_status`, `billing_status`, and `payment_status` assertions.
- Removed force-filled usage of the removed `status` column from the rewritten engine test.
- Updated engine test helpers to pass the current job `row_version` into write services.

## Compatibility workarounds intentionally not added

The migration did not add any of the following legacy patches:

- `VehicleServiceJob::getStatusAttribute()`
- `VehicleServiceJob::setStatusAttribute()`
- `VehicleServiceStatusService::change()` wrapper
- Mapping `invoiced`, `partially_paid`, or `paid` back into operational status
- A shared status column retained only for tests

## Coverage retained in `VehicleServiceEngineTest`

- Job creation, inspection, mixed line totals, and supervisor commission calculation.
- Technician assignment rules and employee commission calculation.
- Inventory issue eligibility and stock availability enforcement.
- Invoice billable-line selection, duplicate invoice prevention, and billing lifecycle update.
- Partial invoice remaining quantity tracking.
- Operational workflow transitions and lifecycle history dimensions.
- Bill-to customer invoice party behavior.
- Resource decimal readability, compact relations, and split lifecycle fields.
- Tenant/cross-scope reference rejection.

## Additional focused coverage

`VehicleServiceLifecycleBoundaryTest` covers the lifecycle-specific edge cases that should remain independent from the broader engine test:

- Initial operational/billing/payment states.
- Lifecycle history dimensions.
- Operational completion not implying billing/payment completion.
- Partial and full billing transitions.
- Payment sync to partially paid and paid.
- Invalid operational backward transition rejection.

## Remaining verification gate

Full runtime checks still need to pass before merge: PHP syntax/static analysis, migrations, backend tests, frontend typecheck, and production-like UAT.
