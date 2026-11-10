# Vehicle Rental Running Chart Date/Time Picker Accessibility, Dynamic Defaults & Chronological Synchronization

**Date:** 2026-11-10  
**Modules:** `VehicleRental`, `Shared`  
**Scope:** Resolves disabled and inaccessible hour slots in HTML5 datetime-local pickers across Chromium-based browsers, synchronizes dynamic operational defaults for Running Chart recording, aligns "Use current time" across active business timezones, and prevents future-time and reversed odometer validation errors.

---

### 1. Root Causes & Technical Analysis

1. **Chromium Partial-Hour Truncation on `<input type="datetime-local">`:**
   - **Problem:** When native HTML5 datetime-local inputs were assigned fractional or minute-specific `min` attributes (e.g. `min="2026-11-10T15:41:00"` or `min="2026-11-10T15:41"` derived from vehicle handover at `3:41:09 PM`), Chromium's 12-hour AM/PM popup widget evaluated whole-hour candidates (`15:00`) against `min`. Because `15:00 < 15:41`, Chromium omitted or disabled hours `01 PM`, `02 PM`, and `03 PM`, causing the dropdown to start at `04 PM`.
   - **Impact:** Operators attempting to record usage or vehicle replacement immediately following custody handover could not select `03 PM`. Forcing manual entry led to invalid selections (such as `11:52:53 PM` instead of `3:52:53 PM`), which was 8 hours in the future and triggered validation failures: `ends_at: Record completed usage ending after its start, not in the future.` and `starts_at: Return must follow handover and cannot be in the future.`.
   - **Fix:** Implemented `toHourFloor()` in `resources/js/shared/utils/businessDate.ts`. Bounding `min` to the top of the hour (`HH:00`) ensures that `15:00 >= 15:00` is true in Chromium, keeping `03 PM` and all subsequent hours selectable while strict minute-level chronological validation is enforced in React and the backend.

2. **Manual Friction and Asynchronous Defaults:**
   - **Problem:** When opening the "Record usage" modal, `Usage start` defaulted to an empty string (`mm/dd/yyyy --:--:--`) and required manual input, despite the vehicle's custody start time or preceding running chart end time being known.
   - **Fix:** In `RunningChartsPanel.tsx`, computed `initialStartTime` (from the latest chart's `ends_at` or `use.handed_over_at`) and passed it to `RunningChartEditor`. `start` now initializes automatically to the exact handover or prior trip end time.

3. **Dynamic Current Time Generation:**
   - **Problem:** Preset buttons ("Use current time") constructed ISO strings from `new Date().toISOString()`, adding unnecessary conversions.
   - **Fix:** Standardized on `businessDateTimeInputValueWithSeconds()` from `@/shared/utils/businessDate.ts`, resolving the active tenant/organization timezone directly.

4. **Client-Side Odometer Inversion Guard:**
   - **Problem:** Inverted odometer entries (`end_odometer < start_odometer`) only failed upon backend submission.
   - **Fix:** Added client-side validation to provide immediate feedback before form dispatch.

---

### 2. Files Modified

1. **`resources/js/shared/utils/businessDate.ts` & `businessDate.test.ts`:**
   - Added and covered `toHourFloor(val)` to floor datetime strings to `HH:00`.
2. **`resources/js/modules/vehicle-rental/RunningChartEditor.tsx`:**
   - Replaced restrictive minute-level `minStart` / `minEnd` with `toHourFloor()`.
   - Pre-populated `start` with `initialStartTime ?? custodyStart`.
   - Bound `maxStart` to `maxEnd` so adjusting start is not blocked by end time.
   - Used `businessDateTimeInputValueWithSeconds()` in `freshLocalNow()`.
   - Added immediate client-side validation for `end_odometer >= start_odometer`.
3. **`resources/js/modules/vehicle-rental/RunningChartsPanel.tsx`:**
   - Passed `initialStartTime` and `initialOdometer` to `RunningChartEditor`.
4. **`resources/js/modules/vehicle-rental/VehicleUseEditor.tsx`:**
   - Floored `replacementHandoverLocal` with `toHourFloor()`.
   - Updated "Use current time" to use `businessDateTimeInputValueWithSeconds()`.
   - Aligned clock drift tolerance to 120 seconds.
5. **`resources/js/modules/vehicle-rental/VehicleUsePanel.tsx`:**
   - Floored handover/return bounds with `toHourFloor()`.
   - Updated event time presets to use `businessDateTimeInputValueWithSeconds()`.
6. **`resources/js/modules/vehicle-rental/RunningChartRegisterPage.tsx`**, **`VehicleUseRegisterPage.tsx`**, **`VehicleAvailabilityPage.tsx`**, **`ReservationEditor.tsx`:**
   - Applied `toHourFloor()` to period filter inputs to ensure unconstrained time selection.

---

### 3. Verification

- **Vitest:**
  - `RunningChartsPanel.test.tsx` & `RunningChartEditor.test.tsx`: 15 passed.
  - Full `vehicle-rental` test suite (29 files): 135 passed.
  - Whole application test suite (142 files): 535 passed.
- **PHPUnit:**
  - `app/Modules/VehicleRental/Tests`: 234 passed (1,981 assertions).
- **TypeScript & ESLint:**
  - `npm run typecheck`: 0 errors.
  - `npm run lint`: 0 errors.
- **Vite Build:**
  - `npm run build`: Production client bundle built cleanly in 8.00s.
