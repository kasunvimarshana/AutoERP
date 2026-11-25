# AutoERP HR, Attendance, Leave, Shift & Payroll — Technical Architecture & Audit Report

**Author:** Kasun Vimarshana  
**Module Scope:** `app/Modules/Hr/` (`Modules\Hr`) & `app/Modules/Payroll/` (`Modules\Payroll`)  
**Frontend Scope:** `resources/js/modules/hr/` & `resources/js/modules/payroll/`  
**Last Verified Commit Baseline:** `26854065d` (+ UI `.xlsx` FormData import fix)

---

## 1. Executive Summary

This document records the end-to-end technical architecture, audit findings, remediations, and verification results for AutoERP's **Human Resources (HR)**, **Multi-Method Attendance Capture**, **Shift & Roster Scheduling**, **Holiday Calendars**, **Leave Management**, **Biometric Hardware Integration**, and **Payroll** modules.

The system follows AutoERP's core engineering invariants (`AGENTS.md`):
- **Multi-tenant & Organization-Unit Isolation:** Every HR entity is scoped by `tenant_id` (and `organization_unit_id` where applicable) via middleware (`current.tenant`, `current.organization-unit:required`, `tenant.feature:hr`) and query-level tenant scoping.
- **Optimistic Concurrency Control (OCC):** Mutable entities carry `row_version` and enforce atomic `expected_version` compare-and-swap (CAS) inside `DB::transaction()` + `lockForUpdate()`.
- **Backend Authority & Zero Magic Values:** All state transitions, balances, attendance calculations, and payroll math are governed server-side using PHP Backed Enums and `DecimalMath` (`bcmath`).
- **Immutability & Audit Trails:** Historical financial/operational records (`hr_employee_rates`, `hr_employee_status_histories`, `hr_attendance_adjustments`, `payroll_payslips`, `payroll_payslip_items`) are append-only or lifecycle-locked via `booted()` model guards.

---

## 2. Audit Findings & Remediations

### 2.1 Issues Identified & Resolved

