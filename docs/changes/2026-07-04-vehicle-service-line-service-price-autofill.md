# Vehicle service line service price autofill

Date: 2026-07-04

## Problem

When users added a vehicle service job line from the right-side drawer, selecting an item did not reliably populate the line pricing fields from the item's pricing context.

Vehicle service work must price line selling value from the item's service price and line cost from the item's purchase price, not from unrelated frontend defaults.

## Correction

Implemented service and purchase price autofill through the item module as the pricing source of truth:

- item lookup results now expose `resolved_service_unit_price` for lookup usage;
- item lookup results now expose `resolved_purchase_unit_price` for lookup usage;
- the item lookup resolution uses the existing item pricing resolver in `service` context, which prefers service price revisions and falls back to sales price only when no service price exists;
- the item lookup resolution also uses the existing pricing resolver in `purchase` context for cost values;
- the vehicle service line item selector now copies the resolved service unit price into `unit_price` and the resolved purchase unit price into `unit_cost` as soon as the user selects an item.

This keeps pricing ownership inside the item module while letting the vehicle service UI consume a resolved value instead of reimplementing pricing rules in React.

## Verification

- `npm run typecheck`
- `npm run lint -- resources/js/modules/vehicle-service/components/line-editor/LineSourceTypeFields.tsx resources/js/modules/vehicle-service/components/line-editor/lineForm.ts resources/js/shared/api/lookupApi.ts`
- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan test --filter=ItemEngineTest`
- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -l app/Modules/Item/Services/ItemQueryService.php`
- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -l app/Modules/Item/Http/Resources/ItemSummaryResource.php`
