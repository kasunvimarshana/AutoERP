# 2026-11-26 — Currency Audit Architecture Integrity & Multi-Currency Exposure Refinements

## Context & Objectives
- Address architectural regressions and review deficiencies identified during the review of the currency audit:
  1. Broken dependency graph: Eliminated circular dependency between `Invoice` and `Payment` modules (`Invoice <-> Payment` cycle) caused by direct model queries to `Modules\Payment\Models\Payment` in `InvoiceBalanceProvider`.
  2. Broken tenant isolation boundary: Resolved `TenantIsolationArchitectureTest` violations in `CurrencyCatalogService::assertStatusChangeAllowed` by injecting `TenantExecutionContextInterface` and properly separating tenant-scoped queries from platform-level cross-tenant checks using `runAsControlPlane`.
  3. Global scope escape hatch elimination: Replaced forbidden `HrBiometricDevice::withoutGlobalScopes()` calls in `HrBiometricPushReceiverController` with explicit `runAsControlPlane` execution for unauthenticated biometric device push endpoints.
  4. Robust rate resolution and unconverted currency handling: Resolved target currency exchange rates via `Invoice::query()` supporting both `currency_id` and `currency_code_snapshot`. Ensured party exposure computation in `InvoiceBalanceProvider::getPartyExposureInCurrency` explicitly reports unconverted foreign currencies when rates are missing, with `VehicleServicePaymentOptionService` halting credit availability rather than silently omitting exposure.
  5. UI Display Consistency: Preserved explicit currency code parameter in `CustomerSummaryCard.tsx` and `SupplierSummaryCard.tsx` for party credit limit displays.

## Modified & Created Files Summary
| File | Action | Description |
|---|---|---|
| `app/Modules/Invoice/Services/InvoiceBalanceProvider.php` | Modified | Removed `use Modules\Payment\Models\Payment;` to break circular module dependency; updated `getPartyExposureInCurrency` to look up rates from `Invoice` only (by `currency_id` or `currency_code_snapshot`). |
| `app/Modules/ReferenceData/Services/CurrencyCatalogService.php` | Modified | Injected `TenantExecutionContextInterface`; added tenant-scoped queries when tenant context is active, and explicit `runAsControlPlane` for platform-level cross-tenant validation. |
| `app/Modules/Hr/Http/Controllers/HrBiometricPushReceiverController.php` | Modified | Replaced `HrBiometricDevice::withoutGlobalScopes()` with `$this->tenantContext->runAsControlPlane(...)` across ZkTeco, Hikvision, and Generic Webhook push endpoints. |
| `app/Modules/VehicleService/Services/VehicleServicePaymentOptionService.php` | Modified | Enhanced target exchange rate matching to check both `currency_id` and `currency_code_snapshot`. |
| `app/Modules/Invoice/Tests/InvoiceBalanceProviderTest.php` | Modified | Added tests for rate auto-resolution from historical invoices and handling of missing exchange rates with unconverted currency reporting. |
| `app/Modules/VehicleService/Tests/VehicleServiceEngineTest.php` | Modified | Added multi-currency credit assessment, over-credit policy tests, and credit blocking test when currency exchange rates are missing. |
| `docs/changes/2026-11-26-currency-audit-and-architecture-integrity-fix.md` | Created | Change record documenting architectural fixes and verification. |

## Verification & Quality Gate Results
- **TypeScript Typecheck (`npm run typecheck`):**
  - Result: 0 errors across all TS/TSX files (strict mode).
- **ESLint Analysis (`npm run lint`):**
  - Result: 0 errors across `resources/js/*`.
- **Backend Code Style (`php vendor/bin/pint --test`):**
  - Result: 0 PSR-12 violations (passed).
- **Architecture & Tenant Isolation Integrity:**
  - `php artisan test tests/Unit/Architecture/CoreModuleArchitectureTest.php`: 6 passed (67 assertions) — acyclic module graph verified.
  - `php artisan test tests/Unit/Database/TenantIsolationArchitectureTest.php`: 8 passed (22 assertions) — 0 tenant isolation violations, 0 global scope escape hatches.
  - `php artisan test --filter ModuleMigrationBaselineTest`: 1 passed (3 assertions).
  - `php artisan test --filter HistoricalRetentionMigrationTest`: 1 passed (22 assertions).
- **Frontend Test Suite (`npx vitest run`):**
  - Result: 181 test files passed, 765 tests passed (100% pass rate).
- **Backend Module Suites (`php artisan test`):**
  - `InvoiceBalanceProviderTest`: 5 passed (28 assertions).
  - `CurrencyCatalogServiceTest`: 5 passed (11 assertions).
  - `ExpensePostingTest`: 5 passed (34 assertions).
  - `VehicleServiceEngineTest --filter test_payment_options_`: 5 passed (26 assertions).
  - `Customer`, `Supplier`, `Payment`, `Hr` test suites: 91 passed (544 assertions).
  - Focused combined run (`Invoice`, `VehicleService`, `ReferenceData`, `Expense`, `Architecture`, `Database`): 109 passed (721 assertions).

## Author & Maintainer
- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)
- **Phone:** [+94713525253](tel:+94713525253)
- **Repository:** [kasunvimarshana/AutoERP](https://github.com/kasunvimarshana/AutoERP)
