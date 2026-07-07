# Vehicle Service Job Bill-To Regression Test

Date: 2026-07-07 06:52:14 +05:30

## Context

Followed up on a runtime report that service job creation failed with `billToCustomer is not defined` after the frontend base merge.

## Changes

- Added a focused vehicle service job form regression test covering vehicle-owner selection, bill-to customer selection, and create payload submission.
- Restarted the local Vite dev server on port `4000` so the browser receives a fresh module graph after the merge conflict resolution.

## Verification

- `npx vitest run resources/js/modules/vehicle-service/components/VehicleServiceJobForm.test.tsx --reporter=dot`
- `npm run typecheck`
