# Fix Vehicle Rental owner-source resolution and datetime integrity

## Trigger

A customer vehicle assignment displayed `No matching owner-supply source assignment found` even though the selected vehicle already had an active owner-supply assignment.

The observed records exposed two separate causes:

1. the requested customer-use start time preceded the owner-supply start time, so the exact containment rule correctly excluded the source; and
2. browser `datetime-local` values were sent without a timezone offset and interpreted by the UTC backend as UTC, shifting the displayed local time by the browser offset.

## Preserved business invariant

```text
Owner-supply starts_at <= Customer-use starts_at
Owner-supply ends_at   >= Customer-use ends_at
```

The source containment rule remains exact. It is not relaxed to date-only matching because customer usage cannot legally or operationally precede the owner-supplied vehicle period.

## Changes

- Added one Vehicle Rental datetime utility as the frontend source of truth for:
  - local input to explicit-offset ISO conversion;
  - UTC API values to local input conversion;
  - agreement/source boundary intersection;
  - safe date-time clamping and display.
- Rental assignment, custody, replacement, source lookup, and running-chart requests now send explicit timezone offsets.
- Vehicle Rental mutation requests reject timezone-less operational datetimes.
- Backend timeline and lookup services normalize explicit instants to UTC before storage and comparison.
- Agreement date boundaries are validated against the local calendar date carried by the submitted offset before instant normalization.
- Selecting a customer vehicle now loads all planning-eligible owner sources for that vehicle.
- A unique overlapping owner source is autoloaded and the proposed customer start/end are fitted to the intersection of:
  - the customer agreement period; and
  - the owner-supply assignment period.
- Multiple valid sources remain an explicit business choice.
- Missing and non-overlapping source states explain the cause instead of silently showing an empty technical lookup.
- User-facing terminology uses `Vehicle owner agreement` instead of `Owner-supply source assignment`.

## Ownership and integrity

- Vehicle Rental owns source-period and rental-timeline validation.
- UTC storage remains authoritative for operational instants.
- Exact source coverage remains authoritative in the backend.
- Company-owned vehicles may still proceed without an owner source only when Vehicle ownership covers the complete customer period.
- No historical assignment timestamp is silently rewritten.
- No schema, permission, accounting, invoice, payment, or finance behavior is changed.

## Verification

```bash
php -l app/Modules/VehicleRental/Http/Requests/RentalDateTimeRules.php
php -l app/Modules/VehicleRental/Http/Requests/StoreRentalAssignmentRequest.php
php -l app/Modules/VehicleRental/Http/Requests/StoreRentalCustodyRequest.php
php -l app/Modules/VehicleRental/Http/Requests/ReplaceRentalAssignmentRequest.php
php -l app/Modules/VehicleRental/Http/Requests/RentalRunningChartMutationRequest.php
php -l app/Modules/VehicleRental/Http/Controllers/RentalLookupController.php
php -l app/Modules/VehicleRental/Services/RentalCustodyService.php
php -l app/Modules/VehicleRental/Services/RentalReplacementService.php
php -l app/Modules/VehicleRental/Services/Validation/RentalAssignmentTimelineGuard.php
php -l app/Modules/VehicleRental/Services/Validation/RentalAssignmentSourceGuard.php
php -l app/Modules/VehicleRental/Services/Validation/RentalRunningChartTimelineGuard.php

php artisan test --filter=RentalAssignmentSourceValidationContractTest
php artisan test --filter=RentalRunningChartTimelineGuardTest
php artisan test --filter=VehicleRental
php artisan test

npx vitest run resources/js/modules/vehicle-rental/rentalDateTime.test.ts
npx vitest run resources/js/modules/vehicle-rental/vehicleRentalUxFoundation.test.ts
npm run typecheck
npm run lint
npm run test
npm run build
```

Paid tools and GitHub Actions are not required or used.
