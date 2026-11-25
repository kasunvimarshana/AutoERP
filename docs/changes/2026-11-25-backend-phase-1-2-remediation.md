# Backend Phase 1 & 2 Remediation — Payroll Security, Tenant Isolation, and Immutability

## Context & Objectives
Resolved critical security, tenant isolation, schema integrity, and Axiom 6 immutability defects across Payroll, HR, Payment, and Purchase modules:
1. Enforced granular `tenant.permission` middleware on all Payroll API routes, added `payroll.loans.manage` to `PayrollAuthorizationService`, removed `PayrollRequest` client-header/payload tenant fallbacks, enforced permission authorization in `PayrollRequest::authorize()`, and removed `?? 1` user ID fallbacks in `PayrollAdjustmentController` and `PayrollLoanController`.
2. Fixed all 5 PHPUnit failures:
   - Replaced `nullOnDelete()` with `restrictOnDelete()` on `hr_employees.user_id` composite FK and converted 5 HR single-column tenant-table FKs (`hr_attendance_punches`, `hr_attendance_regularizations`, `hr_leave_requests`, `hr_comp_off_claims`, `hr_biometric_raw_punches`) to composite `[..., 'tenant_id']` FKs.
   - Extended `TenantOwnedModel` (and called `parent::booted()`) in `PayrollEmployeeLoan` and `PayrollLoanRepayment`.
   - Replaced in-place `allocated_amount` mutation in `PaymentAllocationService` with governed reversal + reissue of a new immutable `PaymentAllocation` record, and updated `PaymentAllocationReversalServiceTest` to verify the immutable reversal lineage.
   - Updated `PurchaseEngineTest` fixtures to construct dedicated `Invoice` / `GoodsReceiptNote` records rather than mutating posted documents.
3. Closed `booted()` immutability bypasses in `PayrollPayRun`, `PayrollPayslip`, `PayrollAdjustment`, `PayrollEmployeeLoan`, and `PayrollLoanRepayment` using dirty-attribute allowlists and `row_version` auto-increment.

## Modified & Created Files Summary

| File | Change Description |
|---|---|
| `app/Modules/Payroll/Services/PayrollAuthorizationService.php` | Added `MANAGE_LOANS` (`payroll.loans.manage`) permission and description. |
| `app/Modules/Payroll/Routes/api.php` | Added `tenant.permission` middleware mapping using `PayrollAuthorizationService` on all Payroll routes. |
| `app/Modules/Payroll/Http/Requests/PayrollRequest.php` | Enforced permission-based `authorize()` via `PermissionCheckerInterface` and removed `tenantId()` / `currentUserId()` fallbacks. |
| `app/Modules/Payroll/Http/Controllers/PayrollAdjustmentController.php` | Removed `?? 1` user ID fallback; throws `AuthenticationException` when unauthenticated. |
| `app/Modules/Payroll/Http/Controllers/PayrollLoanController.php` | Removed `?? 1` user ID fallback; throws `AuthenticationException` when unauthenticated. |
| `app/Modules/Hr/Database/Migrations/2026_06_12_130007_create_hr_employees_table.php` | Changed `hr_employees_user_id_tenant_fk` from `nullOnDelete()` to `restrictOnDelete()`. |
| `app/Modules/Hr/Database/Migrations/2026_06_12_130025_create_hr_attendance_punches_table.php` | Converted `attendance_record_id` FK to composite `['attendance_record_id', 'tenant_id']` with `restrictOnDelete()`. |
| `app/Modules/Hr/Database/Migrations/2026_06_12_130026_create_hr_attendance_regularizations_table.php` | Converted `attendance_record_id` FK to composite `['attendance_record_id', 'tenant_id']` with `restrictOnDelete()`. |
| `app/Modules/Hr/Database/Migrations/2026_06_12_130031_create_hr_leave_requests_table.php` | Converted `attachment_document_id` FK to composite `['attachment_document_id', 'tenant_id']` with `restrictOnDelete()`. |
| `app/Modules/Hr/Database/Migrations/2026_06_12_130032_create_hr_comp_off_claims_table.php` | Converted `attendance_record_id` FK to composite `['attendance_record_id', 'tenant_id']` with `restrictOnDelete()`. |
| `app/Modules/Hr/Database/Migrations/2026_06_12_130038_create_hr_biometric_raw_punches_table.php` | Converted `attendance_punch_id` FK to composite `['attendance_punch_id', 'tenant_id']` with `restrictOnDelete()`. |
| `app/Modules/Payroll/Models/PayrollEmployeeLoan.php` | Extended `TenantOwnedModel`, called `parent::booted()`, protected `Active`/`Completed` terms, and auto-incremented `row_version`. |
| `app/Modules/Payroll/Models/PayrollLoanRepayment.php` | Extended `TenantOwnedModel`, called `parent::booted()`, added `updating` immutability guard for `Deducted` repayments, and auto-incremented `row_version`. |
| `app/Modules/Payroll/Models/PayrollPayRun.php` | Replaced `!isDirty(...)` bypass with strict dirty-attribute allowlist and `row_version` auto-increment. |
| `app/Modules/Payroll/Models/PayrollPayslip.php` | Replaced `!isDirty('status')` bypass with strict dirty-attribute allowlist and `row_version` auto-increment. |
| `app/Modules/Payroll/Models/PayrollAdjustment.php` | Replaced `!isDirty('status')` bypass with strict dirty-attribute allowlist and `row_version` auto-increment. |
| `app/Modules/Payment/Services/PaymentAllocationService.php` | Replaced in-place allocation top-up mutation with governed reversal + reissue of a new immutable `PaymentAllocation`. |
| `app/Modules/Payment/Tests/PaymentAllocationReversalServiceTest.php` | Updated top-up reversal assertion to verify the 3 immutable `PaymentAllocation` records and `supersedes_allocation_id` lineage. |
| `app/Modules/Purchase/Tests/PurchaseEngineTest.php` | Replaced post-posting mutations on `Invoice` and `GoodsReceiptNote` with dedicated non-mutated test fixtures. |
| `app/Modules/Payroll/Tests/PayrollApiTest.php` | Provisioned tenant admin role for API workflow user and added 403 RBAC check for unprivileged users. |
| `app/Modules/Payroll/Tests/PayrollEngineTest.php` | Added piggybacked-mutation immutability tests for `PayrollAdjustment`, `PayrollPayslip`, and `PayrollPayRun`. |

## Verification & Quality Gate Results
- `php artisan test --filter="TenantIsolationArchitectureTest|PaymentAllocationReversalServiceTest|PurchaseEngineTest|Payroll|Hr|ModuleMigrationBaselineTest|HistoricalRetentionMigrationTest"`: **118 passed (1,186 assertions)**, 0 failed.

## Author & Maintainer Signature
- **Lead Architect & Developer:** Kasun Vimarshana
- **Email:** kasunvmail@gmail.com
- **Phone:** +94713525253
- **Repository:** https://github.com/kasunvimarshana/AutoERP
