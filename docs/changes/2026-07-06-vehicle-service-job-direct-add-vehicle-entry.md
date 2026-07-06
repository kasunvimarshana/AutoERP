# Vehicle service job direct add vehicle entry

Date: 2026-07-06

## Problem

The vehicle service job form already supported registering a new vehicle from the vehicle search empty state, but users who already knew the job was for a fresh vehicle still had to start typing before that action appeared.

## Correction

Added a dedicated `Add new vehicle` action in the vehicle service job form UI.

- placed it in the service job panel beside a short vehicle-selection helper block;
- wired it to the same quick-registration modal and save flow already used by the vehicle lookup empty state;
- kept the styling aligned with the existing panel and secondary-action UI pattern.

## Verification

- `npm run typecheck`
- `npm run lint -- resources/js/modules/vehicle-service/components/VehicleServiceJobForm.tsx`
