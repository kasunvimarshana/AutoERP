# Customer sequence drift recovery

Date: 2026-07-06

## Problem

Creating a new customer from the vehicle service quick-registration flow failed with an unexpected server error even though the payload was valid.

The backend log showed a duplicate `customer_number` collision:

- the seeded walk-in customer already used `CUS-000001`;
- the customer sequence still generated `CUS-000001`;
- the insert then failed on the tenant-scoped unique key for customer numbers.

This meant the customer sequence could drift behind real customer data and break normal customer creation.

## Correction

Fixed the customer module in two places:

- the customer seed now keeps the customer sequence aligned with the seeded walk-in customer by setting the next value to `2`;
- the customer number service now self-heals stale customer sequences by checking for an existing generated number, advancing the sequence past the highest existing `CUS-` number in that tenant, and retrying generation.

This corrects both fresh seeded environments and already-existing local databases with stale customer sequence state.

## Verification

- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan test --filter=CustomerEngineTest`
- Added coverage for stale customer sequence recovery in `CustomerEngineTest`
