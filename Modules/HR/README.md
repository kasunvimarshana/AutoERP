# HR Module

HR and Payroll management module for the ERP platform.

## Features

- **Employees** — manage employee records with BCMath-safe salary storage, department assignments, and status lifecycle (active / inactive / terminated).
- **Departments** — organisational units with optional manager references.
- **Payroll Runs** — period-bound payroll cycles with draft → processing → completed workflow.
- **Payslips** — per-employee payslip generation with gross/deductions/net breakdown.
- **Attendance** — employee check-in/check-out tracking with BCMath `duration_hours`, dedup guard, and status lifecycle (`present` / `on_leave` / `half_day`).
- **Performance Goals** — KPI/OKR goal management per employee per period (`q1`/`q2`/`q3`/`q4`/`annual`/`monthly`/`custom`); lifecycle `draft → active → completed` (or `cancelled`); domain events `PerformanceGoalCreated` / `PerformanceGoalCompleted`; DomainException guards (not-found, already-completed, cancelled). `CreatePerformanceGoalUseCase` sets status to `active` on creation; `draft` is available for future "pending publish" workflows.

## Architecture

Follows strict Clean Architecture layering:

| Layer | Namespace |
|-------|-----------|
| Domain | `Modules\HR\Domain` |
| Application | `Modules\HR\Application` |
| Infrastructure | `Modules\HR\Infrastructure` |
| Presentation | `Modules\HR\Presentation` |

## Key Design Decisions

- All monetary values (`salary`, `total_gross`, `total_net`, `gross_salary`, `deductions`, `net_salary`) are stored as `DECIMAL(18,8)` and computed exclusively with BCMath (`bcadd`, `bcsub`) at scale 8 — floating-point arithmetic is forbidden.
- Every model uses `HasTenantScope` for automatic tenant isolation and UUID primary keys.
- `ProcessPayrollRunUseCase` iterates active employees via `chunk(100)` to avoid memory exhaustion, and wraps the entire operation in a `DB::transaction()`.
- Domain events (`EmployeeCreated`, `PayrollRunCompleted`, `PerformanceGoalCreated`, `PerformanceGoalCompleted`) extend `Modules\Shared\Domain\Events\DomainEvent`.

## API Endpoints

All routes are prefixed `api/v1` and require `auth:sanctum`.

| Method | Path | Description |
|--------|------|-------------|
| GET | `/hr/departments` | List departments |
| POST | `/hr/departments` | Create department |
| GET | `/hr/departments/{id}` | Get department |
| PUT | `/hr/departments/{id}` | Update department |
| DELETE | `/hr/departments/{id}` | Delete department |
| GET | `/hr/employees` | List employees |
| POST | `/hr/employees` | Create employee |
| GET | `/hr/employees/{id}` | Get employee |
| PUT | `/hr/employees/{id}` | Update employee |
| DELETE | `/hr/employees/{id}` | Delete employee |
| GET | `/hr/payroll-runs` | List payroll runs |
| POST | `/hr/payroll-runs` | Create payroll run |
| GET | `/hr/payroll-runs/{id}` | Get payroll run |
| POST | `/hr/payroll-runs/{id}/process` | Process payroll run |
| GET | `/hr/attendance` | List attendance records |
| POST | `/hr/attendance/check-in` | Employee check-in |
| POST | `/hr/attendance/{id}/check-out` | Employee check-out |
| GET | `/hr/attendance/{id}` | Get attendance record |
| GET | `/hr/performance-goals` | List performance goals |
| POST | `/hr/performance-goals` | Create performance goal |
| GET | `/hr/performance-goals/{id}` | Get performance goal |
| PUT | `/hr/performance-goals/{id}` | Update performance goal |
| DELETE | `/hr/performance-goals/{id}` | Delete performance goal |
| POST | `/hr/performance-goals/{id}/complete` | Mark goal as completed |
