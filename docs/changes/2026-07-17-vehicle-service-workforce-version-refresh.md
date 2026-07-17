# Vehicle Service workforce version refresh

Date: 2026-07-17

## Problem

Vehicle Service workforce mutations updated the backend job row version and could recalculate commission amounts for every assignment on the selected labour line. The frontend updated only the directly mutated assignment and assumed the next job version was always `expectedVersion + 1`.

That left two stale client-side values:

- the parent job optimistic-concurrency version could differ from the authoritative persisted version;
- other workforce rows could continue showing commission amounts calculated before the mutation.

A later workforce action could therefore be rejected with `expected_version` even during a normal user workflow.

## Correction

- After workforce create, update, or delete, reload the assignable-line projection and the Vehicle Service job.
- Use the persisted job `row_version` as the only version sent back to the parent job state.
- Replace the whole workforce projection so commission reallocations affecting other employees are displayed immediately.
- When the backend correctly rejects a stale mutation, reload the same authoritative state, keep the form open, and tell the user to review and retry.
- Keep the backend optimistic-concurrency validation unchanged.

## Relationship review

No database, model, API, or module relationship changed.

The existing ownership remains valid:

- the Vehicle Service job owns its row version;
- a job line owns its workforce assignments;
- the backend assignment service owns commission recalculation;
- the job detail page owns the displayed job version.

The correction only removes client-side version guessing and stale partial assignment updates.

## Verification

Run:

```bash
npx vitest run resources/js/modules/vehicle-service/components/VehicleServiceEmployeeAssignmentTab.test.tsx --reporter=dot --silent=true
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
php artisan test --filter=VehicleServiceLabourCommissionSplitTest
php artisan test
composer test:mysql
```
