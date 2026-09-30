# Vehicle Service sidebar navigation

## Summary

- Renamed the Vehicle Service submenu label from `Service Jobs` to `Service Job List`.
- Added a permission-aware `Create Service Job` submenu link to `/vehicle-service/jobs/create`.
- Removed Vehicle Rental from the tenant workspace sidebar while keeping its routes and feature implementation unchanged.
- Removed the unused Vehicle Rental navigation definition after disconnecting it from the sidebar.
- Added navigation coverage for the new labels, create-route matching, and hidden rental module.

## Verification

- `npm run build` passed.
- `npx vitest run resources/js/app/navigation/navigationUtils.test.ts --pool=forks --maxWorkers=1 --no-file-parallelism --reporter=dot` passed: 16 tests.
