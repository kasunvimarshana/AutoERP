# Labour and combo item SQL import

Date: 2026-08-07

## Purpose

Created a MySQL import for the 93 combo items and five labour roles supplied in `labour item price updated.csv`.

## Import behavior

- Creates or reuses the labour items `Supervisor`, `Technician`, `Under Wash`, `Body Wash`, and `Finishing` without creating labour price records.
- Creates or reuses all 93 combo items using the CSV `name`, `type`, and `Service_price` values.
- Creates 214 labour bundle lines from nonblank commission cells, using the supplied commission as `item_bundles.unit_cost`.
- Marks only `Supervisor` bundle lines with `uses_job_supervisor = true`; other labour roles remain ordinary assignable labour lines.
- Uses the tenant's active `HOUR` UOM, `LKR` currency, `LABOUR` category, and `PACKAGES` category.
- Reuses exact-name items already present in tenant 1 to avoid duplicating the existing Acid Rain combos and labour roles.
- Runs atomically, locks tenant item rows during the import, and stops on code collisions, incompatible existing items, duplicate bundle relationships, overlapping future prices, or existing service-price differences.
- Preserves immutable price history by never overwriting or deleting an existing item price.

## Output

- `database/imports/2026-08-07-labour-combo-items.sql`

## Verification

- Confirmed the CSV has 93 unique combo codes, 93 unique names, valid service prices, and valid numeric commission values.
- Executed the generated SQL twice in one MySQL transaction against the configured local database, then rolled the transaction back.
- Both passes completed successfully, confirming SQL validity, relationship resolution, final guard checks, and rerun safety without retaining test data.
