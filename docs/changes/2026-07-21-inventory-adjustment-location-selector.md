# Inventory adjustment location selector

## Summary

- Added a visible Location lookup beside Warehouse in the Inventory stock-adjustment form.
- Limited location choices to the selected warehouse and disabled the lookup until a warehouse is selected.
- Cleared the selected location and serial number when the warehouse changes so incompatible dimensions cannot be submitted.
- Reused the existing `warehouse_location_id` adjustment payload and hid the duplicate Location field from Optional dimensions.

## Verification

- `npm test -- --run resources/js/modules/inventory/components/workflows/AdjustmentsTab.test.ts`
- `npm run typecheck`
- `npx eslint resources/js/modules/inventory/components/workflows/AdjustmentsTab.tsx resources/js/modules/inventory/components/workflows/AdjustmentsTab.test.ts`
- `npm run build`

The complete `npm test` run continued making progress without reporting failures but exceeded the five-minute command timeout. The focused adjustment test passed independently.
