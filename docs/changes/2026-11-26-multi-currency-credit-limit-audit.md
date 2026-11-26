# 2026-11-26 — Multi-Currency Credit Limit Calculation and Currency System Audit

## Summary
- **Root Cause Fix for Multi-Currency Credit Limit Error**:
  - Resolved the error: `"Credit limit cannot be checked because the open invoices do not use a single currency matching the customer credit-limit currency."`
  - Previously, `VehicleServicePaymentOptionService::creditAssessment` halted and emitted a blocker warning whenever open invoices spanned multiple currencies or differed from the customer's credit limit currency, even though invoices store snapshot exchange rates against the tenant base currency.
  - Replaced this artificial restriction with authoritative multi-currency exposure calculation in `InvoiceBalanceProviderInterface::getPartyExposureInCurrency`.
- **Authoritative Balance & Exposure Service (`InvoiceBalanceProvider`)**:
  - Extended `getOutstandingTotalsForParties` to return both raw currency `amount` and base-currency `base_amount` via `DecimalMath::mul` using each invoice's recorded `exchange_rate`.
  - Implemented `getPartyExposureInCurrency` using BCMath high-precision decimal math (`DecimalMath`) to convert party outstanding invoices to any target currency (customer default currency or tenant base currency):
    - Invoices in target currency are added directly.
    - Invoices with base currency conversion convert deterministically using invoice `exchange_rate` (`amount * exchange_rate`).
    - Cross-currency invoices convert from base currency to target currency using target currency exchange rate (`base_amount / target_rate`).
- **Vehicle Service Payment Options (`VehicleServicePaymentOptionService`)**:
  - Refactored `creditAssessment`, `options`, and `creditAssessmentForConfirmation` to leverage `getPartyExposureInCurrency` with customer credit limit currency and rate.
  - Completely removed the artificial `$hasMixedCurrencies` blocker. Customers with invoices in different currencies now have their open exposure accurately aggregated and evaluated against their `credit_limit` under their tenant policy (`block_services`, `warning_only`, `ignore_policy`).
- **Currency Deactivation Protection (`CurrencyCatalogService`)**:
  - Hardened currency lifecycle validation in `CurrencyCatalogService::assertStatusChangeAllowed` to prevent deactivation of currencies that:
    1. Are the base currency of an active tenant.
    2. Are actively used by unpaid open invoices (`balance_due > 0`).
    3. Are assigned as default currency on active customers.
    4. Are assigned as default currency on active suppliers.
- **Expense Currency Invariants (`ExpensePostingService`)**:
  - Enforced that posted expenses must use an active currency.
  - Enforced that expenses in tenant base currency must use an exchange rate of `1.000000`.
  - Maintained strict backward-compatible fallback for null currency only if rate is explicitly `1.000000`.
- **UI Currency Display Integrity**:
  - Updated `CustomerSummaryCard.tsx` and `SupplierSummaryCard.tsx` so `credit_limit` renders using the party's default currency code instead of falling back to default tenant symbol without context.

## Files Modified
- `app/Modules/Invoice/Contracts/InvoiceBalanceProviderInterface.php`
- `app/Modules/Invoice/Services/InvoiceBalanceProvider.php`
- `app/Modules/VehicleService/Services/VehicleServicePaymentOptionService.php`
- `app/Modules/ReferenceData/Services/CurrencyCatalogService.php`
- `app/Modules/Expense/Services/ExpensePostingService.php`
- `resources/js/modules/customer/components/CustomerSummaryCard.tsx`
- `resources/js/modules/supplier/components/SupplierSummaryCard.tsx`
- `app/Modules/Invoice/Tests/InvoiceBalanceProviderTest.php`
- `app/Modules/VehicleService/Tests/VehicleServiceEngineTest.php`
- `app/Modules/ReferenceData/Tests/CurrencyCatalogServiceTest.php`
- `app/Modules/Expense/Tests/ExpensePostingTest.php`

## Verification
- Backend Unit & Integration Tests:
  - `php artisan test app/Modules/Invoice/Tests/InvoiceBalanceProviderTest.php` (4 passed, 21 assertions)
  - `php artisan test app/Modules/VehicleService/Tests/VehicleServiceEngineTest.php --filter test_payment_options_` (4 passed, 22 assertions)
  - `php artisan test app/Modules/ReferenceData/Tests/CurrencyCatalogServiceTest.php` (5 passed, 11 assertions)
  - `php artisan test app/Modules/Expense/Tests/ExpensePostingTest.php` (5 passed, 34 assertions)
  - `php artisan test app/Modules/Invoice app/Modules/VehicleService app/Modules/ReferenceData app/Modules/Expense app/Modules/Customer app/Modules/Supplier app/Modules/Payment` (260 passed, 1614 assertions)
- Static Analysis & Linting:
  - `php vendor/bin/pint --test` (0 style violations)
  - `npm run typecheck` (0 TypeScript errors)
  - `npm run lint` (0 ESLint errors)
  - `npx vitest run` (181 test files passed, 765 tests passed)
