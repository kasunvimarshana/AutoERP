# Vehicle Rental Calculation and Expense Lifecycle Fixes

Date: 2026-07-07

## Context

Implemented the confirmed Vehicle Rental deep-audit fixes for calculation quantity correctness, expense lifecycle ownership, calculation aggregate versioning, and guided rental expense relationship entry.

## Changes

- Reworked rental calculation quantities so base rental and driver salary use explicit unit strategies, with monthly rates prorated through the existing billing basis and proration rule enums.
- Calculated prorated monthly monetary amounts from rate and day denominators directly, avoiding six-decimal quantity truncation from changing invoice/payable amounts.
- Rejected unsupported driver salary and overtime unit combinations when rate versions are created.
- Moved calculation-driven expense allocation consumption back into `RentalExpenseService`, including locked allocation/expense updates, row-version increments, and rental status history records.
- Versioned calculation child rows consistently by bumping calculation line, source, and billing-period row versions when their statuses change.
- Exposed billing-period and calculation-line row versions through calculation resources and frontend types.
- Updated the rental expense UI so customer recoveries and owner deductions require a target agreement, derive the customer/supplier from that agreement, and clear stale dependent selections when the relationship context changes.
- Removed stale rental expense eager-load relationships that did not exist on the model.

## Verification

- `php artisan test tests/Feature/VehicleRental tests/Unit/VehicleRental`
- `npm run test -- --run resources/js/modules/vehicle-rental/pages/RentalExpensePage.test.tsx resources/js/modules/vehicle-rental/pages/RentalAgreementPages.test.tsx resources/js/modules/vehicle-rental/pages/RentalAllocationPage.test.tsx`
- `npm run typecheck -- --pretty false`
- `git diff --check`

Result: VehicleRental backend tests passed with 29 tests and 472 assertions; requested frontend tests passed with 12 tests; frontend typecheck passed.
