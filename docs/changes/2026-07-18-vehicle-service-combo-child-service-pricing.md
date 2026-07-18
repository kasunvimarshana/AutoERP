# Vehicle Service combo child service pricing

Date: 2026-07-18

## Problem

Expanding a Vehicle Service combo created every child with a hardcoded zero unit price. Labour and service children therefore ignored their effective Item service-price revisions, displayed zero after being added to a job, and produced a zero line total for percentage commission and technician reporting.

The line editor also treated create responses and later list responses differently: create responses were flattened while list responses retained nested children. Local job totals summed every displayed row, including non-billable combo children, which would inflate the screen total once child prices became non-zero.

## Correction

- Resolve service and labour combo-child prices through the Item-owned `ItemPriceResolutionService`.
- Resolve by the child item, selected bundle UOM, variant, organization unit, tenant base currency, and Vehicle Service job date.
- Persist the resolved amount as the immutable job-line price snapshot.
- Reject combo expansion with an explicit error when a service or labour child has no applicable service price; the existing transaction rolls back the parent and every child atomically.
- Keep combo children non-billable so the combo parent remains the only customer charge and invoice source.
- Flatten nested combo children when loading the job-line list so child rows and their prices remain visible after reload.
- Recalculate frontend local totals from active billable lines only, matching the authoritative backend rule and preventing combo-child double counting.

## Impact and relationship review

No schema or relationship changed.

- Item remains the owner of effective-dated service-price revisions.
- Vehicle Service snapshots the resolved price when it creates the combo child.
- The combo parent remains billable; children remain operational detail for inventory, workforce, commission, and reporting.
- Customer invoice totals, backend job totals, supervisor commission, and inventory quantities are unchanged by non-billable child prices.
- Labour percentage commission and Technician Work line totals now use the configured labour service price as intended.

## Verification

Passed:

```text
PHP syntax checks
Laravel Pint
git diff --check
npm run typecheck -- --pretty false
npm run lint
npx vitest run resources/js/modules/vehicle-service/pages/VehicleServiceJobDetailPage.test.ts --reporter=dot --silent=true
npm run test                                  301 tests
npm run build
php artisan test --filter=combo               4 tests, 20 assertions
php artisan test --filter=VehicleServiceLabourCommissionSplitTest
php artisan test --filter=VehicleServiceEngineTest
php artisan test                              712 tests, 8282 assertions
```

MySQL verification was not runnable in this environment: `composer` is unavailable and the direct PHPUnit MySQL configuration correctly refused to run because no guarded MySQL/MariaDB test connection and `_test` database were configured.
