# End-to-End HR Module Architecture & Implementation

**Date:** 2026-11-24  
**Module:** Human Resources (`Hr`)  
**Author & Maintainer:** Kasun Vimarshana (`kasunvmail@gmail.com`)  
**Phone:** +94713525253  
**Repository:** [kasunvimarshana/AutoERP](https://github.com/kasunvimarshana/AutoERP)

---

## 1. Context & Objectives

A complete, enterprise-grade, end-to-end Human Resources (`Hr`) module has been designed, implemented, and verified in AutoERP. The implementation adheres strictly to AutoERP Core Axioms (Axioms 1–12), specifically:
- **Axiom 1 (Prime Directive)**: Comprehensive domain research and planning before execution; verified through automated unit tests.
- **Axiom 4 & 5 (Module Ownership & Backend Authority)**: The HR module owns employee rosters, time tracking, attendance regularizations, and leave balance ledgers; business calculations and approval gates are strictly enforced on the backend.
- **Axiom 6 (Dual-Timestamp Architecture & Concurrency)**: Separation between physical recording timestamps (`punch_time` UTC, `created_at`) and civil operational dates (`attendance_date`, `start_date`, `end_date`). Optimistic concurrency control (`row_version`) protects leave balances and employee records. Historical audit ledgers (`hr_attendance_adjustments`) are strictly immutable.
- **Axiom 7 (Explicit Database Migrations)**: One table per migration file, explicit column and index definitions line-by-line, composite multi-tenant foreign keys, and `restrictOnDelete()` on historical audit tables.
- **Axiom 9 (Zero Magic Values)**: PHP Backed Enums and TypeScript string union enums govern statuses, shift assignment types, punch categories, and leave rules.
- **Axiom 10 (UI/UX Clarity & Zero Raw IDs)**: Controlled lookup selectors (`HrShiftSelect`, `HrLeaveTypeSelect`, `EmployeeLookupSelect`) ensure internal primary keys are never displayed to users.

---

## 2. Database Schema & Explicit Migrations (1 Table per File)

The existing `hr_employees` table was extended with a nullable `user_id` foreign key (`[user_id, tenant_id]` referencing `users [id, tenant_id]`) to associate employee profiles with authenticated system accounts.

16 dedicated migration files were authored and verified:

| Migration File | Table | Purpose |
|---|---|---|
| `2026_06_12_130017_create_hr_shifts_table.php` | `hr_shifts` | Master shift definitions (code, start/end time, grace periods, overnight flags, half-day thresholds) |
| `2026_06_12_130018_create_hr_shift_breaks_table.php` | `hr_shift_breaks` | Configurable shift break schedules (paid vs unpaid, auto-deductions, duration) |
| `2026_06_12_130019_create_hr_shift_assignments_table.php` | `hr_shift_assignments` | Shift roster assignments supporting recurring patterns, date ranges, and single-day overrides |
| `2026_06_12_130020_create_hr_shift_swaps_table.php` | `hr_shift_swaps` | Shift exchange workflow between peer employees with peer acceptance and manager approval |
| `2026_06_12_130021_create_hr_shift_reallocations_table.php` | `hr_shift_reallocations` | Absent staff shift coverage and emergency reallocations |
| `2026_06_12_130022_create_hr_holiday_calendars_table.php` | `hr_holiday_calendars` | Tenant holiday calendars categorized by jurisdiction, department, or calendar year |
| `2026_06_12_130023_create_hr_holidays_table.php` | `hr_holidays` | Specific holidays within calendars (civil holiday date, recurring vs optional/floating) |
| `2026_06_12_130024_create_hr_attendance_records_table.php` | `hr_attendance_records` | Aggregated daily attendance records (worked minutes, breaks, regular, overtime, double-time, lock status) |
| `2026_06_12_130025_create_hr_attendance_punches_table.php` | `hr_attendance_punches` | Immutable physical punch logs (punch type, UTC server timestamp, client device, geo coordinates, IP) |
| `2026_06_12_130026_create_hr_attendance_regularizations_table.php` | `hr_attendance_regularizations` | Employee missing/wrong punch fix requests with multi-tier manager approval |
| `2026_06_12_130027_create_hr_attendance_adjustments_table.php` | `hr_attendance_adjustments` | Immutable audit adjustment ledger recording all manager corrections and regularizations |
| `2026_06_12_130028_create_hr_leave_types_table.php` | `hr_leave_types` | Leave category definitions (Annual, Sick, Casual, Maternity, Unpaid, Comp-Off) |
| `2026_06_12_130029_create_hr_leave_policies_table.php` | `hr_leave_policies` | Leave policies per department/employment type with accrual frequency and carry-over limits |
| `2026_06_12_130030_create_hr_leave_balances_table.php` | `hr_leave_balances` | Optimistic concurrency balance ledger (`row_version`, allocated, used, pending, carry-over) |
| `2026_06_12_130031_create_hr_leave_requests_table.php` | `hr_leave_requests` | Leave applications, date ranges, half-day flags, approver tracking, and rejection reasons |
| `2026_06_12_130032_create_hr_comp_off_claims_table.php` | `hr_comp_off_claims` | Compensatory off claims earned by working on rest days or public holidays |

---

## 3. Core Domain Engines & Business Logic

### 3.1 Shift & Roster Management Engine (`ShiftRosterService.php`)
- **Resolution Hierarchy**: Automatically resolves an employee's effective shift for any given date:
  1. Approved Shift Swap (Peer + Manager approved).
  2. Absent Staff Reallocation Coverage.
  3. Specific Date Override (`SpecificDateOverride`).
  4. Date Range Assignment (`DateRange`).
  5. Recurring Weekly Pattern (`RecurringWeekly`).
  6. Fallback Organization Unit Default Shift.
- **Workflow Protection**:
  - Validates that peer employees belong to the same tenant and active roster.
  - Requires target employee peer acceptance before manager review.
  - Automatically verifies target employee availability and skills during absent staff reallocation.

### 3.2 Attendance Calculation & Clocking Engine (`AttendanceCalculationEngine.php`)
- **Dual-Timestamp Recording**:
  - `punch_time`: UTC server timestamp recording the physical reality of the punch.
  - `attendance_date`: Operational civil date assigned based on shift schedule and overnight threshold.
- **Punch Deduplication**: Drops duplicate punches occurring within a 120-second window to prevent accidental double-taps.
- **Overnight Shift Handling**: Automatically maps post-midnight punch-outs back to the shift's starting civil date.
- **Multi-Break Tracking**: Evaluates paid breaks vs unpaid breaks against configured shift rules; applies auto-deductions when configured.
- **Overtime & Holiday Premium Rates**:
  - Regular minutes calculated up to scheduled shift duration.
  - Normal overtime accumulated for extra hours on standard working days.
  - Double-time accumulated for work performed on designated rest days or public holidays from `hr_holidays`.

### 3.3 Attendance Regularization & Audit Service (`AttendanceRegularizationService.php`)
- **Employee Dispute & Correction Workflow**:
  - Employees submit requests for missing punches, incorrect timestamps, or missed check-outs with reasons.
  - Approving manager verifies and submits the resolution.
  - Generates synthetic system punches (`PunchSource::Manual` or `PunchSource::Regularization`) linked to the request.
  - Automatically recalculates worked minutes, overtime, and attendance status.
- **Immutable Historical Audit Ledger (`HrAttendanceAdjustment`)**:
  - Every regularization or manager adjustment creates an immutable record in `hr_attendance_adjustments`.
  - Captures `previous_status`, `new_status`, `previous_worked_minutes`, `new_worked_minutes`, adjusting manager ID, and reason.
  - Model lifecycle hook enforces immutability by throwing `LogicException` on any attempt to update or delete an adjustment record.
- **Finalized Record Guardrails**:
  - `HrAttendanceRecord` throws `LogicException` on direct in-place mutation once `lock_status` is `Finalized`. Adjustments must be performed exclusively via regularizations.

### 3.4 Leave Management & Comp-Off Ledger Engine (`LeaveManagementService.php`)
- **Optimistic Concurrency Control**: All leave balance mutations require matching `row_version` to prevent lost updates under concurrent submissions.
- **Balance Lifecycle**:
  - Submitting a request moves days to `pending_days` on `hr_leave_balances`.
  - Approval moves days from `pending_days` to `used_days` and decreases `remaining_days`.
  - Syncs with `HrAttendanceRecord`, creating or updating the daily record to `AttendanceStatus::OnLeave`.
  - Rejection or cancellation cleanly restores days back to available balance.
- **Compensatory Off (Comp-Off) Workflow**:
  - Employees who work on designated rest days or holidays claim comp-off credit linked to their `HrAttendanceRecord`.
  - Manager approval automatically credits 1.0 or 0.5 days to the employee's compensatory leave balance ledger.

---

## 4. API Endpoints & Granular Permissions

Registered 107 routes under `/api/v1/hr/` with tenant feature enforcement and role-based permissions in `HrAuthorizationService`:

- **Shifts & Rosters**:
  - `GET/POST /api/v1/hr/shifts` (`hr.shifts.view`, `hr.shifts.manage`)
  - `GET/POST /api/v1/hr/roster/resolve` (`hr.roster.view`)
  - `POST /api/v1/hr/roster/assign` (`hr.roster.manage`)
  - `POST /api/v1/hr/roster/swaps/request`, `POST /api/v1/hr/roster/swaps/{id}/peer-action`, `POST /api/v1/hr/roster/swaps/{id}/manager-action` (`hr.roster.swap`)
  - `POST /api/v1/hr/roster/reallocations` (`hr.roster.manage`)
- **Attendance**:
  - `GET/POST /api/v1/hr/attendance/punch` (`hr.attendance.punch`)
  - `GET /api/v1/hr/attendance/records` (`hr.attendance.view`)
  - `POST /api/v1/hr/attendance/records/{id}/adjust` (`hr.attendance.adjust`)
  - `GET/POST /api/v1/hr/attendance/regularizations` (`hr.attendance.regularize`)
  - `POST /api/v1/hr/attendance/regularizations/{id}/action` (`hr.attendance.adjust`)
- **Leaves & Comp-Off**:
  - `GET/POST /api/v1/hr/leaves/types` (`hr.leaves.manage`)
  - `GET/POST /api/v1/hr/leaves/policies` (`hr.leaves.manage`)
  - `GET /api/v1/hr/leaves/balances` (`hr.leaves.view`)
  - `GET/POST /api/v1/hr/leaves/requests` (`hr.leaves.apply`, `hr.leaves.view`)
  - `POST /api/v1/hr/leaves/requests/{id}/action` (`hr.leaves.approve`)
  - `POST /api/v1/hr/leaves/requests/{id}/cancel` (`hr.leaves.apply`)
  - `GET/POST /api/v1/hr/leaves/comp-off` (`hr.leaves.apply`, `hr.leaves.view`)
  - `POST /api/v1/hr/leaves/comp-off/{id}/action` (`hr.leaves.approve`)

---

## 5. Frontend Pages & Components

Developed interactive, accessible frontend interfaces in React 19 + TypeScript:
- **`RosterSchedulerPage.tsx` (`/hr/roster`)**:
  - Interactive calendar grid displaying assigned shifts per employee across dates.
  - Direct shift assignment modal (recurring weekly, date-range, and single-day override).
  - Shift swap workflow dialog and absent staff coverage reallocations.
- **`AttendanceManagementPage.tsx` (`/hr/attendance`)**:
  - Live punch widget for employee check-in, check-out, and break logging.
  - Daily attendance records table with color-coded status badges, hours worked, overtime, and lock states.
  - Regularization submission modal for missing punches.
  - Manager approval pane for pending regularization requests and direct audit adjustments.
- **`LeaveManagementPage.tsx` (`/hr/leaves`)**:
  - Real-time leave balance overview cards (allocated, used, pending, remaining).
  - Leave application modal with half-day toggle and civil date validation.
  - Multi-tier manager approval/rejection panel.
  - Compensatory off claim submission and approval tab.
- **Controlled Lookups**:
  - `HrShiftSelect.tsx`: Controlled dropdown displaying shift names and working hours.
  - `HrLeaveTypeSelect.tsx`: Controlled dropdown displaying leave type codes, names, and paid/unpaid badges.
  - `EmployeeLookupSelect.tsx`: Controlled search selector preventing exposure of raw database IDs.

---

## 6. Verification Results

All quality gates and test suites passed:

1. **Backend Unit & Feature Tests**:
   - `php artisan test app/Modules/Hr/Tests/HrEngineTest.php tests/Feature/Hr/HrPermissionBoundaryTest.php tests/Feature/Hr/HrRateMutationBoundaryTest.php`:
     - **16 passed (503 assertions)** in 2.82s.
2. **Database Migration Baselines**:
   - `php artisan test --filter=ModuleMigrationBaselineTest`: **1 passed (3 assertions)**.
   - `php artisan test --filter=HistoricalRetentionMigrationTest`: **1 passed (22 assertions)**.
3. **Frontend Code Quality**:
   - `npm run typecheck`: **0 errors (Exit code 0)**.
   - `npx eslint resources/js/modules/hr`: **0 errors (Exit code 0)**.
4. **Code Style**:
   - `php vendor/bin/pint app/Modules/Hr`: **Clean / Formatted according to Laravel standards**.
