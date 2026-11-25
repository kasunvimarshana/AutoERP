# Backend Phase 4 & 5 Remediation — Migration Hygiene, Composer Security Advisories, Pint & Full Verification

## Context & Objectives
Completed Phase 4 & 5 of the AutoERP remediation plan:
1. **Remaining Migration Hygiene**:
   - **VehicleRental `indexName:`**: Added explicit `indexName:` (`<prefix>_tenant_fk` / `<prefix>_currency_fk`) to all 34 `->constrained(...)` foreign key declarations across 28 migration files in `app/Modules/VehicleRental/Database/Migrations/` (all identifiers <= 27 characters, well below the 60-character ceiling).
   - **Payroll `cascadeOnDelete()`**: Replaced `cascadeOnDelete()` with `restrictOnDelete()` on `payroll_salary_structure_items` (`2026_11_25_100003`), `payroll_tax_brackets` (`2026_11_25_100006`), `payroll_payslips` (`2026_11_25_100010`), and `payroll_payslip_items` (`2026_11_25_100011`). Updated `PayRunExecutionService::calculatePayRun()` to explicitly delete `PayrollPayslipItem` rows for `Draft` payslips prior to deleting draft `PayrollPayslip` rows, and updated `PayrollStructureController::destroy()` to delete structure items inside a transaction before deleting an unreferenced structure.
   - **HR `row_version`**: Added `$table->unsignedInteger('row_version')->default(1);` to 19 mutable/versioned HR entity migrations (`130001`–`130006`, `130008`–`130015`, `130018`, `130023`, `130025`, `130037`, `130038`). Intentionally skipped 2 pure append-only immutable audit/history tables: `hr_employee_status_histories` (`2026_06_12_130016`) and `hr_attendance_adjustments` (`2026_06_12_130027`).
2. **Composer Security Advisories**:
   - `composer audit --locked --no-interaction` initially reported 9 advisories affecting 6 packages (`laravel/framework`, `phpseclib/phpseclib`, `symfony/http-foundation`, `symfony/http-kernel`, `symfony/routing`, `symfony/yaml`).
   - Updated those packages within existing `composer.json` constraints (`laravel/framework` `v12.69.3 => v12.74.0`, `phpseclib/phpseclib` `3.0.57 => 3.0.59`, `symfony/http-foundation` `v7.4.22 => v7.4.24`, `symfony/http-kernel` `v7.4.14 => v7.4.24`, `symfony/routing` `v7.4.13 => v7.4.24`, `symfony/yaml` `v7.4.22 => v7.4.24`, plus 52 transitive updates and 1 polyfill install; PHPUnit `11.5.55` unaffected).
   - Re-ran `composer audit --locked --no-interaction`: **0 security vulnerability advisories found**.
3. **Code Style & Full Verification**:
   - Formatted and verified all dirty PHP files via `php vendor/bin/pint --dirty` and `php vendor/bin/pint --test --dirty`.
   - Executed the full PHPUnit backend suite (`php artisan test`) and verified MySQL schema compatibility via `phpunit.mysql.xml` against local `autoerp_test`.

## Modified & Created Files Summary

| File | Change Description |
|---|---|
| `app/Modules/VehicleRental/Database/Migrations/*.php` (28 files) | Added explicit `indexName:` to all 34 `->constrained('tenants')` and `->constrained('currencies')` declarations. |
| `app/Modules/Payroll/Database/Migrations/2026_11_25_100003_create_payroll_salary_structure_items_table.php` | Replaced `cascadeOnDelete()` with `restrictOnDelete()` on `payroll_structure_items_structure_fk`. |
| `app/Modules/Payroll/Database/Migrations/2026_11_25_100006_create_payroll_tax_brackets_table.php` | Replaced `cascadeOnDelete()` with `restrictOnDelete()` on `payroll_tax_brackets_table_fk`. |
| `app/Modules/Payroll/Database/Migrations/2026_11_25_100010_create_payroll_payslips_table.php` | Replaced `cascadeOnDelete()` with `restrictOnDelete()` on `payroll_payslips_pay_run_fk`. |
| `app/Modules/Payroll/Database/Migrations/2026_11_25_100011_create_payroll_payslip_items_table.php` | Replaced `cascadeOnDelete()` with `restrictOnDelete()` on `payroll_payslip_items_payslip_fk`. |
| `app/Modules/Payroll/Services/PayRunExecutionService.php` | Explicitly deleted `PayrollPayslipItem` records for `Draft` payslips before deleting draft `PayrollPayslip` records during recalculation. |
| `app/Modules/Payroll/Http/Controllers/PayrollStructureController.php` | Wrapped `destroy()` in `DB::transaction()` deleting `$structure->items()` before `$structure->delete()`. |
| `app/Modules/Hr/Database/Migrations/2026_06_12_130001..130006,130008..130015,130018,130023,130025,130037,130038` (19 files) | Added `$table->unsignedInteger('row_version')->default(1);` to mutable/versioned HR entity tables (skipping append-only `hr_employee_status_histories` and `hr_attendance_adjustments`). |
| `composer.lock` | Updated `laravel/framework`, `phpseclib/phpseclib`, `symfony/http-foundation`, `symfony/http-kernel`, `symfony/routing`, `symfony/yaml` and transitive dependencies to resolve all 9 security advisories. |
| `docs/changes/2026-11-25-backend-phase-4-5-remediation.md` | Created Phase 4 & 5 append-only change record. |

## Verification & Quality Gate Results
- `composer audit --locked --no-interaction`: **0 advisories** (`No security vulnerability advisories found.`).
- `php vendor/bin/pint --test --dirty`: **passed** (0 PSR-12 violations).
- `php artisan test`: **1,424 passed (15,030 assertions)**, 0 failed (215.19s, SQLite in-memory).
- `php vendor/bin/phpunit --configuration phpunit.mysql.xml --filter="PayrollEngineTest"` (with `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_DATABASE=autoerp_test`): **9 passed (93 assertions)**, 0 failed (58.50s, full MySQL `migrate:fresh` verified).

## Author & Maintainer Signature
- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** kasunvmail@gmail.com
- **Phone:** +94713525253
- **Repository:** https://github.com/kasunvimarshana/AutoERP
