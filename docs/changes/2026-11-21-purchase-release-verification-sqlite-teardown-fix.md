# Purchase Release Verification Test Teardown SQLite Guard Fix

**Date:** 2026-11-21  
**Modules:** Purchase, Testing

### Summary of Changes

1. **`tests/Database/PurchaseReleaseVerificationTest.php` Teardown Guard:**
   - In `tests/Database/PurchaseReleaseVerificationTest.php`, `tearDown()` unconditionally called `DB::disconnect();` prior to `parent::tearDown()`.
   - In SQLite `:memory:` environments, disconnecting drops the entire in-memory database, which caused `parent::tearDown()` (executing `DatabaseMigrations` rollback) to throw `QueryException: SQLSTATE[HY000]: General error: 1 no such table: migrations` when running `test_populated_currency_upgrade_preserves_amounts_and_does_not_guess_historical_currency`.
   - Guarded `DB::disconnect()` to only execute when `DB::connection()->getDriverName() !== 'sqlite'`.

### Verification Record

- `php artisan test tests/Database/PurchaseReleaseVerificationTest.php --filter=test_populated_currency_upgrade_preserves_amounts_and_does_not_guess_historical_currency`: Passed (15 assertions).
- `php artisan test`: 1,259 passed (13,095 assertions) across the entire test suite with 0 failures.
- `vendor/bin/pint --test tests/Database/PurchaseReleaseVerificationTest.php`: Passed cleanly.
