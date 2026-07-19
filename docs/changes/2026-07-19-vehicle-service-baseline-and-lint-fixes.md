# Vehicle Service baseline and lint fixes

Date: 2026-07-19

## Problem

The Vehicle Service job type had been added through a later `Schema::table()` migration. That violated the repository's fresh-baseline contract, which requires one explicit create/drop table pair per module migration, and caused `ModuleMigrationBaselineTest` to fail.

The Vehicle Service line action component also accepted a `line` prop that it no longer used, causing the full frontend lint check to fail.

## Change

- moved the enum-backed `vehicle_service_jobs.type` column and its `full_service` default into the table's owning create migration;
- deleted the later add-column patch migration;
- removed the unused `line` prop from `LineActions` and both callers while preserving the existing edit/remove callbacks;
- made no relationship, foreign-key, index, or business-behavior changes.

## Deployment safety

This repository treats the module migrations as a clean disposable baseline. A persistent database that has not already executed the deleted add-column migration must be rebuilt from the corrected baseline, or must receive that schema upgrade before deploying the squashed source. Databases that already executed the migration retain the column; deleting the source migration does not drop deployed data.

## Verification

- `php -l app/Modules/VehicleService/Database/Migrations/2026_06_12_190001_create_vehicle_service_jobs_table.php`
- `php artisan test tests/Unit/Database/ModuleMigrationBaselineTest.php`
- isolated in-memory SQLite `php artisan migrate:fresh --seed --force`
- `php artisan test`
- `npm run lint`
- `npm run typecheck -- --pretty false`
- `npm test`
- `npm run build`
- `git diff --check`

All checks passed. The configured application database was not modified during verification.
