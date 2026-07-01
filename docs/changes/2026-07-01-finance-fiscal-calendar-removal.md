# Finance fiscal calendar removal

Date: 2026-07-01

## Problem

Finance posting depended on `finance_fiscal_years` and `finance_fiscal_periods` even though posting, reports, ledger, and budgets already have date-based sources of truth. The extra fiscal-calendar tables added mandatory setup and foreign-key coupling without owning required business behavior.

## Correction

Removed the Finance fiscal-year and fiscal-period tables, models, status request, service, routes, permissions, seeding, frontend page, and client API/types. Journal posting, ledger entries, balances, trial balance, and budgets now use journal dates, date ranges, budget years, and budget months directly. Downstream Finance migrations no longer carry fiscal-calendar columns or foreign keys.

Voucher and report surfaces no longer load fiscal-calendar relations from Finance journals. Tests and schema docs were updated to match the date-based design.

## Verification

- `php artisan test app/Modules/Finance/Tests/FinanceEngineTest.php app/Modules/Finance/Tests/FinanceHardeningTest.php app/Modules/Finance/Tests/FinancePostingContractTest.php app/Modules/Finance/Tests/FinanceEnterpriseCoreTest.php app/Modules/Finance/Tests/FinanceApiWorkflowTest.php app/Modules/Payment/Tests/PaymentFinanceIntegrationTest.php app/Modules/Invoice/Tests/InvoiceFinanceIntegrationTest.php tests/Feature/Api/CoreModulesApiTest.php --stop-on-failure`
- `php artisan test app/Modules/Sales/Tests/FastSalesTest.php app/Modules/Purchase/Tests/FastPurchaseTest.php app/Modules/VehicleService/Tests/VehicleServiceEngineTest.php --stop-on-failure`
- `php artisan test app/Modules/Finance/Tests --stop-on-failure`
- `npm run typecheck`
- `npx vitest run resources/js/app/access/resolvedRouteEntitlements.test.ts --reporter=dot --silent=true`
- `git diff --check`

`php artisan test --stop-on-failure` was also attempted and stopped at `Tests\Feature\Core\ApplicationBootstrapContractTest::test_development_startup_clears_config_and_passes_readiness_before_processes_start`, where the test still expects `php artisan serve` but `composer dev` now uses `php artisan serve --no-reload`.
