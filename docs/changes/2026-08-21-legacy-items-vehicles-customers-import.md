# Legacy items, vehicles, customers, and ownership import

Date: 2026-08-21

## Purpose

Created two portable SQL imports that migrate the requested master data from `deziutge_pos (15).sql` into the schema and seed context supplied by `laravel (7).sql`.

## Outputs

- `database/imports/2026-08-21-legacy-items.sql`
- `database/imports/2026-08-21-legacy-vehicle-customers.sql`

## Item import

- Imports 699 active items: 582 stock items, 109 combos, 6 labour items, and 2 service items.
- Includes 4 required item categories, 36 brands including Generic, 2 referenced UOMs, 2,097 item-unit roles, 1,265 current purchase/sales prices, and 203 valid combo lines.
- Converts HTML entities in legacy names to readable text and maps the legacy product types to the authoritative AutoERP item types.
- Excludes four combo references to inactive legacy product 134 (`Finishing`) instead of creating invalid item relationships.
- Deliberately excludes quantities from `products.qty` and `product_warehouse`; opening stock belongs to an Inventory transaction and is not item-master data.

## Vehicle and customer import

- Imports 1,976 unique vehicles from 1,988 source rows. Twelve exact duplicate rows across nine registration numbers are collapsed without losing an ownership relationship.
- Includes the 1,403 customers referenced by those vehicles, 569 available customer addresses, 37 normalized makes, 315 normalized make/model pairs, and 1,827 current customer ownerships.
- Preserves 149 vehicles that have no customer relationship in the source.
- Corrects common legacy make/model spelling variants while retaining the raw source values and source IDs in metadata.
- Uses stable `LEGACY-CUS-*` and `LEGACY-VEH-*` business codes and resolves all target relationships without copying database IDs.

## Safety and verification

- Both imports resolve the target tenant and organization through organization code `AUTOERP`, currency through `LKR`, and UOMs through their business codes.
- Both use one transaction, prerequisite/collision guards, small staging batches for phpMyAdmin, and deterministic rerun behavior.
- Loaded `laravel (7).sql` into a fresh isolated MariaDB database, ran both imports successfully, and then ran both imports a second time.
- The second run preserved customer and vehicle row-version totals and did not add prices, bundle rows, customers, addresses, vehicles, or ownerships.
- Reconciled all expected counts and confirmed zero orphaned item units, item prices, bundle children, vehicle models, or ownership customers; also confirmed zero duplicate current price scopes and zero vehicles with multiple current customer owners.
- Verification databases were isolated from the project database; no production/project data was changed.
