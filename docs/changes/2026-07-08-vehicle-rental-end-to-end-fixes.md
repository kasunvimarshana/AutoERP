# 2026-07-08 Vehicle Rental End-to-End Fixes

## Context
- Followed up on the vehicle rental end-to-end audit findings covering lessor and lessee agreements, allocations, running chart calculation, invoices, payments, and deposits.
- Kept the fixes scoped to root causes in the owning modules.

## Changes
- Fixed `rental_deposit_requirements.agreement_kind` to use the same portable string shape as `rental_agreements.agreement_kind`, allowing the composite foreign key migration to run cleanly.
- Defaulted rental invoice and owner payable due dates from the agreement payment terms when the UI/API does not send an explicit due date.
- Corrected rental negative calculation line invoice adjustments to use `CreditNote` as the decreasing adjustment type for both receivable invoices and owner payables.
- Scoped rental deposit invoice selection to outbound rental invoices for the deposit customer, and returned/typed the customer relationship needed by the UI.
- Allowed nullable vehicle service commission request fields to resolve to the module defaults instead of failing enum conversion.
- Added focused regression coverage for the deposit schema contract, due-date defaulting, owner payable generation with deductions, invoice lookup scoping, and nullable commission requests.

## Verification
- `php artisan test tests/Feature/VehicleRental tests/Unit/VehicleRental tests/Unit/Reporting/VehicleRentalReportDefinitionServiceTest.php`
- `php artisan test app/Modules/VehicleService/Tests/VehicleServiceEngineTest.php`
- `php artisan test app/Modules/Invoice/Tests`
- `npm run typecheck -- --pretty false`
- `npm run lint`
- `npm test`
- `php artisan migrate --no-interaction`
- `php artisan migrate:status --no-interaction`
- `git diff --check`

## Notes
- The local database contained an empty stale `rental_deposit_requirements` table from a previous failed migration run while Laravel still marked the migration as pending. It was dropped after confirming it had zero records, then recreated by the corrected migration.
