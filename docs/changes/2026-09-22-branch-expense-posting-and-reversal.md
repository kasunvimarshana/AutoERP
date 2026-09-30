# Branch expense posting and reversal

Date: 2026-09-22

## Why

The system needed a branch/location-aware expense workflow where users can maintain reusable expense types, record paid expenses against an approved payment method, and have those costs reflected in Finance reports without creating artificial customer or supplier payment balances.

## What changed

- Added the Expense module with tenant-wide expense types and branch-scoped expense records.
- Added focused screens for expense listing, immediate expense entry, type maintenance, and controlled reversal.
- Added Payment-owned payment-method lookup integration so Expense can use active methods without owning or duplicating Payment data.
- Added an `expense_payment` Finance posting profile and an Operating Expense account role. Posting creates a balanced `Dr Operating Expense / Cr Cash or Bank` journal using the selected method type.
- Added idempotent posting, row-version conflict checks, atomic transactions, immutable posted records, and reversal journals with audit details.
- Added expense permissions, routes, navigation entries, API resources with human-readable related data, and schema documentation.

## Data and schema impact

- Added `expense_types` for reusable tenant-owned expense classifications.
- Added `expenses` for original branch, currency, payment-method, amount, exchange-rate, snapshot, posting, and reversal data.
- Existing revenue records are not changed. Posted expenses flow through the Finance ledger and reduce net profit through the existing profit-and-loss calculation.
- Cash payment methods credit the configured branch Cash role; other active payment methods credit the configured branch Bank role.

## Verification

- Expense posting and reversal test passed, including idempotency, balanced ledger entries, and profit-and-loss impact.
- Finance seeder and Payment posting policy regression tests passed: 8 tests, 72 assertions in the focused suite.
- PHP Pint check, targeted ESLint, PHP syntax checks, route registration, and `git diff --check` passed.
- Production frontend build passed.
- TypeScript validation reports only the pre-existing unrelated `VehicleServiceHistoryReportPage.tsx` optional `meta` warning.
