# Vehicle Rental Running Chart End-to-End Fixes

## Summary
- Restricted the running-chart allocation selector to active customer rental allocations, matching the backend rule that physical usage is recorded against the lessee allocation.
- Added source allocation version handling for running-chart creation so owner/lessor payable contexts are based on a locked, current owner supply allocation.
- Validated owner-applicable running-chart events so owner-only or both-side events cannot be recorded when no owner supply context exists.
- Expanded the running-chart idempotency fingerprint to include saved physical facts and event details, preventing changed duplicate submissions from silently returning an older usage log.
- Replaced raw usage fact/context relationship ids in running-chart API payloads with structured related objects.

## Verification
- `php -l` on modified running-chart PHP service/resource/request/test files.
- `php artisan test tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/RentalCalculationIntegrityContractTest.php`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx resources/js/modules/vehicle-rental/pages/RentalCustodyPage.test.tsx --reporter=dot`
- `npm run typecheck`
- `git diff --check`
