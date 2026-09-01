# Master-data import target compatibility check

## Request

Verify whether `storage/app/private/imports/2026-09-01-source-master-data.sql` can be imported safely into the schema represented by the supplied `laravel (9).sql` dump.

## Validation approach

- Loaded the complete target dump into an isolated temporary database using the native MariaDB client so its late `AUTO_INCREMENT`, index, and foreign-key alterations were included.
- Executed the exact generated import artifact against that temporary database.
- Executed the same import a second time to verify repeat-run/idempotent behavior.
- Queried final record totals, relationship orphans, and overlapping current item-price revisions.
- Did not modify the live/local `laravel` database.

## Results

- First import completed without SQL errors or warnings.
- Imported 1,338 customers, 540 customer addresses, 2 suppliers, 1,890 vehicles, 1,747 customer ownership links, 669 items, 1,811 item units, 214 bundle lines, and 1,220 item-price revisions in addition to compatible records already present in the target dump.
- Six source price lineages were deliberately skipped because the target already contained overlapping current price lineages, matching the import's conflict policy.
- Final totals were 1,339 customers, 540 customer addresses, 4 suppliers, 1,891 vehicles, 1,748 vehicle ownerships, 675 items, 1,829 item units, 218 bundle lines, and 1,228 item-price revisions.
- Customer-address, vehicle/customer ownership, item-unit, item-bundle, and item-price orphan counts were all zero.
- Overlapping current item-price count was zero.
- The second import inserted zero records in every category; only the same six conflicting source price lineages were reported as skipped.
- Verified artifact SHA-256: `FAD36783BAE3A613054B91E66A669A9BAE32C301B84D52DF15F5E667F4B685A7`.
- Removed the isolated temporary database and copied target dump after validation.
