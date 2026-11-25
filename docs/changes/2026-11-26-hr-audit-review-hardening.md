# 2026-11-26 — HR End-to-End Audit Review & Hardening

## Context & Objectives
Post-implementation adversarial review and hardening of the HR & Attendance end-to-end audit changes to eliminate runtime `ErrorException`s, SQL constraint 500s, TOCTOU OCC races, Excel serial date/time parsing gaps, and missing tenant/overlap guards.

## Modified & Created Files Summary
| File | Summary of Changes |
|---|---|
| `app/Modules/Hr/Services/Capture/Adapters/*.php` (all 9 adapters) | Guarded `$payload['punch_type']`, `punch_state`, and `verify_method` with `?? null` to prevent `Undefined array key` 500s; added explicit empty `punch_time` validation in `FileUploadCaptureAdapter`. |
| `app/Modules/Hr/Services/Capture/BatchAttendanceImportService.php` | Added Excel 1900-epoch numeric serial date/time normalization (`normalizeImportDate`, `normalizeImportTime`) and decoupled time validation from invalid dates. |
| `app/Modules/Hr/Http/Controllers/HrHolidayController.php` | Added `whereDate('holiday_date', ...)` duplicate checks on `storeHoliday` and `updateHoliday` (returning 422 instead of 500 SQL constraint errors). |
| `app/Modules/Hr/Http/Controllers/HrLeaveController.php` | Added `after_or_equal` `effective_to` validation on `updateLeavePolicy`, and moved `expected_version` OCC checks inside `DB::transaction()` + `lockForUpdate()` for `action`, `cancel`, and `actionCompOffClaim`. |
| `app/Modules/Hr/Services/LeaveManagementService.php` | Replaced float comparison with `bccomp` for `attachment_mandatory_after_days` check. |
| `app/Modules/Hr/Http/Controllers/HrShiftController.php` | Added tenant-scoped unique `code` validation on `store`/`update`, `$request->has('notes')` clear support on `update`, FK usage blocker (422) on `destroy`, `after_or_equal:start_date` on `assign`/`roster`, and `expected_version` OCC inside `DB::transaction()` + `lockForUpdate()` on `cancelAssignment`, `peerActionSwap`, `managerActionSwap`. |
| `app/Modules/Hr/Services/ShiftRosterService.php` | Added tenant ownership checks in `addRotationStep` and `assignRotation`, plus date-order and active-overlap checks in `assignRotation`. |
| `app/Modules/Hr/Http/Controllers/HrAttendanceController.php` | Added `after_or_equal:start_date` validation on `records` and `lock`. |
| `tests/Feature/Hr/HrEndToEndAuditTest.php` | Added `test_audit_fixes_for_adapters_xlsx_serials_validation_occ_and_fk_guards` covering all review fixes. |

## Verification & Quality Gate Results
- `php artisan test tests/Feature/Hr app/Modules/Hr/Tests app/Modules/Payroll/Tests`: **52 passed (874 assertions)**.
- `php vendor/bin/pint --test` (full repo & modified files): **Passed (0 violations)**.
- `npm run typecheck`: **Passed (0 errors)**.
- `npm run lint`: **Passed (0 errors)**.

## Author & Maintainer Signature
- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** kasunvmail@gmail.com
- **Phone:** +94713525253
- **Repository:** https://github.com/kasunvimarshana/AutoERP
