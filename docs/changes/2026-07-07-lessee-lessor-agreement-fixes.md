# Lessee/Lessor Agreement Fixes

## Context

Fixed the confirmed end-to-end lessee/lessor agreement audit findings recorded in `2026-07-07-lessee-lessor-agreement-audit-findings.md`.

## Changes

- Blocked structural draft agreement edits once dependent records exist.
  - `RentalAgreementService` now rejects changes to party, period, billing settings, payment terms, and currency when the draft agreement already has allocations, rate versions, or a deposit requirement.
  - Non-structural draft edits such as remarks and terms remain owned by the agreement module.

- Rejected unsupported lessor deposit payloads at the backend boundary.
  - `StoreRentalAgreementRequest` prohibits `deposit` unless `agreement_kind` is `customer_rental`.
  - `RentalAgreementService` also rejects any non-null deposit payload for `owner_supply` agreements so service callers cannot silently discard unsupported data.

- Enforced the customer-rental-only deposit invariant in schema.
  - `rental_deposit_requirements` now stores `agreement_kind`.
  - The deposit table references `rental_agreements` through `agreement_id`, `tenant_id`, and `agreement_kind`.
  - A database check/trigger keeps deposit requirements limited to `customer_rental`.

- Blocked wrong-mode agreement operations in the UI.
  - Lessee/lessor detail routes now show only a warning and a link to the correct agreement page when the loaded record kind does not match the route mode.
  - Allocation, activate/terminate, and running-chart panels are hidden in the mismatched route state.

## Verification

- `php artisan test tests/Feature/VehicleRental/RentalAgreementCreateTest.php tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/RentalCalculationIntegrityContractTest.php tests/Unit/VehicleRental/VehicleRentalModuleBaselineTest.php`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx resources/js/app/navigation/navigationUtils.test.ts --reporter=dot`
- `npm run typecheck`
- `npm run lint` passed with 0 errors and existing React Hooks warnings in rental pages.
- `git diff --check`
