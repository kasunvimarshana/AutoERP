# Vehicle service job quick vehicle registration

Date: 2026-07-06

## Problem

Service advisors could only create a job for an existing vehicle. When a vehicle or customer was new, they had to leave the job form, register records in other modules, then return and start again. The vehicle module also exposed a design gap: the frontend supported `createVehicleWithRelations(...ownerships)`, but the owning backend path did not actually accept and persist initial ownerships during vehicle creation.

## Correction

Fixed the vehicle ownership foundation in the owning module:

- added ownership validation and request mapping to `vehicles/with-relations`;
- extended vehicle creation DTO/service flow to persist initial ownerships through the vehicle ownership command service;
- updated the vehicle API test to verify one-shot create returns `current_customer` and current ownership data immediately.

Improved the vehicle service job create UX:

- extended the shared `GenericLookupSelect` with a reusable custom empty-state renderer;
- when the vehicle lookup has no match for the typed value, it now offers a `Register new vehicle` action inside the dropdown;
- added a single quick-registration modal in the vehicle service module;
- the modal lets the user either pick an existing customer or register a new customer inline in the same popup;
- after save, the new customer and vehicle ownership relation are persisted before closing, and the job form auto-selects the new vehicle and customer so the user can continue immediately.

## Verification

- `npm run typecheck`
- `npm run lint -- resources/js/shared/components/GenericLookupSelect.tsx resources/js/shared/components/GenericLookupSelect.test.tsx resources/js/modules/vehicle-service/components/VehicleServiceJobForm.tsx resources/js/modules/vehicle-service/components/VehicleServiceQuickVehicleModal.tsx`
- `npx vitest run resources/js/shared/components/GenericLookupSelect.test.tsx --reporter=dot --silent`
- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan test --filter=VehicleEngineTest`
- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan test --filter=VehicleService`
