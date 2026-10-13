# Purchase debit-note index migration placement correction — 2026-10-13

## Failure reproduced in user-supplied local verification

The user's complete local `php artisan test` transcript after the debit-note uniqueness change reported **919 passing tests, 1 failing test**: `Tests\\Unit\\Database\\ModuleMigrationBaselineTest`. That architecture test requires each `app/Modules/*/Database/Migrations/` file to contain exactly one matching `Schema::create` / `Schema::dropIfExists` pair and prohibits `Schema::table` there. The newly added `2026_10_13_170013_enforce_unique_purchase_return_debit_note.php` was an upgrade migration incorrectly placed in Purchase's fresh-baseline directory. The local frontend checks were all green.

## Root-cause correction

The Purchase module now owns both sides of the schema contract:

1. Its canonical baseline `Database/Migrations/2026_06_12_170010_create_purchase_debit_notes_table.php` declares the unique `(tenant_id, purchase_return_id)` index explicitly, alongside other baseline indexes. This is the correct new-database schema.
2. The additive **same-named** `2026_10_13_170013_enforce_unique_purchase_return_debit_note.php` now resides in `Database/UpgradeMigrations/`, not in `Database/Migrations/`. `PurchaseServiceProvider` loads both directories, consistent with the existing Invoice, VehicleRental and VehicleService migration organization.
3. The upgrade checks `Schema::hasIndex` before adding the constraint, so a fresh install whose baseline already created the index does not create it twice; an older populated database without the index receives it. The original migration basename is retained, preserving Laravel's migration ledger identity for any environment that had already applied the earlier misplaced file.
4. The former misplaced file has been removed. No test was weakened or exempted, and no financial history is discarded.

## Database safety and verification requirements

Index creation remains deliberately **fail-closed** if a populated installation already contains multiple debit notes linked to one return. Run the duplicate-source audit SQL from `docs/changes/2026-10-13-purchase-return-debit-note-uniqueness.md` before upgrading; reconcile conflicts with accountable review, never silently delete or merge financial notes.

At this commit, the affected baseline architecture test should no longer see the upgrade `Schema::table` operation in the baseline-only directory. This is a code-level verification of the root cause, **not** proof that the full test/migration suite was executed on this exact SHA. Re-run `php artisan test`, MySQL tests, fresh/upgrade/rollback migrations, and frontend checks on the new candidate commit. GitHub Actions remains unable to schedule runner jobs (issue #124), and PR #127 is not release-eligible while review and Vehicle Rental financial gates remain open.

This record is append-only; prior change records have not been changed.
