# Vehicle create reference lookups preloaded

Date: 2026-07-12

## Problem

The vehicle create flow still let `Make`, `Model`, and `Type` rely on lookup requests during interaction. `Make` and `Type` already supported local filtering, but they were not explicitly preloaded on mount, and `Model` was still using a request-per-search path.

## Change

- added shared preload helpers for vehicle makes, models, and types in the vehicle lookup API;
- changed vehicle model lookup to use a cached preloaded dataset with frontend filtering by both search term and selected make;
- updated `VehicleMakeSelect`, `VehicleModelSelect`, and `VehicleTypeSelect` to warm their datasets on mount through the shared lookup cache layer;
- kept the existing field APIs and selection behavior unchanged so the create form continues to work with the same form components.

## Verification

- `npm run typecheck`

## Scope

This change affects the frontend vehicle reference lookups used by the vehicle create form and the shared vehicle select components that own those inputs.
