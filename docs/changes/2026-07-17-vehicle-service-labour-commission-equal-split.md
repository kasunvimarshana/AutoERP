# Vehicle Service labour commission equal split

Date: 2026-07-17

## Context

A Vehicle Service labour line calculated the full commission independently for every assigned worker. Assigning multiple workers with the same labour-item commission policy therefore multiplied the intended commission instead of allocating one line-level pool.

## Correction

- added exact equal allocation for assignments on the same line that share one commission type and value;
- retained assignment-specific calculation when workers intentionally use different commission policies, because those records do not represent one shared pool;
- recalculated allocation after employee assignment creation, update, cancellation, and deletion;
- excluded cancelled assignments from the recipient count and reset their unearned commission amount to zero;
- assigned any six-decimal remainder to the final stable assignment so allocated amounts always equal the original commission pool exactly;
- kept the existing Vehicle Service job row lock and transaction boundary, so concurrent assignment changes remain serialized and atomic.

## Relationship review

No database relationship or schema was changed.

The existing relationships remain justified:

- a Vehicle Service job line owns its workforce assignments;
- each workforce assignment references one HR employee;
- commission policy snapshots remain on assignments for historical reporting and intentional per-worker overrides.

Changing or removing those relationships was not required to fix the calculation defect and would add unrelated migration and data-integrity risk.

## Verification

Required commands:

```bash
php artisan test --filter=VehicleServiceLabourCommissionSplitTest
php artisan test --filter=VehicleServiceEngineTest
php artisan test
composer test:mysql
git diff --check
git status --short
```

The implementation also requires PHP syntax checks for the modified Vehicle Service services and focused regression test.
