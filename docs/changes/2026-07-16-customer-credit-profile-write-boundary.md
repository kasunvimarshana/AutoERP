# Customer credit profile write boundary

Date: 2026-07-16

## Problem

`CustomerCreditProfile` is the authoritative customer-owned credit-policy record, but the model still allowed broad mass assignment. A future broad `create()` or `fill()` call could assign tenant scope, customer ownership, credit limits, eligibility flags, or row versions outside the service that owns those rules.

## Correction

`CustomerCreditProfile` now inherits the Core deny-by-default mass-assignment policy.

`CustomerCreditProfileService` remains the single write owner and now uses explicit `forceFill()` followed by `save()` for both first creation and version-checked updates.

The credit calculation, eligibility meaning, defaults, and optimistic-concurrency behavior were not changed.

## Relationships reviewed

No schema or relationship changed.

The Customer-to-credit-profile relationship remains valid because one customer owns one current authoritative credit-policy aggregate. The defect was write authority, not relationship design.

Invoice credit authorization was not guessed in this batch. Exact exposure calculation, over-limit override authority, and audit requirements still require an approved business contract before enforcement can be added safely.

## Verification

A focused boundary test asserts that the credit profile is totally guarded. Existing Customer engine tests exercise creation, retrieval, and version-checked update through the owner service.

Run:

```bash
git diff --check
php artisan test --filter=CustomerCreditProfileWriteBoundaryTest
php artisan test --filter=CustomerEngineTest
php artisan test
composer test:mysql
npm run typecheck -- --pretty false
npm run lint
npm run test
npm run build
```
