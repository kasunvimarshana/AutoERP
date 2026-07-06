# Vehicle service line hidden description

Date: 2026-07-05

## Problem

The add-line drawer still showed a separate `Description` input even though the line description is currently expected to come from the selected item or line type. That extra field reduced the visible width available for the item selector and made the dropdown harder to scan.

## Correction

Removed the visible `Description` input from the vehicle service add-line drawer and kept the required description value populated automatically:

- new line defaults now start with the selected line type label as the hidden description;
- changing line type refreshes that hidden description to the selected type label;
- selecting an item replaces the hidden description with the item name;
- line payload generation now falls back to the line type label when needed so the backend `description` requirement remains satisfied;
- the item selector now spans more width in the drawer for clearer dropdown visibility.

## Verification

- `npm run typecheck`
- `npm run lint -- resources/js/modules/vehicle-service/components/line-editor/LineSourceTypeFields.tsx resources/js/modules/vehicle-service/components/line-editor/LineBasicFields.tsx resources/js/modules/vehicle-service/components/line-editor/lineForm.ts`
