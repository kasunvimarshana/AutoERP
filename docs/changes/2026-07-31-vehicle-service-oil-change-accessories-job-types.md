# Vehicle service Oil Change and Accessories job types

Date: 2026-07-31

## Purpose

Added Oil Change and Accessories as fixed Vehicle Service job types using the existing enum-backed design. Job types are not seeded and do not use a lookup table.

## Changes

- Added backend enum values:
  - `oil_change`
  - `accessories`
- Added `Oil Change` and `Accessories` to the job form dropdown and frontend type contract.
- Added a domain-level `tracksMileage()` rule to keep mileage behavior owned by the Vehicle Service job type.
- Grouped job types by mileage behavior:
  - Full Service and Oil Change require Odometer and support the editable `+5,000` Next Service Mileage suggestion.
  - Body Wash and Accessories clear, disable, and prohibit both mileage values.
- Show mileage values in read-only summaries only for Full Service and Oil Change jobs.
- Kept the existing `full_service` database default unchanged.
- No seeder or database migration was required because `vehicle_service_jobs.type` is a string column validated through the application enum.

## Verification

- PHP syntax checks passed.
- `php artisan test --filter=test_job_type_enforces_mileage_field_rules` passed: 1 test, 15 assertions.
- `npx vitest run resources/js/modules/vehicle-service/components/VehicleServiceJobForm.test.tsx` passed: 7 tests.
- `npm run typecheck` passed.
- Targeted ESLint passed.
- `npm run build` passed.
- `git diff --check` passed.
