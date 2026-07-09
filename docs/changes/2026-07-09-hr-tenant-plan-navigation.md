# HR tenant plan navigation

Date: 2026-07-09

## Problem

The HR backend module and employee frontend routes existed, but HR was not available as a tenant-plan module and was not shown in the left navigation.

## Correction

- Added `hr` to the tenant plan supported module catalogue and frontend tenant module list.
- Added HR to the tenant plan editor's Operations module group.
- Changed HR route entitlements so `/hr/*` requires the `hr` tenant module.
- Added an HR sidebar module with Employees and Create Employee links.
- Added focused tests for HR plan normalization, HR route entitlement, and HR navigation visibility.

## Verification

- `npx vitest run resources/js/app/navigation/navigationUtils.test.ts resources/js/app/access/resolvedRouteEntitlements.test.ts resources/js/app/access/routeEntitlements.test.ts --reporter=dot`
- `php artisan test app/Modules/Tenant/Tests/TenantPlanSchemaTest.php`
- `npm run typecheck`
