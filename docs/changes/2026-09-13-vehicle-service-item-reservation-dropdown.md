# Vehicle Service item reservation dropdown correction

Date: 2026-09-13

## Reported issue

Vehicle Service reserved stock was not visible in the job-line item dropdown even though automatic reservation had been implemented.

The affected local example was batch-tracked item `39199184 - 3M COOLANT BLUE 1L`. Inventory correctly held `1.000000` reserved and `5.000000` available, but the dropdown displayed only the available quantity.

## Root cause

- Untracked item lookup responses already included `reserved_stock_quantity`.
- Batch/lot items use Inventory's separate `batches/service-options` lookup.
- That batch lookup aggregated and returned only `available_stock_quantity`; it omitted `reserved_stock_quantity` and the item's `reorder_level`.
- The frontend intentionally treats a missing reserved quantity as zero, so no `Reserved` notice appeared.
- Stock-bearing lookup endpoints also used a 60-second query cache, allowing recently reserved or released quantities to remain stale.
- The existing `reorder_level` migration was pending in the local application database.

## Implementation

- Inventory's sellable batch lookup now aggregates reserved quantity using the same tenant, organization-unit, warehouse, and location scope as available quantity.
- Aggregate available and reserved quantities are normalized through `DecimalMath`, keeping API decimal strings consistent across database drivers.
- The batch service-option resource now returns:
  - `available_stock_quantity`;
  - `reserved_stock_quantity`;
  - the owning item's `reorder_level`.
- Lookup loaders that expose live stock quantities no longer use the shared 60-second query cache. In-flight request de-duplication remains at the request/client layer, while each new dropdown search receives current stock data.
- Vehicle Service continues to render stock as `Available: <quantity> <UOM> | Reserved: <quantity> <UOM>` when reserved quantity is greater than zero.
- The existing portable `2026_09_12_000001_add_reorder_level_to_items_table` migration was run successfully against the local database.

## Integrity and ownership

- Inventory remains the single source of truth for available and reserved balances.
- Vehicle Service only consumes Inventory lookup values and does not duplicate balance calculations.
- Batch quantities remain batch-specific; reservations from unrelated batches are not combined into the displayed option.
- No reservation records or historical stock movements were modified by this correction.

## Verification

- Inventory batch management suite: **2 tests passed, 10 assertions**.
- Vehicle Service line-item field suite: **1 file, 10 tests passed**.
- TypeScript typecheck passed.
- `git diff --check` passed before the documentation record was added.
- Local runtime verification for the affected COOLANT batch returned:
  - `available_stock_quantity: 5.000000`;
  - `reserved_stock_quantity: 1.000000`.

## Deployment

Run the normal deployment commands:

```sh
php artisan migrate --force --no-interaction
npm run build
```

No data repair is required. Existing active reservations become visible through the corrected lookup response immediately after the updated frontend/backend are deployed.
