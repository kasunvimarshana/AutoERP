# Local database migration-test incident

Date: 2026-09-24

## Incident

During verification of the imported vehicle-history migrations, `php artisan migrate:fresh --env=testing --force` was run. This repository has no `.env.testing`. Unlike PHPUnit, the standalone Artisan command did not receive the SQLite settings declared in `phpunit.xml`; it therefore connected to the local MySQL `laravel` database and rebuilt it.

This corrects the preceding vehicle-history change record's statement that the migration smoke test ran against an isolated SQLite database. The migration definitions passed, but the command was not isolated and reset the local database.

## Current evidence

- The local MySQL `laravel` database has the migrated schema but currently contains zero tenants and zero organization units.
- The old-system history import was not applied. Its command ran without `--apply` and performed zero import/ledger writes.
- MySQL binary logging is disabled, so point-in-time recovery is unavailable.
- The latest identified current-system baseline is `C:\Users\Sadheera\Downloads\laravel (9).sql` from 2026-09-01.
- The latest compatible master-data artifact is `storage/app/private/imports/2026-09-01-source-master-data.sql`.
- The baseline contains 1 vehicle, 1 customer, and 3 vehicle-service jobs. The master-data artifact can restore the documented 1,891-vehicle/1,339-customer master-data state, but it does not contain later operational records.
- `C:\Users\Sadheera\Downloads\taprjfji_autoerp (5).sql` remains the user-supplied old-system source and must not be treated as a current-system backup.

## Required recovery decision

Do not run another database write until the user selects a recovery source or supplies a newer current-system backup. The safest known fallback is to restore `laravel (9).sql`, apply the compatible 2026-09-01 master-data artifact, migrate the restored schema forward, and then reconcile records created after 2026-09-01 from any newer backup the user can provide.