| ID | Area | Finding / Defect | Remediation Implemented | Status |
|---|---|---|---|---|
| **AUD-01** | Attendance Capture | Attendance capture methods were implemented as ad-hoc methods on `AttendanceCaptureService` without a unified contract, and `CaptureMethod::NfcTag` had no adapter or route. | Introduced `AttendanceCaptureAdapterInterface` and `AttendanceCaptureAdapterManager` (`registerAdapter()`, `resolve()`, `capture()`, `supportedMethods()`) with 9 dedicated adapters (`WebPortal`, `MobileGps`, `RfidCard`, `NfcTag`, `Barcode`, `TotpQr`, `ManualAdmin`, `Biometric`, `FileUpload`) plus unified `POST /api/v1/hr/attendance/capture` and `POST /api/v1/hr/attendance/capture/nfc`. | Resolved |
| **AUD-02** | Batch Import (`.xlsx`) | `BatchAttendanceImportService` only parsed CSV/TSV text; `.xlsx` Excel uploads and numeric 1900-epoch Excel serial dates/times failed. | Added native OpenXML `.xlsx` parser (`ZipArchive` + `SimpleXML`) with shared-string resolution and Excel 1900-epoch serial date/time normalization (`normalizeImportDate()`, `normalizeImportTime()`). Enabled `file` (`csv,txt,xlsx`) uploads on both `previewImport` and `commitImport`. | Resolved |
| **AUD-03** | Frontend `.xlsx` Commit | `AttendanceManagementPage.tsx` and `hrApi.ts` restricted file input to `.csv,.txt` and called `importFile.text()` on commit, corrupting binary `.xlsx` files. | Updated `commitAttendanceImport()` in `hrApi.ts` to send `FormData` when `file` is provided, updated `handleCommitImport()` in `AttendanceManagementPage.tsx` to pass `file: importFile`, and updated file input `accept=".csv,.txt,.xlsx"`. | Resolved |
| **AUD-04** | Adapter Payload Guards | All 9 capture adapters accessed `$payload['punch_type']`, `punch_state`, and `verify_method` without null-coalescing, risking `Undefined array key` 500 errors. | Guarded optional keys with `?? null` across all 9 adapters and added explicit empty `punch_time` validation in `FileUploadCaptureAdapter`. | Resolved |
| **AUD-05** | Biometric Reprocessing | `BiometricDeviceService::reprocessUnmappedPunches()` and `reprocessPunchesForUser()` did not pass `CaptureMethod::Biometric` and `idempotencyHash`, risking duplicate punches on replay. | Passed `CaptureMethod::Biometric` and deterministic `idempotencyHash` during unmapped punch reprocessing. | Resolved |
| **AUD-06** | Holiday Calendar CRUD | `HrHolidayCalendar` and `HrHoliday` had models and migrations but no HTTP controller or API routes. | Created `HrHolidayController` with full paginated CRUD (`indexCalendars`, `storeCalendar`, `showCalendar`, `updateCalendar`, `destroyCalendar`, `indexHolidays`, `storeHoliday`, `updateHoliday`, `destroyHoliday`), `expected_version` OCC, `$request->has()` partial preservation, and duplicate date 422 checks. | Resolved |
| **AUD-07** | Leave Type & Policy CRUD | `HrLeaveController` lacked `show`/`update`/`destroy` for Leave Types and Leave Policies, `showLeaveRequest`, and paginated `compOffClaims`. | Completed full CRUD with `expected_version` OCC, `$request->has()` partial updates, usage blockers on delete, `after_or_equal` date validation, and paginated `compOffClaims`. | Resolved |
| **AUD-08** | Leave Concurrency & Math | `LeaveManagementService` lacked `DB::transaction()` + `lockForUpdate()` around balance mutations, allowed overlapping leave requests, lacked half-day/hourly/attachment policy enforcement, used float comparison for attachment threshold, and didn't recalculate attendance on approved leave cancellation. | Wrapped `applyLeave()`, `actionLeaveRequest()`, `cancelLeaveRequest()`, and `actionCompOffClaim()` in `DB::transaction()` with `lockForUpdate()`, enforced `start_date <= end_date` and overlap rejection, replaced float check with `bccomp()`, clamped balances safely, and triggered punch recalculation on cancellation. Moved `expected_version` OCC checks inside transactions to prevent TOCTOU races. | Resolved |
| **AUD-09** | Shift & Roster Guards | `HrShiftController` lacked paginated list endpoints for `shift-assignments`, `shift-swaps`, and `shift-reallocations`, tenant-scoped unique `code` validation, FK usage blocker on delete, and OCC inside transactions on assignment cancel / swap actions. `ShiftRosterService` lacked tenant ownership and leave-conflict checks. | Added paginated endpoints, tenant-scoped `Rule::exists`/`Rule::unique`, 422 FK blocker on `destroy`, `after_or_equal:start_date`, OCC inside `DB::transaction()` + `lockForUpdate()`, and tenant/leave-conflict guards in `ShiftRosterService` (including `addRotationStep()` and `assignRotation()`). | Resolved |
| **AUD-10** | Attendance Validation | `HrAttendanceController::records()` and `listRegularizations()` used raw string validation instead of enum rules and lacked `after_or_equal:start_date` checks. | Added `Rule::enum(...)` and `after_or_equal:start_date` validation on `records()` and `lock()`. | Resolved |
| **AUD-11** | Payroll Leave Clipping | `PayrollAttendanceIntegrationService::aggregateAttendance()` summed `total_days` of approved unpaid leave overlapping a pay period without clipping to `[startDate, endDate]`. | Clipped unpaid leave date ranges to `max(leave.start_date, periodStart)..min(leave.end_date, periodEnd)` when aggregating attendance for payroll. | Resolved |
| **AUD-12** | Shift Rotations API | `HrShiftRotation`, `HrShiftRotationStep`, `HrShiftRotationAssignment`, `ShiftRosterService` rotation methods, and frontend `HrShiftRotationSelect.tsx` (`/api/v1/hr/shift-rotations`) existed, but `HrShiftController` and `app/Modules/Hr/Routes/api.php` lacked the REST endpoints. | Added full tenant-scoped CRUD for `/api/v1/hr/shift-rotations`, step management (`/steps`), and `/api/v1/hr/shift-rotation-assignments` with `expected_version` OCC, overlap validation, and 422 in-use delete protection. | Resolved |

---

## 3. System Architecture & Database Schema

### 3.1 Module Structure

