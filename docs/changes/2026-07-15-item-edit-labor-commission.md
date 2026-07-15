# Item edit labor commission

Date: 2026-07-15

## Problem

The labor-item commission rule could be configured while creating an Item, but the Item Edit workspace did not expose the current Vehicle Service commission rule. Users therefore could not review, activate, deactivate, or update the item-level labor commission after creation.

## Root cause

The create workflow composed the Vehicle Service commission panel after Item creation, while `ItemEditPage` only exposed Item-owned basic, unit, variant, bundle, price, code, and usage-rule tabs. No Vehicle Service-owned edit component was connected to the Item Edit workspace.

## Correction

A focused Vehicle Service-owned `LaborItemCommissionEditor` now:

- loads the current item-level commission rule;
- displays the commission type, value, and active state;
- supports read-only review for users with commission-view permission;
- saves through the canonical Vehicle Service commission API;
- sends the current `row_version` as `expected_version`;
- updates only the commission rule and does not mix commission writes into Item update logic.

`ItemEditPage` now exposes a Commission tab only when:

- the Vehicle Service module is enabled;
- the Item is a labor item;
- the current user has `vehicle_service.commissions.view`.

The editor enables writes only when the user also has `vehicle_service.commissions.manage`.

## Ownership and relationships

No database relationship or schema changed.

Item continues to own catalog data and UOM. Vehicle Service continues to own commission policy. The Item Edit page only composes the Vehicle Service editor, matching the existing create workflow without duplicating policy logic in the Item module.

## Concurrency and history

Commission updates remain atomic and optimistic-version checked by the existing Vehicle Service policy service. Updating the item default does not recalculate historical employee-assignment commission snapshots.

## Verification

```bash
npx vitest run resources/js/modules/item/ItemEditCommissionContract.test.tsx --reporter=dot --silent
npx vitest run resources/js/modules/vehicle-service/components/employee-assignment/assignmentForm.test.ts --reporter=dot --silent
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
php artisan test --filter=VehicleServiceCommissionPolicyTest
php artisan test
composer test:mysql
```
