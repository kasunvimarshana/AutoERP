# HR & Payroll Advanced Integration: Employee Loans, Shift Rotations & Retroactive Pay Adjustments

**Date:** 2026-11-25  
**Author:** Kasun Vimarshana (<kasunvmail@gmail.com>, +94713525253)  
**Modules Affected:** `app/Modules/Payroll`, `app/Modules/Hr`, `resources/js/modules/payroll`, `resources/js/modules/hr`  

---

## 1. Context & Business Need

To complete the unified enterprise HR and Payroll capabilities for AutoERP as requested, this implementation delivers the advanced operational pillars:
1. **Employee Loans & Salary Advances**:
   - Structured term loans and one-off cash salary advances with interest calculations, amortization schedules, and recovery plans.
   - Automatic monthly EMI / advance recovery deductions during pay run batch calculations.
   - Pay run payment lifecycle hook updating loan repayments and remaining balances, with automatic full reversal support.
2. **Retroactive Pay & Backdated Salary Adjustments (Arrears)**:
   - Calculating pay deltas across historical closed/paid pay periods when an employee's salary is revised with an effective date in the past.
   - Generating audit-backed `AdjustmentType::RetroPay` records for the upcoming open pay period without mutating historical finalized payslips, strictly honoring **Axiom 6 (Dual-Timestamp Architecture & Immutability)**.
3. **Shift Rotations & Conflict Prevention**:
   - Multi-day / multi-week cyclic shift rotation schedules (e.g., 14-day Continental shift cycle with alternating morning, evening, and rest days).
   - Dynamic schedule projection via `ShiftRosterService::resolveShift()` based on anchor dates.
   - Automated conflict prevention blocking overlapping active shift assignments and rejecting shifts on approved employee leave dates.
4. **Controlled Frontend UI**:
   - `PayrollLoanManagementPage.tsx` with real-time installment (EMI) preview, application forms, disbursement dialogs, and repayment history tables.
   - `PayrollLoanSelect.tsx` and `HrShiftRotationSelect.tsx` controlled lookup selectors with zero raw ID exposure (**Axiom 10**).
   - Retroactive Arrears computation modal integrated directly into `PayRunManagementPage.tsx`.

---

## 2. Architectural Design & Axioms Compliance

- **Axiom 4 & 5 (Module Ownership & Backend Authority)**:
  - `PayrollCalculationEngine` is the single authoritative source of truth for salary, attendance deductions, taxes, and loan repayments.
  - `Hr` owns shift patterns, rosters, leaves, and attendance; `Payroll` queries aggregated operational facts without leaky boundaries.
- **Axiom 6 (Dual-Timestamp Architecture & Immutability)**:
  - Civil operational dates (`repayment_date`, `disbursed_date`, `anchor_date`, `effective_from_date`) govern accounting and sequencing; physical timestamps (`created_at`) remain immutable server audit facts.
  - Active and disbursed `PayrollEmployeeLoan` records enforce lifecycle `booted()` hooks throwing `LogicException` on illegal mutations or deletes.
  - `PayrollLoanRepayment` records in status `Deducted` cannot be deleted in place. Reversals create explicit reverse records restoring the loan's balance.
  - Backdated salary increases never mutate historical paid payslips in place; they compute arrears and queue pending adjustments for open periods.
- **Axiom 7 (Database Architecture & Explicit Migrations)**:
  - One table per migration file.
  - Explicit column types, composite tenant foreign keys (`['col', 'tenant_id']` referencing `['id', 'tenant_id']`), and `restrictOnDelete()`.
- **Axiom 9 (Zero Magic Values)**:
  - PHP Backed Enums: `LoanType`, `LoanStatus`, `LoanRepaymentStatus`, `AdjustmentType::RetroPay`.
  - TypeScript union types on the frontend.
- **Axiom 10 (Zero Raw ID Exposure)**:
  - Controlled selection components (`PayrollLoanSelect`, `EmployeeLookupSelect`, `PayPeriodSelect`, `HrShiftRotationSelect`) format human-readable codes, references, and names.
- **Axiom 12 (Shared Project Memory)**:
  - Documented here in `/docs/changes`.

---

## 3. Database Schema Migrations

### Payroll Migrations
1. `2026_11_25_100013_create_payroll_employee_loans_table.php`:
   - Table `payroll_employee_loans`: stores loan reference, employee FK, loan type (`loan`, `advance`), principal amount, annual interest rate, term months, monthly installment, total payable, total paid, balance remaining, disbursement date, deduction start period FK, status (`draft`, `approved`, `active`, `completed`, `cancelled`), and approver audit fields.
2. `2026_11_25_100014_create_payroll_loan_repayments_table.php`:
   - Table `payroll_loan_repayments`: stores repayment audit records linked to loans, pay runs, and payslips, with principal repaid, total repaid, balance after, status (`scheduled`, `deducted`, `manual_payment`, `reversed`), and notes.

