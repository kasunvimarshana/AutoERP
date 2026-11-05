# 2026-11-05 Vehicle Rental Agreement & Assign CRUD Refactoring and Audit

## Context
A comprehensive end-to-end audit was conducted across the `VehicleRental` module (`VehicleRent`), focusing specifically on the **Agreement** and **Assign (Vehicle Custody / VehicleUse)** CRUD operations and lifecycle flows across backend domain services, controllers, routes, frontend pages, editors, and review panels.

## Issues Identified & Root Cause
1. **Draft Agreement Closure Deadlock**:
   - `AgreementService::change` strictly enforced `$record->status === AgreementStatus::Active` when executing `AgreementAction::Close`.
   - `Agreement::deleting` model hook throws `LogicException('Agreement history must be preserved.')`.
   - Consequently, an agreement in `Draft` (standalone draft or successor draft) could neither be deleted nor closed/cancelled. If an operator created an unwanted draft or an abandoned successor revision, it remained permanently stuck in `Draft`. In the case of successor drafts, this permanently prevented any future successor creation for the predecessor agreement due to the unique constraint.
2. **Missing Direct REST Show Endpoints for VehicleUse & RunningChart**:
   - While `AgreementService` and `RunningChartService` implemented `find()`, the REST routes lacked single-entity `GET /vehicle-uses/{use}` and `GET /running-charts/{chart}` show endpoints, leaving a gap in standard RESTful Read access.
3. **Data Integrity & UX Boundary Safeguards**:
   - In `VehicleUseEditor.tsx`, string coercion fallback `row.registration_number ?? row.vehicle_number` evaluated empty strings `""` incorrectly; changed to `||`.
   - Planned handover and planned return inputs lacked HTML `min` and `max` constraints tied to the parent agreement's `starts_on` and `ends_on` boundaries, allowing out-of-bounds date selection prior to backend validation rejection.
   - When replacing a vehicle in custody, selecting the same physical vehicle was not caught until backend validation; added UI validation to disable submission and alert the operator.
   - In `AgreementEditor.tsx`, successor agreements allow term edits but must keep the predecessor party and vehicle; added selector disabling and explicit guidance text.
   - In `AgreementSuccessorForm.tsx`, effective start date now enforces `min` constraint (day after predecessor `starts_on`).
   - In `VehicleUsePanel.tsx`, added guidance notice for draft agreements explaining that activation is required prior to vehicle assignment.

## Summary of Changes

### Backend
1. **`app/Modules/VehicleRental/Services/AgreementService.php`**:
   - Updated `change()` to allow `AgreementAction::Close` when `$record->status === AgreementStatus::Active || $record->status === AgreementStatus::Draft`.
   - Validates mandatory closure reason, confirms no active/planned vehicle custody, timestamps closure (`closed_at`, `closed_on`), sets `status = Closed`, increments `row_version`, and logs immutable audit history.
2. **`app/Modules/VehicleRental/Http/Controllers/VehicleUseController.php` & `RunningChartController.php`**:
   - Added `show(AgreementRequest $request, int $use): VehicleUseResource` to `VehicleUseController`.
   - Added `show(AgreementRequest $request, int $chart): RunningChartResource` to `RunningChartController`.
3. **`app/Modules/VehicleRental/Routes/api.php`**:
   - Added `Route::get('vehicle-uses/{use}', [VehicleUseController::class, 'show'])->whereNumber('use');`.
   - Added `Route::get('running-charts/{chart}', [RunningChartController::class, 'show'])->whereNumber('chart');`.
4. **Backend Test Suite**:
   - `AgreementTest.php`: Added `test_draft_agreement_can_be_closed_with_mandatory_reason_and_freezes_lifecycle`.
   - `VehicleUseTest.php`: Added assertion verifying single-resource show route `GET /vehicle-uses/{use}`.
   - `RunningChartTest.php`: Added `test_api_can_fetch_single_chart_by_id`.

### Frontend
1. **`resources/js/modules/vehicle-rental/AgreementsPage.tsx`**:
   - Added `<Button variant="danger" onClick={() => setAction(AgreementAction.Close)}>Close draft</Button>` when reviewing draft agreements.
   - Tailored action dialog confirmation message when closing a draft.
2. **`resources/js/modules/vehicle-rental/AgreementEditor.tsx`**:
   - Fixed vehicle label fallback to `record.vehicle.registration_number || record.vehicle.vehicle_number`.
   - When editing a successor agreement, disables the party and vehicle selectors with explicit contextual guidance.
3. **`resources/js/modules/vehicle-rental/AgreementSuccessorForm.tsx`**:
   - Added `min` constraint on effective start date calculated from predecessor's `starts_on`.
4. **`resources/js/modules/vehicle-rental/VehicleUseEditor.tsx`**:
   - Added `min` constraint on planned handover (`agreement.starts_on`) and `max` constraint on planned return (`agreement.ends_on`).
   - Added validation check preventing the same vehicle from being chosen during atomic vehicle replacement.
5. **`resources/js/modules/vehicle-rental/VehicleUsePanel.tsx`**:
   - Aligned "Running Charts" toggle button inside the action button toolbar.
   - Added draft notice clarifying that the agreement must be activated before vehicles can be dispatched.
6. **`resources/js/modules/vehicle-rental/VehicleUseRegisterPage.tsx`**:
   - Linked customer agreement reference to the agreement review page.
7. **`resources/js/modules/vehicle-rental/vehicleUseApi.ts` & `runningChartApi.ts`**:
   - Added `getVehicleUse(id, signal)` and `getChart(id, signal)` client functions.

## Verification
- **PHPUnit**: `php artisan test --filter=VehicleRental` $\rightarrow$ **175 passed (1,604 assertions)**.
- **Vitest**: `npx vitest run resources/js/modules/vehicle-rental` $\rightarrow$ **28 suites passed (107 tests)**.
- **TypeScript**: `npm run typecheck` $\rightarrow$ **0 errors**.
- **ESLint**: `npm run lint` $\rightarrow$ **0 errors, 0 warnings**.
- **Vite Build**: `npm run build` $\rightarrow$ **Clean production asset build in 8.94s**.
- **Git Diff & Whitespace**: `git diff --check` reported 0 trailing whitespace or line-ending warnings.
