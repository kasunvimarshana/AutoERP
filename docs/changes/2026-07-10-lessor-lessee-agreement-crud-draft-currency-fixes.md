# Lessor/Lessee Agreement CRUD, Draft Validation, and Currency Defaults

## Context

The lessor and lessee agreement flows needed an end-to-end cleanup after draft creation failed with `executed_at: Execution date cannot be in the future.` The same area also needed purchase-style currency defaults, optional agreement terms, and complete draft CRUD behavior without exposing raw relationship mechanics to users.

## Changes

- Made agreement execution date optional during draft creation and validation, while still preventing execution before the agreement date when supplied.
- Made agreement terms optional and filtered blank term clauses in the vehicle rental agreement service so draft agreements can be saved before legal terms are finalized.
- Added tenant base currency metadata for vehicle rental context and used it as the first agreement currency default, with party default currency applied when available and manual user changes preserved.
- Added draft edit routes for general, lessee, and lessor agreement paths, reusing the shared agreement form while keeping create-only rate and deposit fields out of draft updates.
- Added version-checked draft delete support in the vehicle rental module with a dedicated delete request, service method, API route, and detail-page delete action.
- Updated agreement tests and frontend route/access contracts to cover optional execution/terms, default currency behavior, draft edit, and draft delete.

## Verification

- `php -l app/Modules/VehicleRental/Http/Requests/StoreRentalAgreementRequest.php`
- `php -l app/Modules/VehicleRental/Http/Requests/DeleteRentalAgreementRequest.php`
- `php -l app/Modules/VehicleRental/Services/RentalAgreementService.php`
- `php -l app/Modules/VehicleRental/Http/Controllers/RentalAgreementController.php`
- `php -l app/Modules/VehicleRental/Http/Controllers/RentalContextController.php`
- `php artisan test tests\Feature\VehicleRental\RentalAgreementCreateTest.php tests\Unit\VehicleRental\RentalAgreementIntegrityContractTest.php tests\Unit\VehicleRental\VehicleRentalModuleBaselineTest.php`
- `php artisan test tests\Feature\VehicleRental tests\Unit\VehicleRental`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx --reporter=dot`
- `npm run typecheck -- --pretty false`
- `npm run lint`
- `npm run build`
- `git diff --check`
