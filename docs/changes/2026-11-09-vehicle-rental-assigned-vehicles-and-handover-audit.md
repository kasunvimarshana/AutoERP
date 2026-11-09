# Vehicle Rental: Assigned Vehicles & Handover Deep Audit & Fixes

**Date:** 2026-11-09  
**Module:** `VehicleRental`  
**Scope:** Backend (`OperationalTime`, `VehicleUseService`, `VehicleUseTest`), Frontend (`VehicleUseEditor`, `VehicleUsePanel`, tests)

---

## 1. Problem Summary & Root Cause Analysis

### A. Owner Agreement Not Populating in Assigned Vehicles Section
1. **Lookup UX Restriction (`minSearchLength` & `loadOnOpen`):**
   - The `LookupSelect` for `Owner agreement` was initialized with default settings (`minSearchLength = 2`, `loadOnOpen = false`). When users opened the combobox, it displayed `"Type at least 2 characters to search"` and remained completely empty unless users guessed and typed characters of internal agreement references.
2. **Component Remounting on Input:**
   - `VehicleUseEditor` wrapped the `LookupSelect` in a dynamic key `key={`${vehicle.id}:${start}:${end}``, causing the entire combobox to unmount and remount on every keystroke in the date fields, discarding search states and user interactions.
3. **No Automatic Resolution on Date Entry:**
   - When a vehicle and planned handover date were selected, the system did not automatically fetch and pre-select a covering owner agreement even when exactly one matching owner agreement was active and covered the period.
4. **Timezone Offset Space-Decoding (`OperationalTime`):**
   - When datetime strings with timezone offsets were submitted (or parsed from query strings), PHP URL decoding occasionally converted the `+` preceding the offset to a space (`YYYY-MM-DDTHH:mm:ss 05:30`), causing `OperationalTime::parse()` to reject valid ISO-8601 strings against `OperationalFields::TIMESTAMP_FORMAT`.

### B. Hand Over Vehicle Functionality Blocked / Inoperable
1. **Overly Restrictive Early Handover Validation:**
   - In `VehicleUseService::transition()`, handovers were rejected if `$at < $record->starts_at`. In real-world vehicle rentals, customers frequently arrive 15–30 minutes prior to the planned booking time. If a handover was planned for later today or tomorrow, selecting the current actual time failed with `"Record an actual handover within the planned period, not a future event"` because `$at < starts_at`. Conversely, entering the scheduled time failed because `$at->isFuture()` was true. Handover was entirely blocked.
2. **Frontend Datetime-Local `min` Boundary:**
   - In `VehicleUsePanel.tsx`, the `min` constraint for the actual handover timestamp was set to `action.row.starts_at.slice(0, 16)`, physically preventing browser users from selecting any time earlier than the planned handover time.
3. **Action Reason & Input Friction:**
   - In `choose()`, `reason` and `at` were initialized to empty strings without helpful defaults, disabling the form submit button and causing validation friction.

---

## 2. Key Changes Implemented

### Backend (`app/Modules/VehicleRental/`)
1. **`OperationalTime.php`:**
   - Added string normalization prior to validation:
     - Automatically converts space-decoded timezone offsets (`T00:00:00 05:30` $\rightarrow$ `T00:00:00+05:30`).
     - Normalizes timestamps missing second precision (`YYYY-MM-DDTHH:mm+05:30` $\rightarrow$ `:00+05:30`).
2. **`VehicleUseService.php`:**
   - Removed the blocking `$at < $record->starts_at` constraint on handover transitions.
   - Enforces that actual handover must occur before planned return (`$record->ends_at !== null && $at >= $record->ends_at`), within customer and owner contract coverage, and not in the future (allowing a 60-second grace window for client/server clock drift).
3. **`VehicleUseTest.php`:**
   - Added `test_handover_allows_early_collection_within_contract_period` verifying valid early customer handovers.
   - Added `test_operational_time_parse_normalizes_space_offset_and_missing_seconds` verifying timestamp normalizations.
   - Added `test_handover_rejects_actual_time_at_or_after_planned_return` ensuring boundary enforcement.

### Frontend (`resources/js/modules/vehicle-rental/`)
1. **`VehicleUseEditor.tsx`:**
   - Configured `LookupSelect` with `minSearchLength={0}` and `loadOnOpen={true}` so opening the dropdown immediately displays available covering owner agreements.
   - Added automatic selection `useEffect`: when a vehicle and planned handover are entered, if exactly one covering owner agreement exists, it is automatically selected.
   - Added explicit manual trigger button `"Auto-select owner agreement"` for fast one-click recovery.
   - Removed destructive dynamic key on the combobox container to prevent unwanted component unmounting.
   - Fully resolved React Compiler memoization and hook dependencies (`[company, vehicle, start, end, owner, agreement.id]`).
2. **`VehicleUsePanel.tsx`:**
   - Adjusted `min` boundary on the handover datetime picker to `agreement.starts_on ? `${agreement.starts_on}T00:00` : undefined`, allowing actual handover on or after the customer contract start date.
   - Initialized default handover reason (`"Vehicle handed over to customer"`) and return reason (`"Vehicle returned by customer"`).
   - Added quick-action buttons: `"Use planned handover time"` and `"Use current time"` for instant one-click timestamp population.
   - Added contextual hint explaining odometer recording on handover.
3. **Unit Tests:**
   - `VehicleUseEditor.test.tsx`: Added test verifying auto-selection of covering owner agreements.
   - `VehicleUsePanel.test.tsx`: Added test verifying default reasons, quick time buttons, and agreement start bounds.

---

## 3. Verification & Test Results
- **PHPUnit / Pest:**
  - `php artisan test app/Modules/VehicleRental` $\rightarrow$ **220 passed** (1,885 assertions) across 35 test suites.
- **Vitest:**
  - `npx vitest run resources/js/modules/vehicle-rental` $\rightarrow$ **28 suites passed** (124 tests passed).
  - `npm run test` $\rightarrow$ **140 suites passed** (520 tests passed) across the full application.
- **TypeScript:**
  - `npm run typecheck` $\rightarrow$ Passed with **0 errors**.
- **ESLint:**
  - `npm run lint` $\rightarrow$ Passed with **0 errors / 0 warnings**.
