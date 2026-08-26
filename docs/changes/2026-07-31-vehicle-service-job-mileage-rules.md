# Vehicle service job mileage rules

Date: 2026-07-31

## Purpose

Made service mileage entry depend on the selected vehicle service job type while keeping the suggested next service mileage editable.

## Behavior

### Full Service

- Odometer is enabled and mandatory.
- Entering or changing the odometer suggests `odometer + 5,000` in Next Service Mileage.
- The suggestion uses the shared exact decimal utility.
- Next Service Mileage remains optional and editable after the suggestion is applied.
- Changing the odometer again replaces the previous suggestion or manual override with a new `+5,000` suggestion.

### Body Wash

- Odometer and Next Service Mileage are cleared and disabled.
- Changing from Body Wash to Full Service enables both fields with blank values.
- The user must enter a new odometer before saving.
- Backend request validation prohibits mileage values for Body Wash jobs.

## Backend integrity

- Full Service requests require a non-negative odometer.
- Next Service Mileage remains optional but must be non-negative when provided.
- Body Wash requests cannot contain either mileage value.
- The Vehicle Service job service enforces the same invariants for callers that do not pass through the HTTP request layer.
- Existing atomic writes and row-version conflict checks remain unchanged.

## Verification

- PHP syntax checks passed.
- `php artisan test --filter="test_optional_job_fields_persist_and_nullable_commission_fields_default_to_none|test_job_type_enforces_mileage_field_rules"` passed: 2 tests, 17 assertions.
- `npx vitest run resources/js/modules/vehicle-service/components/VehicleServiceJobForm.test.tsx` passed: 4 tests.
- `npm run typecheck` passed.
- Targeted ESLint passed.
- `npm run build` passed.
