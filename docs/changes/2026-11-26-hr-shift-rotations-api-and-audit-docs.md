# 2026-11-26 — HR Shift Rotations REST API & Documentation Audit Alignment

## Summary
- Added missing `/api/v1/hr/shift-rotations` and `/api/v1/hr/shift-rotation-assignments` REST endpoints in `HrShiftController` and `app/Modules/Hr/Routes/api.php` (aligning backend routes with `endpoints.hrShiftRotations`, `HrShiftRotationSelect.tsx`, and `docs/hr-user-guide.md`).
- Implemented tenant-scoped CRUD, step management (`/steps`), `expected_version` optimistic concurrency control (`row_version` increment inside `DB::transaction()` + `lockForUpdate()`), overlap validation, and 422 in-use deletion protection.
- Added feature coverage in `tests/Feature/Hr/HrEndToEndAuditTest.php` (`test_shift_rotations_crud_steps_occ_and_assignments`).
- Corrected schema table-group counts in `docs/hr-technical-architecture-and-audit.md` (10 Shifts/Rostering/Holidays tables, 11 Attendance/Biometrics tables; 56 total) and added `AUD-12`.
- Updated `docs/hr-user-guide.md` to accurately reference `/api/v1/hr/shift-rotations` and `/api/v1/hr/shift-rotation-assignments`.

## Files Modified
- `app/Modules/Hr/Http/Controllers/HrShiftController.php`
- `app/Modules/Hr/Routes/api.php`
- `tests/Feature/Hr/HrEndToEndAuditTest.php`
- `docs/hr-technical-architecture-and-audit.md`
- `docs/hr-user-guide.md`
