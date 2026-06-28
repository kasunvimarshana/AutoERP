# Architecture Foundation and Ownership Correction

Date: 2026-06-28

## Scope

This milestone continues from the verified Item Pricing temporal baseline. It corrects foundational dependency direction and ownership defects without claiming closure of the complete 153-finding clone-118 audit.

## Decisions

- Removed the generic Extension EAV/comment/attachment subsystem. Feature modules own explicit metadata; private file operations use the dedicated PrivateObject capability.
- Consolidated Customer-, Supplier-, and Vehicle-owned relationship persistence into Vehicle-owned `vehicle_ownerships` history with immutable owner snapshots, effective periods, deterministic locking, and optimistic concurrency.
- Broke the Configuration/Tenant/OrganizationUnit dependency cycle through narrow Tenant-owned configuration-target reads and Configuration-owned adapters.
- Removed Item's concrete Inventory dependency. Item owns the base-UOM revision command; Inventory owns conversion of inventory quantities and valuations.
- Replaced Invoice's direct source-module restoration with owner-registered cancellation handlers.
- Replaced Tax's concrete business-model dependencies with immutable owner-side document, item, party, payment, and return mappings.
- Moved idempotency persistence to Idempotency, private storage to PrivateObject, and password hashing to Auth. Core retains technical primitives only.
- Consolidated tenant permission synchronization into a User-owned seeder/provisioner. Feature modules register definitions; no feature seeder writes the permissions table.
- Replaced native migration enums with bounded portable strings in the canonical create migrations.

## Data integrity

- Vehicle ownership history is not hard-deleted; ending a relationship is an explicit versioned transition.
- Current ownership is unique per vehicle and owner role. An active vehicle/owner pair cannot be duplicated.
- Owner code/name snapshots preserve historical meaning if Customer or Supplier master data changes.
- Invoice cancellation and Tax integration leave mutations in the module that owns the affected aggregate.
- Permission catalogue synchronization changes row versions only when catalogue metadata or lifecycle state changes.

## Verification

- 2,569 PHP files parsed with `TOKEN_PARSE`: 0 failures.
- 2,219 module symbols autoloaded: 0 failures.
- 2,501 module namespace/path checks: 0 mismatches.
- Production graph: 29 modules, 183 direct edges, 0 cyclic components.
- Static route/controller actions: 629 actions across 28 route files, 0 failures.
- Migrations: 246 files / 246 unique tables, no patch migrations, no native enums, no structural findings.
- TypeScript semantic check: 0 diagnostics.

## Runtime gates

Laravel/PHPUnit database execution remains blocked in this verification environment by missing PHP extensions. Vitest/Vite remain blocked by the dependency snapshot's absent Linux Rollup optional binary. No polyfill, fake binary, forced exit, schema probe, or weakened compatibility workaround was added.

## Remaining audit scope

This milestone does not close all Finance, Invoice, Payment, temporal-history, reporting, oversized-service, authorization-catalogue, database-cascade, or browser/database-backed adversarial findings from the original audit. Those remain separate evidence-based milestones.
