# Old AutoERP data import into the migrated cPanel schema

Date: 2026-11-09

## Request

Prepare a file that replaces disposable seed/demo rows in the migrated `deziutge_auto` database with all old data from the supplied `taprjfji_autoerp (21).sql` dump. The supplied target snapshot was `deziutge_auto (2).sql`. No live cPanel database was modified during preparation.

## Work completed

- Restored both supplied dumps into separate local temporary databases. Rehearsed the target branch's migrations against the old dump, retaining the empty old running-chart table under `vehicle_rental_running_charts_legacy` and reconciling two upgrade migration records only after verifying their columns, indexes, and foreign keys were already present.
- The upgraded old schema matched the target schema in all common tables, columns, indexes, and foreign keys. The source-only `legacy_vehicle_owner_import` table was preserved as an archive.
- Generated the private, Git-ignored import artifact at `storage/app/private/imports/2026-11-09-taprjfji-to-deziutge-auto.sql`. It preserves target migration history, replaces target seed/runtime rows with the old data, and creates the source-only archive table. It is a one-time replacement import, not a merge with later writes.

## Verification

- Executed the exact artifact against an isolated copy of the supplied target dump.
- Compared all 93,885 imported rows across 267 data tables with the upgraded old source: zero row mismatches.
- Checked 1,099 foreign-key constraints: zero orphaned relationships. The target retained 298 migration records, and `php artisan migrate --force` reported `Nothing to migrate.`
- SHA-256 of the artifact: `4B5887E95087F51DA1CD5A64BBC28E72C38341EE7F2143420E564D647CAE338B`.

## Deployment boundary

The artifact has not been applied to the live cPanel database. It replaces target data with the supplied old snapshot, so the operator must take a fresh target backup, stop web and queue writes, and confirm no later target records need preservation. The old environment's `APP_KEY` and external private files were not present in the SQL dumps; encrypted values and file-backed records require separate environment and file verification.
