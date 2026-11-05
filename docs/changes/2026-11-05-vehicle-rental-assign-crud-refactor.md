# Vehicle Rental: Assign (Vehicle Use) CRUD Comprehensive Audit and Refactor

**Date:** 2026-11-05  
**Module:** `VehicleRental`  
**Scope:** Backend (`app/Modules/VehicleRental/`), Frontend (`resources/js/modules/vehicle-rental/`), Tests

## Context & Purpose

A thorough audit of the Assign (`VehicleUse` / Vehicle Custody) CRUD operations across the `VehicleRental` module identified several friction points, validation rigidities, and UX usability gaps:
1. **Omitted `ends_at` Validation Rigidity**: In `VehicleUseService::planRules()`, `'ends_at' => ['present', 'nullable', 'string']` rejected payloads where `ends_at` was omitted altogether, causing validation failures for open-ended assignments unless an explicit `null` was sent. Relaxing to `'sometimes', 'nullable', 'string'` allows clean omission.
2. **Numeric Odometer Handling**: In `VehicleUseService::transition()` and `replace()`, `odometer`, `return_odometer`, and `handover_odometer` strictly validated for strings. When API consumers or clients submitted numeric values (e.g. `12500`), validation failed with string type errors. Normalizing numeric odometers to strings resolves this across all state transitions.
3. **Register Search Parity**: `VehicleUseRegisterService::list()` searched `vehicle_label_snapshot`, `customerAgreement`, and `ownerAgreement`, but did not include direct matching on the `Vehicle` model's `registration_number` and `vehicle_number`, potentially missing vehicles whose snapshot differed from current asset tags.
4. **Owner Agreement Navigation**: While customer agreement references on `VehicleUseRegisterPage` were clickable links into the agreements register, owner agreement references were plain text.
5. **Register URL Parameter Parsing**: `VehicleUseRegisterPage` ignored URL search parameters on initial mount, causing query parameters passed from other modules or bookmarks to be lost.
6. **Handover & Return Form Boundaries & Guidance**: In `VehicleUsePanel.tsx`, the handover action lacked timestamp bounds and planned period hints; the return action lacked minimum bounds (must follow handover) and did not display the recorded handover odometer reading, forcing operators to guess or cross-reference cards.
7. **Bidirectional Replacement Lineage**: `VehicleUse` supported `replacesUse` (belongsTo) but lacked `replacedByUse` (hasOne). Exposing this relationship provides operators forward and backward lineage visibility across replacements.
8. **Custody Immutability & Deletion Invariant Testing**: `VehicleUse` enforces strict lifecycle immutability and blocks hard deletes via `LogicException` in favor of cancellation and return transitions. Explicit test assertions were added to preserve this domain contract.

## Changes Made

### Backend

1. **`app/Modules/VehicleRental/Models/VehicleUse.php`**:
   - Added `replacedByUse(): HasOne` relationship mapping `replaces_use_id` on the same table.

2. **`app/Modules/VehicleRental/Http/Resources/VehicleUseResource.php`**:
   - Conditionally exposed `replaced_by_use` (`id`, `vehicle_label`) when loaded to provide clients forward lineage visibility.

3. **`app/Modules/VehicleRental/Services/VehicleUseService.php`**:
   - In `planRules()`, relaxed `'ends_at'` to `['sometimes', 'nullable', 'string']` and updated `plan()` / `replace()` to handle `$data['ends_at'] ?? null`.
   - In `transition()`, normalized numeric `odometer` inputs to strings before validation.
   - In `replace()`, normalized numeric `return_odometer` and `handover_odometer` inputs to strings before validation.

4. **`app/Modules/VehicleRental/Services/VehicleUseRegisterService.php`**:
   - Expanded search queries to match `registration_number` and `vehicle_number` directly on the `Vehicle` relationship.

5. **`app/Modules/VehicleRental/Tests/VehicleUseTest.php`**:
   - Added `test_vehicle_use_delete_is_strictly_prohibited_to_preserve_custody_history` asserting that `$use->delete()` throws `LogicException`.
   - Added `test_vehicle_use_identity_and_planned_evidence_are_immutable` asserting that updating unapproved fields throws `LogicException`.
   - Added `test_vehicle_use_plan_accepts_omitted_ends_at_for_open_ended_coverage`.
   - Added `test_vehicle_use_transition_accepts_numeric_odometer`.
   - Added `test_vehicle_use_replaced_by_use_relation` asserting forward lineage resolution.

### Frontend

1. **`resources/js/modules/vehicle-rental/vehicleUse.ts`**:
   - Added optional `replaced_by_use?: { id: number; vehicle_label: string } | null` to the `VehicleUse` TypeScript interface.

2. **`resources/js/modules/vehicle-rental/VehicleUseEditor.tsx`**:
   - Set `max={end || maxEnd}` on the planned handover datetime input to prevent picking a start date after the entered return date.

3. **`resources/js/modules/vehicle-rental/VehicleUsePanel.tsx`**:
   - Added `min` and `max` constraints to the actual event time input on Handover and Return dialogs.
   - Added planned period hint to Handover dialog.
   - Added handover timestamp hint and previous odometer hint (`Handover reading was {x}. Return reading cannot be lower.`) to Return dialog.
   - Added `replaced_by_use` badge indicator when a vehicle use was replaced.

4. **`resources/js/modules/vehicle-rental/VehicleUseRegisterPage.tsx`**:
   - Initialized `search`, `status`, and `filters` from `window.location.search`.
   - Rendered owner agreement reference as a clickable navigation link to `/vehicle-rental/owner/agreements?search=...`.
   - Displayed "Replaced by vehicle {label}" badge when `replaced_by_use` is present.

5. **`resources/js/modules/vehicle-rental/VehicleUsePanel.test.tsx` & `VehicleUseRegisterPage.test.tsx`**:
   - Added tests verifying return dialog odometer helper hints and bounds.
   - Added tests verifying owner agreement link navigation and forward replacement lineage display.

## Verification

- **PHPUnit**: `php artisan test --filter=VehicleRental` $\rightarrow$ **185 passed (1,647 assertions)**.
- **Vitest**: `npx vitest run resources/js/modules/vehicle-rental` $\rightarrow$ **28 suites passed (111 tests)**.
- **TypeScript**: `npm run typecheck` $\rightarrow$ **0 errors**.
- **ESLint**: `npm run lint` $\rightarrow$ **0 errors, 0 warnings**.
- **Vite Build**: `npm run build` $\rightarrow$ **Clean production build in 8.54s**.
