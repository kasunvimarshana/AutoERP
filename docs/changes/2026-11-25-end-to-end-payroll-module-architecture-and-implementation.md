# End-to-End Payroll Module Architecture & Implementation

**Date:** 2026-11-25  
**Module:** Payroll (`Payroll`)  
**Author & Maintainer:** Kasun Vimarshana (`kasunvmail@gmail.com`)  
**Phone:** +94713525253  
**Repository:** [kasunvimarshana/AutoERP](https://github.com/kasunvimarshana/AutoERP)

---

## 1. Context & Objectives

A complete, enterprise-grade, dynamic, and fully configurable Payroll (`Payroll`) module has been designed, implemented, and verified in AutoERP. The implementation adheres strictly to the AutoERP Core Axioms (Axioms 1–12):
- **Axiom 1 (Prime Directive)**: Grounded domain research covering complex gross-to-net calculations, proration, statutory tiers, progressive tax brackets, and GL reconciliation; verified with automated end-to-end tests.
- **Axiom 4 & 5 (Module Ownership & Backend Authority)**: The Payroll module encapsulates salary structures, employee assignments, pay period schedules, batch pay runs, gross-to-net calculations, payslip generation, and GL journal posting. Business calculations, tax brackets, and payment states are strictly enforced on the backend.
- **Axiom 6 (Dual-Timestamp Architecture & Concurrency)**: Strict architectural separation between physical recording timestamps (`created_at`, `posted_at`, `reversed_at`) and operational civil pay dates (`period_start_date`, `period_end_date`, `payout_date`). Optimistic concurrency control (`row_version`) protects pay runs, payslips, and assignments. Lifecycle guardrails (`LogicException`) prevent modifying or deleting approved or paid financial records in place; corrections must follow governed reversal or retroactive adjustment records.
- **Axiom 7 (Explicit Database Migrations)**: One table per migration file across all 12 tables, line-by-line column and constraint definitions, composite multi-tenant foreign keys, and explicit indexes.
- **Axiom 9 (Zero Magic Values)**: 10 PHP Backed Enums and TypeScript string union types define all component types, calculation formulas, cycles, statuses, and payment methods.
- **Axiom 10 (UI/UX Clarity & Zero Raw IDs)**: Controlled lookup selectors (`PayrollSalaryStructureSelect`, `PayrollComponentSelect`, `PayPeriodSelect`) ensure database primary keys are never displayed or manually entered by users.

---

## 2. Database Schema & Explicit Migrations (1 Table per File)

12 dedicated migration files were authored and executed cleanly:

| Migration File | Table | Purpose |
|---|---|---|
| `2026_11_25_100001_create_payroll_salary_components_table.php` | `payroll_salary_components` | Earning and deduction definitions (Basic, HRA, Medical, Transport, Provident Fund, Loan Deduction) with tax/statutory flags |
| `2026_11_25_100002_create_payroll_salary_structures_table.php` | `payroll_salary_structures` | Master salary structure templates (code, name, cycle, currency) |
| `2026_11_25_100003_create_payroll_salary_structure_items_table.php` | `payroll_salary_structure_items` | Line item formulas per structure (Fixed, PercentageOfBase, Formula, Slab) with sequence ordering |
| `2026_11_25_100004_create_payroll_employee_salary_assignments_table.php` | `payroll_employee_salary_assignments` | Employee salary assignment with temporal effective dates, base salary, currency, and `row_version` |
| `2026_11_25_100005_create_payroll_tax_tables_table.php` | `payroll_tax_tables` | Progressive income tax tables per jurisdiction and tax year with standard personal relief allowances |
| `2026_11_25_100006_create_payroll_tax_brackets_table.php` | `payroll_tax_brackets` | Slabs for progressive taxation (lower limit, upper limit, rate percent, base tax amount) |
| `2026_11_25_100007_create_payroll_statutory_rules_table.php` | `payroll_statutory_rules` | Statutory contribution rules (EPF, ETF, Pension, Social Security) with employee vs employer rate splits |
| `2026_11_25_100008_create_payroll_pay_periods_table.php` | `payroll_pay_periods` | Civil accounting pay periods with start date, end date, payout date, working days count, and closed status |
| `2026_11_25_100009_create_payroll_pay_runs_table.php` | `payroll_pay_runs` | Batch pay run ledger (`Draft`, `Calculated`, `Approved`, `Paid`, `Reversed`) with batch aggregates and GL journal reference |
| `2026_11_25_100010_create_payroll_payslips_table.php` | `payroll_payslips` | Individual employee payslip header capturing gross pay, total deductions, tax, statutory, net pay, and immutable snapshot |
| `2026_11_25_100011_create_payroll_payslip_items_table.php` | `payroll_payslip_items` | Itemized line items (Basic, Allowances, Overtime, Absent deductions, EPF, Tax) with rate, factor, and calculated amount |
| `2026_11_25_100012_create_payroll_adjustments_table.php` | `payroll_adjustments` | Governed retroactive adjustments, bonuses, and commission claims linked to pay periods |

### Schema Hygiene Correction
During initial migration verification, an InnoDB constraint limitation was resolved in 4 HR attendance and leave migration tables where MySQL rejected `ON DELETE SET NULL` on non-nullable `tenant_id` columns in composite foreign keys. Foreign keys referencing `attendance_record_id` were standardized to reference the nullable parent key directly with explicit tenant scoping.

---

## 3. Core Domain Architecture & Calculation Engines

### 3.1 Proration Engine (`PayrollProrationService.php`)
- Calculates exact factor ($0.0$ to $1.0$) for mid-period joiners and leavers based on civil employment dates.
- Computes both calendar day ratio ($\text{eligible calendar days} / \text{total period calendar days}$) and civil working day ratio ($\text{eligible working days} / \text{total period working days}$).
- Accurately prorates base salary, fixed allowances, and recurring deductions without rounding drift.

### 3.2 HR Attendance & Leave Integration (`PayrollAttendanceIntegrationService.php`)
- Reads physical and operational data directly from the `Hr` module:
  - **Overtime Hours**: Aggregates normal overtime minutes ($1.5\times$ hourly rate) and double-time minutes on rest days / holidays ($2.0\times$ hourly rate) from `hr_attendance_records`.
  - **Unexcused Absences**: Calculates daily wage deductions for unexcused absences.
  - **Unpaid Leave**: Computes deductions for approved leaves categorized as unpaid (`HrLeaveType::is_paid = false`).
- Converts standard working hours (8 hours/day, 22 working days/month = 176 monthly hours) to exact hourly and daily rates.

### 3.3 Progressive Tax Engine (`PayrollTaxService.php`)
- Supports progressive tax tables (PAYE / APIT / Progressive Income Tax) with configurable tax years.
- Applies standard personal tax relief thresholds before subjecting annual/monthly taxable gross to tax brackets.
- Evaluates tax brackets sequentially, calculating marginal tax per bracket and preserving intermediate slab breakdowns.

### 3.4 Statutory Rules Engine (`PayrollStatutoryService.php`)
- Handles statutory funds (EPF, ETF, Pension, Social Security).
- Splits liabilities cleanly:
  - **Employee Deduction**: Deducted from gross salary, reducing net pay.
  - **Employer Contribution**: Recorded as employer payroll operational liability, tracked separately on payslips.
- Supports maximum statutory salary caps and exempt thresholds.

### 3.5 Master Calculation Engine (`PayrollCalculationEngine.php`)
- Orchestrates end-to-end Gross-to-Net execution:
  1. Base earnings and allowances (fixed, percentage-of-base, and formula-derived).
  2. Attendance additions (overtime $1.5\times$, holiday work $2.0\times$).
  3. Absences and unpaid leave deductions.
  4. Gross Pay calculation ($\sum \text{Earnings} - \text{Absence Deductions}$).
  5. Statutory contributions (Employee EPF + Employer EPF/ETF).
  6. Progressive income tax calculation.
  7. Retroactive adjustments, bonuses, and one-off deductions from `payroll_adjustments`.
  8. Net Pay determination ($\text{Gross Pay} - \text{Total Deductions} - \text{Statutory} - \text{Tax} \pm \text{Adjustments}$).
- Returns structured `CalculatedPayslipDTO` and line item breakdowns with 6-decimal precision.

### 3.6 Finance GL Double-Entry Journal Integration (`PayrollJournalPostingService.php`)
Integrates directly with AutoERP's Finance ledger via `FinancePostingInterface`:
- **Debit**: Salaries and Wages Expense (Gross Pay)
- **Debit**: Employer Statutory Expense (Employer EPF/ETF/Pension liability)
- **Credit**: Withholding Tax Payable (PAYE/APIT)
- **Credit**: Statutory Payable (Total Employee + Employer Liabilities)
- **Credit**: Payroll Deductions Clearing (Unpaid leave / absent / advance deductions)
- **Credit**: Net Salaries Payable / Bank Clearing (Net Pay)
- Guarantees $\sum \text{Debits} \equiv \sum \text{Credits}$ to 6 decimal places.
- Full automated journal reversal when an approved or paid pay run is voided via `reversePayRun()`.

### 3.7 Pay Run Lifecycle Service (`PayRunExecutionService.php`)
Governs the batch pay run state machine:
- `Draft`: Employees enrolled; parameters initialized.
- `Calculated`: Batch calculation executed for all assigned employees; line items generated.
- `Approved`: Manager reviews and approves pay run; records become locked.
- `Paid`: Financial payout confirmed; double-entry journal posted to General Ledger; payment method stamped.
- `Reversed`: Managed cancellation; voids payslips and issues automated reversal journal in GL.
- Lifecycle hooks enforce immutability: modifying or deleting an approved or paid payslip or applied adjustment throws `LogicException`.

---

## 4. API Controllers & Routes

Registered under `/api/v1/payroll`:
- `PayrollComponentController`: Salary component master CRUD.
- `PayrollStructureController`: Salary structures and line-item formula builder.
- `PayrollAssignmentController`: Employee salary assignments.
- `PayrollTaxTableController`: Progressive tax tables and bracket configurations.
- `PayrollStatutoryRuleController`: Statutory deduction and employer liability rules.
- `PayrollPeriodController`: Pay period calendar schedules and closing.
- `PayrollPayRunController`: Pay run batch lifecycle (`store`, `calculate`, `approve`, `pay`, `reverse`).
- `PayrollPayslipController`: Payslip queries, item breakdowns, and PDF/printable data.
- `PayrollAdjustmentController`: Controlled retroactive adjustments and bonuses.

---

## 5. Frontend UI & User Experience (React 19 + TypeScript)

Built under `resources/js/modules/payroll`:
- **Zero Raw ID Exposure (Axiom 10)**: Custom controlled selection components:
  - `PayrollSalaryStructureSelect.tsx`: Code and structure label selection.
  - `PayrollComponentSelect.tsx`: Component selection with badge categories.
  - `PayPeriodSelect.tsx`: Pay period selector displaying date intervals and status badges.
- **Salary Structure Builder (`SalaryStructurePage.tsx`)**:
  - Interactive formula and line-item manager.
  - Live preview of fixed vs percentage allowances and deductions.
  - Modal component creator for on-the-fly component additions.
- **Pay Run Management Console (`PayRunManagementPage.tsx`)**:
  - Period filtering and status badges (`Draft`, `Calculated`, `Approved`, `Paid`, `Reversed`).
  - High-level KPI summary cards (Total Gross, Total Net, Employer Liabilities, Total Tax).
  - Batch action controls: Run Calculations, Approve Pay Run, Mark Paid & Post to GL, Reverse Pay Run with mandatory reason dialog.
- **Employee Payslip Viewer (`EmployeePayslipPage.tsx`)**:
  - Printable and downloadable employee payslip view.
  - Itemized earnings, attendance overtime, unpaid leave deductions, statutory contributions, and net salary.
  - Visual breakdown of employer contributions (EPF/ETF) distinct from employee take-home pay.

---

## 6. Verification & Automated Tests

All tests and validation gates pass with zero errors:

```bash
# 1. Payroll Test Suite
php artisan test --filter=Payroll
# PASS Modules\Payroll\Tests\PayrollApiTest (1 test, 22 assertions)
# PASS Modules\Payroll\Tests\PayrollEngineTest (5 tests, 38 assertions)
# Total: 6 passed (60 assertions)

# 2. Database Migration Baseline Test
php artisan test --filter=ModuleMigrationBaselineTest
# PASS Tests\Unit\Database\ModuleMigrationBaselineTest (1 passed, 3 assertions)

# 3. Historical Retention Migration Test
php artisan test --filter=HistoricalRetentionMigrationTest
# PASS Tests\Unit\Architecture\HistoricalRetentionMigrationTest (1 passed, 22 assertions)

# 4. PHP Code Style (Laravel Pint)
php vendor/bin/pint --test app/Modules/Payroll
# Result: passed (0 violations)

# 5. ESLint Verification
npx eslint resources/js/modules/payroll
# Result: 0 errors, 0 warnings

# 6. TypeScript Compilation
npm run typecheck
# Result: 0 errors across entire workspace
```

---

## 7. Author Signoff

- **Lead Architect & Developer:** Kasun Vimarshana  
- **Email:** [kasunvmail@gmail.com](mailto:kasunvmail@gmail.com)  
- **Phone:** [+94713525253](tel:+94713525253)  
- **Date:** 2026-11-25
