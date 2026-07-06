# Vehicle Rental Deep Audit Findings

## Why

Vehicle rental was audited end to end after the latest allocation, custody, running-chart, and calculation fixes to identify remaining correctness, concurrency, schema, API payload, and UI workflow gaps without changing runtime code.

## Findings

1. Deposit movements are blocked by a schema/service mismatch. `rental_deposit_links.fingerprint` is required and unique, but `RentalDepositService::link()` creates receipt, application, refund, forfeiture, and reversal links without a fingerprint.
2. Repair expense recoveries are classified as other recoveries during billing. `RentalCalculationService` compares the cast `RentalExpenseType` enum to the raw string `repair`, so repair expenses never select the repair component code.
3. Expense allocations are not safely governed after calculation draft creation. Calculation sources snapshot usage fact versions but do not snapshot expense allocation versions, approval only checks whether another approved calculation consumed the allocation, and expense transition bulk updates change allocation statuses without incrementing allocation row versions.
4. Custody event confirmation is not timeline-locked. Confirmation locks only the custody event row, then reads confirmed events without locking the allocation or custody timeline, so concurrent confirmations can pass sequence checks against stale custody state.
5. Allocation and replacement source selections are not fully conflict-aware. Owner source allocations and finance agreements are locked or validated by current state, but the request and frontend lookup flow do not carry expected source/finance row versions. The generic allocation request also accepts `replaces_allocation_id`, but replacement ownership belongs to the replacement workflow.
6. Rate-version creation is not version-checked against the reviewed agreement. The create request has no expected agreement row version, and the service only locks the agreement and checks terminal status before creating a new contract rate version.
7. Vehicle finance exposes unsupported interest methods. The UI and request allow `reducing_balance` and `custom`, but automatic schedule generation always uses flat interest unless a custom schedule is supplied, and the UI does not capture a custom schedule.
8. Some API payloads still expose raw relationship IDs instead of structured related objects: agreement `reservation_id`, calculation line `source_id`, and deposit link `reverses_link_id`.
9. Deposit link reversal is backend-only. The route and service exist, but the vehicle rental frontend API/page has no reversal helper or UI action, so users cannot correct deposit movements from the rental UI.

## Verification

- Read the latest `/docs/changes` records before review.
- Reviewed vehicle rental routes, requests, resources, services, models, migrations, frontend API helpers, lookup components, and key rental pages.
- `php artisan test tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/RentalCalculationIntegrityContractTest.php`
- `php -l app/Modules/VehicleRental/Services/RentalDepositService.php`
- `php -l app/Modules/VehicleRental/Services/RentalCalculationService.php`
- `php -l app/Modules/VehicleRental/Services/RentalCustodyService.php`

## Notes

No runtime code was changed in this audit pass.
