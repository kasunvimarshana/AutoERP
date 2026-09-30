# Current item export

Date: 2026-08-12

## Purpose

Created a portable SQL import containing the current tenant's complete active item catalogue and its required item-owned relationships.

## Behavior

- Exports 677 active items: 573 stock, 95 combo, 6 labour, and 3 other item types.
- Includes 5 item categories, 36 brands, 3 referenced UOMs, and the referenced LKR currency.
- Includes 1,831 item-UOM records, 1,228 current item prices, and 222 combo/package bundle lines.
- Resolves all relationships by organization-unit, item, category, brand, UOM, and currency business codes instead of copying database IDs.
- Preserves the distinct `DIFOLT` and `Generic` brands from the current database.
- Uses conflict guards, row locks, and one transaction to prevent partial or ambiguous imports.
- Splits source records into small insert batches for phpMyAdmin compatibility and safely reuses matching records on reruns.
- Excludes stock quantities because opening stock is a separate inventory transaction, not item-master data.

## Output

- `database/imports/2026-08-12-current-items.sql`

## Verification

- Executed the complete import twice inside a rollback-only transaction against the current database.
- Both passes reconciled all 677 items, 1,831 item units, 1,228 prices, and 222 bundle lines without changing counts.
- Source-to-target maps reconciled all categories, brands, referenced UOMs, currency, and items.
- Rollback restored the original database snapshot, so verification made no permanent changes.
