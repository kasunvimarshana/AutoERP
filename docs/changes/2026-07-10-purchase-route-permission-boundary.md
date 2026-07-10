# Purchase Route Permission Boundary

## Summary

- Added route-level tenant permission middleware to Purchase routes whose controller actions already require one exact Purchase permission.
- Kept existing controller authorization assertions as defense in depth.
- Left Purchase lookup/context routes that intentionally use `PurchaseAuthorizationService::assertAny()` unchanged at route level because the current tenant permission middleware accepts one exact permission, not an any-of set.

## Root cause

Purchase routes were protected by auth, tenant, organization-unit, and feature middleware, but exact Purchase permissions were enforced only inside controller actions. A future controller action could accidentally miss its manual authorization call.

## Design notes

- This change mirrors existing controller-owned permissions at the route boundary for exact-permission actions.
- The Purchase module remains the permission owner by reusing `PurchaseAuthorizationService` constants.
- No controller behavior, request validation, service logic, models, migrations, or frontend code was changed.
- No compatibility shortcuts were added.

## Verification

- Source readback should confirm `PurchaseAuthorizationService` is imported by `app/Modules/Purchase/Routes/api.php`.
- Source readback should confirm exact-permission Purchase routes now call `middleware($requires(...))`.
- Full local `php artisan test`, frontend typecheck, lint, build, and Vitest should be run after pulling the branch.
