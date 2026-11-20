# Change Record: Vehicle Rental CRUD Audit Skeptical Review and Hardening

**Date:** 2026-11-20  
**Scope:** `Modules/VehicleRental` & frontend `resources/js/modules/vehicle-rental`  
**Purpose:** Skeptical review, inspection, and hardening of the VehicleRental module following the prior attempt. Identified and fixed critical double-billing race conditions during billing cycle finalization, invalid state transition allowing cancelled billing cycles to be finalized or previewed, validation routing flaws on incident review/attachment endpoints, missing authorization on billing cycle and agreement financial adjustment endpoints, organizational unit isolation bypass in expense allocation unlinking, rejection of draft adjustment voiding, and missing frontend API client bindings.

## Identified Deficiencies and Root Cause Analysis

1. **Cancelled Billing Cycle Finalization and Preview Bypass (`TwoStageInvoicingService.php`)**:
   - **Input:** Invoking `TwoStageInvoicingService::finalize()` or `previewFinalStage()` on a billing cycle in `Cancelled` status.
   - **Expected:** `ValidationException` prohibiting finalization or preview of a cancelled billing cycle.
   - **Actual:** `finalize()` only verified `$cycle->status === BillingCycleStatus::Finalized`, ignoring `Cancelled`. Cancelled cycles were resurrected into `Finalized` and generated real financial invoices.
   - **Root Cause:** Incomplete status validation check in `TwoStageInvoicingService::finalize()` and `previewFinalStage()`.

2. **Race-Condition Double Billing on Attached Running Charts (`TwoStageInvoicingService.php`)**:
   - **Input:** Finalizing a draft billing cycle when one or more of its attached running charts were finalized by another billing cycle of the same commercial kind after initial attachment.
   - **Expected:** `ValidationException` blocking duplicate invoice/bill generation for usage evidence already finalized elsewhere.
   - **Actual:** `finalize()` never inspected `$cycle->charts` against other finalized cycles, double-billing the physical running chart.
   - **Root Cause:** Finalized status check was performed only at attachment time (`attachRunningCharts`), not re-verified atomically at `finalize()`.

3. **Incident Review & Attachment Endpoint Validation Routing Failure (`RentalIncidentRequest.php`)**:
   - **Input:** Dispatching `POST /incidents/{incident}/confirm` or `reject` without `expected_version` (or uploading attachment without file).
   - **Expected:** 422 Unprocessable Entity with `expected_version` (or `file`) validation error.
   - **Actual:** Request fell through to `POST /incidents` create rules, reporting irrelevant errors like `vehicle_use_id is required` and `incident_type is required`.
   - **Root Cause:** Conditional branching in `RentalIncidentRequest::rules()` checked `$this->has('expected_version')` rather than route parameters (`$this->route('action') !== null` or `$this->is('*/attachments*')`).

4. **Missing Billing Authorization on Agreement Financial Adjustments (`AgreementController.php`)**:
   - **Input:** An authenticated user with only `CUSTOMER_VIEW` or `OWNER_VIEW` calling `POST /{kind}/agreements/{agreement}/financial-adjustments`.
   - **Expected:** 403 Forbidden.
   - **Actual:** 201 Created — a read-only agreement viewer could issue credit and debit note invoices directly into the ledger.
   - **Root Cause:** `AgreementController::createFinancialAdjustment` relied only on `agreements->find()` (which checks read-only view permission) without calling `assertBilling()`.

5. **Missing Authorization on Billing Cycle Index and Show Endpoints (`BillingCycleController.php`, `RentalAuthorization.php`)**:
   - **Input:** An authenticated user without agreement view or billing permissions calling `GET /billing-cycles` or `GET /billing-cycles/{id}`.
   - **Expected:** 403 Forbidden.
   - **Actual:** 200 OK — unauthorized users could inspect all billing cycles, statements, and financial totals.
   - **Root Cause:** `BillingCycleController::index` and `show` lacked permission checks. Implemented `RentalAuthorization::canViewOrBillCycle()` and `assertCycleView()`.

6. **Draft Adjustment Voiding Rejection (`AgreementAdjustmentController.php`)**:
   - **Input:** Calling `POST /adjustments/{id}/void` on an adjustment in `Draft` status.
   - **Expected:** 200 OK transitioning draft adjustment to `Voided` with history trail.
   - **Actual:** 409 Conflict: `Only authorized adjustments can be voided. Current status is draft.`
   - **Root Cause:** Voiding check strictly required `Authorized`, needlessly prohibiting cancellation of draft adjustments.

7. **Multi-Branch Organization Unit Isolation in Expense Allocation Unlinking (`RentalExpenseController.php`)**:
   - **Input:** User in branch A calling `POST /expenses/allocations/{id}/unlink-invoice` for an allocation belonging to branch B.
   - **Expected:** 404 Not Found.
   - **Actual:** 200 OK unlinking invoice across organization units.
   - **Root Cause:** Query lacked `where('organization_unit_id', $context->organizationUnitId)`.

8. **Missing Frontend Client API Bindings (`agreementApi.ts`)**:
   - **Input:** Frontend components needing to cancel billing cycles or manage agreement adjustments.
   - **Expected:** Strongly-typed API client functions.
   - **Actual:** `cancelBillingCycle`, `listAgreementAdjustments`, `getAgreementAdjustment`, `createAgreementAdjustment`, and `voidAgreementAdjustment` were absent from `agreementApi.ts`.

9. **Extraneous Method Arguments in `assertBilling` Calls (`TwoStageInvoicingService.php`, `RentalBillingDocumentService.php`)**:
   - Cleaned up call sites passing unused 3rd argument to `assertBilling($context, $kind)`.

## Verification Record

- **Test Suite (Backend)**: `php artisan test app/Modules/VehicleRental/Tests/` — 324 tests passed (2,696 assertions), 0 failures.
- **Specific Audited Edge Cases**: `php artisan test app/Modules/VehicleRental/Tests/VehicleRentalCrudAuditAndEdgeCasesTest.php` — 14 tests passed (134 assertions), 0 failures.
- **Frontend Test Suite**: `npm run test -- resources/js/modules/vehicle-rental` — 38 test files passed (172 tests), 0 failures.
- **TypeScript Typecheck**: `npm run typecheck` — 0 errors.
- **Git Diff Hygiene**: `git diff --check` — clean, no formatting or whitespace anomalies.
