# Restore Vehicle Rental agreement CRUD actions

## Trigger

The current Vehicle Rental agreement list allowed draft editing only through a conditional button, but did not provide any View action and had no delete API or UI action. Users therefore could not inspect active or closed agreement details and could not remove an unused draft.

## Root cause

The fresh Vehicle Rental workspace consolidated agreement management into a list/dialog screen but omitted the complete agreement CRUD interaction boundary:

- the existing agreement show endpoint was not reachable from the UI;
- edit was visible only for drafts, without a separate detail view explaining the lifecycle;
- draft delete support was absent from the fresh backend and frontend contracts.

## Correct lifecycle

```text
View   → Draft, Active, and Closed
Edit   → Draft only
Delete → Draft only, with the current row version and no operational or financial history
```

Active and closed agreements remain immutable historical records. Active commercial changes continue through successor rate versions, and operational closure continues through the existing Close action.

## Changes

- Added a read-only agreement detail dialog using the existing scoped agreement show endpoint.
- Added View to every agreement row, including users with view-only permission.
- Kept Edit restricted to drafts and agreement-manage permission.
- Added a version-checked DELETE endpoint under agreement-manage permission.
- Added a Vehicle Rental-owned delete request and service method.
- Delete rejects active or closed agreements and any agreement with assignment or calculation history.
- Draft rate versions and their cascade-owned rate lines are removed before deleting the unused draft agreement.
- Added backend route/request coverage and frontend API/UI regression contracts.

## Scope and integrity

- Vehicle Rental owns all agreement lifecycle changes.
- No schema, relationship, Invoice, Payment, Tax, Finance, Vehicle, Customer, or Supplier changes.
- No hard deletion of active, closed, operational, or financial history.
- Optimistic concurrency remains mandatory.
- Tenant and organization scope resolution remains controller-owned and permission-protected.

## Verification

```bash
php -l app/Modules/VehicleRental/Http/Requests/DeleteRentalAgreementRequest.php
php -l app/Modules/VehicleRental/Http/Controllers/RentalAgreementController.php
php -l app/Modules/VehicleRental/Services/RentalAgreementService.php
php -l app/Modules/VehicleRental/Routes/api.php
php artisan test --filter=RentalCrudInterfaceBoundaryTest
php artisan test --filter=VehicleRental
php artisan test
composer test:mysql

npx vitest run resources/js/modules/vehicle-rental/rentalAgreementCrudActions.test.ts
npx vitest run resources/js/modules/vehicle-rental/vehicleRentalCrudWorkflow.test.ts
npm run typecheck
npm run lint
npm run test
npm run build
```

Paid tools and GitHub Actions are not required or used.
