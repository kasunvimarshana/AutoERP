# Vehicle service job field visibility

Date: 2026-07-31

## Purpose

Applied the simplified Vehicle Service job field set consistently across create, edit, overview, and workshop-summary presentations.

## Changes

- Removed the following controls from the job edit form:
  - Bill-to customer
  - Job date
  - Expected delivery
  - Fuel level
  - Priority
  - Supervisor commission
  - Commission value
- Removed the corresponding fields from the main job Overview and the left Workshop Status summary.
- Kept the underlying backend values and payload behavior unchanged so unrelated job edits preserve stored data.
- Kept Odometer and Next Service Mileage visible for Full Service jobs.
- Hid Odometer and Next Service Mileage from Body Wash read-only summaries because they are not applicable.
- Kept both mileage controls visible in Body Wash edit mode, but styled them with a grey background, muted text, disabled cursor, and a `Not applicable to Body Wash` hint.

## Verification

- `npx vitest run resources/js/modules/vehicle-service/components/VehicleServiceJobForm.test.tsx` passed: 6 tests.
- `npm run typecheck` passed.
- Targeted ESLint passed.
- `npm run build` passed.
- `git diff --check` passed.
