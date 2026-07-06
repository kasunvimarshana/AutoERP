# Vehicle Rental Deep Audit Production Fixes

Date: 2026-07-06 07:04:33 +05:30

## Context

Implemented the production hardening fixes identified in `2026-07-06-vehicle-rental-deep-audit-findings.md`.

## Changes

- Added deterministic fingerprints to rental deposit links and exposed reversed deposit movements through structured `reverses_link` objects instead of raw reversal IDs.
- Added a frontend deposit movement table with a guarded reverse action wired to the existing deposit-link reversal endpoint.
- Made owner-source and finance-source allocation selections conflict-aware by carrying selected row versions from lookup results through allocation and replacement requests.
- Removed public `replaces_allocation_id` input from direct allocation creation; replacement ownership remains inside the replacement workflow.
- Locked custody allocation timelines during confirmation to prevent overlapping handover/return confirmation races.
- Added agreement row-version enforcement for standalone rental rate-version creation.
- Revalidated calculation source snapshots before approval, including usage fact versions, context fingerprints, expense allocation versions, and expense allocation status.
- Fixed repair expense recovery classification by comparing against the `RentalExpenseType::Repair` enum and bumping expense allocation row versions during status transitions.
- Replaced raw API response IDs for agreement reservation, calculation line source, and deposit reversal links with structured related objects.
- Implemented reducing-balance vehicle finance schedule generation and removed unsupported custom schedule selection from the UI while requiring explicit schedules for API-level custom finance agreements.
- Updated vehicle rental TypeScript contracts, lookup payloads, and focused contract tests for the corrected API shape and concurrency requirements.

## Verification

- `php -l` on all modified PHP files.
- `php artisan test tests/Unit/VehicleRental/RentalEndToEndContractFixTest.php tests/Unit/VehicleRental/RentalCalculationIntegrityContractTest.php`
- `npx vitest run resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx resources/js/modules/vehicle-rental/pages/RentalCustodyPage.test.tsx --reporter=dot`
- `npm run typecheck`
- `php artisan test tests/Unit/VehicleRental tests/Feature/VehicleRental`
- `git diff --check`
