# Runtime test failure follow-up

## Context

A local `php artisan test` run reported 583 passing tests and 17 failures. The failing buckets were:

- Item Base UOM usage audit querying the removed `vehicle_service_jobs.status` column.
- Reporting test fixtures calling Vehicle Service write services without current job row versions.
- Vehicle Service lifecycle history tests relying on ambiguous relation ordering.

## Change

- Updated Item Base UOM usage audit to use `vehicle_service_jobs.operational_status` for Vehicle Service open-line detection instead of the removed mixed `status` column.
- Kept the Vehicle Service status-history relation order-neutral so model relationships do not encode presentation ordering.
- Moved latest-first status-history ordering to the Vehicle Service controller presentation boundary.
- Updated the focused Vehicle Service lifecycle boundary test to read status history in deterministic insertion order.

## Remaining follow-up

- Update `Modules\Reporting\Tests\TechnicianWorkReportTest` fixture helpers so direct calls to `VehicleServiceLineService::create()` and `VehicleServiceEmployeeAssignmentService::create()` pass the current `VehicleServiceJob::row_version`.
- Re-run the failed Item, Reporting, and Vehicle Service test groups locally.
- Re-run the full production gate after the targeted failures pass.

## Verification

- Source-level fetch-back verified the Item audit query no longer references `parents.status`.
- Source-level fetch-back verified Vehicle Service status-history relation is order-neutral and controller status-history output orders latest first.
- Source-level fetch-back verified focused lifecycle boundary test uses deterministic status-history retrieval.
- Runtime re-run was not available in this connector session.
