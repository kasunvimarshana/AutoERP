# Backend Phase 3 Remediation — Monetary Precision, DecimalMath, Semantic GL Posting, and Optimistic Concurrency

## Context & Objectives
Completed Backend Phase 3 of the AutoERP remediation plan:
1. **Payroll Monetary Precision (`decimal(20, 6)`)**: Upgraded all 33 monetary columns across `2026_11_25_100003` through `2026_11_25_100014` from `decimal(15, 4)` to `decimal(20, 6)` and updated the corresponding 10 Payroll model `$casts` to `decimal:6` (preserving non-monetary percentages, rates, and day/hour counts).
2. **Exact `DecimalMath` Arithmetic**: Replaced floating-point monetary math with `Modules\Core\Services\DecimalMath` in `PayrollTaxService`, `OwnerDisbursementReportService` (`generate()`), and `VehicleServiceHistoryReportService`.
3. **Finance Semantic Posting Profile Integration**: Added `FinancePostingProfileCode::PayrollPayRun` and semantic payroll account roles (`SalariesExpense`, `EmployerStatutoryExpense`, `StatutoryPayable`, `PayrollDeductionsClearing`, `SalariesPayable`) in `FinanceSeeder`, and updated `PayrollJournalPostingService` to post via `payroll_pay_run` semantic profile keys and resolve tenant `base_currency_id` (failing clearly with `InvalidArgumentException` if unconfigured).
4. **Optimistic Concurrency (`expected_version` / `row_version`)**:
   - Verified `PayrollPayRun`, `PayrollPayslip`, and `PayrollAdjustment` auto-increment `row_version` in `booted()` `updating` hooks; added `expected_version` validation and 409 conflict enforcement in `PayRunExecutionService`, `PayrollPayRunController`, and `PayrollAdjustmentController`.
   - Audited the 34 `Update*Request` FormRequests: `UpdateCustomerRequest`, `UpdateCustomerContactRequest`, `UpdateCustomerCreditProfileRequest`, `UpdateSupplierRequest`, `UpdateVehicleRequest`, `UpdateEmployeeRequest`, and `UpdateUserQuickAccessRequest` already enforce `row_version` (and `UpdateVehicleOwnershipRequest` already enforces `expected_version`); added `expected_version` validation alongside `row_version` in `UpsertUnitOfMeasureRequest` and `UpsertUomConversionRequest`. Sub-resource and `Item` tables without a `row_version` column were left untouched.

## Modified & Created Files Summary

| File | Change Description |
|---|---|
| `app/Modules/Payroll/Database/Migrations/2026_11_25_100003..100006,100009..100014` (10 files) | Upgraded 33 monetary `decimal(15, 4)` columns to `decimal(20, 6)` and updated defaults to 6 decimals. |
| `app/Modules/Payroll/Models/PayrollAdjustment.php`, `PayrollEmployeeLoan.php`, `PayrollEmployeeSalaryAssignment.php`, `PayrollLoanRepayment.php`, `PayrollPayRun.php`, `PayrollPayslip.php`, `PayrollPayslipItem.php`, `PayrollSalaryStructureItem.php`, `PayrollTaxBracket.php`, `PayrollTaxTable.php` | Updated monetary `$casts` from `decimal:4` to `decimal:6`. |
| `app/Modules/Payroll/Services/PayrollTaxService.php` | Replaced `(float)` / `sprintf` / `min()` math with `DecimalMath` at scale 6. |
| `app/Modules/VehicleRental/Services/OwnerDisbursementReportService.php` | Replaced `(float)` / `+` / `-` / `number_format` monetary math in `generate()` with `DecimalMath`. |
| `app/Modules/Reporting/Services/VehicleServiceHistoryReportService.php` | Replaced `(float)` collection sums on `invoice_total`, `paid_total`, and `balance_due` with `DecimalMath::sum()`. |
| `app/Modules/Finance/Enums/FinancePostingProfileCode.php` | Added `PayrollPayRun = 'payroll_pay_run'`. |
| `app/Modules/Finance/Enums/FinanceAccountRoleCode.php` | Added `SalariesExpense`, `EmployerStatutoryExpense`, `StatutoryPayable`, `PayrollDeductionsClearing`, `SalariesPayable`. |
| `app/Modules/Finance/Database/Seeders/FinanceSeeder.php` | Seeded `payroll_pay_run` posting profile and semantic role mappings. |
| `app/Modules/Payroll/Services/PayrollJournalPostingService.php` | Integrated `payroll_pay_run` semantic posting profile, `profileKey` lines, `DecimalMath`, and tenant `base_currency_id` resolution. |
| `app/Modules/Payroll/Services/PayRunExecutionService.php` | Added `?int $expectedVersion` optimistic concurrency check (`409` on mismatch) on `calculatePayRun`, `approvePayRun`, `payPayRun`, and `reversePayRun`. |
| `app/Modules/Payroll/Http/Controllers/PayrollPayRunController.php` | Validated `expected_version` (`sometimes|integer|min:1`) and forwarded it to `PayRunExecutionService`. |
| `app/Modules/Payroll/Http/Controllers/PayrollAdjustmentController.php` | Validated `expected_version` (`sometimes|integer|min:1`) and returned `409` on mismatch in `cancel()`. |
| `app/Modules/UOM/Http/Requests/UpsertUnitOfMeasureRequest.php` | Added `expected_version` (`nullable|integer|min:1`) validation rule alongside `row_version`. |
| `app/Modules/UOM/Http/Requests/UpsertUomConversionRequest.php` | Added `expected_version` (`nullable|integer|min:1`) validation rule alongside `row_version`. |
| `app/Modules/Payroll/Tests/PayrollEngineTest.php` | Updated 6-decimal assertions and verified `postingProfileCode`, `profileKey`, and `currencyId` on posted GL context. |
| `app/Modules/Payroll/Tests/PayrollApiTest.php` | Added `expected_version` on pay run transitions and verified `409` conflict on stale `expected_version`. |

## Verification & Quality Gate Results
- `php artisan test app/Modules/Payroll/Tests app/Modules/VehicleRental/Tests app/Modules/Finance/Tests`: **412 passed (6,324 assertions)**, 0 failed.
- `php artisan test app/Modules/Customer/Tests app/Modules/Supplier/Tests app/Modules/Item/Tests app/Modules/Vehicle/Tests app/Modules/Hr/Tests app/Modules/User/Tests`: **66 passed (519 assertions)**, 0 failed.
- `php artisan test tests/Feature/Uom`: **22 passed (166 assertions)**, 0 failed.
- `php artisan test app/Modules/Reporting/Tests`: **67 passed (800 assertions)**, 0 failed.
- `php artisan test --filter="VehicleServiceHistoryReport|ModuleMigrationBaselineTest|HistoricalRetentionMigrationTest"`: **2 passed (25 assertions)**, 0 failed.
- `php vendor/bin/pint --test --dirty`: **passed** (0 violations).

## Author & Maintainer Signature
- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** kasunvmail@gmail.com
- **Phone:** +94713525253
- **Repository:** https://github.com/kasunvimarshana/AutoERP
