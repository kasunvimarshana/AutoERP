# Vehicle service inventory default warehouse selection

Date: 2026-07-09

## Problem

The vehicle service job inventory tab always opened with empty `Issue warehouse` and `Issue location` selectors, even when the tenant already had a default warehouse and a default location configured. That forced users to repeat the same operational selection before checking issue readiness or posting stock deductions for job lines.

## Correction

Updated the vehicle service inventory issue tab to reuse the existing warehouse module defaults:

- load the tenant-scoped default warehouse when the tab opens;
- load the selected warehouse's default location automatically;
- when the user changes the warehouse manually, clear the previous location and prefill the new warehouse's default location;
- keep manual user selections authoritative after the user explicitly overrides them.

This keeps the UI aligned with the warehouse module's existing default source of truth and speeds up inventory issue for service jobs without introducing duplicate rules.

## Verification

- `npm run typecheck -- --pretty false`
- `npm run lint -- resources/js/modules/vehicle-service/components/VehicleServiceInventoryIssueTab.tsx`
