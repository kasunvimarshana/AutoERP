# 2026-11-26 — HR Leave Management Phase 2 (Database, Backend, Policy Enforcement & Full UI)

## Context & Objectives
Implemented Phase 2 of HR Leave Management end-to-end:
- Enabled soft-deleted leave type code reuse (including `COMP_OFF`) via portable composite unique index `hr_leave_types_tenant_code_uk (tenant_id, code, deleted_at)` with `whereNull('deleted_at')` validation.
- Added `HrLeaveBalance` deletion guard to `LeaveManagementService::deleteLeaveType()`.
- Enforced active `HrLeavePolicy` rules (`effective_from`/`effective_to`, `probation_restricted`, `max_consecutive_days`, `annual_allocation_days`, department/employment-type specificity) in `LeaveManagementService::applyLeave()`.
- Added cross-year leave request balance splitting/deduction/restoration across `applyLeave()`, `actionLeaveRequest()`, and `cancelLeaveRequest()`.
- Computed authoritative `total_days` server-side from date range, `HrHoliday` / scheduled rest days, and `sandwich_rule_applied`.
- Added qualifying holiday/rest-day attendance verification and duplicate-date guard to `LeaveManagementService::claimCompOff()`.
- Exposed complete Leave Types CRUD, Leave Policies CRUD, and Comp-Off Claims queue/claim/action in `hrApi.ts`, `hrTypes.ts`, `hrConstants.ts`, `LeaveManagementPage.tsx`, and `resources/js/modules/hr/components/leave/*` with `expected_version` OCC, `hrPermissions` gating, modal-scoped error display, and zero raw database IDs.

## Modified & Created Files Summary
| File | Summary of Changes |
|---|---|
| `app/Modules/Hr/Database/Migrations/2026_06_12_130028_create_hr_leave_types_table.php` | Updated `hr_leave_types_tenant_code_uk` to `['tenant_id', 'code', 'deleted_at']`. |
| `app/Modules/Hr/Services/LeaveManagementService.php` | Added `HrLeaveBalance` guard on `deleteLeaveType()`, active `HrLeavePolicy` enforcement, cross-year balance splitting, server-side `total_days` computation, and `claimCompOff()` attendance & duplicate guards. |
| `app/Modules/Hr/Http/Controllers/HrLeaveController.php` | Made `total_days` optional on `apply()` so server-computed `total_days` is authoritative. |
| `app/Modules/Hr/Tests/HrEngineTest.php` | Updated comp-off test to create qualifying rest-day attendance and rotation leave test to target a working day. |
| `tests/Feature/Hr/LeaveManagementPhase2Test.php` | Added 4 feature/integration tests (40 assertions) covering all Phase 2 backend fixes and OCC. |
| `resources/js/modules/hr/hrTypes.ts` | Added `HrLeaveTypePayload`, `HrLeavePolicyPayload`, leave status/enum types, and `employment_type` on `HrLeavePolicy`. |
| `resources/js/modules/hr/hrConstants.ts` | Added leave status, accrual frequency, duration type, and comp-off status option constants. |
| `resources/js/modules/hr/hrApi.ts` | Added `updateLeaveType`, `deleteLeaveType`, `updateLeavePolicy`, `deleteLeavePolicy`, `listCompOffClaims`, and `expected_version` OCC forwarding on leave/comp-off mutations. |
| `resources/js/modules/hr/components/leave/LeaveRequestsTab.tsx` | Extracted Balances & Requests tab with filters, pagination, Apply modal, and Cancel modal. |
| `resources/js/modules/hr/components/leave/CompOffClaimsTab.tsx` | Extracted Comp-Off Claims tab with filters, pagination, Claim modal, and Approve/Reject OCC actions. |
| `resources/js/modules/hr/components/leave/LeaveTypesTab.tsx` | Extracted Leave Types CRUD tab with `expected_version` OCC and confirmation dialog. |
| `resources/js/modules/hr/components/leave/LeavePoliciesTab.tsx` | Extracted Leave Policies CRUD tab with department/employment-type lookup selects and `expected_version` OCC. |
| `resources/js/modules/hr/LeaveManagementPage.tsx` | Refactored into a 4-tab Leave Management workspace gated by `hrPermissions`. |
| `resources/js/modules/hr/LeaveManagementPage.test.tsx` | Added 5 Vitest tests covering permission gating, 4-tab rendering, OCC, and modal errors. |

## Verification & Quality Gate Results
- `php artisan test tests/Feature/Hr tests/Unit/Hr app/Modules/Hr/Tests`: **58 passed (932 assertions)**.
- `php artisan test --filter "ModuleMigrationBaselineTest|HistoricalRetentionMigrationTest"`: **2 passed (25 assertions)**.
- `php vendor/bin/pint --test app/Modules/Hr tests/Feature/Hr`: **Passed (0 violations)**.
- `npm run typecheck`: **Passed (0 errors)**.
- `npm run lint`: **Passed (0 errors, 0 warnings)**.
- `npx vitest run resources/js/modules/hr/LeaveManagementPage.test.tsx`: **1 passed / 5 tests passed**.
- `npm run build`: **Passed** (`vite build`, 804 modules transformed).

## Author & Maintainer Signature
- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** kasunvmail@gmail.com
- **Phone:** +94713525253
- **Repository:** https://github.com/kasunvimarshana/AutoERP
