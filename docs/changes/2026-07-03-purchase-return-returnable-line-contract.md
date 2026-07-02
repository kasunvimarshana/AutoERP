# Purchase return returnable-line contract alignment

Date: 2026-07-03

## Problem

The purchase return create UI expected returnable GRN lines to include frontend-only `source_line_id` and `returnable_quantity` fields. The Purchase module API returns the owning goods receipt line `id` and `remaining_returnable_quantity`, so real backend responses could load no return lines or submit the wrong source-line contract.

## Correction

Aligned the purchase return frontend with the backend returnable-line contract. The create form now treats the returned GRN line `id` as the purchase return `source_line_id`, uses `remaining_returnable_quantity` for display and "Return Selected", and keeps the payload source type explicit.

Updated the purchase source-flow test data to mirror the real API shape and added coverage that saving a referenced return submits the correct GRN line id and remaining quantity.

## Verification

- `npx vitest run resources/js/modules/purchase/PurchaseSourceCreateFlows.test.tsx --reporter=dot --silent=true`
- `npm run typecheck`
- `php artisan test app/Modules/Purchase/Tests/PurchaseOrderApiTest.php --filter partial_grn_return --stop-on-failure`
- `npm run build`
