# Report filter query serialization

## Why

The shared GET query serializer converted report filter objects to `[object Object]`. The reporting API requires `filters` to be an array, so the Item Wise Ageing Report failed with HTTP 422 before its data query ran.

## Changes

- Serialize one-level object parameters with bracketed keys such as `filters[warehouse_id]`, omitting null and undefined values.
- Preserve existing scalar and repeated query parameter behavior.
- Add focused regression tests for both shapes.

## Verification

- Targeted Vitest passed (2 tests).
- TypeScript typecheck and targeted ESLint passed.
