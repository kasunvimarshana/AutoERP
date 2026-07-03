# Inventory availability aggregates stock dimensions

Date: 2026-07-03

## Problem

Purchased stock was posted correctly into `inventory_stock_balances`, but availability reads could show zero when the user checked only item and warehouse. The stock rows were stored against a warehouse location, while the availability service used an exact balance lookup with `warehouse_location_id = null`.

## Correction

Kept the fix in the owning Inventory module. Inventory availability now validates the requested item, warehouse, and any selected optional dimensions, then aggregates matching stock balance rows when optional dimensions such as warehouse location, variant, or batch are not selected. Exact write paths still use locked exact-dimension balance records.

Added a regression test proving warehouse-level availability includes stock posted into multiple warehouse locations while a location-specific read still returns only that location.

## Verification

- `php artisan test app/Modules/Inventory/Tests/InventoryMovementTest.php --filter availability_aggregates_location_balances_when_location_is_not_selected --stop-on-failure`
- `php artisan test app/Modules/Inventory/Tests --stop-on-failure`
- `php artisan test app/Modules/Purchase/Tests --stop-on-failure`
- Live tenant-scoped availability checks returned `200.000000` and `300.000000` for the locally posted purchase stock in tenant `1`, organization unit `1`, warehouse `1`
- `git diff --check`
