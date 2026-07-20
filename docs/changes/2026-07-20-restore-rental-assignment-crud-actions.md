# Restore Vehicle Operations view, planned edit, and planned delete

## Trigger

The Vehicle Operations workspace exposed only handover, cancel, return, and replacement actions. The backend already had a scoped assignment show endpoint, but the UI did not expose it. Planned assignments also had no update or delete contract.

## Correct lifecycle

```text
View   → Planned, Active, Returned, Replaced, and Cancelled
Edit   → Unused Planned only
Delete → Unused Planned only
Cancel → Planned record retained as history
```

Active, returned, replaced, and cancelled operations remain immutable historical records. Operational changes after handover continue through Return and Replace rather than direct editing.

## Changes

- Added a read-only Vehicle Operation detail dialog using the existing scoped show endpoint.
- Added View to every operation row, including users with assignment-view permission only.
- Added a reusable assignment create/edit dialog that preloads the complete planned operation.
- Added version-checked PUT and DELETE endpoints under `vehicle_rental.assignments.manage`.
- Reused the authoritative planning validation for edits:
  - active agreement and side compatibility;
  - agreement period;
  - vehicle availability and same-side overlap prevention;
  - driver overlap prevention with linked owner/customer pair handling;
  - exact owner-source period coverage;
  - vehicle ownership source.
- Current assignment is excluded from its own vehicle and driver overlap checks.
- Edit/delete reject assignments with custody events, running charts, replacement references, or dependent assignments.
- Self-referencing owner-source updates are rejected.
- Delete requires the current row version and removes only an unused planned row.

## Scope and integrity

- Vehicle Rental assignment lifecycle ownership only.
- No schema, agreement, vehicle, HR, running-chart, invoice, payment, tax, or finance changes.
- No hard deletion of operational history.
- No automatic mutation of dependent assignments.
- Tenant and organization-unit scope remain request/context owned.
- Optimistic concurrency remains mandatory.

## Verification

```bash
php -l app/Modules/VehicleRental/Http/Requests/StoreRentalAssignmentRequest.php
php -l app/Modules/VehicleRental/Http/Requests/UpdateRentalAssignmentRequest.php
php -l app/Modules/VehicleRental/Http/Requests/DeleteRentalAssignmentRequest.php
php -l app/Modules/VehicleRental/Http/Controllers/RentalAssignmentController.php
php -l app/Modules/VehicleRental/Services/RentalAssignmentService.php
php -l app/Modules/VehicleRental/Routes/api.php
php -l tests/Unit/VehicleRental/RentalAssignmentCrudLifecycleContractTest.php

php artisan test --filter=RentalAssignmentCrudLifecycleContractTest
php artisan test --filter=RentalCrudInterfaceBoundaryTest
php artisan test --filter=VehicleRental
php artisan test
composer test:mysql

npx vitest run resources/js/modules/vehicle-rental/rentalAssignmentCrudActions.test.ts
npx vitest run resources/js/modules/vehicle-rental/vehicleRentalCrudWorkflow.test.ts
npm run typecheck
npm run lint
npm run test
npm run build
```

Paid tools and GitHub Actions are not required or used.
