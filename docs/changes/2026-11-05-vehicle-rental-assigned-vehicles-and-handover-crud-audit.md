# Vehicle Rental: Assigned Vehicles & Handover CRUD Forensic Audit & Hardening

**Date:** 2026-11-05  
**Module:** `VehicleRental`  
**Scope:** Backend (`app/Modules/VehicleRental/`), Frontend (`resources/js/modules/vehicle-rental/`), Tests

## Context & Purpose
A comprehensive, end-to-end audit was conducted across the Assigned Vehicles and Handover (`VehicleUse`) CRUD lifecycle, covering Create (Plan), Read (List / Show / Register / History / Availability / Sources), Update (Handover, Return, Cancel, Replace transitions), and Delete (prohibited hard delete with legal/physical custody protection):

1. **`replacedByUse` Eager Loading Omission**:
   - In `OperationalFields::USE_RELATIONS`, `'replacedByUse'` was omitted. Because `VehicleUseResource` checked `$this->relationLoaded('replacedByUse')`, vehicle uses that were replaced never returned their replacement lineage in API lists or registers. Consequently, the UI never displayed `"Replaced by vehicle [label]"`.
2. **Availability Query & Workshop Service Job Conflict Desynchronization**:
   - In `VehicleUseService::availableVehicles()`, only `$this->rentalBlocker->conflicts(...)` was checked. Vehicles blocked by active workshop service jobs (in `VehicleService` module via `VehicleServiceAvailabilityBlocker`) were erroneously reported as available. When operators subsequently attempted to plan the vehicle, `VehicleAvailabilityService::assertAvailable()` failed.
3. **Replacement Form Validation Key Disconnect**:
   - In `VehicleUseService::replace()`, atomic replacement calls `$this->transition(..., ReturnVehicle)` on the previous vehicle and `$this->transition(..., Handover)` on the replacement vehicle. When odometer validation failed (e.g., return odometer lower than previous handover or out-of-sequence readings), `transition()` threw a `ValidationException` keyed on `'odometer'`. Similarly, invalid event timestamps threw on `'occurred_at'`. However, `VehicleUseEditor.tsx` maps errors to `'return_odometer'`, `'handover_odometer'`, and `'starts_at'`. Uncaught generic keys left replacement form fields without field-level error feedback.
4. **Vehicle Use Register Query by Notes**:
   - `VehicleUseRegisterService::list()` searched `vehicle_label_snapshot`, `vehicle.registration_number`, `vehicle.vehicle_number`, `customerAgreement.reference`, and `ownerAgreement.reference`, but omitted `notes`. Operators could not locate vehicle uses by notes/reasons.
5. **Vehicle Use Register URL Parameter Synchronization**:
   - `VehicleUseRegisterPage.tsx` did not initialize `from` and `until` from URL search parameters and did not synchronize filters with `window.history.replaceState()`, preventing bookmarking and resetting state upon page refresh.
6. **Actor Null-Safety in Vehicle Use History**:
   - In `VehicleUseHistoryResource`, `$this->actor->first_name` lacked null-safety. If an actor record was missing, deleted, or generated via system automation, history deserialization produced a 500 error.
7. **Controller Destroy Route Parameter Count Mismatch**:
   - `VehicleUseController::destroy()` defined `int $use`. When routed via `DELETE customer/agreements/{agreement}/vehicles/{use}`, parameter injection bound the customer agreement ID to `$use`.
8. **Frontend Ergonomics & Form Boundary Constraints**:
   - In `VehicleUseEditor.tsx`, during replacement, `min` replacement time is now constrained to the previous vehicle's handover time, with descriptive guidance indicating the previous handover timestamp and old vehicle handover odometer.
   - In `VehicleUsePanel.tsx`, return odometer input now enforces `min={action.row.handover_odometer}` for client-side HTML5 boundary enforcement matching the hint text.
   - In `VehicleUseEditor.tsx`, when `initialVehicleId` is provided (e.g. from availability query dispatch), if the vehicle is not in page 1 of active vehicles, a direct API query resolves and selects the vehicle immediately.
   - In `AgreementsPage.tsx`, Owner Agreement reviews now provide a quick link to inspect all vehicle uses utilizing that owner's vehicle in the vehicle-use register (`/vehicle-rental/vehicle-uses?search=${encodeURIComponent(selected.reference)}`).

