# Require Vehicle Service job card and type

## Request

Make Manual Job Card mandatory and require an explicit service type when creating a Vehicle Service job.

## Changes

- Removed the Full Service default so the job type starts on the empty Select option.
- Marked Manual Job Card and Type required in the form, with field-level guidance and submit validation.
- Kept mileage fields inactive until a type is selected and explained why.
- Enforced Manual Job Card as required in the Vehicle Service job request validation. Type was already required by the backend.

## Verification

- `npm run typecheck` passed.
- PHP syntax check passed for `StoreVehicleServiceJobRequest.php`.
- `git diff --check` passed.
- Automated tests were not run.
