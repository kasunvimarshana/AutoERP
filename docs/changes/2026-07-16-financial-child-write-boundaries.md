# Financial child write boundaries

Date: 2026-07-16

## Problem

Authoritative financial child records still declared `guarded = ['id']`. That allowed a future broad `create()` or `fill()` call to assign tenant scope, document ownership, amounts, statuses, snapshots, realization data, or allocation state without going through the service that owns those invariants.

Affected models:

- `InvoiceLine`
- `InvoiceBalance`
- `PaymentLine`
- `PaymentAllocation`

## Correction

The four child models now inherit the Core deny-by-default mass-assignment policy.

Their owning services use explicit `forceFill()` followed by `save()`:

- Invoice line creation remains in `InvoiceLineService`.
- Invoice balance creation remains in `InvoiceBalanceService`.
- Payment line creation remains in `PaymentCreationService`.
- Payment allocation creation remains in `PaymentAllocationService`.

Test fixtures that intentionally construct Payment lines now use the same explicit write style instead of making the production model permissive.

## Relationships reviewed

No schema or relationship changed.

The following relationships are valid and remain unchanged:

- Invoice has many Invoice lines.
- Invoice has one Invoice balance.
- Payment has many Payment lines.
- Payment has many Payment allocations.
- Payment allocations reference invoices for settlement history.

These are business-owned, directional relationships rather than redundant or circular schema ownership. The defect was write authority, not relationship design.

## Verification

A focused boundary test asserts that all four authoritative child models are totally guarded.

Run:

```bash
git diff --check
php artisan test --filter=FinancialChildWriteBoundaryTest
php artisan test --filter=PaymentCreationIdempotencyTest
php artisan test
composer test:mysql
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
```
