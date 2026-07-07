# Vehicle Rental End-to-End Contract Fixes

## Why

The vehicle rental audit found that several write paths were locking records without exposing or requiring the loaded row version in the frontend/backend contract. A few UI flows also ignored source context or sent the wrong relationship semantics, which made the end-to-end workflow vulnerable to stale writes and invalid guided selections.

## What Changed

- Added explicit expected-version contracts for rental reservation updates/transitions, expense transitions, custody confirmations/reversals, rental invoice creation, finance agreement activation, and finance payable creation.
- Returned `row_version` from reservation, expense, custody, allocation, deposit, and finance resources so the UI can send conflict-aware commands.
- Wired frontend commands to send loaded row versions and fixed reservation-to-agreement conversion by loading the reservation, pre-filling the agreement form, and sending `expected_reservation_version`.
- Changed rental agreement lookup filtering to use `customer_rental` and `owner_supply` instead of invoice/payment directions.
- Updated deposit management to use structured receipt payment links for apply/refund actions with both requirement and payment expected versions.
- Added employee lookup support for employee reimbursement expense allocations.
- Kept agreement terms as versioned active/inactive rows during draft updates instead of deleting and recreating terms.
- Removed stale generic vehicle rental running-chart and driver-overtime report definitions from the shared reporting catalog.
- Replaced corrupted punctuation in vehicle rental UI/service text with plain ASCII separators.
- Added source-contract coverage for the new rental version contracts, reservation conversion, lookup mapping, report catalog cleanup, and text cleanup.

## Verification

- `php -l` on modified PHP files and new PHP files: passed.
- `php artisan route:list --path=vehicle-rental`: passed, 55 routes.
- `php artisan test tests\Unit\VehicleRental tests\Unit\Reporting\VehicleRentalReportDefinitionServiceTest.php`: passed, 15 tests / 190 assertions.
- `npx vitest run resources/js/modules/vehicle-rental/vehicleRentalPermissions.test.ts --reporter=dot`: passed.
- `npm run typecheck`: passed.
- `npm run build`: passed.
- `git diff --check`: passed.
- `php artisan test`: ran broader suite; unrelated existing failures remain in `Tests\Unit\Database\ModuleMigrationBaselineTest` and `Tests\Feature\Core\ApplicationBootstrapContractTest`.
