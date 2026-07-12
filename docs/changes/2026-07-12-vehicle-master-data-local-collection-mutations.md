# Vehicle master data local collection mutations

Date: 2026-07-12

## Problem

The vehicle master-data workspace for makes, types, categories, and models reloaded the full list after create, edit, and activate/deactivate actions. That caused unnecessary API recalls and reset the visible collection even though those mutations already returned the updated resource.

## Change

- added local collection state to `VehicleMasterDataPage` for all four vehicle master-data kinds;
- removed the mutation-driven list refresh key and replaced create/edit/toggle flows with in-place collection updates from the mutation responses;
- kept normal server fetching for actual filter and pagination changes;
- made the local updates filter-aware so rows are removed when they no longer match the active status or make filter, and newly created rows are inserted locally on page 1 when they match the current filters;
- updated the existing page test expectation to reflect the no-reload create flow.

## Verification

- `npm run typecheck`
- direct run of `resources/js/modules/vehicle/VehicleMasterDataPage.test.tsx` is still blocked by the existing Vitest `react-router` ESM loading issue in the current test environment

## Scope

This change is limited to the frontend vehicle master-data workspace covering makes, types, categories, and models. It reduces unnecessary list reloads while preserving filter, pagination, and backend behavior.
