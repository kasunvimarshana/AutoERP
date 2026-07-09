# Vehicle Rental Production Readiness Audit Findings

Date: 2026-07-09

## Context

Reviewed the current Vehicle Rental module after the latest lessor/lessee agreement separation and route-state fixes. This pass checked recent change records, current repository state, module routes, authorization, schema state, concurrency patterns, frontend permission handling, automated tests, production build, and dependency advisories.

No runtime code was changed in this pass.

## Verdict

The core agreement, allocation, running-chart, calculation, invoice/payable, deposit, and document flows have strong automated coverage and pass the current verification gates. The module is suitable for staging or a controlled pilot, but the complete Vehicle Rental surface should not be declared fully production ready until the vehicle-finance findings below are fixed and verified.

## Findings

### 1. Finance installment status refresh is neither automatic nor conflict safe

Severity: High

`VehicleFinanceService::refreshDueStatuses()` is the only path that changes scheduled installments to due, overdue, partially paid, or paid. The method is exposed through an authenticated HTTP endpoint, but there is no frontend caller, scheduled command, or scheduler registration that invokes it automatically.

The refresh also reads installments in chunks and saves derived balances, statuses, and incremented row versions without a transaction, row lock, or conditional optimistic update. A concurrent payable or payment-related write can therefore use the same installment version while the refresh overwrites derived state or fails to advance the version as a unique concurrency token.

Impact: installment statuses can remain stale indefinitely in normal UI usage, and manually invoking the refresh does not satisfy the project's atomic, conflict-aware write contract.

Recommended direction: make due-status refresh an owner-module scheduled command or an explicitly documented deployment job. Process each bounded batch transactionally, lock the affected installment and invoice state in a deterministic order, and advance versions from the locked current row. Add concurrency and scheduler-registration coverage.

### 2. Finance payable action uses the wrong frontend permission

Severity: Medium

The backend correctly protects installment payable creation with `vehicle-rental.financial.create`. `VehicleFinancePage` shows the "Create payable" action using `vehicle-rental.finance-agreements.manage` instead.

Impact: a user allowed to manage finance agreements but not create financial documents sees an action that returns a permission error, while a financial-document creator without agreement-management permission cannot access the action intended for that permission.

Recommended direction: add a dedicated `canCreateDocument` check using `vehicleRentalPermissions.financialCreate`, retain `canManage` for agreement creation and activation, and add a focused permission regression test.

## Confirmed Guardrails

- All 26 Vehicle Rental migrations are applied in the local MySQL database.
- All 56 Vehicle Rental API routes require authentication, tenant context, organization context, and the Vehicle Rental tenant feature.
- Reviewed controller actions enforce explicit Vehicle Rental permissions.
- Core mutating services use transactions, row locks, and expected versions across the principal agreement, allocation, custody, usage, calculation, invoice, deposit, and finance-document paths.
- No current TODO/FIXME markers, migration patch tables, or dynamic migration loops were found in the module.
- Composer and production npm dependency audits report no known vulnerabilities.

## Verification

- `php artisan test tests/Feature/VehicleRental tests/Unit/VehicleRental`
  - 35 tests passed with 546 assertions.
- `php artisan test`
  - 583 tests passed with 6,627 assertions.
- `npm test -- --run`
  - 59 files and 217 tests passed.
- `npm run typecheck -- --pretty false`
- `npm run lint`
- `npm run build`
- `composer audit --locked --no-interaction`
- `npm audit --omit=dev --audit-level=high`
- `php artisan migrate:status --no-interaction`
- `php artisan route:list --path=vehicle-rental --json`
- `git diff --check`
