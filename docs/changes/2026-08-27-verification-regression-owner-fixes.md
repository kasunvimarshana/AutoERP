# Verification regression owner fixes

Date: 2026-08-27

## Purpose

Resolve the three independent failures exposed by local full-suite verification after the Vehicle Rental removal cleanup, without changing the retired Vehicle Rental runtime or unrelated business behavior.

## Changes

- Vehicle Service now keeps `manual_job_card` and `next_service_mileage` in the canonical `vehicle_service_jobs` create migration and removes the later module-local `Schema::table()` patch migration, restoring the one-table-per-file module migration baseline.
- The Vehicle optional-odometer contract test now owns its database setup through `RefreshDatabase` before asserting the live `vehicles.odometer_reading` schema, so the test no longer depends on suite execution order.
- The Vehicle Service inventory-flow source contract now expects the line-create API's authoritative `mutation.rowVersion` instead of the obsolete `expectedVersion + 1` implementation detail.

## Scope boundary

- No Vehicle Rental runtime, route, provider, migration, report, navigation, or entitlement is restored.
- No Vehicle Service inventory runtime behavior is changed; the frontend assertion is aligned with the implementation that already consumes the backend mutation row version.
- No Vehicle optional-odometer business behavior is changed.

## Verification

The reported pre-fix run had two Laravel failures (`ModuleMigrationBaselineTest`, `VehicleOptionalOdometerContractTest`) and one Vitest failure (`vehicleServiceJobLineInventoryFlow.test.ts`), while TypeScript typecheck, lint, and production build completed successfully.

Re-run `php artisan test`, `npm run typecheck -- --pretty false`, `npm run test`, `npm run lint`, and `npm run build` from a normal checkout after pulling this change.
