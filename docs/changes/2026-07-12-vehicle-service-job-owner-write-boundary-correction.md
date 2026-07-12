# Vehicle service job owner write-boundary correction

Date: 2026-07-12

## Context

A merged frontend branch had widened `VehicleServiceJob` mass assignment and added a conditional repair migration for one drifted local database. Those changes conflicted with the project's clean-baseline migration policy and weakened the Vehicle Service module's write ownership.

The authoritative create-table migration already owns `bill_to_customer_id`. A local database that predates or diverges from the current disposable baseline must be rebuilt from the baseline rather than supported by a conditional compatibility migration.

## Correction

- restored `VehicleServiceJob` to total mass-assignment guarding;
- kept create and update writes inside `VehicleServiceJobService` using explicit `forceFill()` calls after validation and scope checks;
- preserved `bill_to_customer_id` creation, update, validation, relationship loading, and regression coverage;
- removed `2026_07_09_120001_repair_vehicle_service_jobs_bill_to_customer_column.php`;
- retained the existing baseline schema as the single source of truth.

## Superseded record

This record supersedes the implementation conclusion in `2026-07-09-vehicle-service-job-bill-to-schema-repair.md`. That historical record remains append-only evidence, but its conditional repair-migration approach is no longer current.

## Verification required

- PHP syntax check for `VehicleServiceJob.php` and `VehicleServiceJobService.php`;
- `php artisan test --filter=VehicleServiceEngineTest`;
- `php artisan test --filter=ModuleMigrationBaselineTest`;
- full SQLite suite;
- full MySQL suite;
- fresh migration and seed on a disposable database.
