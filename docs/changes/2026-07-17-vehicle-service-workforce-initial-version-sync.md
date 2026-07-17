# Vehicle Service workforce initial version synchronization

Date: 2026-07-17

## Problem

The workforce screen only loaded assignable job lines when it first opened. It received the parent page's previously known `row_version` and did not verify that version against the current persisted Vehicle Service job before enabling the first assignment mutation.

When another Vehicle Service action had already advanced the job version, the first workforce create, update, or delete request was predictably rejected. The stale-version recovery introduced in the previous batch then loaded the current version, so the user's second attempt succeeded.

## Correction

- Load the current Vehicle Service job and the assignable workforce lines together when the workforce screen opens.
- Publish the persisted job `row_version` to the parent job state before the workforce data becomes available for interaction.
- Continue loading the same authoritative pair after workforce mutations and genuine concurrency conflicts.
- Keep the backend optimistic-concurrency check unchanged.

## Relationship review

No database, model, API, or module relationship changed.

The Vehicle Service job remains the owner of its row version. The workforce screen only synchronizes that server-owned token before allowing a mutation and continues to treat job lines and assignments as the authoritative workforce projection.

## Verification

Run:

```bash
npx vitest run resources/js/modules/vehicle-service/components/VehicleServiceEmployeeAssignmentTab.test.tsx --reporter=dot --silent=true
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
php artisan test
composer test:mysql
```
