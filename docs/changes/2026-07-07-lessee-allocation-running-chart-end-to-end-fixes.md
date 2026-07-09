# Lessee Allocation Running Chart End-to-End Fixes

Date: 2026-07-07

## Context

The lessee agreement, allocation, and running-chart audit found three workflow and integrity gaps:

- With-driver lessee allocations had no frontend path to assign drivers, even though running-chart entry requires an active driver assignment.
- Running-chart usage and commercial fact edits could cross rental rate-version boundaries while billing rejects mixed-rate calculation periods later.
- Reversed running-chart entries still occupied the global fingerprint unique key, blocking exact re-entry after reversal.

## Changes

- Added a guided driver assignment form to the rental allocation detail page using the shared employee lookup and the existing `assignRentalDriver` API.
- Added rental rate-period guards in `RentalRateVersionService` and applied them from physical usage creation and commercial usage fact edits.
- Blocked rate activation when a new rate boundary would split existing unreversed running-chart usage.
- Added `fingerprint_sequence` to `rental_usage_logs` and changed usage creation to return existing unreversed duplicates while allowing a new sequence after all exact matches are reversed.
- Updated focused contract coverage and added a feature test for rejecting usage periods that cross active rate-version boundaries.

## Verification

- `php artisan test tests/Unit/VehicleRental/RentalAgreementIntegrityContractTest.php tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/RentalCalculationIntegrityContractTest.php tests/Feature/VehicleRental/RentalAgreementCreateTest.php`
- `npm run typecheck -- --pretty false`
- `npx eslint resources/js/modules/vehicle-rental/pages/RentalAllocationDetailPage.tsx`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx resources/js/modules/vehicle-rental/pages/RentalCustodyPage.test.tsx --reporter=dot`
