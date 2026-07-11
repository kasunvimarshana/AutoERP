# Lessor/Lessee Agreement Draft Edit Timezone and Lock Fix

## Context

The lessor and lessee agreement draft edit flow could surface confusing validation failures after rate versions, allocations, or deposit requirements existed. The form also converted API timestamps through the browser timezone, which could make unchanged agreement periods look modified when the user's local timezone differed from the business timezone.

## Changes

- Converted agreement edit date/time inputs through the configured business timezone instead of the browser timezone.
- Prevented locked structural agreement fields from being resubmitted once dependent rental records exist.
- Disabled locked party, period, billing, payment term, and currency controls on draft edit while leaving execution details, legal context, remarks, and optional clauses editable.
- Added field-level validation feedback on the agreement form.
- Allowed the shared customer and supplier lookup controls to receive a disabled state.
- Added a regression test proving locked draft edits omit structural fields and preserve business-timezone display.

## Verification

- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx --reporter=dot`
- `npm run typecheck -- --pretty false`
- `php artisan test tests/Feature/VehicleRental/RentalAgreementCreateTest.php tests/Unit/VehicleRental/RentalAgreementIntegrityContractTest.php`
- `npm run lint`
- `git diff --check`
