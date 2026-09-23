# Current legacy vehicle and owner export

Date: 2026-08-12

## Purpose

Created a portable SQL import for the current database's imported legacy vehicles and their current customer owners.

## Behavior

- Exports 1,888 active `LEGACY-VEH-%` vehicles and 1,745 current customer ownerships.
- Includes the 1,336 referenced customers, 540 customer addresses, 36 vehicle makes, and 386 vehicle models required by those relationships.
- Sets both vehicle number and registration to the exported registration value.
- Resolves organization units and relationships by stable business codes instead of copying database IDs.
- Imports 143 vehicles without an ownership row because the source database has no current customer owner for them.
- Uses transaction guards to reject missing prerequisites, identity conflicts, registration conflicts, and conflicting current owners.
- Splits source data into small insert batches for phpMyAdmin compatibility and supports safe reruns without duplicate records.

## Output

- `database/imports/2026-08-12-current-legacy-vehicles-current-owners.sql`

## Verification

- Executed the complete import twice inside a rollback-only transaction against the current database.
- Both passes reconciled 1,888 vehicles, 1,745 current ownerships, 1,336 customers, and 540 addresses.
- The first pass synchronized all vehicle numbers to registration and incremented row versions exactly once; the second pass made no further version changes.
- Source-to-target maps reconciled all customers, makes, models, and vehicles.
- Rollback restored the original database snapshot, so verification made no permanent changes.
