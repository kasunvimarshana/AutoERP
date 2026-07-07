# Vehicle service job vehicle search debounce

Date: 2026-07-06

## Problem

In the new vehicle service job form, the vehicle lookup reacted too quickly while the user was still typing, which made the debounce feel too short for vehicle-number search.

## Correction

Adjusted the vehicle lookup in the vehicle service job form to use a `1000ms` debounce for search input.

This change is scoped only to the service job vehicle field and does not change the shared lookup default used elsewhere in the application.

## Verification

- `npm run typecheck`
- `npm run lint -- resources/js/modules/vehicle-service/components/VehicleServiceJobForm.tsx`
