# 2026-11-26 — Comprehensive Backdated CRUD and Lifecycle Immutability Audit

**Author:** Kasun Vimarshana <kasunvmail@gmail.com>  
**Phone:** +94713525253

## Summary
- **Backdated Inventory Movements & UOM Revisions Root-Cause Fix**:
  - In `StockCardService::currentQuantity`, fixed an architectural flaw where historical UOM conversion factor revisions were filtered using `$revision->applied_at < $movement->posted_at`.
  - When backdating an inventory movement to an earlier operational date, its physical `posted_at` is contemporary (`now()`). If a UOM conversion factor revision occurred between the backdated `movement_date` and the physical recording timestamp `posted_at`, the engine incorrectly considered the revision to have occurred prior to the movement and skipped the conversion factor, corrupting running balances and throwing a `LogicException`.
  - Fixed to filter by civil operational date (`$appliedDate < $movementDate`), strictly adhering to Rule 6 Dual-Timestamp Architecture.
- **Model Lifecycle Immutability & Safe Backdated CRUD Enforcement**:
  - Implemented `booted()` lifecycle hooks across transaction models and lines to enforce strict immutability of approved/finalized records directly at the database layer (Rule 6 & Rule 5):
    - `PurchaseOrder` & `PurchaseOrderLine`: Approved and non-draft purchase orders and their lines are immutable in place and cannot be deleted (throwing `LogicException`). Draft purchase orders remain editable.
    - `PurchaseReturn` & `PurchaseReturnLine`: Posted/non-draft purchase returns and their lines are immutable in place and lines cannot be deleted (throwing `LogicException`).
    - `PurchaseDebitNote`: Approved and non-draft purchase debit notes cannot have core attributes (`amount`, `debit_note_date`, etc.) mutated in place (throwing `LogicException`).
    - `SaleReturn` & `SaleReturnLine`: Posted sale returns and their return lines are immutable in place and lines cannot be deleted (throwing `LogicException`).
    - `VehicleServiceJob` & `VehicleServiceJobLine`: Completed, invoiced, partially paid, and paid vehicle service jobs and their lines are immutable in place and lines cannot be deleted (throwing `LogicException`). In-progress / draft jobs remain editable.
- **Biometric Push Webhook Tenant Context Resolution Fix**:
  - In `tests/Feature/Hr/BiometricIntegrationTest.php`, resolved the issue where unauthenticated public hardware push receiver endpoints were invoked within a pre-scoped tenant context, causing `TenantExecutionContext` to throw `LogicException('Tenant-scoped execution cannot broaden into the platform control plane.')`. Separated public webhook HTTP requests from tenant context scoping, ensuring full 100% test pass rate across the HR test suite.
- **HR Attendance Period Locks & Safe Backdated Adjustments**:
  - Enforced `AttendanceLockStatus::Finalized` check in `AttendanceRegularizationService::directAdjust` (throwing `InvalidArgumentException('Cannot adjust a finalized attendance record.')`) to prevent backdated direct adjustments from modifying finalized attendance periods.
  - Updated `HrAttendanceController::listRegularizations` to sequence records chronologically by civil `attendance_date` descending, then `id` descending, replacing physical `created_at`.
- **Payment & Cash Receipt Backdating UX**:
  - Fixed missing `notes` input in `PaymentEntryPage.tsx`. The backend policy (`PaymentCashReceiptDatePolicy`) strictly requires notes/justification when creating backdated cash receipts. The frontend previously omitted this field, blocking users from fulfilling the validation requirement. Added reactive notes inputs for direct payments and selling settlement forms with dynamic guidance hints indicating when reasons are required.
- **Frontend Dual-Timestamp & Civil Date Bounds Across Modules**:
  - Enhanced `ReversalDialog.tsx` with `minDate` and `maxDate` properties and validation guards, ensuring reversals cannot be dated earlier than the original source transaction civil date or in the future.
  - Wired `minDate` into `ExpenseListPage`, `InvoiceDetailPage`, `GoodsReceiptDetailPage`, and `GoodsReceiptListPage`.
  - Added civil date bounds (`min` / `max`) and informative hints across `ExpenseCreatePage`, `GoodsReceiptForm`, `PurchaseOrderForm`, `PurchaseReturnForm`, `PurchaseDebitNoteForm`, `PurchaseInvoiceCreatePage`, `FinanceJournalDetailPage`, `PaymentAllocationReversalPanel`, `PaymentRefundPanel`, `DepositPanel`, and `VehicleServicePaymentPreparePage`.
  - Replaced browser-local timezone calls (`new Date()`) with workspace-aware `businessDateInputValue()` in `inventoryUi.tsx`, `InventoryOverview.tsx`, `AttendanceManagementPage.tsx`, and `LeaveRequestsTab.tsx`. Fixed UTC date arithmetic in `VehicleFinanceEditor.tsx`.

