# Vehicle service quick customer dropdown top placement

Date: 2026-07-07

## Problem

In `VehicleServiceQuickVehicleModal`, the existing-customer lookup sits close to the bottom of the modal content. Its dropdown opened downward by default, which caused the option list to overflow and become visually clipped inside the modal area.

## Correction

Added a small reusable dropdown placement option to the shared `GenericLookupSelect` and applied it to the customer lookup in `VehicleServiceQuickVehicleModal`.

- shared lookup now supports `dropdownPlacement="top" | "bottom"`;
- the quick vehicle modal customer dropdown now opens upward from the input.

## Verification

- `npm run typecheck`
- `npm run lint -- resources/js/shared/components/GenericLookupSelect.tsx resources/js/modules/vehicle-service/components/VehicleServiceQuickVehicleModal.tsx`
