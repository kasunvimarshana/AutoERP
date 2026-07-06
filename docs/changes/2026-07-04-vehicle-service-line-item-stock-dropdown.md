# Vehicle service line item stock dropdown

Date: 2026-07-04

## Problem

The add-line item lookup in vehicle service jobs only showed plain item labels. Users could not see stock availability while choosing an item, which made inventory-backed selections slower and less informed.

## Correction

Enhanced the item lookup contract and dropdown presentation for vehicle service job lines:

- item lookup results now expose `available_stock_quantity` together with the existing pricing context values;
- available stock is aggregated from inventory stock balances in the owning backend query layer and normalized as a decimal string;
- the shared lookup component now supports custom option rendering;
- the vehicle service add-line item selector now renders each item as a richer dropdown row that highlights:
  - item code and name;
  - available stock quantity with base UOM for stockable items;
  - a clear non-stock notice for service and labour items.

This keeps stock data sourced from backend module ownership while giving the user a clearer decision surface in the item dropdown.

## Verification

- `npm run typecheck`
- `npm run lint -- resources/js/modules/vehicle-service/components/line-editor/LineSourceTypeFields.tsx resources/js/shared/components/GenericLookupSelect.tsx resources/js/shared/components/LookupSelect.tsx resources/js/shared/api/lookupApi.ts`
- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan test --filter=ItemEngineTest`
- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -l app/Modules/Item/Services/ItemQueryService.php`