```text
app/Modules/Hr/
├── Biometric/                 # Hardware adapters (ZKTeco Push/Socket, Hikvision ISAPI, Dahua CGI, USB, Generic Webhook)
├── Commands/                  # Artisan commands (DetectMissingPunchesCommand)
├── Contracts/                 # AttendanceCaptureAdapterInterface
├── Database/Migrations/       # 42 explicit 1-table-per-file migrations (2026_06_12_130001..130042)
├── DTOs/                      # Typed immutable Data Transfer Objects
├── Enums/                     # 32 PHP Backed Enums (CaptureMethod, PunchType, AttendanceStatus, etc.)
├── Http/Controllers/          # 16 REST controllers under /api/v1/hr/* + public biometric push receivers
├── Models/                    # 32 Eloquent models with tenant scoping & lifecycle guards
├── Routes/api.php             # Route definitions with tenant.feature:hr & tenant.permission:*
└── Services/
    ├── Capture/               # AttendanceCaptureAdapterManager, AttendanceCaptureService, BatchAttendanceImportService, 9 Adapters
    ├── AttendanceAnomalyService.php
    ├── AttendanceCalculationEngine.php
    ├── AttendanceRegularizationService.php
    ├── LeaveManagementService.php
    ├── ShiftRosterService.php
    └── Employee*.php / Hr*.php

app/Modules/Payroll/
├── Database/Migrations/       # 14 explicit 1-table-per-file migrations (2026_11_25_100001..100014)
├── DTOs/                      # CalculatedLineItemDTO, CalculatedPayslipDTO
├── Enums/                     # 13 PHP Backed Enums (PayRunStatus, PayslipStatus, CalculationType, etc.)
├── Http/Controllers/          # 10 REST controllers under /api/v1/payroll/*
├── Models/                    # 14 Eloquent models with ScopesPayrollTenant & immutability hooks
├── Routes/api.php             # Route definitions with payroll.* permissions
└── Services/                  # CalculationEngine, PayRunExecutionService, TaxService, StatutoryService,
                               # ProrationService, LoanService, RetroactivePayService, AttendanceIntegrationService,
                               # JournalPostingService
```

### 3.2 Database Schema Overview (56 Tables Total)

#### HR Core & Master Data (16 tables)
- `hr_departments`, `hr_designations`, `hr_employment_types`, `hr_skills`, `hr_certifications`, `hr_licenses`
- `hr_employees` (core employee record with `employee_number`, `user_id`, `status`, `availability_status`, `row_version`)
- `hr_employee_contacts`, `hr_employee_addresses`, `hr_employee_documents`
- `hr_employee_skill_assignments`, `hr_employee_certification_assignments`, `hr_employee_license_assignments`
- `hr_employee_rates` (append-only compensation rate history; create-only API)
- `hr_employee_availabilities`, `hr_employee_status_histories` (immutable audit trail with `restrictOnDelete()`)

#### Shifts, Rostering & Holidays (10 tables)
- `hr_shifts`, `hr_shift_breaks`
- `hr_shift_assignments` (`recurring`, `date_range`, `specific_date`)
- `hr_shift_rotations`, `hr_shift_rotation_steps`, `hr_shift_rotation_assignments`
- `hr_shift_swaps`, `hr_shift_reallocations`
- `hr_holiday_calendars`, `hr_holidays`

#### Attendance, Capture Infrastructure & Biometrics (11 tables)
- `hr_attendance_records` (daily calculated record: `attendance_date`, `status`, `worked_minutes`, `overtime_minutes`, `anomaly_type`, `lock_status`)
- `hr_attendance_punches` (raw/normalized punch events with `capture_method`, `idempotency_hash`, `punch_time`, `punch_type`)
- `hr_attendance_regularizations`, `hr_attendance_adjustments` (immutable audit log)
- `hr_attendance_geofences`, `hr_attendance_kiosks` (rotating TOTP secret)
- `hr_employee_attendance_credentials` (`rfid`, `nfc`, `barcode`, `pin`)
- `hr_attendance_batch_imports` (two-phase dry-run preview & commit audit)
- `hr_biometric_devices`, `hr_biometric_device_users`, `hr_biometric_raw_punches`

#### Leave Management (5 tables)
- `hr_leave_types`, `hr_leave_policies`, `hr_leave_balances`, `hr_leave_requests`, `hr_comp_off_claims`

#### Payroll (14 tables)
- `payroll_salary_components`, `payroll_salary_structures`, `payroll_salary_structure_items`, `payroll_employee_salary_assignments`
- `payroll_tax_tables`, `payroll_tax_brackets`, `payroll_statutory_rules`
- `payroll_pay_periods`, `payroll_pay_runs`, `payroll_payslips`, `payroll_payslip_items`
- `payroll_adjustments`, `payroll_employee_loans`, `payroll_loan_repayments`

