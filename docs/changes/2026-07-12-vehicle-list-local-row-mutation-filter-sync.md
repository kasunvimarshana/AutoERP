# Vehicle list local row mutation filter sync

Date: 2026-07-12

## Problem

The vehicle list already updated row status locally after activate/deactivate, but it did not account for the active status filter. A vehicle could remain visible in the current list even after its new status no longer matched the filtered dataset.

## Change

- kept the vehicle list mutation flow frontend-local without introducing a full list reload;
- updated the activate/deactivate row mutation path to reconcile the changed vehicle against the current status filter;
- when the updated vehicle no longer matches the active status filter, the row is removed locally from the current list;
- when it still matches, the row is updated in place from the mutation response.

## Verification

- `npm run typecheck`

## Scope

This change is limited to the frontend vehicle list page. It keeps row-level vehicle status changes local while maintaining consistent filtered-list behavior after mutations.
