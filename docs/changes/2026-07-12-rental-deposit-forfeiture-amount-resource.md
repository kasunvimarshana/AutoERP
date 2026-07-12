# Rental deposit forfeiture amount resource

Date: 2026-07-12

## Evidence

`RentalDepositService` maintains `received_amount`, `applied_amount`, `refunded_amount`, `forfeited_amount`, and `balance_amount` as the authoritative deposit movement totals. The deposit status engine also uses `forfeited_amount` to determine the `forfeited` lifecycle state.

`RentalDepositRequirementResource` exposed all of those totals except `forfeited_amount`. API clients could therefore receive a forfeited or partially forfeited deposit state without the amount required to reconcile that state to the deposit balance and movement history.

## Correction

- Added `forfeited_amount` to the Vehicle Rental deposit resource beside the existing received, applied, refunded, and balance fields.
- The field uses the shared decimal serializer and preserves exact six-decimal API precision.
- Added a resource behavioral test using the real deposit model, enum, resource, request, and application decimal service.

## Ownership and scope

- Vehicle Rental remains the owner of deposit requirements and movement summaries.
- Payment remains the owner of receipt, refund, allocation, posting, reversal, and instrument lifecycles.
- No deposit calculation, status transition, payment, invoice, finance, tax, schema, or frontend behavior changed.
- No compatibility field, duplicated total calculation, or hardcoded fallback was introduced.

## Verification

Run from the latest `worktree-0.0.8` branch:

```bash
php artisan test --filter=RentalDepositRequirementResourceTest
php artisan test
composer test:mysql
```
