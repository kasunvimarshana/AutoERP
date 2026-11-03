# Vehicle Rental Reservation Snapshots and Parity Verification

## Request

Conduct a comprehensive, end-to-end, in-depth audit of the entire VehicleRental module on branch `worktree-0.0.8` against `worktree-0.0.8-vehicle-rental-module`. Verify complete feature parity across all components, audit every CRUD operation, inspect data integrity and snapshot immutability, and document all findings.

## Changes

- **Reservation Snapshot Synchronization**: Hardened `RentalReservationService::update()` so that if `customer_id` or `currency_id` is updated on a draft/pending reservation, the corresponding denormalized canonical snapshots (`customer_name_snapshot` and `currency_code_snapshot`) are updated in lockstep. Previously, updating customer or currency preserved the initial snapshots, creating stale denormalized references.
- **Reservation Test Hardening**: Extended `RentalReservationTest` to assert that updating reservation customer and currency updates the snapshots and increments row version atomically.
- **Deep Parity Verification**: Verified that all 12 aggregates across the reference branch and target branch provide 100% operational feature parity with full UI and API routes (Dashboard, Reservations, Customer Agreements, Owner Agreements, Vehicle Use Dispatches, Running Charts, Fleet Expenses, Vehicle Finance, Incident Evidence, and Month-Grid Running Chart Reports).

## Verification

- **Backend Automated Tests**: `php artisan test --filter=VehicleRental`: 162 passed, 1,457 assertions in 30.90s.
- **Frontend Automated Tests**: `npx vitest run resources/js/modules/vehicle-rental`: 22 test files passed, 90 tests passed in 11.84s.
- **TypeScript Typecheck**: `npm run typecheck -- --pretty false`: 0 errors.
