# Vehicle service job create form simplification and service mileage

Date: 2026-07-30

## Problem

The vehicle service job create flow still exposed several secondary fields that were not required for starting a job, while also missing two practical workshop inputs:

- `Bill-to customer`, `Expected delivery`, `Fuel level`, and `Priority` added noise to the create experience;
- `Job date` was required in the payload even though users did not need to choose it manually for draft creation;
- users needed a place to capture a paper-based manual job card reference;
- users also needed a convenient `Next service mileage` value derived from the entered odometer reading.

## Change

- simplified the vehicle service job form UI to hide `Bill-to customer`, `Job date`, `Expected delivery`, `Fuel level`, and `Priority` from the create/edit form flow;
- kept `Bill-to customer` behavior intact in the backend by continuing to default it to the selected customer when no explicit value is provided;
- made create-time `job_date` and `priority` authoritative in the backend by auto-filling them on POST when omitted:
  - `job_date` defaults to the current business date;
  - `priority` defaults to `normal`;
- added persisted job fields for:
  - `manual_job_card_number`
  - `next_service_mileage`
- added a schema migration for those new fields on `vehicle_service_jobs`;
- exposed the new fields through the vehicle service DTO, request, service, model casts, API resource, and frontend types;
- added `Manual job card` and `Next service mileage` inputs to the job form;
- implemented frontend autofill for `Next service mileage` using `Odometer + 5000`, while preserving user edits once they manually override the suggested value;
- surfaced the new values in the vehicle service summary and detail views;
- refreshed vehicle service create-form and backend engine regression coverage for the new behavior.

## Verification

- `npm run typecheck`
- `php artisan test --filter="test_job_resource_keeps_decimals_readable_and_relations_compact|test_job_create_request_defaults_job_date_and_priority_and_persists_new_optional_fields"`
- attempted: `npx vitest run resources/js/modules/vehicle-service/components/VehicleServiceJobForm.test.tsx`

## Notes

The focused Vitest command is currently blocked by the repository's existing React Router ESM/CommonJS test-environment issue (`Cannot use import statement outside a module`) before the test file begins executing.

## Scope

This change affects the Vehicle Service module job create/edit form contract, its persisted job fields, and related summary/detail presentation.
