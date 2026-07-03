# Vehicle Service end-to-end audit fixes

Date: 2026-07-03

## Problem

Vehicle Service jobs had a `row_version`, but most job mutations did not expose or enforce it. Concurrent edits could update lines, inspections, workforce assignments, inventory issues, invoices, documents, or status actions without detecting that another user had already changed the same service job.

The inventory issue API also allowed domain-rule failures from the owning Inventory module to leak as unhandled API exceptions, so an expected stock-shortage response could become a generic server error.

## Correction

Kept the fix in the owning Vehicle Service paths and the shared API exception layer. Vehicle Service job resources now expose `row_version`, mutating Vehicle Service requests require the expected job version, and backend services lock the job row before validating and applying writes. Child writes that change the job state now bump the job version after successful persistence.

Updated the Vehicle Service frontend to pass the current job version through status actions, job edits, line edits, workforce assignments, inventory issues, invoice creation, and document create/delete flows. API domain-rule failures raised as `InvalidArgumentException` now return the shared 422 JSON contract instead of a generic 500 response.

## Verification

- `php artisan test app/Modules/VehicleService/Tests --stop-on-failure`
- `npx vitest run resources/js/modules/vehicle-service --reporter=dot --silent=true`
- `npm run typecheck`
- `npm run build`
- `php artisan route:list --path=api/v1/vehicle-service --except-vendor`
- `php artisan test tests\Feature\Auth\AuthErrorContractTest.php tests\Feature\Auth\ExceptionTracePrivacyTest.php tests\Unit\Core\ApiErrorResponseFactoryTest.php --stop-on-failure`