### HR Migrations
1. `2026_06_12_130033_create_hr_shift_rotations_table.php`:
   - Table `hr_shift_rotations`: stores rotation code, name, description, cycle days (e.g., 7, 14, 21, 28), and status (`active`, `inactive`).
2. `2026_06_12_130034_create_hr_shift_rotation_steps_table.php`:
   - Table `hr_shift_rotation_steps`: stores cyclic sequence mapping from day 1 to `cycle_days`, shift FK (nullable for rest days), and notes.
3. `2026_06_12_130035_create_hr_shift_rotation_assignments_table.php`:
   - Table `hr_shift_rotation_assignments`: links employees to rotation patterns starting on an anchor date.

---

## 4. Key Services & Domain Logic

### `PayrollLoanService` (`app/Modules/Payroll/Services/PayrollLoanService.php`)
- `applyLoan(array $data)`: computes total interest and installment EMI based on principal, term, and rate.
- `approveLoan(PayrollEmployeeLoan $loan, int $userId)`: moves loan to `Approved`.
- `disburseLoan(PayrollEmployeeLoan $loan, string $disbursedDate, ?int $startPeriodId)`: activates loan for payroll deductions.
- `cancelLoan(PayrollEmployeeLoan $loan, string $reason)`: cancels draft/approved loans.
- `getActiveLoansForEmployee(int $tenantId, int $employeeId, ?int $payPeriodId)`: retrieves active loans eligible for recovery in a given pay period.
- `recordDeduction(...)`: atomic deduction execution reducing loan balance and transitioning to `Completed` upon full repayment.
- `reverseRepayment(...)`: restores loan balance and reactivates completed loans on pay run reversal.

### `PayrollRetroactivePayService` (`app/Modules/Payroll/Services/PayrollRetroactivePayService.php`)
- `calculateSalaryArrears(...)`: queries historical paid payslips between backdated effective date and target period, calculates difference ($NewSalary - OldSalary \times ActiveRatio$), and creates an approved `PayrollAdjustment` of type `AdjustmentType::RetroPay` without altering historical payslips.

### `ShiftRosterService` Enhancements (`app/Modules/Hr/Services/ShiftRosterService.php`)
- Shift overlap validation in `assignShift()`: prevents concurrent specific-date, date-range, and recurring assignments for the same employee.
- Leave conflict prevention in `assignShift()`: checks `HrLeaveRequest` for approved leave on assigned dates and blocks scheduling.
- Shift rotation methods: `createRotation()`, `addRotationStep()`, `assignRotation()`.
- Rotation resolution in `resolveShift()`: maps target date against `anchor_date` modulo `cycle_days` to resolve the scheduled shift or rest day (`rotation_rest`).

---

## 5. Frontend Components

- `resources/js/modules/payroll/PayrollLoanManagementPage.tsx`:
  - Full management page for employee loans & advances with status filters, real-time installment calculations, approval/disbursement workflows, and deduction histories.
- `resources/js/modules/payroll/components/PayrollLoanSelect.tsx`:
  - Controlled dropdown for loans/advances formatting `[loan_reference] employee_name (loan_type) - Balance: $X`.
- `resources/js/modules/payroll/PayRunManagementPage.tsx`:
  - Added Retroactive Arrears computation modal and action button.
- `resources/js/modules/hr/components/HrShiftRotationSelect.tsx`:
  - Controlled selector for shift rotation cycle patterns.

---

## 6. Verification & Automated Test Results

1. **`Modules\Payroll\Tests\PayrollEngineTest`**:
   - `test_employee_loan_and_salary_advance_deduction_and_lifecycle_guardrails`: verifies $60,000 loan + $15,000 advance deduction, pay run net pay calculation, repayment record creation, balance decrement, loan completion, model lifecycle guardrails, and full reversal.
   - `test_retroactive_pay_arrears_calculation_across_historical_periods`: verifies backdated salary increment from $100,000 to $130,000, $30,000 retro adjustment generation, immutability of historical paid payslips, and automatic gross inclusion in subsequent pay runs.
   - **Result**: 7 passed (73 assertions).
2. **`Modules\Payroll\Tests\PayrollApiTest`**:
   - Verifies REST endpoints for loan application, listing, approval, disbursement, and retroactive arrears calculation.
   - **Result**: 1 passed (30 assertions).
3. **`Modules\Hr\Tests\HrEngineTest`**:
   - `test_shift_rotations_overlap_validation_and_leave_conflicts`: verifies 14-day Continental rotation cycle schedule resolution, rest day resolution, rejection of overlapping shift assignments, and rejection of shift assignments on approved leave dates.
   - **Result**: 12 passed (157 assertions).
4. **Code Quality & Formatting**:
   - `vendor/bin/pint`: clean formatting across all modified files.
   - `npm run typecheck` (`tsc --noEmit`): 0 TypeScript errors.
   - `npx eslint`: 0 errors, 0 warnings.