## Changes Made

### Backend
1. **`app/Modules/VehicleRental/Constants/OperationalFields.php`**:
   - Added `'replacedByUse'` to `USE_RELATIONS` array so the `replaced_by_use` relation is consistently eager-loaded across all list, show, and register queries.
2. **`app/Modules/VehicleRental/Services/VehicleUseService.php`**:
   - In `availableVehicles()`: added `$this->availability->assertUnblocked($context->tenantId, $context->organizationUnitId, (int) $vehicle->id, $startDb, $endDb, $this->rentalBlocker)` check inside try-catch to properly filter out vehicles blocked by workshop service jobs.
   - In `replace()`: caught `ValidationException` on `ReturnVehicle` and mapped `'odometer'` $\rightarrow$ `'return_odometer'` and `'occurred_at'` $\rightarrow$ `'starts_at'`. Caught `ValidationException` on `Handover` and mapped `'odometer'` $\rightarrow$ `'handover_odometer'` and `'occurred_at'` $\rightarrow$ `'starts_at'`.
3. **`app/Modules/VehicleRental/Services/VehicleUseRegisterService.php`**:
   - Added `->orWhere('notes', 'like', $pattern)` to the search filter so operators can search vehicle uses by assignment notes.
4. **`app/Modules/VehicleRental/Http/Resources/VehicleUseHistoryResource.php`**:
   - Made actor name resolution null-safe: `$this->actor ? trim(($this->actor->first_name ?? '').' '.($this->actor->last_name ?? '')) : 'System'`.
5. **`app/Modules/VehicleRental/Http/Controllers/VehicleUseController.php`**:
   - Updated `destroy(AgreementRequest $request, ?int $first = null, ?int $second = null)` method signature to handle both 1-parameter and 2-parameter DELETE routes safely.
6. **`app/Modules/VehicleRental/Tests/VehicleUseTest.php`**:
   - Added `test_available_vehicles_excludes_workshop_service_blocked_vehicle`.
   - Added `test_replacement_maps_validation_error_to_return_odometer`.
   - Added `test_vehicle_use_register_searches_notes`.

### Frontend
1. **`resources/js/modules/vehicle-rental/VehicleUsePanel.tsx`**:
   - Set `min={action.type === VehicleUseAction.Return && action.row.handover_odometer ? action.row.handover_odometer : undefined}` on the Return odometer input.
   - Trimmed reason and odometer strings on submit.
2. **`resources/js/modules/vehicle-rental/VehicleUseEditor.tsx`**:
   - Constrained `minStart` during replacement to `replacement.handed_over_at.slice(0, 16)`.
   - Displayed previous vehicle handover hint for replacement time.
   - Set `min` and hint for old vehicle return odometer (`replacement.handover_odometer`).
   - Added direct fallback vehicle fetch by ID when `initialVehicleId` is outside page 1.
   - Trimmed all string inputs upon submission.
3. **`resources/js/modules/vehicle-rental/VehicleUseRegisterPage.tsx`**:
   - Initialized `from` and `until` filter states from URL search parameters with timezone offset handling.
   - Added `syncUrl()` using `window.history.replaceState` when applying and clearing filters.
4. **`resources/js/modules/vehicle-rental/AgreementsPage.tsx`**:
   - Added a quick link tab for Owner Agreements to inspect related vehicle uses in the register.
5. **Frontend Tests**:
   - `VehicleUseEditor.test.tsx`: Added test verifying replacement return odometer guidance and min bound.
   - `VehicleUseRegisterPage.test.tsx`: Added test verifying URL search parameter initialization for `from` and `until`.
   - `AgreementsPage.test.tsx`: Added test verifying Owner Agreement quick-link to Vehicle Uses register.

## Verification
- **PHPUnit**: `php artisan test --filter=VehicleRental` $\rightarrow$ **201 passed (1,743 assertions)**.
- **Vitest**: `npx vitest run resources/js/modules/vehicle-rental` $\rightarrow$ **28 suites passed (119 tests)**.
- **TypeScript**: `npm run typecheck` $\rightarrow$ **0 errors**.
- **ESLint**: `npm run lint` $\rightarrow$ **0 errors, 0 warnings**.
- **Vite Build**: `npm run build` $\rightarrow$ **Clean production build in 10.30s**.
