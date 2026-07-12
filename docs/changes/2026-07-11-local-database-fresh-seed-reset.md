# Local database fresh seed reset

Date: 2026-07-11

## Context

The local MySQL database had drifted out of sync with the current Laravel migration baseline and a partial seed attempt exposed schema mismatches. The requested recovery path was a full local reset instead of a targeted repair.

## Action

- ran `artisan migrate:fresh --seed --no-interaction` with the PHP 8.3 runtime;
- allowed the command to fully rebuild the schema from scratch and reseed the baseline module data;
- verified that all migrations are now marked as ran;
- verified representative seeded table counts directly from the database.

## Verification

- `artisan migrate:status --no-interaction` shows the full migration set as ran;
- representative seeded counts after reset:
  - `tenants`: 1
  - `organization_units`: 1
  - `platform_operators`: 1
  - `warehouses`: 1
  - `finance_accounts`: 16
  - `items`: 6
  - `suppliers`: 1
  - `customers`: 1
  - `vehicles`: 1
  - `hr_employees`: 2
  - `permissions`: 255

## Scope

This was a local environment reset and reseed only. It intentionally destroyed prior local database contents in favor of a clean baseline aligned with the current code and migrations.
