# Vehicle service line item UOM autofill

Date: 2026-07-05

## Problem

In the vehicle service job add-line drawer, selecting an item already autofilled description and pricing, but the UOM field still had to be selected manually even though the item lookup already returned the item's base UOM.

## Correction

Updated the vehicle service line item selection flow so the selected item's `base_uom` is copied into the line `uom` field at the same time as the item description, unit cost, and unit price are applied.

This keeps the behavior consistent and reduces manual input during job-line creation without changing the API contract.

## Verification

- `npm run typecheck`
- `npm run lint -- resources/js/modules/vehicle-service/components/line-editor/LineSourceTypeFields.tsx`
