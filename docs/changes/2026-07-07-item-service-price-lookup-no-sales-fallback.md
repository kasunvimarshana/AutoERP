# Item service price lookup no sales fallback

Date: 2026-07-07

## Problem

The vehicle service job add-line item lookup exposed `resolved_service_unit_price` using the item price resolver's service context. That resolver incorrectly fell back to the sales price when an item had no service price, so the add-line drawer showed a sales price as the service unit price.

## Correction

Adjusted the item pricing behavior in the owning item module:

- service-context price resolution now considers only the item's actual service price;
- item lookup now returns `0.000000` for `resolved_service_unit_price` when no service price exists;
- item engine coverage now verifies that a sales-only item does not leak its sales price into service-context lookups.

## Verification

- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -l app/Modules/Item/Services/ItemPriceResolutionService.php`
- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -l app/Modules/Item/Services/ItemQueryService.php`
- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan test --filter=ItemEngineTest`
