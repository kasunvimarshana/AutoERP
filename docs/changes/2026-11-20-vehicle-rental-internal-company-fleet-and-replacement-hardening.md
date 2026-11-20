# Vehicle Rental Module: Internal Company Fleet Deployment, Replacement Hardening & Register Scope Alignment

**Date:** 2026-11-20  
**Scope:** `app/Modules/VehicleRental/`, `resources/js/modules/vehicle-rental/`, `docs/changes/`

## 1. Overview & Architectural Findings

Following an audit of the initial internal vehicle deployment implementation, several critical bugs and architectural omissions were identified and resolved:

1. **Fatal Flaw in Vehicle Replacement on Internal Deployments**:
   - In `VehicleUseService::replace(...)`, replacing an internal owner-leased deployment called `planOwner` with the replacement vehicle ID. However, `planOwner` enforced `$input['vehicle_id'] === $owner->vehicle_id`. Because vehicle replacement requires a *different* vehicle (`$input['vehicle_id'] !== $previous->vehicle_id`), vehicle replacement on an internal deployment was structurally impossible and threw a validation exception.
   - In `VehicleDriverReassignmentService::replaceVehicle(...)`, the code unconditionally called `plan($context, (int) $currentUse->customer_agreement_id, ...)`. For internal deployments where `customer_agreement_id` is `null`, `(int) null` evaluated to `0`, causing an uncaught `ModelNotFoundException` (HTTP 404).

2. **Missing Company-Owned Vehicle Internal Deployment**:
   - The prior attempt only provided a mechanism to deploy third-party owner vehicles internally (`POST /owner/agreements/{agreement}/vehicles`). Company-owned vehicles could not be deployed for internal company operations without a customer agreement.
   - Introduced `planInternal(...)` in `VehicleUseService` and exposed `POST /api/v1/vehicle-rental/vehicle-uses/internal` to handle deploying both company-owned fleet vehicles (validated via `CompanyVehicleCoverageService`) and owner-leased vehicles internally.

3. **Inverted Scope Filter in Running Chart & Vehicle Use Registers**:
   - In `RunningChartRegisterService`, filtering by `kind=customer` previously checked `$u->whereNull('owner_agreement_id')`. This conflated "vehicle supply source" with "commercial agreement kind", completely excluding third-party vehicles rented out to customers from the Customer filter, and incorrectly including company-owned internal vehicles in the Customer filter.
   - Fixed `kind=customer` to filter `$u->whereNotNull('customer_agreement_id')`, `kind=owner` to filter `$u->whereNotNull('owner_agreement_id')`, and added explicit support for `kind=internal` (`$u->whereNull('customer_agreement_id')`) across both `RunningChartRegisterService` and `VehicleUseRegisterService`.

4. **UI Register Custody Transitions & Operational Scope Controls**:
   - In `VehicleUseRegisterPage.tsx`, internal company deployments lacked custody handover and return actions if not navigating inside an agreement panel. Added direct custody transition actions (Handover, Cancel plan, Record return) on register rows.
   - Updated the UI scope dropdowns across `VehicleUseRegisterPage` and `RunningChartRegisterPage` to clearly distinguish "Customer rentals", "Owner-leased vehicles", and "Internal company operations".

5. **Fleet Running Chart Print Reporting**:
   - Extended `RunningChartReportService` and `RunningChartReportRequest` to allow generating the Company Fleet Running Chart report for company-owned internal deployments (`agreement_id` is null or 0, with `vehicle_use_id`).

## 2. Changes Implemented

### Backend
- `app/Modules/VehicleRental/Services/VehicleUseService.php`:
  - Added `planInternal(AgreementContext $context, array $input, ?int $replaces = null): VehicleUse`.
  - Refactored `planOwner(...)` to delegate to `planInternal(...)`.
  - Fixed `replace(...)` to dispatch to `planInternal(...)` when replacing internal deployments.
