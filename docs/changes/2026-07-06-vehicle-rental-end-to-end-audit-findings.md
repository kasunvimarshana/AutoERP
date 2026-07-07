# Vehicle Rental End-to-End Audit Findings

## Why

Vehicle rental allocation, replacement, custody, running-chart usage, and billing paths were audited after the recent allocation fixes to verify source ownership integrity, stale-write safety, guided UI flows, relationship payload shape, and validation completeness.

## Findings

1. Owner-supplied customer allocation is blocked by the current UI flow. The available vehicle lookup excludes vehicles already covered by an owner-supply allocation, while the source allocation lookup is disabled until a vehicle is selected.
2. Company-owned allocation does not require or resolve a company ownership record. A customer allocation can mark a vehicle as `company_owned` while persisting `vehicle_ownership_id` as null, so supplier/customer-owned vehicles can be misclassified if the API omits the ownership reference.
3. Allocation-dependent create paths are not version-checked. Custody event creation and running-chart usage creation lock the allocation but do not require the allocation row version reviewed by the user.
4. Billing calculation creation is not version-checked against the reviewed agreement. The calculation request and UI submit agreement id, period, and financial side without an expected agreement row version.
5. The generic custody page exposes replacement-only event types. `replacement_out` and `replacement_in` require a replacement reference, but the page offers them without capturing one, creating a backend rejection path users can select.
6. Replacement request validation does not validate nested custody item fields. `old_return.items` and `new_handover.items` are accepted as arrays, but the service expects each item to contain the custody item fields required by the normal custody request.
7. `RentalCustodyEventResource` still exposes `replacement_id` as a raw relationship id instead of returning a structured replacement summary.

## Verification

- Read recent `/docs/changes` records before review.
- Reviewed vehicle rental allocation, replacement, custody, usage, billing, lookup, resource, request, controller, service, model, and migration paths.
- Checked the referenced video files' metadata; local `ffprobe`, `ffmpeg`, and Python/OpenCV tooling were unavailable, so frame-level video inspection was not performed.
- `php artisan test tests\Unit\VehicleRental\RentalEndToEndContractFixTest.php tests\Unit\VehicleRental\VehicleRentalModuleBaselineTest.php`
- `npm run typecheck`

## Notes

No runtime code was changed in this audit pass.
