# Vehicle Service end-to-end audit findings

Date: 2026-07-03

## Scope

Reviewed the current Vehicle Service backend, frontend workflow, recent change notes, and the external Vehicle Service business audit. No production code was changed in this pass.

## Findings

- Inventory issue readiness can be misleading when stock is stored in warehouse locations. Vehicle Service checks warehouse-level availability without a location, but Inventory posting writes against the exact selected stock dimensions. The current UI only selects a warehouse, so users can see a line as ready and then fail the actual issue.
- The job edit form exposes `customer_complaint`, but the job update service does not persist it. The value is only used during create, so editing that field from the job form can silently lose the user's change.
- Vehicle Service line UOM selection is generic and backend validation only checks active UOM scope. It does not enforce item-UOM compatibility at line entry, unlike the Item/Purchase paths, so bad UOM choices can be deferred to later inventory conversion failures.
- Payment mutation versioning uses `expected_job_version` and a separate `InvalidArgumentException` path while the rest of Vehicle Service uses `expected_version` and the shared validation-error path. This is functional but creates unnecessary API-contract drift inside one module.
- The current job/invoice model has only one customer on the job and invoices that same customer. The business audit requires separate registered customer and bill-to/invoice debtor support.
- The current implementation covers generic job lines, inventory issue, labour/service assignment, invoices, payments, and documents, but it does not yet model the source-document families from the business audit: material issue notes, outside work orders, labour charge source documents, delivery authorization/gate pass, or job profitability by source cost versus selling value.

## Verification

- `php artisan test app/Modules/VehicleService/Tests --stop-on-failure` passed: 20 tests, 138 assertions.
- `php artisan route:list --path=api/v1/vehicle-service --except-vendor` listed 35 Vehicle Service routes.
- `git diff --check` passed before this audit record was added.
- `npx vitest run resources/js/modules/vehicle-service --reporter=dot --silent=true` and `npm run typecheck` could not complete because the machine had no free space on `C:\`, and subsequent D-drive cache attempts hit Node out-of-memory failures.
