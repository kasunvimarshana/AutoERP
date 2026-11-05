# Vehicle Service job type management review

Date: 2026-11-02

## Request

Identify where the New Vehicle Service Job form's Type choices come from, whether users can create job types, and where a management UI would fit.

## Findings

- The five choices are fixed in the Vehicle Service form and mirrored by the backend `VehicleServiceJobType` enum. The form does not load them from an API or database table.
- No job-type management route, API, seeder, or UI exists. The create-defaults API supplies supervisor commission defaults only.
- Job type controls mileage requirements in both the frontend and backend. Existing jobs store the selected string value, so editable types would need an owned Vehicle Service catalog, stable values for historical records, and explicit mileage behavior.
- A management screen under Vehicle Service, reachable from the job list or its module navigation and restricted to an appropriate management permission, fits the current module boundary. The create-job form should remain focused on selecting a type.
- No relationship change is justified for the current fixed enum. A configurable catalog would introduce a relationship only after its lifecycle and historical-data rules are defined.

## Changes

- Documentation only. No application code, schema, data, or existing change records changed.
