# Import collation portability fix

Date: 2026-08-23

## Problem

The item import failed in phpMyAdmin with MariaDB error `#1267 - Illegal mix of collations` while comparing a temporary staging UOM code with `unit_of_measures.code`.

The target schema uses `utf8mb4_unicode_ci`, while temporary tables inherited `utf8mb4_general_ci` from databases created with that default collation.

## Changes

- Added an explicit `utf8mb4` character set and `utf8mb4_unicode_ci` collation to every temporary staging and guard table in all three portable import files.
- Applied the correction consistently to item/price, customer/vehicle/current-owner, and supplier imports so later business-key joins cannot fail for the same reason.

## Verification

- Created an isolated verification database whose explicit default collation was `utf8mb4_general_ci` while retaining the target tables' `utf8mb4_unicode_ci` definitions.
- Successfully ran all three imports and then ran all three imports a second time.
- Both runs produced 699 items, 1,265 current prices, 1,403 customers, 1,976 vehicles, 1,827 current ownerships, and 3 suppliers.
- Confirmed zero duplicate current price scopes, zero vehicles with multiple current owners, and zero orphaned current customer ownerships.
- Removed the isolated verification database after testing; the local `laravel` database was not changed.
