# Show batch details in inventory availability

## Request

Make separate stock lines for the same item distinguishable when they belong to different batches, and show item codes in their own column.

## Changes

- Added a Code / SKU column beside Item and show the batch number under the item name when the stock line has a batch.
- Kept batch-specific stock lines separate and included batch number in Availability CSV exports.
- Hydrated batch details in historical stock balance responses so the date filter shows the same batch identity as current stock.

## Verification

- `php -l` passed for the stock controller and historical balance service.
- `npm run typecheck` passed.
- `git diff --check` passed.
- Automated tests were not run.
