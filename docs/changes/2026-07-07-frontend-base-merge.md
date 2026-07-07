# Frontend Base Merge

Date: 2026-07-07 06:23:37 +05:30

## Context

Merged `kushan/frontend_base` into `worktree-0.0.5` and resolved the only content conflict in the vehicle service job form.

## Changes

- Kept the incoming vehicle-first service job flow with existing vehicle search, quick vehicle registration, and owner-derived customer selection.
- Preserved the current branch's bill-to customer behavior by adding the controlled bill-to customer selector back into the merged form.
- Updated vehicle selection so the selected vehicle owner becomes both the service customer and the default bill-to customer, while existing jobs still preserve an explicit saved bill-to customer.

## Verification

- `git diff --check`
- `npm run typecheck`
- `npx vitest run resources/js/shared/api/lookupCache.test.ts resources/js/shared/components/GenericLookupSelect.test.tsx --reporter=dot`
- `php artisan test app/Modules/Customer/Tests/CustomerEngineTest.php app/Modules/Item/Tests/ItemEngineTest.php app/Modules/Vehicle/Tests/VehicleEngineTest.php app/Modules/VehicleService/Tests/VehicleServiceEngineTest.php`
