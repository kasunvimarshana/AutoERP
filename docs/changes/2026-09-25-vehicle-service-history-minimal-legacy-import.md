# Vehicle service history minimal legacy import

Date: 2026-09-25

## Request

The user approved development of a safer, reduced old-system vehicle-history import. The report must show Job Type, service items, and odometer information, while current-system jobs continue to use the existing operational detail flow. Missing history vehicles may be created with a verified current owner, but identifier conflicts must never be merged automatically.

## Implementation

- Added three explicit Vehicle Service-owned tables for import batches, immutable legacy history headers, and immutable history items.
- Added an allowlisted SQL dump reader. The supplied dump is parsed as inert text and no source statement is executed.
- Added `vehicle-service:import-legacy-history`, which is read-only unless `--apply` is explicitly supplied.
- Runs the import inside the platform's explicit tenant execution boundary, an atomic database transaction, and an import lock.
- Matches vehicles deterministically by VIN, chassis, normalized registration, engine number, code, then vehicle number. Ambiguous or conflicting identifiers block the import.
- Supports creating a history-referenced missing vehicle only when exactly one valid current customer owner can be resolved. Creation uses the Customer, Vehicle, and Vehicle Ownership domain services.
- Stores source identities, SHA-256 hashes, source payloads, and database unique constraints for traceability and idempotency.
- Does not create operational jobs or write inventory, invoice, payment, or finance records.
- Unified the Vehicle Service History report across current jobs and global legacy history.
- Added visible Job Type and Current System / Old System badges to the report UI.
- Legacy cards are read-only and show only the requested items and mileage context; they do not link to operational job screens.

## Applied import

Source file: `taprjfji_autoerp (5).sql`

Source SHA-256: `3f182446d68ddd3ed3ec29b8bf2bdc56f3140c7eefbde87cb29d723aae3d4d31`

- Imported legacy jobs: 18
- Imported legacy items: 59
- Unique history vehicles matched: 7
- Missing history vehicles: 0
- Created customers: 0
- Created vehicles: 0
- Conflicts: 0
- Orphan imported items: 0
- Job Type: all 18 imported records are `full_service`

The live master and operational counts remained unchanged at 1,339 customers, 1,891 vehicles, and 3 current Vehicle Service jobs. The combined organization-context report returns 21 records: 18 legacy and 3 current.

A transaction-consistent pre-import database backup was created at:

`storage/app/private/backups/pre-legacy-vehicle-history-import-20260925-032024.sql`

Backup SHA-256: `DC13564F089981EC6525DA56C5C3D13B81946087FA8EDBC96327DEDAD602E696`

## Verification

- Reapplying the same source returned `already_imported`; no duplicate rows were created.
- Focused SQL parser test passed with 3 assertions.
- PHP syntax checks passed.
- Frontend TypeScript check passed.
- Focused ESLint completed with zero errors and the page's pre-existing effect warning.
- Production frontend build passed.
- Export reconciliation returned all 21 rows with no missing Job Type labels.
- Legacy report rows expose no live-job links.
- `git diff --check` passed.

During migration verification, the first additive attempt exposed a missing composite candidate key before any import data was written. The two verified-empty new tables were removed, the original batch migration was corrected, and all three migrations then completed successfully. Existing application tables and data were not altered by that failed attempt.