## Modified Files
- `app/Modules/Inventory/Services/StockCardService.php`
- `app/Modules/Purchase/Models/PurchaseDebitNote.php`
- `app/Modules/Purchase/Models/PurchaseOrder.php`
- `app/Modules/Purchase/Models/PurchaseOrderLine.php`
- `app/Modules/Purchase/Models/PurchaseReturn.php`
- `app/Modules/Purchase/Models/PurchaseReturnLine.php`
- `app/Modules/Selling/Models/SaleReturn.php`
- `app/Modules/Selling/Models/SaleReturnLine.php`
- `app/Modules/VehicleService/Models/VehicleServiceJob.php`
- `app/Modules/VehicleService/Models/VehicleServiceJobLine.php`
- `app/Modules/Hr/Services/AttendanceRegularizationService.php`
- `app/Modules/Hr/Http/Controllers/HrAttendanceController.php`
- `app/Modules/Inventory/Tests/StockCardTest.php`
- `app/Modules/Hr/Tests/HrEngineTest.php`
- `tests/Feature/Core/BackdatedCrudSafetyTest.php`
- `tests/Feature/Hr/BiometricIntegrationTest.php`
- `resources/js/shared/components/ReversalDialog.tsx`
- `resources/js/modules/expense/pages/ExpenseCreatePage.tsx`
- `resources/js/modules/expense/pages/ExpenseListPage.tsx`
- `resources/js/modules/finance/pages/FinanceJournalDetailPage.tsx`
- `resources/js/modules/hr/AttendanceManagementPage.tsx`
- `resources/js/modules/hr/components/leave/LeaveRequestsTab.tsx`
- `resources/js/modules/inventory/components/InventoryOverview.tsx`
- `resources/js/modules/inventory/components/inventoryUi.tsx`
- `resources/js/modules/invoice/pages/InvoiceDetailPage.tsx`
- `resources/js/modules/payment/components/PaymentAllocationReversalPanel.tsx`
- `resources/js/modules/payment/components/PaymentRefundPanel.tsx`
- `resources/js/modules/payment/pages/PaymentEntryPage.tsx`
- `resources/js/modules/purchase/components/GoodsReceiptForm.tsx`
- `resources/js/modules/purchase/components/PurchaseDebitNoteForm.tsx`
- `resources/js/modules/purchase/components/PurchaseOrderForm.tsx`
- `resources/js/modules/purchase/components/PurchaseReturnForm.tsx`
- `resources/js/modules/purchase/pages/GoodsReceiptDetailPage.tsx`
- `resources/js/modules/purchase/pages/GoodsReceiptListPage.tsx`
- `resources/js/modules/purchase/pages/PurchaseInvoiceCreatePage.tsx`
- `resources/js/modules/vehicle-rental/DepositPanel.tsx`
- `resources/js/modules/vehicle-rental/VehicleFinanceEditor.tsx`
- `resources/js/modules/vehicle-service/pages/VehicleServicePaymentPreparePage.tsx`

## Verification
- **Code Style (Pint)**:
  - `php vendor/bin/pint --test`: PASSED (0 violations).
- **TypeScript & Frontend Quality Gates**:
  - `npm run typecheck`: PASSED (0 errors).
  - `npm run lint`: PASSED (0 errors).
  - `npm test -- --run` (Vitest): PASSED (181 test files passed, 765 tests passed).
- **Automated Backend Test Suites**:
  - `php artisan test --filter BackdatedCrudSafetyTest`: PASSED (13 passed, 79 assertions).
  - `php artisan test --filter StockCardTest`: PASSED (7 passed, 24 assertions).
  - `php artisan test --filter BiometricIntegrationTest`: PASSED (6 passed, 43 assertions).
  - `php artisan test --filter HrEngineTest`: PASSED (12 passed, 158 assertions).
  - `php artisan test --filter ModuleMigrationBaselineTest`: PASSED (1 passed, 3 assertions).
  - `php artisan test --filter HistoricalRetentionMigrationTest`: PASSED (1 passed, 22 assertions).
  - Full backend test suite `php artisan test`: 1,486 passed, 0 failures (15,558 assertions).
