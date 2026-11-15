# Vehicle Rental Dedicated Billing Review & Source Restoration Hardening

**Date:** 2026-11-15  
**Scope:** `VehicleRental`, `Invoice`, `Frontend / Vehicle Rental`  
**Focus:** Review of dedicated Customer Invoices & Vehicle Owner Bills cockpit, atomic source restoration on unposted invoice cancellation, multi-tenant isolation hardening, unbounded full-table scan prevention, and frontend permission-based workflow gating.

---

## 1. Issues Identified in Prior Attempt
During deep code inspection and review of the prior implementation, several critical design defects and edge-case failures were uncovered:
1. **Missing Source Restoration on Draft Cancellation**:
   - `cancelDocument` only transitioned invoice status and cancelled invoice balances, failing to execute promised source restoration logic.
   - When draft/stage 1 billing cycle invoices were cancelled, the linked `BillingCycle` remained in `stage1_generated`, permanently blocking creation of subsequent billing cycles for that agreement/period due to overlap validation.
   - When base charges (`CustomerBaseCharge` / `OwnerBaseCharge`) or usage charges were cancelled, their `voided_at` remained `null`, preventing period re-billing.
   - Rental expense allocations (`RentalExpenseAllocation`) remained permanently bound to cancelled invoices.
2. **Missing Atomic Concurrency Transaction**:
   - `cancelDocument` executed invoice status transition in one transaction and balance cancellation in a separate subsequent step, exposing records to partial state inconsistency if failure occurred.
3. **Multi-Tenant Query Leak in Agreement Filtering**:
   - `listDocuments` looked up agreement references using `CustomerAgreement::where('id', $agreementId)` without tenant scoping (`where('tenant_id', ...)`).
4. **Unbounded Full-Table Scan in Context Bulk-Loading**:
   - In `bulkLoadRentalContexts`, when invoices had no customer/owner charge IDs and no agreement references in sources, the queries for `CustomerAgreement` and `OwnerAgreement` executed with empty closures, causing full table scans and loading all agreements in the tenant into memory.
5. **View-Only User Permission Bypass on Document Detail**:
   - Document detail modal payload set `can_edit = true` and `can_cancel = true` purely on draft status without verifying the user's billing authorization (`assertBilling`).
6. **Frontend AP-Only User 403 Forbidden on Initial Page Mount**:
   - Users with only `agreementPermissions.owner.view` were blocked on initial page load because `RentalInvoicesAndBillsPage` defaulted to the `ar` tab and immediately fired an outbound invoice query that was rejected with 403.
7. **Detail Modal Drawer Unmounting during Payment Allocation**:
   - Launching payment allocation from the detail modal replaced the component tree with `AgreementPaymentModal`, unmounting the detail modal and losing its internal state and scroll position.
8. **Owner Settlement Modal State Synchronization**:
   - `OwnerSettlementModal` did not re-synchronize when re-opened or passed updated agreement props.

---

## 2. Key Corrections & Improvements

### A. Backend (`app/Modules/VehicleRental/Services/RentalBillingDocumentService.php`)
- **Atomic Cancellation & Source Restoration**:
  - Wrapped `cancelDocument` in a single `DB::transaction(...)` with optimistic row-version concurrency check.
  - Added `restoreSourcesOnCancellation`:
    - Automatically marks unfinalized `BillingCycle` records as `BillingCycleStatus::Cancelled`, recording the invoice number and audit cancellation reason.
    - Unlinks `RentalExpenseAllocation` records by resetting `invoice_id` and `billed_at` so expenses can be re-allocated.
    - Voids underlying `CustomerBaseCharge`, `OwnerBaseCharge`, `CustomerUsageCharge`, and `OwnerUsageCharge` records by populating `voided_at`, `voided_by`, and `void_reason`.
- **Multi-Tenant Scoping**:
  - Enforced tenant scoping on agreement lookups in `listDocuments`: `where('tenant_id', $context->tenantId)`.
  - Enforced tenant scoping on base and usage charge queries in `bulkLoadRentalContexts`.
- **Query Optimization**:
  - Guarded agreement queries in `bulkLoadRentalContexts` so they only execute when relevant IDs or references exist, preventing full table scans.
- **Permission-Gated Document Actions**:
  - `getDocumentDetail` evaluates `assertBilling` for the agreement kind so view-only users receive `can_cancel = false` and `can_edit = false`.

### B. Frontend (`resources/js/modules/vehicle-rental/`)
- **`RentalInvoicesAndBillsPage.tsx`**:
  - Integrated `useAuth` and `hasPermission` with `agreementPermissions` and `BILLING_PERMISSION`.
  - Dynamically initializes and switches tab to `'ap'` if the user only has owner view permission.
  - Dynamically gates creation buttons: hides "+ Advance Base Rent" and "+ Billing Cycle Invoice" if lacking customer bill permission, hides "+ Owner Settlement Bill" if lacking owner bill permission.
  - Hides unauthorized tabs so users only see workflows they have access to.
- **`RentalBillingDocumentDetailModal.tsx`**:
  - Retains detail modal mounted while `AgreementPaymentModal` is active, avoiding DOM teardown and preserving scroll position and state.
  - Added dedicated "Print" and "PDF" buttons with signed print link resolution and same-origin fallbacks.
- **`OwnerSettlementModal.tsx`**:
  - Added `useEffect` to reset and re-synchronize form state and agreement selection on open.

### C. Automated Test Hardening (`RentalBillingDocumentTest.php` & `RentalInvoicesAndBillsPage.test.tsx`)
- Added tests verifying:
  - Billing cycle cancellation and unblocking of subsequent cycle creation for the same period.
  - Base charge voiding upon draft cancellation and unblocking of period re-billing.
  - View-only user rejection on draft edit and cancellation (403 Forbidden).
  - Multi-tenant isolation rejecting cross-tenant invoice inspection, updating, and cancellation.
  - Frontend permission-based tab selection and creation button gating.

---

## 3. Verification Summary
- `php artisan test app/Modules/VehicleRental/Tests/RentalBillingDocumentTest.php`: 10 passed (71 assertions).
- `php artisan test app/Modules/VehicleRental/Tests`: 284 passed (2341 assertions).
- `php artisan test tests/Unit/Architecture`: 12 passed (124 assertions).
- `npx vitest run resources/js/modules/vehicle-rental/RentalInvoicesAndBillsPage.test.tsx resources/js/app/navigation/rentalNavigation.test.ts`: 9 passed.
- `npx vitest run resources/js/modules/vehicle-rental`: 33 files passed (154 assertions).
- `npm run typecheck`: 0 errors.