---

## 4. Unified Attendance Capture Adapter Design & Duplicate-Safe Sync

### 4.1 Adapter Contract & Manager
All attendance capture channels implement `Modules\Hr\Contracts\AttendanceCaptureAdapterInterface`:
- `method(): CaptureMethod` — returns the backed enum channel identifier.
- `capture(int $tenantId, array $payload): HrAttendancePunch` — validates channel-specific credentials/constraints, normalizes the punch, enforces duplicate-safe ingestion, and triggers daily attendance recalculation.

`AttendanceCaptureAdapterManager` resolves built-in `CaptureMethod` cases via the Laravel service container and supports runtime extension via `registerAdapter(CaptureMethod|string $method, string $adapterClass)`.

### 4.2 Supported Capture Channels (9 Built-in Adapters)

| `CaptureMethod` | Adapter Class | Endpoint(s) | Validation & Security Controls |
|---|---|---|---|
| `web_portal` | `WebPortalCaptureAdapter` | `POST /api/v1/hr/attendance/capture/web` (or `/capture`) | Verifies active tenant employee; records server timestamp (`now()`) or validated punch time. |
| `mobile_gps` | `MobileGpsCaptureAdapter` | `POST /api/v1/hr/attendance/capture/mobile` | Haversine distance check against active `HrAttendanceGeofence` coordinates & `radius_meters`; flags/rejects out-of-fence punches. |
| `rfid_card` | `RfidCardCaptureAdapter` | `POST /api/v1/hr/attendance/capture/rfid` | Resolves `card_uid` against active `HrEmployeeAttendanceCredential` (`credential_type = rfid`, `status = active`, non-expired). |
| `nfc_tag` | `NfcTagCaptureAdapter` | `POST /api/v1/hr/attendance/capture/nfc` | Resolves `tag_uid` / `nfc_uid` against active `HrEmployeeAttendanceCredential` (`credential_type = nfc`). |
| `barcode` | `BarcodeCaptureAdapter` | `POST /api/v1/hr/attendance/capture/barcode` | Resolves `barcode` against active `HrEmployeeAttendanceCredential` (`credential_type = barcode`) or employee badge. |
| `totp_qr` | `TotpQrCaptureAdapter` | `GET .../kiosks/{kiosk}/qr-token`, `POST .../capture/qr-scan` | Validates HMAC-SHA256 rotating TOTP token (`kiosk_id`, 30s time window + drift tolerance) and prevents token replay via cache/nonce tracking. |
| `manual_admin` | `ManualEntryCaptureAdapter` | `POST /api/v1/hr/attendance/punch`, `POST .../capture` | Admin-authorized manual punch entry with audit notes. |
| `biometric` | `BiometricCaptureAdapter` | `POST .../biometric/devices/{device}/sync`, `/push/*`, `/iclock/*` | Ingests mapped hardware punches from ZKTeco, Hikvision ISAPI, Dahua CGI, USB, or generic webhooks. |
| `file_upload` | `FileUploadCaptureAdapter` | `POST .../capture/import/preview`, `.../import/commit` | Two-phase dry-run validation + atomic commit for CSV, TSV, and `.xlsx` OpenXML files with 1900-epoch Excel serial date/time support. |

### 4.3 Duplicate-Safe Sync & Idempotency
1. **Punch-level Idempotency (`hr_attendance_punches.idempotency_hash`):** Every adapter computes or accepts a deterministic SHA-256 `idempotency_hash` over `(tenant_id, employee_id, punch_time, punch_type, capture_method, source_ref)`. Duplicate submissions within the deduplication window or with matching hashes are safely idempotent.
2. **Biometric Raw Log Deduplication (`hr_biometric_raw_punches`):** Raw hardware logs store `payload_checksum` and `(device_id, biometric_user_id, punch_time)` uniqueness constraints so repeated device pushes or pull syncs never double-insert raw logs. Unmapped punches are staged as `unmapped` and reprocessed idempotently once employee-device mapping is created.

---

## 5. HR, Leave, Shift, Approval, Audit & Payroll Integration Design

