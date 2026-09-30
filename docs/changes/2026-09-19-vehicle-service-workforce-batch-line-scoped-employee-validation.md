# Vehicle service workforce batch employee reuse across labour lines

Date: 2026-09-19

## Why

The batch request applied Laravel `distinct` to `lines.*.employee_ids.*`. That rejected the same employee in different labour lines, including child lines of a combo, even though the domain service and database enforce uniqueness per line and employee.

## What changed

- Removed the cross-line `distinct` rule from the batch request.
- Added line-scoped duplicate validation so one employee cannot be selected twice for the same labour line.
- Added an endpoint regression test using two labour children of one combo. It verifies that the same employee can be assigned to both lines, while a duplicate within one line returns a validation error and creates no assignment.

## Data and schema impact

- No migration or existing assignment data change was needed. The database unique key remains `(vehicle_service_job_line_id, employee_id)`.
- The existing transaction, expected Job Card version check, workforce eligibility validation, and supervisor rules remain in effect.

## Verification

- Focused VehicleService batch tests: 2 passed, 12 assertions.
- PHP Pint check passed for both changed PHP files.
- `git diff --check` and PHP syntax checks passed.