- `app/Modules/VehicleRental/Services/VehicleDriverReassignmentService.php`:
  - Fixed `replaceVehicle(...)` to branch: calls `planInternal(...)` when `customer_agreement_id` is null, preventing `ModelNotFoundException`.
  - Fixed undefined array keys when accessing optional replacement owner agreement IDs.
- `app/Modules/VehicleRental/Services/RunningChartRegisterService.php` & `VehicleUseRegisterService.php`:
  - Aligned `kind=customer` to `$u->whereNotNull('customer_agreement_id')`.
  - Added explicit `kind=internal` filter for `$u->whereNull('customer_agreement_id')`.
- `app/Modules/VehicleRental/Http/Controllers/VehicleUseController.php` & `Routes/api.php`:
  - Added `POST /api/v1/vehicle-rental/vehicle-uses/internal` -> `VehicleUseController::storeInternal`.
  - Supported `kind` query filter in `VehicleUseRegisterRequest`.
- `app/Modules/VehicleRental/Services/RunningChartReportService.php` & `Http/Requests/RunningChartReportRequest.php`:
  - Made `agreement_id` optional when `vehicle_use_id` is provided, generating the Company Fleet Running Chart sheet for internal deployments.
- `app/Modules/VehicleRental/Http/Resources/VehicleUseResource.php`:
  - Null-safe `?->value` operator on `driver_mode` for customer and owner agreements.
- `app/Modules/VehicleRental/Tests/EndToEndWorkflowTest.php`:
  - Added 4 end-to-end integration tests verifying company-owned internal deployment, vehicle replacement via both `VehicleUseService` and `VehicleDriverReassignmentService`, and register scope filtering.
- `app/Modules/VehicleRental/Tests/VehicleRentalCrudAuditAndEdgeCasesTest.php`:
  - Updated filter assertion to verify that customer rentals using owner vehicles correctly appear under both `kind=customer` and `kind=owner`.

### Frontend
- `resources/js/modules/vehicle-rental/vehicleUseApi.ts`:
  - Added `planInternalVehicleUse(...)`.
- `resources/js/modules/vehicle-rental/vehicleUse.ts` & `runningCharts.ts`:
  - Added `internal` to `kind` filter types.
- `resources/js/modules/vehicle-rental/incidentEvidence.ts`:
  - Added `owner_agreement_reference` and `owner_party_name` to `IncidentVehicleUseOption`.
- `resources/js/modules/vehicle-rental/IncidentRegisterPage.tsx`:
  - Formatted vehicle selection options to show owner party name or company fleet tag when customer agreement is omitted.
- `resources/js/modules/vehicle-rental/VehicleUseRegisterPage.tsx`:
  - Added "Deploy vehicle for internal use" form modal and row-level custody action buttons (Handover, Cancel plan, Record return).
  - Added scope filter dropdown with Customer rentals, Owner-leased vehicles, and Internal company operations.
- `resources/js/modules/vehicle-rental/RunningChartRegisterPage.tsx`:
  - Updated filter options and added Print Fleet Sheet support for company internal operations.

## 3. Verification Record

- **Backend Integration Tests**:
  - `php artisan test app/Modules/VehicleRental/Tests/EndToEndWorkflowTest.php`: 4 passed (60 assertions).
  - `php artisan test app/Modules/VehicleRental/Tests/OwnerVehicleInternalUseTest.php`: 5 passed (69 assertions).
  - `php artisan test app/Modules/VehicleRental/Tests/VehicleRentalCrudAuditAndEdgeCasesTest.php`: 16 passed (168 assertions).
  - `php artisan test app/Modules/VehicleRental/Tests/`: All 344 passed (2,948 assertions, 0 failures).
- **Frontend Vitest Tests**:
  - `npm run test -- resources/js/modules/vehicle-rental`: All 38 test suites passed (181 passed, 0 failures).
- **TypeScript Static Verification**:
  - `npm run typecheck` (`tsc --noEmit`): Passed with 0 errors.