### 5.1 Shift Roster Precedence
`ShiftRosterService::resolveRosterForEmployeeDate()` resolves the effective daily schedule in strict precedence order:
1. **Holiday Calendar (`hr_holidays`):** Marks the day as `is_holiday = true` (with `holiday_name`).
2. **Approved Leave (`hr_leave_requests`):** Reflects approved time-off.
3. **Shift Reallocation / Cover (`hr_shift_reallocations`):** Temporary cover assignment for an absent employee.
4. **Approved Shift Swap (`hr_shift_swaps`):** Two-stage workflow (Peer Accept/Reject → Manager Approve/Reject) with OCC & leave-conflict validation.
5. **Specific-Date Override → Date-Range Assignment → Recurring Weekly Assignment (`hr_shift_assignments`).**
6. **Shift Rotation Cycle (`hr_shift_rotations` / `hr_shift_rotation_steps`):** Modulo cycle calculation from `rotation_start_date`.

### 5.2 Attendance Calculation & Anomaly Engine
`AttendanceCalculationEngine` evaluates daily punches against the resolved shift:
- Computes `worked_minutes`, break deductions (`is_break_paid`, `auto_deduct_break`, `hr_shift_breaks`), `late_minutes`, `early_leave_minutes`, and `overtime_minutes` (against `full_day_threshold_minutes` and `half_day_threshold_minutes`).
- `AttendanceAnomalyService` detects `missing_in`, `missing_out`, and `both_missing` anomalies; admins can quick-resolve them or employees can submit `HrAttendanceRegularization` requests.
- `HrAttendanceAdjustment` records every manual override with `previous_*` and `new_*` snapshots, `adjustment_reason`, and `adjusted_by`.
- Attendance periods can be locked (`lock_status = locked`) before payroll execution.

### 5.3 Leave Management & Comp-Off
- `LeaveManagementService` manages `HrLeaveType`, `HrLeavePolicy` (accrual frequency, carry-forward, max negative balance, attachment thresholds), `HrLeaveBalance`, `HrLeaveRequest`, and `HrCompOffClaim`.
- All balance mutations run inside `DB::transaction()` with `lockForUpdate()`, `bcmath` arithmetic, overlapping request rejection, and automatic attendance recalculation when an approved leave is cancelled.

### 5.4 Payroll Calculation & General Ledger Integration
`PayRunExecutionService` orchestrates batch payroll for a `PayrollPayPeriod`:
1. **Attendance Aggregation (`PayrollAttendanceIntegrationService`):** Aggregates `worked_minutes`, `overtime_minutes`, and **period-clipped** unpaid leave days across `[period_start, period_end]`.
2. **Proration (`PayrollProrationService`):** Prorates base salary for mid-period joiners, leavers, and unpaid leave using `DecimalMath`.
3. **Earnings & Deductions (`PayrollCalculationEngine`):** Evaluates `fixed`, `percentage_of_basic`, `formula`, and `attendance_based` components from `PayrollSalaryStructure`.
4. **Taxes & Statutory Rules (`PayrollTaxService`, `PayrollStatutoryService`):** Applies progressive `PayrollTaxTable` brackets and employer/employee statutory contributions (e.g. EPF/ETF).
5. **Loans & Adjustments (`PayrollLoanService`, `PayrollRetroactivePayService`):** Deducts scheduled `PayrollLoanRepayment` installments and applies approved `PayrollAdjustment` / retro-pay arrears.
6. **Lifecycle & GL Posting (`PayrollJournalPostingService`):** `Draft` → `Calculated` → `Approved` → `Paid` (posts balanced debit/credit journal entries via `Finance` semantic posting profiles) → `Reversed` (creates linked reversal journal entries; never mutates historical records in place).

---

## 6. Verification Results

*(Exact observed test & quality-gate results on current workspace after all edits)*

| Gate | Command | Result |
|---|---|---|
| **HR & Payroll Backend Tests** | `php artisan test tests/Feature/Hr app/Modules/Hr/Tests app/Modules/Payroll/Tests` | **53 passed (892 assertions)** |
| **PHP Code Style (Pint)** | `php vendor/bin/pint --test` | **Passed (0 violations)** |
| **TypeScript Typecheck** | `npm run typecheck` | **Passed (0 errors)** |
| **ESLint** | `npm run lint` | **Passed (0 errors)** |
| **Frontend Unit/Integration Tests** | `npx vitest run` | **177 passed / 177 test files (745 tests passed)** |
