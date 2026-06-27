# Vehicle ownership source-of-truth correction

## Context

Vehicle ownership was persisted independently by Customer, Supplier, and Vehicle modules. The three tables, APIs, permissions, services, and lifecycle rules could disagree and created circular module dependencies.

## Decision

`vehicle_ownerships` is the sole persistence source. Vehicle owns relationship lifecycle, concurrency, validity periods, immutable snapshots, supersession, and permissions. Customer and Supplier only resolve an active owner identity into a snapshot through owner-provided resolvers.

## Changes

- Removed `customer_vehicles` and `supplier_vehicles` tables, models, services, routes, resources, seeders, and permissions.
- Added typed owner resolution for customer, supplier, and company ownership.
- Added immutable owner code/name snapshots and revision lineage.
- Added optimistic `row_version`, deterministic row locking, overlap validation, current/active guards, explicit end, and non-destructive supersession.
- Removed destructive ownership delete behavior.
- Migrated Vehicle Service, Vehicle Rental, Reporting, tests, and filtered Customer/Supplier UI views to the canonical API.
- Removed runtime schema-probing from Customer/Supplier ownership blockers.
- Replaced mutable Edit/Delete UI with Supersede/End actions and version-aware commands.

## Verification

- Changed PHP lint: 53 files, zero syntax failures.
- Changed internal imports: 333 checked, zero missing.
- Migration inventory: 249 files, 249 unique created tables, no patch migrations.
- TypeScript: zero diagnostics.
- ESLint: zero errors and zero warnings.
- Vitest/Vite blocked by the provided dependency snapshot missing `@rollup/rollup-linux-x64-gnu`.
