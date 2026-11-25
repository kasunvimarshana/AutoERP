# Security, Strictness, MySQL Integration, and Payroll Axiom Remediation

## Context & Objectives
Remediated verified security, MySQL compatibility, Eloquent N+1 strictness, Payroll REST/axiom compliance, PHP strict types, and frontend business-date alignment issues across AutoERP without altering existing business logic.

## Modified & Created Files Summary

| File | Change Description |
|---|---|
| `app/Modules/Invoice/Validators/InvoiceCssSanitizer.php` | Added `InvoiceCssSanitizer` to detect and strip `</style`, `<script`, HTML tag breakouts, `javascript:`, `vbscript:`, `data:text/html`, and `expression(` from `custom_css`. |
| `app/Modules/Invoice/Http/Requests/StoreInvoiceLayoutRequest.php` | Added `InvoiceCssSanitizer::containsDangerousConstructs()` validation rule on `custom_css`. |
| `app/Modules/Invoice/Http/Requests/UpdateInvoiceLayoutRequest.php` | Added `InvoiceCssSanitizer::containsDangerousConstructs()` validation rule on `custom_css`. |
| `app/Modules/Invoice/Services/InvoiceLayoutService.php` | Enforced `InvoiceCssSanitizer::containsDangerousConstructs()` in `validateCustomContent()`. |
| `resources/views/invoice/print.blade.php` | Defensively sanitized `$layout['custom_css']` via `InvoiceCssSanitizer::sanitize()` before rendering inside `<style>`. |
| `app/Modules/Invoice/Tests/InvoiceLayoutCssSecurityTest.php` | Added feature tests verifying `custom_css` XSS rejection in requests/service and defensive stripping in `print.blade.php`. |
| `app/Modules/Vehicle/Services/VehicleQueryService.php` | Removed invalid `vehicle_rental_running_charts => vehicle_id` check and fixed `vehicle_rental_reservations` column to `requested_vehicle_id`. |
| `app/Modules/VehicleRental/Services/RentalBillingDocumentService.php` | Updated `RunningChart` overlap query to use `starts_at`/`ends_at` and removed invalid `with(['vehicle', 'driver'])`. |
| `app/Modules/VehicleRental/Services/TwoStageInvoicingService.php` | Added `make` and `model` eager loads on `Vehicle` relations to satisfy `Model::preventLazyLoading()`. |
| `app/Modules/VehicleRental/Tests/TwoStageInvoicingTest.php` | Replaced hardcoded `currency_id = 1` with `(int) $customerAg->currency_id`. |
| `app/Providers/AppServiceProvider.php` | Added `declare(strict_types=1);` and enabled `Model::preventLazyLoading(! app()->isProduction())`. |
| `app/Modules/Payroll/Enums/*.php` | Added `values()` helper to `AdjustmentType`, `CalculationType`, `PayrollCycleType`, `PayrollPaymentMethod`, `PayRunType`, and `SalaryComponentType`. |
| `app/Modules/Payroll/Http/Controllers/*.php` | Replaced unbounded `->get()` in `index()` endpoints with bounded `->paginate($perPage)` (`1..100`, default `20`), replaced hardcoded `'in:...'` strings with `Rule::in(...::values())`, and added `expected_version` optimistic concurrency check to `PayrollComponentController::update()`. |
| `app/Modules/Payroll/Tests/PayrollApiTest.php` | Added assertions for paginated `components` index and `expected_version` 409 conflict / increment on `PayrollComponentController::update()`. |
| `app/Http/Controllers/Controller.php`, `database/seeders/DatabaseSeeder.php`, `routes/*.php`, `config/*.php` (14 files) | Added `declare(strict_types=1);`. |
| `phpunit.mysql.xml` | Added Author/Email/Phone XML header comment. |
| `resources/js/modules/tenant/components/PlatformTenantSubscriptionPanel.tsx` | Replaced `toLocalDateTime(new Date().toISOString())` with `businessDateTimeInputValue()`. |

## Verification & Quality Gate Results
- `php artisan test app/Modules/Payroll/Tests app/Modules/Invoice/Tests`: **65 passed (480 assertions)**
- `php vendor/bin/phpunit -c phpunit.mysql.xml --filter "VehicleBlockerTest|VehicleRentalQuotationPreviewTest|RentalQuotationPreviewTest|TwoStageInvoicingTest"` (MySQL `autoerp_test`): **35 tests, 250 assertions (OK)**
- `npx tsc --noEmit`: **0 errors**
- `npm run lint`: **0 errors**

## Author & Maintainer Signature
- Lead Architect & Developer: Kasun Vimarshana
- Email: kasunvmail@gmail.com
- Phone: +94713525253
- Repository: https://github.com/kasunvimarshana/AutoERP
