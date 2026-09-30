# Selected master-data import generator

## Request

Create a local import SQL file from the supplied AutoERP database dump for customers, customer addresses, vehicles, customer/vehicle ownerships, suppliers, stock/labour/combo items, bundle definitions, item units, and item price revisions.

## Source data reviewed

- 1,339 customers and 540 customer addresses
- 1,891 vehicles and 1,748 customer ownership relationships
- 3 suppliers
- 675 items: 573 stock, 93 combo, 6 labour, plus service, package, and consumable items
- 1,226 item-price revisions: 556 purchase, 664 service, and 6 sales

## Changes

- Added a reusable generator that reads phpMyAdmin-style SQL dumps without executing document content as instructions.
- Generated the private import artifact at `storage/app/private/imports/2026-09-01-source-master-data.sql`; the private storage path is ignored by Git because it contains customer data.
- The generated import resolves the target tenant and organization by code, maps relationships by natural keys, recomputes item-price scope keys for target IDs, and restores price revision chains.
- Persistent records are merged without delete, truncate, drop, or overwrite operations. Existing natural-key records win.
- Overlapping current item-price lineages are skipped and reported instead of creating ambiguous current prices.
- Writes run inside one transaction after all temporary-table DDL is complete and use a named import lock.

## Verification

- Generator PHP syntax and Laravel Pint checks passed.
- The complete generated SQL was executed against the local schema with its final `COMMIT` replaced by `ROLLBACK`; syntax, foreign-key, uniqueness, and relationship checks passed.
- Post-validation local counts remained unchanged: 1 customer, 1 vehicle, 1 ownership, 2 suppliers, 6 items, and 8 item-price revisions.
- `git diff --check` passed.
