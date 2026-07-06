# Vehicle Rental End-to-End Audit Fixes

## Summary
- Fixed company-owned vehicle allocation flows so backend validation requires an active company ownership record and the allocation/replacement forms resolve and submit that relationship explicitly.
- Fixed owner-supplied replacement/allocation source selection so vehicle selection is derived from the selected source allocation instead of blocking the lookup before a vehicle is chosen.
- Added required optimistic version tokens to custody, running-chart usage, and rental calculation commands so writes are conflict-aware against the current allocation or agreement row.
- Restricted customer-facing custody event creation to normal custody handover/return events and kept replacement-only custody events under the replacement workflow.
- Added nested replacement custody item validation and changed custody event resources to expose a structured replacement summary instead of a raw replacement id.

## Verification
- `php -l` on modified PHP controllers, requests, resources, services, and contract tests.
- `php artisan test tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/RentalCalculationIntegrityContractTest.php`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx resources/js/modules/vehicle-rental/pages/RentalCustodyPage.test.tsx --reporter=dot`
- `npm run typecheck`
