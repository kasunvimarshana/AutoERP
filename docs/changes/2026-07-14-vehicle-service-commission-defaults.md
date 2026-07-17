# Vehicle Service commission defaults

Date: 2026-07-14

## Problem

Vehicle Service already stored actual commission snapshots on service jobs and line employee assignments, but every supervisor and technician commission had to be entered manually. Labor items also needed to support ordinary item UOMs such as Hour, Unit, Job, or Service without coupling commission rules to one measurement type.

## Correction

- Added an organization-unit supervisor commission default owned by Vehicle Service.
- Added labor-item and workforce-role commission defaults owned by Vehicle Service.
- Kept `none`, `fixed`, and `percentage` as the canonical commission types.
- Kept the existing actual commission snapshots:
  - supervisor commission remains on `vehicle_service_jobs` and is calculated from the whole job grand total;
  - employee commission remains on `vehicle_service_line_employees` and is calculated from the selected line total.
- New jobs resolve the current supervisor default on the backend and snapshot it into the job.
- Existing jobs preserve their stored supervisor commission unless an explicit edit changes it.
- New labor-line assignments resolve the exact active item and role default, then snapshot the resolved values into the assignment.
- Existing assignments preserve their stored commission unless an explicit edit changes it.
- Added typed APIs and permissions for viewing and managing commission policies.
- Added an inline supervisor-default editor in the Vehicle Service jobs workspace.
- Added an optional commission section to labor-item creation when Vehicle Service is enabled and the user can manage commission policies.
- Added assignment-form autofill when a labor line or workforce role is selected.

## UOM behavior

Commission rules do not own or restrict UOMs. Labor items continue to use the Item/UOM modules as the single source of truth. A labor item may therefore use Hour, Unit, Job, Service, or another valid controlled UOM. Percentage employee commission uses the resulting labor line total, not the UOM code.

## Data integrity and concurrency

- Policy writes lock the organization unit and the current policy row.
- Existing policies require `expected_version`; stale updates fail explicitly.
- Policy values use the shared exact-decimal service and reject negative values or percentages above 100.
- Policy models are deny-by-default guarded and written only by the Vehicle Service owner service.
- Policy changes never recalculate historical jobs or assignments.
- Item creation and Vehicle Service policy creation remain separate owner-module writes. If the optional policy write fails after the Item succeeds, the UI reports the partial result and retries only the policy; it does not duplicate or delete the Item.

## Relationships

Two justified relationships were added:

- supervisor policy → tenant and organization unit;
- labor commission rule → tenant, organization unit, and labor item.

No reverse relationship was added to Item, because Item does not own Vehicle Service commission behavior. No new relationship was added to jobs or employee assignments because those existing records already own the actual commission snapshots. This avoids circular ownership and duplicate sources of truth.

## Permissions

- `vehicle_service.commissions.view`
- `vehicle_service.commissions.manage`

Module enablement does not grant these permissions automatically.

## Verification

Run from `worktree-0.0.8`:

```bash
php artisan test --filter=VehicleServiceCommissionPolicyTest
npx vitest run resources/js/modules/vehicle-service/components/employee-assignment/assignmentForm.test.ts --reporter=dot --silent
php artisan test
composer test:mysql
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
```
