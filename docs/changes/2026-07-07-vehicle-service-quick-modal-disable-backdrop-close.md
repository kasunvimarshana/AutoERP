# Vehicle service quick modal disable backdrop close

Date: 2026-07-07

## Problem

`VehicleServiceQuickVehicleModal` closed when the user clicked outside the modal. That immediately removed the form from the UI and discarded the user’s in-progress vehicle and customer entry, forcing them to start over.

## Correction

Added a reusable `closeOnBackdrop` option to the shared `Modal` component and disabled backdrop closing for `VehicleServiceQuickVehicleModal`.

- outside clicks no longer close the quick vehicle modal;
- the normal close button and standard close handling remain available.

## Verification

- `npm run typecheck`
- `npm run lint -- resources/js/shared/components/Modal.tsx resources/js/modules/vehicle-service/components/VehicleServiceQuickVehicleModal.tsx`
