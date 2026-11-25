# Backend Phase 4 & 5 Review Fix — Soft-Delete Audit Preservation on Payroll Salary Structures

## Context & Objectives
During independent verification of Phase 4 & 5 remediation:
1. Identified a data-loss defect in `PayrollStructureController::destroy()`: because `PayrollSalaryStructure` uses `SoftDeletes` while `PayrollSalaryStructureItem` does not, adding `$structure->items()->delete()` permanently hard-deleted child `payroll_salary_structure_items` rows when soft-deleting a salary structure, whereas `restrictOnDelete()` is not triggered by soft-deletes and historical/restorable structures require their items to remain intact.
2. Reverted `PayrollStructureController::destroy()` to `$structure->delete();` so soft-deleted structures preserve their child items.
3. Extended `PayrollApiTest` to cover:
   - Pay run recalculation (verifying `PayRunExecutionService::calculatePayRun()` cleanly replaces draft `PayrollPayslipItem` and `PayrollPayslip` rows under MySQL `restrictOnDelete()`).
   - Soft-deleting an unused `PayrollSalaryStructure` via `DELETE /api/v1/payroll/structures/{id}` and asserting `payroll_salary_structure_items` remain intact.

## Modified & Created Files Summary

| File | Change Description |
|---|---|
| `app/Modules/Payroll/Http/Controllers/PayrollStructureController.php` | Removed premature `$structure->items()->delete()` from `destroy()` so soft-deleting a salary structure preserves its child items. |
| `app/Modules/Payroll/Tests/PayrollApiTest.php` | Added pay run recalculation assertion and salary structure soft-delete + item preservation assertions. |
| `docs/changes/2026-11-25-backend-phase-4-5-review-fix.md` | Created append-only review-fix change record. |

## Verification & Quality Gate Results
- `composer audit --locked --no-interaction`: **0 advisories** (`No security vulnerability advisories found.`).
- `php vendor/bin/pint --test --dirty`: **passed** (0 PSR-12 violations).
- `php vendor/bin/phpunit --configuration phpunit.mysql.xml --filter="PayrollApiTest"` (with `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_DATABASE=autoerp_test`): **1 passed (43 assertions)**, 0 failed.
- `php artisan test`: **1,424 passed (15,037 assertions)**, 0 failed (223.70s, SQLite in-memory).

## Author & Maintainer Signature
- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** kasunvmail@gmail.com
- **Phone:** +94713525253
- **Repository:** https://github.com/kasunvimarshana/AutoERP
