# Vehicle service quick customer active owner fix

Date: 2026-07-06

## Problem

The vehicle service quick-registration modal allowed creating a new customer with a non-active status, while the vehicle ownership module correctly rejects non-active customers as vehicle owners.

This let the UI create an invalid next-step combination:

- customer creation succeeded;
- vehicle creation then failed with `Only an active customer can own or use a vehicle.`

## Correction

Aligned the quick-registration UX with the owned vehicle ownership rule:

- new customers created from the vehicle service quick-registration modal now always save with `active` status;
- the modal no longer exposes a status selector for this fast-path flow and instead shows status as fixed active.

This keeps the quick-create path valid for immediate vehicle ownership assignment and avoids the conflicting state combination.

## Verification

- `npm run typecheck`
- `npm run lint -- resources/js/modules/vehicle-service/components/VehicleServiceQuickVehicleModal.tsx`
