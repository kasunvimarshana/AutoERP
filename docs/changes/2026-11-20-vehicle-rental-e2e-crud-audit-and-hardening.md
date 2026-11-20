# Change Record: Vehicle Rental End-to-End CRUD Audit and Hardening

**Date:** 2026-11-20  
**Scope:** `Modules/VehicleRental`  
**Purpose:** Comprehensive end-to-end audit, inspection, and hardening of all CRUD operations across the `VehicleRental` module. Identified and fixed business logic bugs, authorization gaps, cross-cycle chart isolation defects, unhandled draft billing cycle deadlocks, expense allocation state transitions, request validation mismatches, and data presentation compliance.

## Summary of Changes

1. **Cross-Cycle Running Chart Finalization Isolation (`TwoStageInvoicingService.php`)**:
   - Fixed a critical cross-cycle blocking flaw where finalizing physical usage evidence on a Customer cycle permanently locked the running chart from ever being attached to the corresponding Owner settlement cycle (and vice versa). Running charts represent shared physical evidence between Accounts Receivable (customer billing) and Accounts Payable (owner settlement).
   - In both `attachRunningCharts()` and `getCandidateRunningCharts()`, chart attachment status queries are now scoped strictly to cycles matching the agreement kind (`customer_agreement_id IS NOT NULL` vs `owner_agreement_id IS NOT NULL`).

2. **Draft Billing Cycle Cancellation and Period Unlocking (`TwoStageInvoicingService.php`, `BillingCycleController.php`, `BillingCycleRequest.php`, `api.php`)**:
   - Resolved a deadlock where creating a draft billing cycle without an initial invoice persisted the cycle in `Draft`, permanently blocking date reuse if dates were entered incorrectly (since no invoice existed to cancel).
   - Implemented `TwoStageInvoicingService::cancelCycle()` and added `POST /api/v1/vehicle-rental/billing-cycles/{cycle}/cancel` with optimistic locking (`row_version`), reason validation, and audit trail transition to `Cancelled`, releasing the date window for re-entry.
   - Prohibited destructive `DELETE /api/v1/vehicle-rental/billing-cycles/{cycle}`, returning HTTP 405 `MethodNotAllowedHttpException` to preserve immutability and audit trails.

3. **Agreement Adjustments Authorization, Presentation, and Lifecycle (`AgreementAdjustmentController.php`, `AgreementAdjustmentRequest.php`, `AgreementAdjustmentResource.php`, `AgreementAdjustment.php`, `AgreementAdjustmentType.php`, `AgreementAdjustmentCategory.php`, `api.php`)**:
   - Closed an authorization bypass where adjustment endpoints previously lacked permission checks: integrated `RentalAuthorization` customer and owner view/manage gates.
   - Added agreement status verification to prevent adjustments against closed agreements.
   - Replaced raw database responses with `AgreementAdjustmentResource` and generated human-readable references (`ADJ-00001`) and enum labels, conforming to project rules against naked foreign keys.
   - Implemented `POST /api/v1/vehicle-rental/adjustments/{adjustment}/void` with optimistic concurrency checking (`row_version`) and history recording.
   - Bound HTTP 405 `MethodNotAllowedHttpException` on `DELETE /api/v1/vehicle-rental/adjustments/{adjustment}`.

4. **Rental Incidents Dedicated Request and Filter Fix (`RentalIncidentRequest.php`, `RentalIncidentController.php`, `RentalIncidentAttachmentController.php`, `RentalIncidentService.php`)**:
   - Replaced inappropriate binding of `AgreementRequest` on incident endpoints with dedicated `RentalIncidentRequest`, validating `IncidentStatus` and `IncidentType` enums and pagination parameters.
   - Updated `RentalIncidentService::list()` and controllers to properly accept and filter query results by `IncidentStatus` and `IncidentType`.

5. **Rental Expense Allocation Status & Invoice Reconciliation (`RentalExpenseService.php`, `RentalExpenseController.php`, `OwnerAgreement.php`)**:
   - Fixed status handling in `RentalExpenseService`: `billAllocation()` explicitly marks the allocation as `Invoiced` and stores the linked `invoice_id`. `unlinkCancelledInvoice()` reverts allocation status to `Confirmed` and clears `invoice_id`.
   - Updated `unlinkAllocationInvoice` controller endpoint to return both root-level and nested `data` payload formats, satisfying frontend client contract (`rentalExpenseApi.ts`) and existing tests.
   - Added `supplier()` alias to `OwnerAgreement` to align with `CustomerAgreement::customer()`.

6. **Invoice Document Optimistic Version Bump (`RentalBillingDocumentService.php`)**:
   - Ensured `$invoice->row_version += 1` occurs during draft rental invoice document editing to preserve concurrency protection.

7. **Comprehensive End-to-End Edge Case Verification (`VehicleRentalCrudAuditAndEdgeCasesTest.php`)**:
   - Added new comprehensive test suite with 8 tests (89 assertions) covering:
     - Adjustment permission enforcement (401 Unauthorized / 403 Forbidden).
     - Customer and Owner adjustment CRUD, human-readable references, labels, and voiding lifecycle.
     - Strict validation rules (mutual exclusivity between customer and owner agreements, positive amounts, reason length).
     - Draft billing cycle cancellation and period reuse.
     - Cross-cycle running chart finalization isolation between customer and owner cycles.
     - Incident query filtering and review validation.
     - Universal HTTP 405 enforcement across all immutable delete endpoints.
     - Expense allocation billing lifecycle, invoice linking, cancellation, and unlinking status transitions.

## Verification
- **VehicleRental Test Suite**: `php artisan test app/Modules/VehicleRental/Tests/` — 318 passed (2,651 assertions), 0 failures.
- **Frontend Vitest Suite**: `npm run test -- resources/js/modules/vehicle-rental` — 38 test files passed (172 tests), 0 failures.
- **TypeScript Typecheck**: `npm run typecheck` — 0 errors.
