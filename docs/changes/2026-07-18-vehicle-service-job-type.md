# Vehicle service job type

Date: 2026-07-18

## Problem

Vehicle service jobs did not record whether the job was a full service or a body wash.

## Change

- added a required, enum-backed job type with `Full Service` and `Body Wash` choices;
- defaulted existing and newly created jobs to `Full Service`;
- persisted and returned the type through the vehicle service backend;
- added the controlled type selector to the job form and displayed it in the job summary.

No relationships were added or changed because job type is a fixed classification owned by the vehicle service job.

## Verification

- `npm run typecheck`
- `npx vitest run resources/js/modules/vehicle-service/components/VehicleServiceJobForm.test.tsx resources/js/modules/vehicle-service/api/jobs.test.ts`
- `php artisan test app/Modules/VehicleService/Tests/VehicleServiceEngineTest.php app/Modules/VehicleService/Tests/VehicleServiceLabourCommissionSplitTest.php tests/Feature/VehicleService/VehicleServiceRentalAvailabilityIntegrationTest.php`

## Scope

This change affects only vehicle service job type capture, persistence, API output, and summary display.
