# Item and batch price default UOM/currency

## Problem

New item-price and batch/lot-price forms opened without a selected UOM or currency even though the item API already returned its base UOM and the tenant base currency.

## Changes

- Passed the persisted item base UOM and tenant base currency from the item edit page into the pricing tab.
- New item-price revisions now preselect those item defaults.
- New batch/lot-price revisions now preselect the same defaults.
- Superseding an existing price continues to preserve that revision's own UOM and currency instead of replacing them with the item defaults.
- Added a focused UI regression test covering both create forms.

No database schema or backend API changes were required.

## Verification

- `vitest run resources/js/modules/item/components/ItemPriceTab.test.tsx --reporter=dot`
- `tsc --noEmit`
- ESLint on the changed item pricing files
- `vite build --logLevel error`
- `git diff --check`
