# Restore Selling discount upgrades for existing databases

**Date:** 2026-11-17
**Module:** Selling

Posting a sale failed with `MassAssignmentException` for `SaleLine.discount_type`. The September Selling table-creation migrations had already run on the local MySQL database before the November discount columns were moved into those baseline files and the original discount upgrade migrations were removed. Laravel does not rerun completed migrations, so the live `sales` table lacked three bill-discount columns and `sale_lines` lacked four line-discount columns. Eloquent's guarded-column check rejected the absent `discount_type` column. The request was inside a database transaction.

Removed the later discount columns from the two original table-creation migrations and restored the original `2026_11_10_180001` and `2026_11_10_180002` migration names under `Selling/Database/UpgradeMigrations`, one table per migration. The Selling provider now loads both the baseline and upgrade paths. Fresh databases create the historical baseline and then apply the upgrades; existing databases that already recorded those original migration names skip them, while older databases missing the columns apply them. No runtime validation or mass-assignment rules were weakened, and no relationships changed.

Before applying the local upgrade, backed up `sales`, `sale_lines`, and migration history to `storage/app/private/backups/selling-before-discount-schema-fix-2026-11-17.sql`. Both upgrades ran on local MySQL. The existing data remained unchanged: 2 sales, 2 sale lines, line total 2620.000000, and 815 invoices before and after. Existing discount amounts defaulted to zero; `SaleLine.discount_type` is now fillable because the column exists.

Verification: the full Selling workflow and module migration baseline suites passed on a fresh SQLite schema (9 tests, 111 assertions). After restoring the original upgrade migration names, the focused discount/return and migration baseline tests passed (2 tests, 16 assertions). Pint and `git diff --check` passed.
