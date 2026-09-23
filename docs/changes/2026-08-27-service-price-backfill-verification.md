# Service-price backfill verification

Date: 2026-08-27

## Verification

- Cloned the current local item and price data into an isolated database without changing `laravel`.
- Ran `database/imports/2026-08-27-service-prices-from-sales.sql` against the isolated copy.
- The first run inserted 702 service-price revisions and retained the 2 existing service prices, producing 704 current service prices in total.
- Confirmed zero copied-amount mismatches and zero generated service scope-key mismatches.
- Ran the SQL a second time; it inserted zero rows and retained the same 704 current service prices.
- Removed the isolated verification database after testing.

## MariaDB compatibility correction

- Declared the target table and both source aliases in `LOCK TABLES`, as required when MariaDB reads aliases of a write-locked table.
- This preserves the intended concurrency protection while allowing the atomic self-table `INSERT ... SELECT` operation.
