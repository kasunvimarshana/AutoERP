# Vehicle service job vehicle-first selection

Date: 2026-07-04

## Problem

The new vehicle service job form forced users to select the customer first and only then search the customer's vehicles. The workshop flow tracks jobs by vehicle number or registration, so this inverted the real operating model and added an unnecessary customer lookup step.

## Correction

Updated the vehicle service job creation flow to use the vehicle as the primary selector:

- the vehicle field now loads service-available vehicles directly without requiring a customer filter;
- the customer field is now derived from the selected vehicle's current customer ownership and shown as a read-only display field;
- existing job initialization now prefers the selected vehicle's current customer snapshot when available, while still falling back to the stored job customer for existing records;
- the create page guidance now reflects the vehicle-first workflow.

The backend API contract already supported this flow because the service-available vehicle lookup accepts requests without `customer_id`, and vehicle lookup results already include `current_customer`.

## Verification

- `npm run typecheck`
- `npm run lint -- resources/js/modules/vehicle-service/components/VehicleServiceJobForm.tsx resources/js/modules/vehicle-service/pages/VehicleServiceJobCreatePage.tsx resources/js/shared/api/lookupApi.ts`
