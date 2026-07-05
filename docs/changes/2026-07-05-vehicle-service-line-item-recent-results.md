# Vehicle service line item recent results

Date: 2026-07-05

## Problem

In the vehicle service add-line drawer, after a user searched items once and then focused the item field again, the dropdown reverted to the generic `Enter 2 characters or more` prompt. That interrupted the flow even when the user had already loaded a relevant item list moments earlier.

## Correction

Extended the shared lookup state layer and dropdown behavior to support optional recent-result reopening:

- the shared lookup store now keeps a recent-results slice for opt-in lookups;
- `GenericLookupSelect` and `LookupSelect` now support a `recentResultsKey`;
- when a lookup has recent results and the field is focused again without a new search term, the dropdown reopens with the last loaded result set instead of the minimum-character prompt;
- the vehicle service add-line `Item` lookup now uses this shared behavior, scoped by line source type.

This improves the item-search flow without changing the existing server-search rules for new queries.

## Verification

- `npm run typecheck`
- `npm run lint -- resources/js/shared/components/GenericLookupSelect.tsx resources/js/shared/components/LookupSelect.tsx resources/js/shared/state/lookupCacheStore.ts resources/js/modules/vehicle-service/components/line-editor/LineSourceTypeFields.tsx`
