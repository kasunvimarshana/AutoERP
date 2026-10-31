# Add Wheel Alignment Vehicle Service job type

## Request

Add Wheel Alignment as a Vehicle Service job type and enable mileage fields for it, Full Service, and Oil Change.

## Changes

- Added `wheel_alignment` to the backend job type enum and frontend type selector.
- Marked Wheel Alignment as mileage-tracked in the backend enum and a shared frontend mileage rule.
- Reused the shared mileage rule for form field enablement and job summary display; backend request and service validation already rely on the enum rule.

## Verification

- `npm run typecheck` passed.
- PHP syntax check passed for `VehicleServiceJobType.php`.
- `git diff --check` passed.
- Automated tests were not run.
