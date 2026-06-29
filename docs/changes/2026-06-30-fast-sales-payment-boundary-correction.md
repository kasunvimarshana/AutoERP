# Fast Sales Payment Boundary Correction

Date: 2026-06-30

## Scope

Implemented the first open Fast Sales blocker slice from the completed-marked TODO list: customer receipt creation no longer accepts client-selected Finance deposit accounts or directly posts receipt journals from Sales.

## Root causes

- `FastSalesService` still depended on obsolete Payment lifecycle inputs such as `PaymentStatus`, `bankAccountId`, and payment-line `internalBankAccountId`.
- Fast Sales exposed `payment_accounts` in context responses and accepted `destination_account_id` in receipt payloads, allowing the Sales UI/API to select internal Finance accounts.
- Fast Sales built receipt Finance posting lines itself, placing Payment-owned posting responsibility in the Sales module.
- Fast Sales tests had drifted behind current tenant execution, payment method, item pricing, tax, and reference-data schemas.

## Changes

- Removed Fast Sales `PaymentStatus` usage and obsolete Payment DTO named arguments.
- Removed `payment_accounts` from Fast Sales context responses.
- Removed the frontend Deposit account selector and stopped sending `destination_account_id`.
- Prohibited receipt account fields in both Fast Sales request validation and service-level authority checks.
- Changed customer receipt creation to use Payment-owned draft creation followed by submit, approve, and post lifecycle services.
- Removed direct Fast Sales receipt-journal posting; Payment now owns receipt posting and pending allocation realization.
- Updated Sales payment preparation to pass metadata into the current `CreatePaymentData` contract.
- Updated Fast Sales tests for tenant/current-user context, current payment method schema, temporal item price revisions, tenant-owned tax rows, timezone reference data, and Payment lifecycle assertions.

## Verification

- `php -l` passed for all changed PHP files.
- `php artisan test app/Modules/Sales/Tests/FastSalesTest.php` passed: 14 tests, 72 assertions.
- `git diff --check` passed.
- Static scan confirms Sales production code no longer references `PaymentStatus`, `payment_accounts`, `requires_bank_account`, `bankAccountId`, `internalBankAccountId`, or direct receipt posting helpers.
- Static scan confirms `destination_account_id` remains only as a prohibited field and in the rejection test.

## Remaining gates

- Full TypeScript typecheck is still blocked by existing Purchase payment form invoice snapshot typing errors unrelated to this Fast Sales slice.
- Remaining TODO items outside this slice include broader Payment cleanup in other modules, database integrity audits, Vehicle Rental workflow work, and full release verification.
